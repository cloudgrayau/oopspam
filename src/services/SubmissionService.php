<?php
namespace cloudgrayau\oopspam\services;

use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\records\SubmissionRecord;

use CraftCms\Cms\Support\Query;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SubmissionService {

  public function recordSubmission(string $ipaddress): void {
    if (random_int(1, 100) === 1) {
      $this->gcSubmissions();
    }
    $binary = self::binary($ipaddress);
    if ($binary === null) {
      return;
    }
    // Postgres can't take raw bytes through a text parameter, so it gets a hex-decoded expression
    $value = DB::connection()->getDriverName() === 'pgsql'
      ? DB::raw("decode('" . bin2hex($binary) . "', 'hex')")
      : $binary;
    SubmissionRecord::query()->create(['ipaddress' => $value]);
  }

  public function checkSubmission(string $ipaddress): bool {
    $binary = self::binary($ipaddress);
    if ($binary === null) {
      return false;
    }
    $query = SubmissionRecord::query()->where('dateCreated', '>=', Query::prepareDateForDb(now()->subHour()));
    $this->whereIp($query, $binary);
    return $query->count() >= OOPSpam::getInstance()->getSettings()->maxSubmissions;
  }

  public function clearSubmissions(): void {
    SubmissionRecord::query()->truncate();
  }

  public function gcSubmissions(): void {
    SubmissionRecord::query()->where('dateCreated', '<', Query::prepareDateForDb(now()->subHour()))->delete();
  }

  private function whereIp(Builder $query, string $binary): void {
    if (DB::connection()->getDriverName() === 'pgsql') {
      $query->whereRaw("ipaddress = decode(?, 'hex')", [bin2hex($binary)]);
    } else {
      $query->where('ipaddress', $binary);
    }
  }

  private static function binary(string $ip): ?string {
    $ip = trim($ip);
    $binary = $ip === '' ? false : @inet_pton($ip);
    return $binary === false ? null : $binary;
  }

}