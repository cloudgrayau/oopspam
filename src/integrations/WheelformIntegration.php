<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use Craft;
use craft\helpers\StringHelper;
use yii\base\Event;

class WheelformIntegration {
  
  public function getName(): string {
    return 'Wheel Form';
  }

  public function parse(): void {
    $plugin = Craft::$app->plugins->getPlugin('wheelform');
    if ((int)StringHelper::replace($plugin->getVersion(), '.', '') >= 402){
      Event::on(\wheelform\controllers\MessageController::class, \wheelform\controllers\MessageController::EVENT_BEFORE_SAVE, function(\wheelform\events\MessageEvent $e){
        $params = [
          'content' => []
        ];
        foreach($e->message as $obj) {
          switch($obj->field->type){
            case 'text':
              if (stristr($obj->field->name, 'message')){
                $params['content'][] = $obj->value;
              }
              break;
            case 'email':
              $params['email'] = $obj->value;
              break;
            case 'textarea':
              $params['content'][] = $obj->value;
              break;
          }
        }
        if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
          $e->sendMessage = false;
          $e->saveMessage = false;
        }
      });
    }
  }
  
}

?>