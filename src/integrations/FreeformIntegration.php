<?php
namespace cloudgrayau\oopspam\integrations;

use cloudgrayau\oopspam\OOPSpam;

use Solspace\Freeform\Events\Forms\ValidationEvent;
use Solspace\Freeform\Fields\Implementations\{EmailField, TextareaField};
use Solspace\Freeform\Form\Form;
use yii\base\Event;

class FreeformIntegration extends Integration {

  public function name(): string {
    return 'Freeform';
  }

  public function register(): void {
    Event::on(Form::class, Form::EVENT_AFTER_VALIDATE, function (ValidationEvent $event): void {
      $form = $event->getForm();
      if (!$this->isFrontEndRequest() || !$form->isLastPage() || !$form->isValid() || $form->isMarkedAsSpam()) {
        return;
      }
      $settings = OOPSpam::getInstance()->getSettings();
      $fields = [];
      $overrides = $settings->forms[$form->getHandle()] ?? null;
      if (is_array($overrides)) {
        if (!empty($overrides['disabled'])) {
          return;
        }
        $fields = (array) ($overrides['fields'] ?? []);
        OOPSpam::overrideSettings($overrides);
      }
      $params = ['email' => '', 'content' => []];
      foreach ($form->getFields() as $field) {
        if (in_array($field->getHandle(), $fields, true)) {
          OOPSpam::addContent($params['content'], $field->getValue());
        }
      }
      $useTextareas = empty($params['content']); // no content fields configured (or all empty)
      foreach ($form->getFields() as $field) {
        if ($field instanceof EmailField && $params['email'] === '') {
          $params['email'] = trim((string) $field->getValueAsString());
        } elseif ($field instanceof TextareaField && $useTextareas) {
          OOPSpam::addContent($params['content'], $field->getValue());
        }
      }
      if (empty($params['content'])) { // nothing to check the length of
        $params['checkForLength'] = false;
      }
      if ($settings->enableContextual && $settings->contextualContent !== '' && in_array('freeform', $settings->contextual, true)) {
        $params['contextual'] = true;
      }
      if (!$this->passes($params)) {
        $form->markAsSpam('OOPSpam', 'Blocked by OOPSpam');
      }
    });
  }

}
