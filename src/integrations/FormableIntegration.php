<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class FormableIntegration {
  
  public string $integration = '';
  public function getName(): string {
    return 'Formable';
  }

  public function parse(string $integration): void {
    $this->integration = $integration;
    Event::on(\bytesof\formable\services\Submissions::class, \bytesof\formable\services\Submissions::EVENT_BEFORE_SUBMIT, function (\bytesof\formable\events\SubmissionEvent $event) {
      $submission = $event->submission;
      $fields = [];
      $handle = $submission->getForm()->handle;
      if (isset(OOPSpam::$plugin->settings->forms[$handle])){
        $settings = OOPSpam::$plugin->settings->forms[$handle];
        if ((isset($settings['disabled'])) && ($settings['disabled'])){
          return;
        }
        if ((isset($settings['fields'])) && (!empty($settings['fields']))){
          $fields = (array)$settings['fields'];
        }
        OOPSpam::overrideSettings($settings);
      }
      $params = [
        'email' => '',
        'content' => []
      ];
      foreach($fields as $field){
        OOPSpam::addContent($params['content'], $submission->getValue($field));
      }
      $empty = empty($params['content']);
      foreach($submission->getFormFields() as $field){
        switch(get_class($field)){
          case 'bytesof\formable\fields\Email':
            $params['email'] = $submission->getValue($field->handle);
            break;
          case 'bytesof\formable\fields\Textarea':
            if ($empty){
              $params['content'][] = $submission->getValue($field->handle);
            }
            break;
        }
      }
      if (empty($params['content'])){ /* override checkForLength when no content fields */
        $params['checkForLength'] = false;
      }
      if ((OOPSpam::$plugin->settings->enableContextual) && (!empty(OOPSpam::$plugin->settings->contextualContent)) && (in_array($this->integration, OOPSpam::$plugin->settings->contextual))){
        $params['contextual'] = true;
      }
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $submission->isSpam = true;
        $submission->spamReason = 'Blocked by OOPSpam';
      }
    });
  }
  
}

?>