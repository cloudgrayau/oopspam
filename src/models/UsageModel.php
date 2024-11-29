<?php
namespace cloudgrayau\oopspam\models;

use craft\base\Model;
use craft\validators\DateTimeValidator;
use DateTime;

class UsageModel extends Model {
  
  public ?int $id = null;
  public int $limit = 0;
  public int $remaining = 0;
  public ?DateTime $dateCreated = null;
  public ?DateTime $dateUpdated = null;
  public ?string $uid = null;
  
  public function rules(): array {
    $rules = parent::rules();
    $rules[] = [['id', 'limit','remaining'], 'integer'];
    $rules[] = [['uid'], 'string'];
    $rules[] = [['dateCreated', 'dateUpdated'], DateTimeValidator::class];
    return $rules;
  }
  
}