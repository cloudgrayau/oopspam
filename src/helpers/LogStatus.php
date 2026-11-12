<?php
namespace cloudgrayau\oopspam\helpers;

use cloudgrayau\oopspam\records\LogRecord;

use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Ui\Nodes\Callout;

use function CraftCms\Cms\t;

class LogStatus {

  /** The appearance shared by every status bar. */
  public const APPEARANCE = 'outline-fill';

  /** pass | spam | failed */
  public static function key(LogRecord $log): string {
    return !$log->isValid ? 'failed' : ($log->isSpam ? 'spam' : 'pass');
  }

  /** The translated label for a verdict: pass | spam | failed. */
  public static function labelFor(string $key): string {
    return match ($key) {
      'pass' => t('Pass', category: 'oopspam'),
      'spam' => t('Spam', category: 'oopspam'),
      default => t('Failed', category: 'oopspam'),
    };
  }

  public static function label(LogRecord $log): string {
    $label = self::labelFor(self::key($log));
    return $log->isReport ? $label . ' (' . t('Reported', category: 'oopspam') . ')' : $label;
  }

  /** The badge colour used in the Logs list table. */
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

  /** The callout variant for a verdict: pass | spam | failed. */
  public static function variantFor(string $key): string {
    return match ($key) {
      'pass' => 'success',   // green
      'spam' => 'info',      // blue
      default => 'danger',   // red
    };
  }

  /** A log's variant: yellow if it's been reported, otherwise its verdict's. */
  public static function variant(LogRecord $log): string {
    return $log->isReport ? 'warning' : self::variantFor(self::key($log));
  }

  public static function callout(string $id, string $label, string $variant): Callout {
    return Callout::make($id, '**' . t('Status', category: 'oopspam') . ':** ' . $label)
      ->variant($variant)
      ->appearance(self::APPEARANCE);
  }

  /** The status bar for the detail page. */
  public static function node(string $id, LogRecord $log): Callout {
    return self::callout($id, self::label($log), self::variant($log));
  }

}