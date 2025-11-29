<?php
namespace cloudgrayau\oopspam\models;

use craft\base\Model;
use craft\validators\DateTimeValidator;
use DateTime;

class SubmissionModel extends Model {
  
  public int $ipaddress = 0;
  public ?DateTime $dateCreated = null;
  public ?DateTime $dateUpdated = null;
  public ?string $uid = null;
  
  public function rules(): array {
    $rules = parent::rules();
    $rules[] = [['ipaddress'], 'integer'];
    $rules[] = [['uid'], 'string'];
    $rules[] = [['dateCreated', 'dateUpdated'], DateTimeValidator::class];
    return $rules;
  }
  
}