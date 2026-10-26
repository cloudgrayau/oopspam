<?php
namespace cloudgrayau\oopspam\controllers;

use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\helpers\SettingsHelper;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use craft\helpers\Cp;
use yii\web\Response;

class SettingsController extends Controller {
  
  use RequiresPluginAccess;
    
  // Public Methods
  // =========================================================================
  
  public function actionIndex(): void {
    Craft::$app->response->redirect(UrlHelper::cpUrl('oopspam/settings'))->send();
    exit();
  }

  public function actionSettings(): Response {
    $disableAdmin = version_compare(Craft::$app->getVersion(), '5.6.0', '<');
    $this->requireAdmin($disableAdmin);
    $settings = OOPSpam::$plugin->getSettings();
    $js = <<<JS
$('#settings-allowedLanguages, #settings-allowedCountries, #settings-blockedCountries').selectize({
    plugins: ['remove_button'],
});
$('#settings-spamScore-num').change(function(e){
  let scores = JSON.parse($(this).attr('data-scores'));
  $('#settings-spamScore-text').html(scores[this.value-1]);
});
JS;
    Craft::$app->getView()->registerJs($js);
    $edition = (isset(Craft::$app->edition->value)) ? Craft::$app->edition->value : Craft::$app->getEdition();  
    return $this->renderTemplate('oopspam/settings', [
      'settings' => $settings,
      'helper' => [
        'countries' => SettingsHelper::getCountries(),
        'languages' => SettingsHelper::getLanguages(),
        'services' => SettingsHelper::getServices(),
        'integrations' => SettingsHelper::getIntegrations(),
        'edition' => $edition,
        'scores' => [
          'Extremely strict',
          'Very strict',
          'Moderate (recommended)',
          'Slightly lenient',
          'Lenient',
          'Very lenient'
        ]
      ],
      'limits' => OOPSpam::$plugin->logs->getUsage(),
      'admin' => [
        'disableAdmin' => $disableAdmin,
        'readOnly' => !Craft::$app->getConfig()->getGeneral()->allowAdminChanges,
        'cpNotice' => (method_exists('\craft\helpers\Cp', 'readOnlyNoticeHtml')) ? Cp::readOnlyNoticeHtml() : ''
      ]
    ]);
  }

}