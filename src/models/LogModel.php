<?php
namespace cloudgrayau\oopspam\models;

use craft\base\Model;
use craft\validators\DateTimeValidator;
use DateTime;

class LogModel extends Model {
  
  public ?int $id = null;
  public string $type = '';
  public string $endpoint = '';
  public string $params = '';
  public string $response = '';
  public ?int $isValid = null;
  public ?int $isSpam = null;
  public ?int $isReport = null;
  public ?DateTime $dateCreated = null;
  public ?DateTime $dateUpdated = null;
  public ?string $uid = null;
  
  private ?array $params_ = null;
  private ?array $response_ = null;
  
  public function rules(): array {
    $rules = parent::rules();
    $rules[] = [['id', 'isValid','isSpam','isReport'], 'integer'];
    $rules[] = [['type','endpoint','params','response','uid'], 'string'];
    $rules[] = [['dateCreated', 'dateUpdated'], DateTimeValidator::class];
    return $rules;
  }
  
  public function getParam($param){
    if (is_null($this->params_)){
      $this->params_ = json_decode($this->params, true) ?? [];
    }
    return $this->params_[$param] ?? '';
  }
  
  public function getParams(): array {
    if (is_null($this->params_)){
      $this->params_ = json_decode($this->params, true) ?? [];
    }
    return $this->params_;
  }
  
  public function getResponse($response){
    if (is_null($this->response_)){
      $this->response_ = json_decode($this->response, true) ?? [];
    }
    return $this->response_[$response] ?? '';
  }
  
  public function getResponses(): array {
    if (is_null($this->response_)){
      $this->response_ = json_decode($this->response, true) ?? [];
    }
    return $this->response_;
  }
  
}