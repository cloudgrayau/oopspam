<?php
namespace cloudgrayau\oopspam\helpers;

use CraftCms\Cms\Ui\Nodes\{Callout, MarkdownContent, TemplateContent};

use function CraftCms\Cms\t;

class ResultsHelper {

  /** @param array<string, mixed> $panels heading => data */
  public static function nodes(string $id, string $status, array $panels): array {
    $label = match ($status) {
      'Pass' => t('Pass', category: 'oopspam'),
      'Spam' => t('Spam', category: 'oopspam'),
      default => t('Failed', category: 'oopspam'),
    };
    $nodes = [Callout::make("$id-status", '**' . t('Status', category: 'oopspam') . ':** ' . $label)
      ->variant($status === 'Pass' ? 'info' : 'warning')];
    $i = 0;
    foreach ($panels as $heading => $data) {
      $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      $nodes[] = MarkdownContent::make("$id-heading-$i", '### ' . $heading)->displayInPane(false);
      $nodes[] = TemplateContent::make("$id-panel-$i", '<pre style="white-space:pre-wrap;word-break:break-word;padding:16px;margin:0;background:rgba(127,127,127,0.12);font-size:13px;">' . e($json) . '</pre>');
      $i++;
    }
    return $nodes;
  }

}