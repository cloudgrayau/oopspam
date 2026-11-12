<?php
namespace cloudgrayau\oopspam\helpers;

use cloudgrayau\oopspam\records\LogRecord;

use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Html;

use function CraftCms\Cms\t;

class LogStatus {

  /** pass | spam | failed */
  public static function key(LogRecord $log): string {
    return !$log->isValid ? 'failed' : ($log->isSpam ? 'spam' : 'pass');
  }

  public static function label(LogRecord $log): string {
    $label = match (self::key($log)) {
      'failed' => t('Failed', category: 'oopspam'),
      'spam' => t('Spam', category: 'oopspam'),
      default => t('Pass', category: 'oopspam'),
    };
    return $log->isReport ? $label . ' (' . t('Reported', category: 'oopspam') . ')' : $label;
  }

  public static function fill(LogRecord $log): string {
    if ($log->isReport) {
      return Color::Yellow->value;
    }
    return match (self::key($log)) {
      'failed' => Color::Red->value,
      'spam' => Color::Blue->value,
      default => Color::Green->value,
    };
  }

  /** A table cell holding the coloured badge. */
  public static function badge(LogRecord $log): array {
    return ['html' => Html::tag('craft-badge', Html::encode(self::label($log)), ['fill' => self::fill($log)])];
  }

}