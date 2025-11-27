<?php
namespace cloudgrayau\oopspam\controllers;

use cloudgrayau\oopspam\OOPSpam;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use yii\web\Response;

class ReputationController extends Controller {
    
  // Public Methods
  // =========================================================================

  public function actionReputation(): Response {
    $settings = OOPSpam::$plugin->getSettings();
    $results = [];
    $errors = [];
    if (isset($_POST['domain'])){
      $domain = UrlHelper::isAbsoluteUrl(Craft::$app->getRequest()->getBodyParam('domain')) ? (parse_url(str_replace('www.', '', Craft::$app->getRequest()->getBodyParam('domain')))['host']) : trim(str_replace('www.', '', Craft::$app->getRequest()->getBodyParam('domain')));
      if (!empty($domain)){        
        if ((filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) && (str_contains($domain, '.')) && ((checkdnsrr($domain, 'A')) || (checkdnsrr($domain, 'MX')))){
          $results = OOPSpam::$plugin->antiSpam->checkReputation($domain);
          if (isset($results['error'])){
            $errors[] = 'Error: '.$results['error']['code'];
          }
        } else {
          $errors[] = 'Domain Name is not valid';
        }
      } else {
        $errors[] = 'Domain Name cannot be blank';
        $domain = parse_url(UrlHelper::siteHost())['host'];
      }
    } else {
      $domain = parse_url(UrlHelper::siteHost())['host'];
    }
    $css = <<<CSS
pre.sf-dump .sf-dump-compact {
  display: block;
}
pre.sf-dump .sf-dump-expanded .sf-dump-compact {
  display: inline !important;
}
.sf-dump-str-collapse {
  display: inline !important;
}
.sf-dump-str-expand {
  display: none !important;
}
.sf-dump-note {
  pointer-events: none;
}
.sf-dump-toggle, .sf-dump-str-toggle {
    display: none;
}
CSS;
    Craft::$app->getView()->registerCss($css);
    return $this->renderTemplate('oopspam/reputation', [
      'settings' => $settings,
      'domain' => $domain,
      'results' => $results,
      'errors' => $errors,
      'limits' => OOPSpam::$plugin->logs->getUsage()
    ]);
  }

}