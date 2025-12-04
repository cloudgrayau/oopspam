<?php
namespace cloudgrayau\oopspam;
use cloudgrayau\oopspam\models\SettingsModel;
use cloudgrayau\oopspam\controllers\SettingsController;
use cloudgrayau\oopspam\services\AntiSpamService;
use cloudgrayau\oopspam\services\LogsService;
use cloudgrayau\oopspam\services\SubmissionService;
use cloudgrayau\oopspam\widgets\OOPSpamWidget;
use cloudgrayau\utils\UtilityHelper;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\UrlHelper;
use craft\services\Dashboard;
use craft\services\Gc;
use craft\web\UrlManager;
use craft\web\Application;
use yii\base\Event;

class OOPSpam extends Plugin {

  public static $plugin;
  public string $schemaVersion = '1.5.0';
  public bool $hasCpSettings = true;
  public bool $hasReadOnlyCpSettings = true;
  public bool $hasCpSection = true;
  
  // Public Methods
  // =========================================================================
  
  public function getCpNavItem(): array {
    $nav = parent::getCpNavItem();
    if ($this->settings->pluginName){
      $nav['label'] = $this->settings->pluginName;
    }
    if ($this->settings->apiKey){
      if ($this->settings->enableLogs){
        $nav['subnav']['logs'] = [
          'label' => Craft::t('oopspam', 'Logs'),
          'url' => 'oopspam/logs',
        ];
      }
      $nav['subnav']['reputation'] = [
        'label' => Craft::t('oopspam', 'Domain Reputation'),
        'url' => 'oopspam/reputation',
      ];
      $nav['subnav']['test'] = [
        'label' => Craft::t('oopspam', 'Test Suite'),
        'url' => 'oopspam/test',
      ];
    }    
    if ((Craft::$app->getConfig()->getGeneral()->allowAdminChanges) || (version_compare(Craft::$app->getVersion(), '5.6.0') >= 0)){
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
    $this->hasCpSection = (Craft::$app->getConfig()->getGeneral()->allowAdminChanges || $this->settings->apiKey) ? true : false;
    $this->_registerComponents();
    $this->_parseSettings();
    $this->_registerGc();
    $this->_registerInit();
    if (Craft::$app->getRequest()->getIsCpRequest()){
      $this->_registerConfigChanges();
      $this->_registerCpUrlRules();
      if ($this->settings->apiKey && $this->settings->enableLogs){
        $this->_registerWidgets();
      }      
    }
  }
  
  /*public function getSettingsResponse(): mixed {
    return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('oopspam/settings'));
  }*/
  
  public static function config(): array {
    return [
      'components' => [
        'antiSpam' => ['class' => AntiSpamService::class],
        'logs' => ['class' => LogsService::class],
        'submissions' => ['class' => SubmissionService::class]
      ]
    ];
  }
  
  public static function checkSpam(array $params, string $type = ''): bool {
    if (self::$plugin->settings->apiKey){
      return self::$plugin->antiSpam->checkSpam($params, $type);
    }
    return true;
  }
  
  public static function testSpam(array $params, string $type = ''): array {
    define('OOPSPAM_TEST', true);
    if (self::$plugin->settings->apiKey){
      return self::$plugin->antiSpam->checkSpam($params, $type);
    }
    return [];
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
      $edition = (isset(Craft::$app->edition->value)) ? Craft::$app->edition->value : Craft::$app->getEdition();        
      if ($this->settings->enableUserRegistration && $edition){
        $this->antiSpam->initRegistration();
      }
      if ($this->settings->enableCommerce && ($edition >= 2) && (Craft::$app->plugins->isPluginEnabled('commerce'))){ /* craft pro */
        $this->antiSpam->initCommerce();
      }
      if (!empty($this->settings->integrations)){
        $this->antiSpam->initIntegrations();
      }
    });
  }
  
  private function _registerConfigChanges(): void {
    Craft::$app->getProjectConfig()->onUpdate('plugins.oopspam.settings', [$this->logs, 'handleChangedProductConfig']);
    Craft::$app->getProjectConfig()->onUpdate('plugins.oopspam.settings', [$this->submissions, 'handleChangedProductConfig']);
  }
  
  private function _registerGc(): void {
    Event::on(Gc::class, Gc::EVENT_RUN, function() {
      $this->logs->gcLogs();
      $this->submissions->gcSubmissions();
    });
  }
  
  private function _registerCpUrlRules(): void {
    Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
      if ($this->settings->apiKey){
        $base = ($this->settings->enableLogs) ? 'oopspam/logs' : 'oopspam/reputation/reputation';
      } else {
        $base = 'oopspam/settings/settings';
      }
      $event->rules = array_merge([
        'oopspam' => $base,
        'oopspam/logs' => 'oopspam/logs/logs',
        'oopspam/logs/<id:[0-9]+>' => 'oopspam/logs/log',
        'oopspam/logs/clear' => 'oopspam/logs/clear',
        'oopspam/reputation' => 'oopspam/reputation/reputation',
        'oopspam/test' => 'oopspam/test/test',
        'oopspam/settings' => 'oopspam/settings/settings',
        'settings/plugins/oopspam' => 'oopspam/settings/settings'
      ], $event->rules);
    });
  }
  
  private function _registerWidgets(): void {
    Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES,
      function(RegisterComponentTypesEvent $event) {
        $event->types[] = OOPSpamWidget::class;
      }
    );
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
