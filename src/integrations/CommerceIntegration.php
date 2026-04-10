<?php
namespace cloudgrayau\oopspam\integrations;
use cloudgrayau\oopspam\OOPSpam;

use craft\commerce\events\ProcessPaymentEvent;
use craft\commerce\services\Payments;
use craft\commerce\elements\Order;
use craft\commerce\events\CreateSubscriptionEvent;
use craft\commerce\services\Subscriptions;
use craft\elements\User;
use yii\base\Event;

class CommerceIntegration {
  
  public function getName(): string {
    return 'Commerce';
  }

  public function parse(): void {
    $settings = OOPSpam::$plugin->settings;
    if ($settings->enableCommerce){
      Event::on(Payments::class, Payments::EVENT_BEFORE_PROCESS_PAYMENT, function (ProcessPaymentEvent $e){
        $params = [
          'email' => $e->order->email,
          'checkForLength' => false
        ];
        if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
          $e->isValid = false;
        }
      });
    }
    if ($settings->enableSubscriptions){
      Event::on(Subscriptions::class, Subscriptions::EVENT_BEFORE_CREATE_SUBSCRIPTION, function (CreateSubscriptionEvent $e){
        $user = $e->user;
        $params = [
          'email' => $user->email,
          'checkForLength' => false
        ];
        if (!OOPSpam::$plugin->antiSpam->checkSpam($params, $this->getName())){
          $e->isValid = false;
        }
      });
    }
  }
  
}

?>