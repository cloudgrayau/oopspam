<?php
namespace cloudgrayau\oopspam;
use cloudgrayau\oopspam\models\SettingsModel;
use cloudgrayau\oopspam\controllers\SettingsController;
use cloudgrayau\oopspam\services\AntiSpamService;
use cloudgrayau\oopspam\services\LogsService;
use cloudgrayau\utils\UtilityHelper;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\helpers\UrlHelper;
use craft\services\Gc;
use craft\web\UrlManager;
use craft\web\Application;
use yii\base\Event;

class OOPSpam extends Plugin {

  public static $plugin;
  public string $schemaVersion = '1.0.0';
  public bool $hasCpSettings = true;
  public bool $hasCpSection = true;
  
  // Public Methods
  // =========================================================================
  
  public function getCpNavItem(): array {
    $nav = parent::getCpNavItem();
    if ($this->settings->pluginName){
      $nav['label'] = $this->settings->pluginName;
    }
    $nav['subnav']['logs'] = [
      'label' => Craft::t('oopspam', 'Logs'),
      'url' => 'oopspam/logs',
    ];
    if (Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
      $nav['subnav']['settings'] = [
        'label' => Craft::t('oopspam', 'Settings'),
        'url' => 'oopspam/settings',
      ];
    }
    return $nav;
  }
  
  public function init(): void {
    parent::init();
    self::$plugin = $this;
    $this->hasCpSection = $this->settings->enableLogs;
    $this->_registerComponents();
    $this->_parseSettings();
    $this->_registerConfigChanges();
    $this->_registerGc();
    $this->_registerInit();
    if (Craft::$app->getRequest()->getIsCpRequest()){
      $this->_registerCpUrlRules();
    }
  }
  
  public function getSettingsResponse(): mixed {
    return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('oopspam/settings'));
  }
  
  public static function config(): array {
    return [
      'components' => [
        'antiSpam' => ['class' => AntiSpamService::class],
        'logs' => ['class' => LogsService::class]
      ]
    ];
  }
  
  public static function checkSpam(array $params, string $type = ''): bool {
    if (self::$plugin->settings->apiKey){
      return self::$plugin->antiSpam->checkSpam($params, $type);
    }
    return true;
  }
  
  // Private Methods
  // =========================================================================
  
  private function _registerComponents(): void {
    UtilityHelper::registerModule();
  }
  
  private function _registerInit(): void {    
    Craft::$app->on(Application::EVENT_INIT, function() {      
      if (!$this->settings->apiKey || Craft::$app->getRequest()->getIsCpRequest() || Craft::$app->getRequest()->getIsConsoleRequest()){
        return;
      }
      if ($this->settings->enableUserRegistration){
        $this->antiSpam->initRegistration();
      }
      if (!empty($this->settings->integrations)){
        $this->antiSpam->initIntegrations();
      }
    });
  }
  
  private function _registerConfigChanges(): void {
    if (Craft::$app->getRequest()->getIsCpRequest()){
      Craft::$app->getProjectConfig()->onUpdate('plugins.oopspam.settings', [$this->logs, 'handleChangedProductConfig']);
    }
  }
  
  private function _registerGc(): void {
    Event::on(Gc::class, Gc::EVENT_RUN, function() {
      $this->logs->gcLogs();
    });
  }
  
  private function _registerCpUrlRules(): void {
    Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
      $event->rules += [
        'oopspam' => 'oopspam/logs',
        'oopspam/logs' => 'oopspam/logs/logs',
        'oopspam/logs/<id:[0-9]+>' => 'oopspam/logs/log',
        'oopspam/logs/clear' => 'oopspam/logs/clear',
        'oopspam/settings' => 'oopspam/settings/settings'
      ];
    });
  }
  
  private function _parseSettings(): void { /* Fix table-based settings from config file */
    $settings = Craft::$app->config->getConfigFromFile('oopspam');
    foreach(['blockedEmails','blockedIPs','allowedEmails','allowedIPs'] as $option){
      if (isset($settings[$option])){
        $data = [];
        foreach($settings[$option] as $row){
          $row = (array)$row;
          if (isset($row[0]) && !empty(trim($row[0]))){
            $data[] = array(trim($row[0]));
          }
        }
        $this->settings[$option] = $data;
      }
    }
  }

  // Protected Methods
  // =========================================================================
  
  protected function createSettingsModel(): SettingsModel {
    return new SettingsModel();
  }
   
}
