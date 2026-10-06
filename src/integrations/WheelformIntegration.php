<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use yii\base\Event;

class WheelformIntegration {
  
  public string $integration = '';
  public function getName(): string {
    return 'Wheel Form';
  }

  public function parse(string $integration): void {
    $this->integration = $integration;
    Event::on(\wheelform\controllers\MessageController::class, \wheelform\controllers\MessageController::EVENT_BEFORE_SAVE, function(\wheelform\events\MessageEvent $e){
      $fields = [];
      $handle = $e->form_id;
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
      if (!empty($fields)){
        foreach($e->message as $obj) {
          if (in_array($obj->field->name, $fields)){
            OOPSpam::addContent($params['content'], $obj->value);
          }
        }
      }
      $empty = empty($params['content']);
      foreach($e->message as $obj) {
        switch($obj->field->type){
          case 'text':
            if ($empty && (stristr($obj->field->name, 'message'))){
              $params['content'][] = $obj->value;
            }
            break;
          case 'email':
            $params['email'] = $obj->value;
            break;
          case 'textarea':
            if ($empty){
              $params['content'][] = $obj->value;
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
        $e->sendMessage = false;
        $e->saveMessage = false;
      }
    });
  }
  
}

?>