<?php
namespace cloudgrayau\oopspam\controllers;

use cloudgrayau\oopspam\helpers\LogStatus;
use cloudgrayau\oopspam\helpers\ResultsHelper;
use cloudgrayau\oopspam\helpers\UsageHelper;
use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\records\LogRecord;
use cloudgrayau\utils\UtilityHelper;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Ui\Controls\Lightswitch;
use CraftCms\Cms\Ui\Nodes\{ActionMenu, Field, Table, TemplateContent};
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\{cp_url, t};

class LogsController {

  use RespondsWithFlash;

  public function index(Request $request): CpScreenResponse {
    $plugin = OOPSpam::getInstance();
    $settings = $plugin->getSettings();
    $perPage = max(10, min(250, (int) $request->query('per_page', $settings->logsPerPage)));
    $status = (string) $request->query('status', '');
    $sortInput = (array) $request->query('sort', []);
    $first = is_array($sortInput[0] ?? null) ? $sortInput[0] : [];
    $sort = [
      'field' => (string) ($first['field'] ?? ''),
      'direction' => (string) ($first['direction'] ?? 'desc'),
    ];
    $page = $plugin->logs->paginate($perPage, $status, $sort);

    $rows = collect($page->items())->map(function (LogRecord $log): array {
      $score = $log->getResponse('Score');
      return [
        'id' => $log->id,
        'date' => ['label' => $log->dateCreated->format('j F Y - H:i'), 'url' => cp_url("oopspam/logs/{$log->id}")],
        'type' => (string) $log->type,
        'score' => is_numeric($score) ? (int) $score : '-',
        'status' => LogStatus::badge($log),
      ];
    })->all();

    $arrival = $request->query() === [];
    $table = Table::make($arrival ? 'oopspam-logs-' . bin2hex(random_bytes(4)) : 'oopspam-logs')
      ->columns([
        ['key' => 'date', 'label' => t('Date', category: 'oopspam'), 'sortable' => true],
        ['key' => 'type', 'label' => t('Integration', category: 'oopspam'), 'sortable' => true],
        ['key' => 'score', 'label' => t('Score', category: 'oopspam'), 'sortable' => true, 'thin' => true],
        ['key' => 'status', 'label' => t('Status', category: 'oopspam'), 'thin' => true],
      ])
      ->rows($rows)
      ->pagination([
        'total' => $page->total(),
        'per_page' => $page->perPage(),
        'current_page' => $page->currentPage(),
        'last_page' => $page->lastPage(),
        'next_page_url' => $page->nextPageUrl(),
        'prev_page_url' => $page->previousPageUrl(),
        'from' => $page->firstItem(),
        'to' => $page->lastItem(),
      ])
      ->perPage($perPage, [10, 25, 50, 100, 250])
      ->bulkDeletable(cp_url('oopspam/logs'), t('Are you sure you wish to delete the selected logs?', category: 'oopspam'))
      ->deletable(cp_url('oopspam/logs'), t('Are you sure you wish to delete this log?', category: 'oopspam'))
      ->statusFilter([
        ['value' => 'pass', 'label' => t('Pass', category: 'oopspam')],
        ['value' => 'spam', 'label' => t('Spam', category: 'oopspam')],
        ['value' => 'failed', 'label' => t('Failed', category: 'oopspam')],
      ])
      ->emptyMessage(t('No logs found.', category: 'oopspam'));

    $name = $settings->pluginName ?: 'OOPSpam';
    
    $menu = LogRecord::query()->exists()
    ? ActionMenu::make('oopspam-logs-actions', [[
        'label' => t('Clear Logs', category: 'oopspam'),
        'destructive' => true,
        'action' => 'oopspam/logs/clear',
        'confirm' => t('Are you sure you wish to clear the logs?', category: 'oopspam'),
      ]])
    : null;
    
    return new CpScreenResponse()
      ->title(t('Logs', category: 'oopspam'))
      ->selectedSubnavItem('logs')
      ->crumbs([new ActionItem()->label($name)->href(cp_url('oopspam')), new ActionItem()->label(t('Logs', category: 'oopspam'))])
      ->ui(Ui::make(array_values(array_filter([UsageHelper::node('oopspam-usage-logs'), $menu, $table]))), new UiContext());
  }
  
