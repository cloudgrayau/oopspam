<?php
namespace cloudgrayau\oopspam\models;

use craft\base\Model;
use craft\validators\DateTimeValidator;
use yii\db\Expression;
use DateTime;

class SubmissionModel extends Model {
  
  public string|Expression $ipaddress = '';
  public ?DateTime $dateCreated = null;
  public ?DateTime $dateUpdated = null;
  public ?string $uid = null;
  
  public function rules(): array {
    $rules = parent::rules();
    $rules[] = [['uid'], 'string'];
    $rules[] = [['dateCreated','dateUpdated'], DateTimeValidator::class];
    return $rules;
  }
  
}