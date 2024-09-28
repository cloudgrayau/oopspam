<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class ContactFormIntegration {
  
  public function getName(): string {
    return 'Contact Form';
  }

  public function parse(): void {
    Event::on(\craft\contactform\Mailer::class, \craft\contactform\Mailer::EVENT_BEFORE_SEND, function(\craft\contactform\events\SendEvent $e){
      $submission = $e->submission;
      $params = [
        'email' => $submission['fromEmail'] ?? '',
        'content' => $submission['message'] ?? ''
      ];
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $e->isSpam = true; 
      }
    });
  }
  
}

?>