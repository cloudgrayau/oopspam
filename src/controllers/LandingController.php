<?php
namespace cloudgrayau\oopspam\controllers;

use cloudgrayau\oopspam\OOPSpam;

use function CraftCms\Cms\cp_url;

class LandingController {

  public function __invoke() {
    $settings = OOPSpam::getInstance()->getSettings();
    if (!$settings->apiKey) {
      return redirect(cp_url('settings/plugins/oopspam'));
    }
    return redirect(cp_url($settings->enableLogs ? 'oopspam/logs' : 'oopspam/reputation'));
  }

}