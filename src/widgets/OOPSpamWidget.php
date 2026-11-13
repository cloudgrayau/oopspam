<?php
namespace cloudgrayau\oopspam\widgets;

use cloudgrayau\oopspam\helpers\LogStatus;
use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\records\LogRecord;

use CraftCms\Cms\Dashboard\Widgets\Widget;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Controls\{Number, Text};
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\{Ui, UiContext};

use function CraftCms\Cms\{cp_url, currentUser, t};

class OOPSpamWidget extends Widget {

  public ?string $title = null;
  public int $limit = 5;

  #[\Override]
  protected static function allowMultipleInstances(): bool {
    return false;
  }

  /** Logs must be on, and the user needs access to the plugin to open them. */
  private static function isAvailable(): bool {
    $s = OOPSpam::getInstance()->getSettings();
    return $s->apiKey && $s->enableLogs && (currentUser()?->can('accessPlugin-oopspam') ?? false);
  }

  #[\Override]
  public static function displayName(): string {
    return OOPSpam::getInstance()->getSettings()->pluginName ?: t('OOPSpam', category: 'oopspam');
  }

  #[\Override]
  public static function icon(): ?string {
    return OOPSpam::getInstance()->getBasePath() . '/icon-mask.svg';
  }

  #[\Override]
  public static function isSelectable(): bool {
    return self::isAvailable() && parent::isSelectable();
  }

  #[\Override]
  public function getRules(): array {
    return [
      'title' => ['nullable', 'string', 'max:255'],
      'limit' => ['required', 'integer', 'min:1', 'max:100'],
    ];
  }

  #[\Override]
  public function settingsUi(UiContext $context = new UiContext): Ui {
    return Ui::make([
      Field::make(t('Title', category: 'oopspam'))
        ->control(Text::make('title')->value($this->title)->placeholder(static::displayName())),
      Field::make(t('Limit', category: 'oopspam'))
        ->required()
        ->control(Number::make('limit')->value($this->limit)->min(1)->max(100)->size(2)),
    ]);
  }

  #[\Override]
  public function getTitle(): ?string {
    return $this->title ?: static::displayName();
  }

  public function component(): ?string {
    return self::isAvailable() ? 'craft:html-widget' : null; // null hides the widget
  }

  /** @return array{html: string} */
  public function props(): array {
    $logs = OOPSpam::getInstance()->logs->recent($this->limit);
    if ($logs->isEmpty()) {
      return ['html' => Html::tag('craft-empty', '', ['label' => t('No logs found.', category: 'oopspam')])];
    }
    $items = $logs->map(function (LogRecord $log): string {
      $score = $log->getResponse('Score');
      return Html::tag('li', implode('', [
        Html::a(Html::encode($log->dateCreated->format('j F Y - H:i')), cp_url("oopspam/logs/{$log->id}")),
        Html::tag('span', Html::encode(is_numeric($score) ? (string) $score : '-'), ['class' => 'light ms-auto', 'title' => t('Score', category: 'oopspam')]),
        Html::tag('craft-badge', Html::encode(LogStatus::label($log)), ['fill' => LogStatus::fill($log)]),
      ]), ['style' => 'display:flex;align-items:center;gap:8px;']);
    })->implode('');
    return ['html' => Html::tag('ul', $items, ['class' => 'space-y-3', 'role' => 'list'])
      . Html::tag('p', Html::a(t('View all logs', category: 'oopspam'), cp_url('oopspam/logs')), ['class' => 'mt-3'])];
  }

}
