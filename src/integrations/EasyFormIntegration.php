<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class EasyFormIntegration {
  
  public string $integration = '';
  public function getName(): string {
    return 'Easy Form';
  }

  public function parse(string $integration): void {
    $this->integration = $integration;
    Event::on(\yannkost\easyform\services\Submissions::class, \yannkost\easyform\services\Submissions::EVENT_BEFORE_SAVE_SUBMISSION, function (\yannkost\easyform\events\SubmissionEvent $event) {
      $submission = $event->submission;
      $fields = [];
      if (isset(OOPSpam::$plugin->settings->forms[$submission->formHandle])){
        $settings = OOPSpam::$plugin->settings->forms[$submission->formHandle];
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
        OOPSpam::addContent($params['content'], $submission->getFieldValue($field));
      }
      $empty = empty($params['content']);
      foreach($submission->getForm()->getFields() as $field){
        switch($field['type']){
          case 'email':
            $params['email'] = $submission->getFieldValue($field['handle']);
            break;
          case 'textarea':
            if ($empty){
              $params['content'][] = $submission->getFieldValue($field['handle']);
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
        $submission->markAsSpam();
        $submission->spamReason = 'Blocked by OOPSpam';
      }
    });
  }
  
}

?>