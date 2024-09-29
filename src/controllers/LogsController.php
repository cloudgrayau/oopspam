<?php
namespace cloudgrayau\oopspam\controllers;

use cloudgrayau\oopspam\OOPSpam;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;

use yii\web\NotFoundHttpException;
use yii\web\Response;

class LogsController extends Controller {
    
  // Public Methods
  // =========================================================================
  
  public function actionIndex(): void {
    Craft::$app->response->redirect(UrlHelper::url('oopspam/logs'))->send();
  }
  
  public function actionUpdate(): void {
    $this->requirePostRequest();
    if (OOPSpam::$plugin->logs->updateLog(Craft::$app->getRequest()->getBodyParam('uid'))){
      $this->setSuccessFlash('Log reported.');
    } else {
      $this->setFailFlash('Couldn’t report log.');
    }
  }
  
  public function actionDelete(): void {
    $this->requirePostRequest();
    $deleteLogs = Craft::$app->getRequest()->getBodyParam('deleteLogs');
    OOPSpam::$plugin->logs->deleteLogs($deleteLogs);
    $this->setSuccessFlash(((count($deleteLogs) > 1) ? 'Logs' : 'Log').' deleted.');
  }
  
  public function actionClear(): void {
    OOPSpam::$plugin->logs->clearLogs();
    Craft::$app->response->redirect(UrlHelper::url('oopspam/logs'))->send();
    $this->setSuccessFlash('Logs cleared.');
  }

  public function actionLogs(): Response {
    $settings = OOPSpam::$plugin->getSettings();
    $js = <<<JS
$('#main-form').submit(function(e){
  if (confirm('Are you sure you wish to delete the selected logs?')){
    return true;
  }
  return false;
});
$('#clearLogs').click(function(e){
  if (confirm('Are you sure you wish to clear the logs?')){
    return true;
  }
  return false;
});
$('#logs .selectallcontainer .checkbox').click(function(e){
  $(this).toggleClass('checked');
  $('#logs tbody .checkbox').prop('checked', ($(this).hasClass('checked')) ? true : false);
  updateSelected();
});
$('#logs tbody .checkbox').change(function(e){
  if ($('#logs .checkbox:checked').length == 0){
    $('#logs .selectallcontainer .checkbox').removeClass('checked');
  }
  updateSelected();
});
function updateSelected(){
  $('#selected').html($('#logs .checkbox:checked').length);
}
JS;
    Craft::$app->getView()->registerJs($js);
    return $this->renderTemplate('oopspam/logs', [
      'settings' => $settings,
      'logs' => OOPSpam::$plugin->logs->getLogs()
    ]);
  }
  
  public function actionLog(int $id): Response {
    $settings = OOPSpam::$plugin->getSettings();  
    $log = OOPSpam::$plugin->logs->getLog($id);
    if (isset($log->id)){
      $js = <<<JS
$('#isReport').click(function(e){
  if ($('#isReport input').val() == 1){
    $('#saveLog').removeClass('disabled');
  } else {
    $('#saveLog').addClass('disabled');
  }
});
$('#main-form').submit(function(e){
  if (confirm('Are you sure you wish to report this log?')){
    return true;
  }
  return false;
});
JS;
      $css = <<<CSS
pre.sf-dump .sf-dump-compact {
  display: block;
}
pre.sf-dump .sf-dump-expanded .sf-dump-compact {
  display: inline !important;
}
.sf-dump-str-collapse {
  display: inline !important;
}
.sf-dump-str-expand {
  display: none !important;
}
.sf-dump-note {
  pointer-events: none;
}
.sf-dump-toggle, .sf-dump-str-toggle {
    display: none;
}
CSS;
      Craft::$app->getView()->registerCss($css);
      Craft::$app->getView()->registerJs($js);
      return $this->renderTemplate('oopspam/logs/log', [
        'settings' => $settings,
        'log' => $log,
        'id' => $id
      ]);
    } else {
      throw new NotFoundHttpException('Page not found.');
    }
  }

}