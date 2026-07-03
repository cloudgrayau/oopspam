<?php
namespace cloudgrayau\oopspam\services;

use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\models\SubmissionModel;
use cloudgrayau\oopspam\records\SubmissionRecord;

use Craft;
use craft\events\ConfigEvent;
use craft\helpers\Db;
use craft\base\Component;
use yii\db\Expression;

class SubmissionService extends Component {
  
  public function handleChangedProductConfig(ConfigEvent $event){
    if (isset($event->oldValue['enableLimiting']) && ($event->oldValue['enableLimiting'] !== $event->newValue['enableLimiting'])){
      $this->clearSubmissions();
    }
  }
  
  public function recordSubmission(string $ipaddress): void {
    if ($ipaddress === ''){
      return;
    }
    $binary = inet_pton($ipaddress);
    if ($binary === false){
      return;
    }
    $data = [
      'ipaddress' => self::binaryCondition($binary)
    ];
    $submissionRecord = new SubmissionRecord;
    $submissionRecord->setAttributes($data, false);    
    $submissionRecord->save();
  }
  
  public function checkSubmission(string $ipaddress): bool {
    if ($ipaddress === ''){
      return false;
    }
    $binary = inet_pton($ipaddress);
    if ($binary === false){
      return false;
    }
    $maxSubmissions = OOPSpam::$plugin->settings->maxSubmissions;
    $date = new \DateTime();
    $date->modify('-1 hour');
    $count = SubmissionRecord::find()->where([
      '>=', 'dateCreated', Db::prepareDateForDb($date)
    ])->andWhere(['ipaddress' => self::binaryCondition($binary)])->count();
    if ($count >= $maxSubmissions){
      return true;
    }
    return false;
  }
  
  public function clearSubmissions(): void {
    Db::truncateTable(SubmissionRecord::tableName(), SubmissionRecord::getDb());
  }
  
  public function gcSubmissions(): void {
    $date = new \DateTime();
    $date->modify('-1 hour');
    SubmissionRecord::deleteAll(['<', 'dateCreated', Db::prepareDateForDb($date)]);
  }
  
  private static function binaryCondition(string $binary): string|Expression {
    return Craft::$app->db->getIsPgsql() ? new Expression("decode(:value, 'hex')", [':value' => bin2hex($binary)]) : $binary;
  }
  
}
