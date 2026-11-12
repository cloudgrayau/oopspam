<?php
namespace cloudgrayau\oopspam\helpers;

use cloudgrayau\oopspam\OOPSpam;

use CraftCms\Cms\Ui\Nodes\Callout;

use function CraftCms\Cms\t;

class UsageHelper {

  public static function node(string $id): ?Callout {
    $usage = OOPSpam::getInstance()->logs->getUsage();
    $limit = (int) ($usage['limit'] ?? 0);
    if ($limit <= 0) {
      return null; // no API call has returned its rate-limit headers yet
    }
    $used = max(0, $limit - (int) ($usage['remaining'] ?? 0));
    return Callout::make($id, '**' . t('API Usage', category: 'oopspam') . ':** ' . number_format($used) . ' / ' . number_format($limit))
      ->variant('info');
  }

}