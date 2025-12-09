<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class ExpressFormsIntegration {
  
  public $integration = '';
  public function getName(): string {
    return 'Express Forms';
  }

  public function parse(string $integration): void {
    $this->integration = $integration;
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
      if (empty($params['content'])){ /* override checkForLength when no content fields */
        $params['checkForLength'] = false;
      }
      if ((OOPSpam::$plugin->settings->enableContextual) && (!empty(OOPSpam::$plugin->settings->contextualContent)) && (in_array($this->integration, OOPSpam::$plugin->settings->contextual))){
        $params['contextual'] = true;
      }
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $e->getForm()->markAsSpam();
      }
    });
  }
  
}

?>