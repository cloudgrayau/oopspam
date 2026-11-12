<?php
namespace cloudgrayau\oopspam\helpers;

use CraftCms\Cms\Ui\Contracts\Node;
use CraftCms\Cms\Ui\Nodes\{MarkdownContent, TemplateContent};

class ResultsHelper {

  /**
   * A status bar followed by a JSON panel per heading.
   *
   * @param  string  $status  Pass | Spam | Failed
   * @param  array<string, mixed>  $panels  heading => data
   * @return list<Node>
   */
  public static function nodes(string $id, string $status, array $panels): array {
    $key = strtolower($status);
    return [LogStatus::callout("$id-status", LogStatus::labelFor($key), LogStatus::variantFor($key)), ...self::panels($id, $panels)];
  }

  /**
   * @param  array<string, mixed>  $panels  heading => data
   * @return list<Node>
   */
  public static function panels(string $id, array $panels): array {
    $nodes = [];
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
