<?php
namespace cloudgrayau\oopspam\controllers;

use cloudgrayau\oopspam\OOPSpam;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Http\Responses\CpScreenResponse;

use function CraftCms\Cms\{cp_url, t};

class LogsController {

  public function index(): CpScreenResponse {
    $name = OOPSpam::getInstance()->getSettings()->pluginName ?: 'OOPSpam';
    return new CpScreenResponse()
      ->title(t('Logs', category: 'oopspam'))
      ->crumbs([new ActionItem()->label($name)->href(cp_url('oopspam'))])
      ->contentTemplate('oopspam/_logs/_content')
      ->inertiaPage('cp/Screen');
  }

}