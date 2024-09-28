<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use Craft;
use craft\events\ModelEvent;
use yii\base\Event;

class CommentsIntegration {
  
  public function getName(): string {
    return 'Comments';
  }

  public function parse(): void {
    Event::on(\verbb\comments\elements\Comment::class, \verbb\comments\elements\Comment::EVENT_BEFORE_SAVE, function(ModelEvent $e){
      $comment = $e->sender;
      $params = [
        'email' => ($comment->userId) ? Craft::$app->getUser()->getIdentity()->email : $comment->email,
        'content' => $comment->getComment()
      ];
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $comment->status = \verbb\comments\elements\Comment::STATUS_SPAM;
      }
    });
  }
  
}

?>