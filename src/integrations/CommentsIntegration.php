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

  public function parse(string $integration): void {
    Event::on(\verbb\comments\elements\Comment::class, \verbb\comments\elements\Comment::EVENT_BEFORE_SAVE, function(ModelEvent $e){
      $comment = $e->sender;
      if ((OOPSpam::$plugin->settings->enableContextual) && (!empty(OOPSpam::$plugin->settings->contextualContent)) && (in_array($integration, OOPSpam::$plugin->settings->contextual))){
        $entry = Entry::find()->id($comment->ownerId)->one();
        $params = [
          'content' => $comment->getComment(),
          'contextual' => true,
          'context' => ($entry) ? ('Title: '.$entry->title."\n".'Description: '.OOPSpam::$plugin->settings->contextualContent) : OOPSpam::$plugin->settings->contextualContent
        ];
      } else {
        $params = [
          'email' => ($comment->userId) ? Craft::$app->getUser()->getIdentity()->email : $comment->email,
          'content' => $comment->getComment()
        ];
      }
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $comment->status = \verbb\comments\elements\Comment::STATUS_SPAM;
      }
    });
  }
  
}

?>