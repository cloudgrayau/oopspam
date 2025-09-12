<?php
namespace cloudgrayau\oopspam\controllers;

use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\helpers\SettingsHelper;

use Craft;
use craft\web\Controller;
use yii\web\Response;

class SettingsController extends Controller {
    
  // Public Methods
  // =========================================================================

  public function actionSettings(): Response {
    $settings = OOPSpam::$plugin->getSettings();
    $js = <<<JS
$('#settings-allowedLanguages, #settings-allowedCountries, #settings-blockedCountries').selectize({
    plugins: ['remove_button'],
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
        'edition' => $edition
      ],
      'limits' => OOPSpam::$plugin->logs->getUsage()
    ]);
  }

}