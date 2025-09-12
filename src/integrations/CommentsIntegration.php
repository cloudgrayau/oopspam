<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use Craft;
use craft\elements\Entry;
use yii\base\Event;

class CommentsIntegration {
  
  public $integration = '';
  public function getName(): string {
    return 'Comments';
  }

  public function parse(string $integration): void {
    $this->integration = $integration;
    Event::on(\verbb\comments\elements\Comment::class, \verbb\comments\elements\Comment::EVENT_BEFORE_VALIDATE, function(\yii\base\ModelEvent $e){
      $comment = $e->sender;
      $params = [
        'email' => ($comment->userId) ? Craft::$app->getUser()->getIdentity()->email : $comment->email,
        'content' => $comment->getComment()
      ];
      if ((OOPSpam::$plugin->settings->enableContextual) && (!empty(OOPSpam::$plugin->settings->contextualContent)) && (in_array($this->integration, OOPSpam::$plugin->settings->contextual))){
        $entry = Entry::find()->id($comment->ownerId)->one();
        $params['contextual'] = true;
        $params['context'] = 'Title: '.$entry->title.' | Description: '.OOPSpam::$plugin->settings->contextualContent;
      }
      if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
        $comment->addError('comment', 'Comment blocked due to spam.');
      }
    });
  }
  
}

?>