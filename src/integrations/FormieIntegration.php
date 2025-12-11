<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class FormieIntegration {
  
  private array $fields = [];
  public string $integration = '';
  public function getName(): string {
    return 'Formie';
  }

  public function parse(string $integration): void {
    $this->integration = $integration;
    Event::on(\verbb\formie\services\Submissions::class, \verbb\formie\services\Submissions::EVENT_AFTER_SPAM_CHECK, function(\verbb\formie\events\SubmissionSpamCheckEvent $e){
      $params = [
        'content' => []
      ];
      if (class_exists('\verbb\formie\elements\db\NestedFieldRowQuery')){ /* Formie 2 */
        foreach($e->submission->form->getCustomFields() as $field){
          $value = $e->submission->getFieldValue($field->handle);
          if ($value instanceof \verbb\formie\elements\db\NestedFieldRowQuery){
            foreach($value->all() as $fieldrow) {
              $rows = $fieldrow->getCustomFields();
              foreach($rows as $row){
                switch(get_class($row)){
                  case 'verbb\formie\fields\formfields\Email':
                    $params['email'] = (string)$fieldrow->getFieldValue($row->handle);
                    break;
                  case 'verbb\formie\fields\formfields\MultiLineText':
                    $params['content'][] = (string)$fieldrow->getFieldValue($row->handle);
                    break;
                }
              }
            }
          } else {
            switch(get_class($field)){
              case 'verbb\formie\fields\formfields\Email':
                $params['email'] = (string)$value;
                break;
              case 'verbb\formie\fields\formfields\MultiLineText':
                $params['content'][] = (string)$value;
                break;
            }
          }
        } 
      } else { /* Formie 3 */
        $this->fields = [];
        $this->extractFields($e->submission->form->getFields());
        foreach($this->fields as $field){
          switch(get_class($field)){
            case 'verbb\formie\fields\Email':
              $params['email'] = (string)$e->submission->getFieldValue($field->getFieldKey());
              break;
            case 'verbb\formie\fields\MultiLineText':
              $params['content'][] = (string)$e->submission->getFieldValue($field->getFieldKey());
              break;
          }
        }
      }
      if (empty($params['content'])){ /* override checkForLength when no content fields */
        $params['checkForLength'] = false;
      }
      if ((OOPSpam::$plugin->settings->enableContextual) && (!empty(OOPSpam::$plugin->settings->contextualContent)) && (in_array($this->integration, OOPSpam::$plugin->settings->contextual))){
        $params['contextual'] = true;
      }
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $e->submission->isSpam = true;
      }
    });
  }
  
  protected function extractFields($fields){
    foreach ($fields as $field){
      $this->fields[] = $field;
      if (method_exists($field, 'getFields')) { /* Check if field acts as a container (Group, Repeater, Fieldset) */
        $this->extractFields($field->getFields());
      }
    }
  }
  
}

?>