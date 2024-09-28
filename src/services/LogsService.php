<?php
namespace cloudgrayau\oopspam\services;

use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\models\LogModel;
use cloudgrayau\oopspam\records\LogRecord;

use Craft;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\base\Component;

class LogsService extends Component {
  
  private string $endpoint = '/spamdetection/report';
  
  public function handleChangedProductConfig(ConfigEvent $event){
    if (isset($event->oldValue['enableLogs']) && ($event->oldValue['enableLogs'] !== $event->newValue['enableLogs'])){
      $this->clearLogs();
    }
  }
  
  public function recordLog(string $endpoint, array $params, array $results, string $type=''): void {
    $data = [
      'endpoint' => $endpoint,
      'params' => json_encode($params),
      'response' => json_encode($results),
      'isValid' => (isset($results['Score'])) ? 1 : 0,
      'isSpam' => (OOPSpam::$plugin->antiSpam->isSpam($results)) ? 1 : 0,
      'isReport' => 0,
      'type' => $type
    ];
    $logRecord = new LogRecord;
    $logRecord->setAttributes($data, false);    
    $logRecord->save();
  }
  
  public function updateLog(string $uid): bool {
    $logRecord = LogRecord::find()->where(['uid' => $uid])->one();
    if ($logRecord){
      $isReport = Craft::$app->getRequest()->getBodyParam('isReport');
      if ($isReport){
        $data = json_decode($logRecord->getAttribute('params'), true);
        $data['shouldBeSpam'] = (!$logRecord->getAttribute('isSpam')) ? true : false;
        $endpoint = OOPSpam::$plugin->antiSpam->apiVersion.$this->endpoint;
        $result = OOPSpam::$plugin->antiSpam->sendRequest($data, $endpoint);
        if ($result['response']){
          $logRecord->setAttribute('isReport', true);
          $logRecord->save();
          Craft::$app->getProjectConfig()->set('plugins.oopspam.settings.apiUsage', $result['limits']); /* UPDATE LIMITS */
          Craft::$app->getProjectConfig()->saveModifiedConfigData();
          return true;
        }
      }
    }
    return false;
  }
  
  public function getLog(int $id): LogModel {
    $logModel = new LogModel();
    $logRecord = LogRecord::find()->where(['id' => $id])->one();
    if ($logRecord){
      $logModel->setAttributes($logRecord->getAttributes(), true);
    }
    return $logModel;
  }
  
  public function getLogs(): array {
    $this->gcLogs(); /* Garbage Collect */
    $logModels = [];
    $logRecords = LogRecord::find()->orderBy('dateCreated desc')->all();
    foreach($logRecords as $logRecord){
      $logModel = new LogModel();
      $logModel->setAttributes($logRecord->getAttributes(), true);
      $logModels[] = $logModel;
    }
    return $logModels;
  }
  
  public function deleteLogs(array $ids): void {
    LogRecord::deleteAll('id IN ('.implode(',',$ids).')');
  }
  
  public function clearLogs(): void {
    Db::truncateTable(LogRecord::tableName(), LogRecord::getDb());
  }
  
  public function gcLogs(): void {
    LogRecord::deleteAll('dateCreated <= NOW() - INTERVAL '.OOPSpam::$plugin->settings->maxLogs.' DAY');
  }
  
}