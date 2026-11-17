<?php
namespace cloudgrayau\oopspam\integrations;

use cloudgrayau\oopspam\OOPSpam;

use CraftCms\Cms\Support\Arr;
use CraftCms\ContactForm\Events\MessageSending;
use Illuminate\Support\Facades\Event;

class ContactFormIntegration extends Integration {

  public function name(): string {
    return 'Contact Form';
  }

  public function register(): void {
    Event::listen(function (MessageSending $event): void {
      if (!$this->isFrontEndRequest() || !$event->isValid || $event->isSpam) {
        return;
      }
      $settings = OOPSpam::getInstance()->getSettings();
      $fields = [];
      $overrides = $settings->forms['contact-form'] ?? null;
      if (is_array($overrides)) {
        if (!empty($overrides['disabled'])) {
          return;
        }
        $fields = (array) ($overrides['fields'] ?? []);
        OOPSpam::overrideSettings($overrides);
      }
      // The validated submission: fromEmail, fromName, subject and message (a string, or an array of sub-fields)
      $submission = $event->message->variables;
      $params = ['email' => trim((string) ($submission['fromEmail'] ?? '')), 'content' => []];
      foreach ($fields as $field) { // e.g. `subject`, or `message.phone` for a sub-field
        $value = data_get($submission, (string) $field);
        OOPSpam::addContent($params['content'], is_array($value) ? Arr::flatten($value) : $value);
      }
      if (empty($params['content'])) {
        OOPSpam::addContent($params['content'], $submission['fromName'] ?? null);
        OOPSpam::addContent($params['content'], Arr::flatten((array) ($submission['message'] ?? [])));
      }
      if ($settings->enableContextual && $settings->contextualContent !== '' && in_array('contact-form', $settings->contextual, true)) {
        $params['contextual'] = true;
      }
      if (!$this->passes($params)) {
        $event->isSpam = true; // Contact Form skips sending, and still shows the visitor its success message
      }
    });
  }

}
