<?php
namespace cloudgrayau\oopspam\services;

use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\records\{LogRecord, UsageRecord};

use CraftCms\Cms\Support\Query;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function CraftCms\Cms\cp_url;

class LogsService {

  public function recordLog(string $endpoint, array $params, array $results, string $type = ''): void {
    try {
      $flags = JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE;
      LogRecord::query()->create([
        'endpoint' => $endpoint,
        'params' => json_encode($params, $flags),
        'response' => json_encode($results, $flags),
        'isValid' => isset($results['Score']) ? 1 : 0,
        'isSpam' => OOPSpam::getInstance()->antiSpam->isSpam($results) ? 1 : 0,
        'isReport' => 0,
        'type' => $type,
      ]);
    } catch (\Throwable $e) { // never let logging break a form submission
      Log::warning('OOPSpam could not record a log: ' . $e->getMessage());
    }
  }

  /** Reports a log back to OOPSpam as a false positive or negative. */
  public function report(string $uid): bool {
    $log = LogRecord::findByUid($uid);
    if (!$log || $log->isReport) {
      return false;
    }
    $anti = OOPSpam::getInstance()->antiSpam;
    $data = $log->getParams();
    $data['shouldBeSpam'] = !$log->isSpam;
    $result = $anti->reportLog($data, $anti->apiVersion . '/spamdetection/report');
    if (!$result['response']) {
      return false;
    }
    $log->isReport = 1;
    $log->save();
    $this->updateUsage($result['limits']);
    return true;
  }

  public function updateUsage(array $usage): void {
    try {
      UsageRecord::query()->whereKey(1)->update([
        'limit' => (int) ($usage['limit'] ?? 0),
        'remaining' => (int) ($usage['remaining'] ?? 0),
      ]);
    } catch (\Throwable $e) {
      Log::warning('OOPSpam could not update usage: ' . $e->getMessage());
    }
  }

  /** @return array{limit: int, remaining: int} */
  public function getUsage(): array {
    $row = UsageRecord::query()->find(1);
    return ['limit' => (int) ($row->limit ?? 0), 'remaining' => (int) ($row->remaining ?? 0)];
  }

  public function getLog(int $id): ?LogRecord {
    $log = LogRecord::query()->find($id);
    if ($log && $log->getParam('context')) { // contextual logs never expose the visitor's email or IP
      $params = $log->getParams();
      unset($params['email'], $params['senderIP']);
      $log->params = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
    return $log;
  }

  public function paginate(int $perPage, string $status = '', array $sort = []): LengthAwarePaginator {
    $query = LogRecord::query()
      ->select(['id', 'type', 'response', 'isValid', 'isSpam', 'isReport', 'dateCreated']);
  
    match ($status) {
      'failed' => $query->where('isValid', 0),
      'spam' => $query->where('isValid', 1)->where('isSpam', 1),
      'pass' => $query->where('isValid', 1)->where('isSpam', 0),
      default => null,
    };
  
    $field = (string) ($sort['field'] ?? '');
    $direction = strtolower((string) ($sort['direction'] ?? '')) === 'asc' ? 'asc' : 'desc';
    $sortable = ['date', 'type', 'score'];
    if (!in_array($field, $sortable, true)) {
      $field = '';
    }
    match ($field) {
      'date' => $query->orderBy('dateCreated', $direction),
      'type' => $query->orderBy('type', $direction),
      'score' => $query->orderByRaw($this->scoreSql() . ' ' . $direction),
      default => null,
    };
    $query->orderByDesc('dateCreated')->orderByDesc('id'); // newest first, and a stable order for ties
  
    return $query->paginate($perPage)
      ->withPath(cp_url('oopspam/logs'))
      ->appends(array_filter([
        'per_page' => $perPage,
        'status' => $status,
        'sort' => $field !== '' ? [['field' => $field, 'direction' => $direction]] : null,
      ]));
  }
  
  /** The score lives inside the JSON response, and logs without one (Failed) count as -1. */
  private function scoreSql(): string {
    return DB::connection()->getDriverName() === 'pgsql'
      ? "COALESCE(CAST(response::json->>'Score' AS INTEGER), -1)"
      : "COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(response, '$.Score')) AS SIGNED), -1)";
  }

  /** @param list<int|string> $ids */
  public function deleteLogs(array $ids): int {
    $ids = array_values(array_filter($ids, fn ($id) => is_int($id) || (is_string($id) && ctype_digit($id))));
    return $ids === [] ? 0 : LogRecord::query()->whereIn('id', array_map('intval', $ids))->delete();
  }

  public function clearLogs(): void {
    LogRecord::query()->truncate();
  }

  public function gcLogs(): void {
    $days = OOPSpam::getInstance()->getSettings()->maxLogs;
    LogRecord::query()->where('dateCreated', '<=', Query::prepareDateForDb(now()->subDays($days)))->delete();
  }

}