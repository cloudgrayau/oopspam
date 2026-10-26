<?php
namespace cloudgrayau\oopspam\controllers;

trait RequiresPluginAccess {

  public function beforeAction($action): bool {
    if (!parent::beforeAction($action)) {
      return false;
    }
    $this->requireCpRequest();
    $this->requirePermission('accessPlugin-oopspam');
    return true;
  }

}