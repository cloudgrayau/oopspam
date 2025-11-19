<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class FormieIntegration {
  
  public $integration = '';
  public function getName(): string {
    return 'Formie';
  }

  public function parse(string $integration): void {
    $this->integration = $integration;
    Event::on(\verbb\formie\services\Submissions::class, \verbb\formie\services\Submissions::EVENT_AFTER_SPAM_CHECK, function(\verbb\formie\events\SubmissionSpamCheckEvent $e){
      $params = [
        'content' => []
      ];
      foreach($e->submission->form->getCustomFields() as $field){
        switch(get_class($field)){
          case 'verbb\formie\fields\formfields\Email':
          case 'verbb\formie\fields\Email':
            $params['email'] = (string)$e->submission->getFieldValue($field->handle);
            break;
          case 'verbb\formie\fields\formfields\MultiLineText':
          case 'verbb\formie\fields\MultiLineText':
            $params['content'][] = (string)$e->submission->getFieldValue($field->handle);
            break;
        }
      }
      if ((OOPSpam::$plugin->settings->enableContextual) && (!empty(OOPSpam::$plugin->settings->contextualContent)) && (in_array($this->integration, OOPSpam::$plugin->settings->contextual))){
        $params['contextual'] = true;
      }
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $e->submission->isSpam = true;
      }
    });
  }
  
}

?>