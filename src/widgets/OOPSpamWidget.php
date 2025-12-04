<?php
namespace cloudgrayau\oopspam\widgets;

use Craft;
use craft\base\Widget;
use craft\helpers\Html;
use cloudgrayau\oopspam\OOPSpam;

class OOPSpamWidget extends Widget {
  
  public ?string $title = null;
  public ?int $limit = 5;
  
  public function __construct($config = []) {
    if ($this->title === null){
      $this->title = self::displayName();
    }
    if (($config['limit'] ?? null) === '') {
      unset($config['limit']);
    }
    parent::__construct($config);
  }
  
  protected function defineRules(): array {
    $rules = parent::defineRules();
    $rules[] = [['title','limit'], 'required'];
    $rules[] = [['limit'], 'integer', 'min' => 1];
    return $rules;
  }

  public static function displayName(): string {
    return (OOPSpam::$plugin->settings->pluginName) ? OOPSpam::$plugin->settings->pluginName : Craft::t('oopspam', 'OOPSpam');
  }
  
  protected static function allowMultipleInstances(): bool {
    return false;
  }
  
  public static function icon(): string {
    return Craft::getAlias('@cloudgrayau/oopspam/icon-mask.svg');
  }
  
  public function getTitle(): ?string {
    return $this->title;
  }
  
  public function getBodyHtml(): ?string {
    $logs = OOPSpam::$plugin->logs->getLogs($this->limit);
    return Craft::$app->getView()->renderTemplate('oopspam/widget/widget', [
      'logs' => $logs
    ]);
  }
  
  public function getSettingsHtml(): string {
    return Craft::$app->getView()->renderTemplate('oopspam/widget/settings', [
      'widget' => $this
    ]);
  }

}
