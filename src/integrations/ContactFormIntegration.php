<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class ContactFormIntegration {
  
  public string $integration = '';
  public function getName(): string {
    return 'Contact Form';
  }

  public function parse(string $integration): void {
    $this->integration = $integration;
    Event::on(\craft\contactform\Mailer::class, \craft\contactform\Mailer::EVENT_BEFORE_SEND, function(\craft\contactform\events\SendEvent $e){
      $fields = [];
      if (isset(OOPSpam::$plugin->settings->forms['contact-form'])){
        $settings = OOPSpam::$plugin->settings->forms['contact-form'];
        if ((isset($settings['disabled'])) && ($settings['disabled'])){
          return;
        }
        if ((isset($settings['fields'])) && (!empty($settings['fields']))){
          $fields = (array)$settings['fields'];
        }
        OOPSpam::overrideSettings($settings);
      }
      $submission = $e->submission;
      $params = [
        'email' => $submission['fromEmail'] ?? '',
        'content' => []
      ];
      foreach($fields as $field){
        OOPSpam::addContent($params['content'], $submission[$field] ?? null);
      }
      if (empty($params['content'])){
        $params['content'] = array_merge([$submission['fromName'] ?? ''], (array)($submission['message'] ?? ''));
      }
      if ((OOPSpam::$plugin->settings->enableContextual) && (!empty(OOPSpam::$plugin->settings->contextualContent)) && (in_array($this->integration, OOPSpam::$plugin->settings->contextual))){
        $params['contextual'] = true;
      }
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $e->isSpam = true; 
      }
    }, append: false);
  }
  
}

?>
