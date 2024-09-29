<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class ExpressFormsIntegration {
  
  public function getName(): string {
    return 'Express Forms';
  }

  public function parse(): void {
    Event::on(\Solspace\ExpressForms\models\Form::class, \Solspace\ExpressForms\models\Form::EVENT_VALIDATE_FORM, function(\Solspace\ExpressForms\events\forms\FormValidateEvent $e){
      if (!$e->getForm()->isValid()){
        return;
      }
      $params = [
        'content' => []
      ];
      foreach($e->getForm()->getFields() as $field){
        switch(get_class($field)){
          case 'Solspace\ExpressForms\fields\Email':
            $params['email'] = $field->getValue();
            break;
          case 'Solspace\ExpressForms\fields\Textarea':
            $params['content'][] = $field->getValue();
            break;
        }
      }
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $e->getForm()->markAsSpam();
      }
    });
  }
  
}

?>