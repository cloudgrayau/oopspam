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
      $settings = OOPSpam::$plugin->settings->forms[$this->integration] ?? [];
      if ((isset($settings['disabled'])) && ($settings['disabled'])){
        return;
      }
      OOPSpam::overrideSettings($settings);
      $submission = $e->submission;
      $params = [
        'email' => $submission['fromEmail'] ?? '',
        'content' => array_merge([$submission['fromName'] ?? ''], (array)($submission['message'] ?? ''))
      ];
      $fields = $settings['fields'] ?? [];
      if ($fields && is_array($submission['message'] ?? null)){
        $selected = array_filter(array_intersect_key($submission['message'], array_flip($fields)), 'is_scalar');
        if (trim(implode('', $selected)) !== ''){
          $params['content'] = array_merge([$submission['fromName'] ?? ''], array_values($selected));
        }
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
