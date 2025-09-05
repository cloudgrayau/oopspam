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
    Event::on(\Solspace\Freeform\Form\Form::class, \Solspace\Freeform\Form\Form::EVENT_SUBMIT, function (\Solspace\Freeform\Events\Forms\SubmitEvent $e){      
      $params = [
        'content' => []
      ];
      foreach($e->getForm()->getFields() as $field){
        switch(get_class($field)){
          case 'Solspace\Freeform\Fields\Implementations\EmailField':
            $params['email'] = $field->getValue();
            break;
          case 'Solspace\Freeform\Fields\Implementations\TextareaField':
            $params['content'][] = $field->getValue();
            break;
        }
      }
      if ((OOPSpam::$plugin->settings->enableContextual) && (!empty(OOPSpam::$plugin->settings->contextualContent)) && (in_array($this->integration, OOPSpam::$plugin->settings->contextual))){
        $params['contextual'] = true;
      }
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $e->getForm()->markAsSpam('OOPSpam', 'Blocked by OOPSpam');
      }
    });
  }
  
}

?>