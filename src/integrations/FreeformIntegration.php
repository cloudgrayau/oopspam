<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class FreeformIntegration {
  
  public function getName(): string {
    return 'Freeform';
  }

  public function parse(): void {
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
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $e->getForm()->markAsSpam('OOPSpam', 'Blocked by OOPSpam');
      }
    });
  }
  
}

?>