  public function show(int $id): CpScreenResponse|RedirectResponse {
    $plugin = OOPSpam::getInstance();
    $log = $plugin->logs->getLog($id);
    if ($log === null) {
      return redirect(cp_url('oopspam/logs'));
    }
    $name = $plugin->getSettings()->pluginName ?: 'OOPSpam';
    $score = $log->getResponse('Score');
    $reportable = $log->isValid && !$log->isReport && !empty($log->getResponse('Details'));
  
    $summary = [
      t('Spam Score', category: 'oopspam') => is_numeric($score) ? (string) $score : '-',
      t('Endpoint', category: 'oopspam') => (string) $log->endpoint,
      t('Email', category: 'oopspam') => (string) ($log->getParam('email') ?: '-'),
      t('Integration', category: 'oopspam') => (string) $log->type,
      t('IP Address', category: 'oopspam') => (string) ($log->getParam('senderIP') ?: t('None provided', category: 'oopspam')),
      t('Created at', category: 'oopspam') => $log->dateCreated->format('j F Y - H:i:s'),
    ];
    if ($log->isReport) {
      $summary[t('Reported at', category: 'oopspam')] = $log->dateUpdated->format('j F Y - H:i:s');
    }
    $html = '<dl style="display:grid;grid-template-columns:max-content 1fr;gap:6px 24px;margin:0;">';
    foreach ($summary as $label => $value) {
      $html .= '<dt style="font-weight:bold;">' . e($label) . '</dt><dd style="margin:0;word-break:break-word;">' . e($value) . '</dd>';
    }
    $html .= '</dl>';
  
    $nodes = [
      LogStatus::node('log-status', $log),
      TemplateContent::make('log-summary', $html),
    ];
    if ($reportable) {
      $nodes[] = Field::make(t($log->isSpam ? 'Report as not spam' : 'Report as spam', category: 'oopspam'), Lightswitch::make('isReport'))
        ->instructions(t('Tells OOPSpam this result was wrong, to improve its detection. This uses one API call and can’t be undone.', category: 'oopspam'));
    }
    $nodes = [
      ...$nodes,
      ...ResultsHelper::panels('log', [
        t('Parameters', category: 'oopspam') => $log->getParams(),
        t('Response', category: 'oopspam') => $log->getResponses(),
      ]),
      UtilityHelper::footer('oopspam-footer-log'),
    ];
  
    $title = $log->dateCreated->format('j F Y - H:i:s');
    $response = new CpScreenResponse()
      ->title($title)
      ->selectedSubnavItem('logs')
      ->crumbs([
        new ActionItem()->label($name)->href(cp_url('oopspam')),
        new ActionItem()->label(t('Logs', category: 'oopspam'))->href(cp_url('oopspam/logs')),
        new ActionItem()->label($title),
      ])
      ->ui(Ui::make($nodes), new UiContext(values: ['isReport' => false]), $reportable
        ? ['submit' => ['method' => 'post', 'url' => cp_url("oopspam/logs/{$id}/report")], 'defaultFormActions' => []]
        : []);
    return $reportable ? $response->submitButtonLabel(t('Report', category: 'oopspam')) : $response;
  }
  
  public function report(Request $request, int $id): Response {
    $log = LogRecord::query()->find($id);
    abort_if($log === null, 404);
    if (!$request->boolean('isReport')) {
      return $this->asFailure(t('Switch on the report option first.', category: 'oopspam'));
    }
    return OOPSpam::getInstance()->logs->report($log->uid)
      ? $this->asSuccess(t('Log reported.', category: 'oopspam'), redirect: cp_url("oopspam/logs/{$id}"))
      : $this->asFailure(t('Couldn’t report log.', category: 'oopspam'));
  }

  public function destroy(Request $request): Response {
    $ids = (array) $request->input('ids', $request->input('id', []));
    $count = OOPSpam::getInstance()->logs->deleteLogs($ids);
    return $count > 0
      ? $this->asSuccess(t($count > 1 ? 'Logs deleted.' : 'Log deleted.', category: 'oopspam'))
      : $this->asFailure(t('No logs were deleted.', category: 'oopspam'));
  }

  public function clear(): Response {
    OOPSpam::getInstance()->logs->clearLogs();
    return $this->asSuccess(t('Logs cleared.', category: 'oopspam'), redirect: cp_url('oopspam/logs'));
  }

}