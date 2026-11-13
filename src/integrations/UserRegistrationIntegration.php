<?php
namespace cloudgrayau\oopspam\integrations;

use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function CraftCms\Cms\t;

class UserRegistrationIntegration extends Integration {

  public function name(): string {
    return 'User Registration';
  }

  public function register(): void {
    Event::listen(function (ElementSaving $event): void {
      $user = $event->element;
      if (!$user instanceof User || !$user->firstSave || !$event->isValid) {
        return;
      }
      if (!$this->isFrontEndRequest()) { // users added in the CP or by console commands aren't checked
        return;
      }
      if (!$this->passes(['email' => (string) $user->email, 'checkForLength' => false])) {
        $user->errors()->add('email', t('This registration was flagged as spam.', category: 'oopspam'));
        $event->isValid = false;
      }
    });
  }

}
