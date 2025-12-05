<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;
use Craft;
use yii\base\Event;

class FormieIntegration {
  
  public $integration = '';
  public function getName(): string {
    return 'Formie';
  }

  public function parse(string $integration): void {
    $this->integration = $integration;
    Event::on(\verbb\formie\services\Submissions::class, \verbb\formie\services\Submissions::EVENT_AFTER_SPAM_CHECK, function(\verbb\formie\events\SubmissionSpamCheckEvent $e){
      $fields = 0;
      $params = [
        'content' => []
      ];
      foreach($e->submission->form->getCustomFields() as $field){
        switch(get_class($field)){
          case 'verbb\formie\fields\formfields\Email':
          case 'verbb\formie\fields\Email':
            $params['email'] = (string)$e->submission->getFieldValue($field->handle);
            $fields++;
            break;
          case 'verbb\formie\fields\formfields\MultiLineText':
          case 'verbb\formie\fields\MultiLineText':
            $params['content'][] = (string)$e->submission->getFieldValue($field->handle);
            $fields++;
            break;
        }
      }
      if ((OOPSpam::$plugin->settings->enableContextual) && (!empty(OOPSpam::$plugin->settings->contextualContent)) && (in_array($this->integration, OOPSpam::$plugin->settings->contextual))){
        $params['contextual'] = true;
      }
      if ($fields > 0) {
        if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
          $e->submission->isSpam = true;
        }
      } else {
        Craft::warning("No relevant fields found in Formie `{$e->submission->form->handle}` submission to check for spam.", 'oopspam');
      }
    });
  }
  
}

?>