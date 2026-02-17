<?php
namespace cloudgrayau\oopspam\controllers;

use cloudgrayau\oopspam\OOPSpam;

use Craft;
use craft\web\Controller;
use yii\web\Response;

class TestController extends Controller {
  
  static private function validateEmail($email):bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
  }
    
  // Public Methods
  // =========================================================================

  public function actionTest(): Response {
    $settings = OOPSpam::$plugin->getSettings();
    $general = new \stdClass();
    $contextual = new \stdClass();
    $email = Craft::$app->getRequest()->getBodyParam('email') ?? Craft::$app->getUser()->getIdentity()->email;
    $ip = Craft::$app->getRequest()->getBodyParam('ip') ?? Craft::$app->request->getUserIP();
    $content = Craft::$app->getRequest()->getBodyParam('content') ?? '';
    $context = Craft::$app->getRequest()->getBodyParam('context') ?? OOPSpam::$plugin->settings->contextualContent;
    $checkForLength = Craft::$app->getRequest()->getBodyParam('checkForLength') ?? OOPSpam::$plugin->settings->checkForLength;
    if (isset($_POST['general'])){
      if (empty($email)){
        $general->email[] = 'Please enter an email address';
      } else {
        if (!self::validateEmail($email)){
          $general->email[] = 'Please enter a valid email address';
        }
      }
      if (empty($ip)){
        $general->ip[] = 'Please enter an IP address';
      }
      if (count((array)$general) === 0) {
        $params = [
          'ip' => $ip,
          'email' => $email,
          'content' => $content,
          'checkForLength' => $checkForLength
        ];
        $results = OOPSpam::testSpam($params, 'Test Suite');        
        $general->data = $results['data'];
        $general->results = $results['results'];
        $general->status = is_array($results['results']) ? !OOPSpam::$plugin->antiSpam->isSpam($results['results']) : true;
      }
    }
    if (isset($_POST['contextual'])){
      if (empty($context)){
        $contextual->context[] = 'Please enter some context';
      }
      if (empty($content)){
        $contextual->content[] = 'Please enter some content';
      }
      if (count((array)$contextual) === 0) {
        $params = [
          'contextual' => true,
          'context' => $context,
          'content' => $content,
          'checkForLength' => $checkForLength
        ];
        $results = OOPSpam::testSpam($params, 'Test Suite');
        unset($results['data']['senderIP']);
        unset($results['data']['email']);
        $contextual->data = $results['data'];
        $contextual->results = $results['results'];
        $contextual->status = is_array($results['results']) ? !OOPSpam::$plugin->antiSpam->isSpam($results['results']) : true;
      }
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
@media screen and (max-width: 767px) {
.oopspam {
  grid-template-columns: 1fr !important;  
}
}
CSS;
    Craft::$app->getView()->registerCss($css);
    return $this->renderTemplate('oopspam/test', [
      'email' => $email,
      'ip' => $ip,
      'content' => $content,
      'context' => $context,
      'checkForLength' => $checkForLength,
      'settings' => $settings,
      'general' => $general,
      'contextual' => $contextual,
      'limits' => OOPSpam::$plugin->logs->getUsage()
    ]);
  }

}