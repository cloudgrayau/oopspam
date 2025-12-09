<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class FreeformIntegration {
  
  public $integration = '';
  public function getName(): string {
    return 'Freeform';
  }

  public function parse(string $integration): void {
    $this->integration = $integration;
    Event::on(\Solspace\Freeform\Form\Form::class, \Solspace\Freeform\Form\Form::EVENT_BEFORE_VALIDATE, function (\Solspace\Freeform\Events\Forms\ValidationEvent $e){
      $form = $e->getForm();
      if ($form->isValid()){
        $params = [
          'content' => []
        ];
        foreach($form->getFields() as $field){
          switch(get_class($field)){
            case 'Solspace\Freeform\Fields\Implementations\EmailField':
              $params['email'] = $field->getValue();
              break;
            case 'Solspace\Freeform\Fields\Implementations\TextareaField':
              $params['content'][] = $field->getValue();
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
          $form->markAsSpam('OOPSpam', 'Blocked by OOPSpam');
        }
      }
    });
  }
  
}

?>