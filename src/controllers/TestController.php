<?php
namespace cloudgrayau\oopspam\controllers;

use cloudgrayau\oopspam\controllers\concerns\OOPSpamScreens;
use cloudgrayau\oopspam\helpers\ResultsHelper;
use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\utils\UtilityHelper;

use CraftCms\Cms\Ui\Controls\{Choice, Lightswitch, Text, Textarea};
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\{UiContext, UiResolver};
use CraftCms\Cms\Ui\Nodes\{Field, Group};
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use function CraftCms\Cms\{currentUser, t};

class TestController {

  use OOPSpamScreens;

  public function show(): CpScreenResponse {
    $settings = OOPSpam::getInstance()->getSettings();
    return $this->screen([
      'type' => 'general',
      'email' => currentUser()?->email ?? '',
      'ip' => (string) request()->ip(),
      'context' => $settings->contextualContent,
      'content' => '',
      'checkForLength' => $settings->checkForLength,
    ]);
  }

  public function run(Request $request): CpScreenResponse {
    $values = $request->validate([
      'type' => ['required', Rule::in(['general', 'contextual'])],
      'email' => ['required_if:type,general', 'nullable', 'email'],
      'ip' => ['required_if:type,general', 'nullable', 'string', 'max:64'],
      'context' => ['required_if:type,contextual', 'nullable', 'string'],
      'content' => ['required_if:type,contextual', 'nullable', 'string'],
    ]);
    $values['checkForLength'] = $request->boolean('checkForLength');
    $general = $values['type'] === 'general';
    $out = OOPSpam::testSpam($general ? [
      'ip' => $values['ip'],
      'email' => $values['email'],
      'content' => $values['content'] ?? '',
      'checkForLength' => $values['checkForLength'],
    ] : [
      'contextual' => true,
      'context' => $values['context'],
      'content' => $values['content'],
      'checkForLength' => $values['checkForLength'],
    ], 'Test Suite');
    if (!$general) {
      unset($out['data']['senderIP'], $out['data']['email']);
    }
    $results = $out['results'];
    $status = (is_array($results) && isset($results['Score']))
      ? (OOPSpam::getInstance()->antiSpam->isSpam($results) ? 'Spam' : 'Pass')
      : 'Failed';
    return $this->screen($values, ResultsHelper::nodes('test', $status, ['Parameters' => $out['data'], 'Response' => $results]));
  }

  private function screen(array $values, array $nodes = []): CpScreenResponse {
    $values = $this->withDefaults($values);
    return $this->formScreen(t('Test Suite', category: 'oopspam'), 'test', 'oopspam/test', t('Run Test', category: 'oopspam'), $this->form($values, $nodes), $values, [], 'oopspam/test/refresh');
  }
  
  public function refresh(Request $request): JsonResponse {
    $values = $this->withDefaults((array) $request->input('values', $request->all()));
    return new JsonResponse(['ui' => app(UiResolver::class)->resolve(
      $this->form($values),
      new UiContext(values: $values, refreshable: true)
    )]);
  }
  
  private function withDefaults(array $values): array {
    $values['type'] ??= 'general';
    $values['context'] ??= OOPSpam::getInstance()->getSettings()->contextualContent;
    return $values;
  }
  
  private function form(array $values, array $nodes = []): Ui {
    $type = $values['type'] ?? 'general';
    return Ui::make([
      Field::make(t('Test type', category: 'oopspam'), Choice::make('type')->reactive()->options([
        ['value' => 'general', 'label' => t('General', category: 'oopspam')],
        ['value' => 'contextual', 'label' => t('Contextual (Experimental)', category: 'oopspam')],
      ]))->required(),
      ...($type === 'general' ? [
        Field::make(t('Email Address', category: 'oopspam'), Text::make('email')->inputType('email')),
        Field::make(t('IP Address', category: 'oopspam'), Text::make('ip')),
      ] : [
        Field::make(t('Contextual Analysis', category: 'oopspam'), Textarea::make('context')->rows(3))
          ->instructions(t('Briefly describe your website’s purpose or business (2-3 sentences maximum).', category: 'oopspam')),
      ]),
      Field::make(t('Content', category: 'oopspam'), Textarea::make('content')->rows(5)),
      Field::make(t('Check Content Length', category: 'oopspam'), Lightswitch::make('checkForLength'))
        ->instructions(t('If the `content` parameter is shorter than 20 characters, it will be considered spam.', category: 'oopspam')),
      ...$nodes,
      UtilityHelper::footer('oopspam-footer-test'),
    ]);
  }

}