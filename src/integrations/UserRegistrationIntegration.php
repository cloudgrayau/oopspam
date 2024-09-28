<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use craft\elements\User;
use craft\events\ModelEvent;
use yii\base\Event;

class UserRegistrationIntegration {
  
  public function getName(): string {
    return 'User Registration';
  }

  public function parse(): void {
    Event::on(User::class, User::EVENT_BEFORE_SAVE, function (ModelEvent $e){
      $user = $e->sender;
      if ($user->firstSave){
        $params = [
          'email' => $e->sender->email ?? '',
          'content' => $e->sender->fullName ?? '',
          'checkForLength' => false
        ];
        if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
          $e->isValid = false;
        }
      }
    });
  }
  
}

?>