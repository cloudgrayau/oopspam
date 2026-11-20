<?php
namespace cloudgrayau\oopspam;

use cloudgrayau\oopspam\console\GcCommand;
use cloudgrayau\oopspam\helpers\SettingsHelper;
use cloudgrayau\oopspam\integrations\UserRegistrationIntegration;
use cloudgrayau\oopspam\models\SettingsModel;
use cloudgrayau\oopspam\requests\SettingsRequest;
use cloudgrayau\oopspam\services\{AntiSpamService, LogsService, SubmissionService};
use cloudgrayau\oopspam\widgets\OOPSpamWidget;
use cloudgrayau\utils\UtilityHelper;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Ui\{Ui, UiContext};
use CraftCms\Cms\Ui\Controls\{Choice, Lightswitch, Number, Table, Text, Textarea};
use CraftCms\Cms\Ui\Enums\ChoicePresentation;
use CraftCms\Cms\Ui\Nodes\{Callout, Field, Group, Tab, TemplateContent};
use CraftCms\Cms\Plugin\Events\PluginSettingsSaved;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Plugin\{Plugin, PluginSettings};
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;

use function CraftCms\Cms\{cp_url, currentUser, t};

class OOPSpam extends Plugin {
  
  protected array $commands = [GcCommand::class];
  protected array $widgets = [OOPSpamWidget::class];

  private bool $wasLogging = true;
  private bool $wasLimiting = false;

  public string $schemaVersion = '2.0.0';
  public bool $hasCpSettings = true;
  public bool $hasCpSection = true;
  
  public AntiSpamService $antiSpam { get => $this->antiSpam ??= new AntiSpamService; }
  public LogsService $logs { get => $this->logs ??= new LogsService; }
  public SubmissionService $submissions { get => $this->submissions ??= new SubmissionService; }

  protected function schedule(Schedule $schedule): void {
    $schedule->command('oopspam:gc')->everySixHours()->withoutOverlapping();
  }

  #[\Override]
  protected static function createSettings(): PluginSettings {
    return new SettingsModel();
  }

  private function numeric(UiContext $context, string $key, int $saved): int {
    $value = $this->current($context, $key, $saved);
    return (is_numeric($value) && (int) $value > 0) ? (int) $value : $saved;
  }

  private function registerIntegrations(SettingsModel $s): void {
    if (!$s->apiKey) {
      return;
    }
    $integrations = [];
    if ($s->enableUserRegistration && Edition::isAtLeast(Edition::Team)) { // Solo only has one user account
      $integrations[] = new UserRegistrationIntegration;
    }
    // Form and comment plugins: e.g. `contact-form` => integrations\ContactFormIntegration, once its class exists
    $list = SettingsHelper::getPluginIntegrations();
    foreach ($s->integrations as $handle) {
      $class = 'cloudgrayau\\oopspam\\integrations\\' . str_replace('-', '', ucwords((string) $handle, '-')) . 'Integration';
      if (!isset($list[$handle]) || !class_exists($class) || SettingsHelper::getIntegrationStatus($handle, $list[$handle]) !== 'available') {
        continue;
      }
      $integrations[] = new $class;
    }
    foreach ($integrations as $integration) {
      $integration->register();
    }
  }

  private function current(UiContext $context, string $key, mixed $saved): mixed {
    $old = old("settings.$key"); // input flashed by a save that failed validation
    if ($old !== null) {
      return $old;
    }
    return $context->values['settings'][$key] ?? $context->values[$key] ?? $saved;
  }

  /**
   * The spam score as Freeform-style radio cards: Craft's own Choice control (so it saves like any other
   * setting), drawn as cards by giving each option a placeholder thumbnail, then restyled so each whole
   * card is bordered, with a small icon, title and description inside and a radio in the corner.
   *
   * @return list<\CraftCms\Cms\Ui\Contracts\Node>
   */
  private function scoreCards(int $score, ?string $warning): array {
    $options = [];
    foreach (SettingsHelper::getScores() as $value => $label) {
      $options[] = [
        'value' => (string) $value,
        'label' => sprintf('%d – %s', $value, t($label, category: 'oopspam')),
        'labelHtml' => '<span class="oopspam-score-heading"><span class="oopspam-score-icon">' . SettingsHelper::scoreIcon($value) . '</span>'
          . '<span>' . $value . ' – ' . Html::encode(t($label, category: 'oopspam')) . '</span></span>'
          . '<span class="oopspam-score-description">' . Html::encode(t(SettingsHelper::getScoreDescriptions()[$value], category: 'oopspam')) . '</span>'
          . '<span class="oopspam-score-radio" aria-hidden="true"></span>'
          . ($value === 3 ? '<span class="oopspam-score-badge">' . Html::encode(t('Recommended', category: 'oopspam')) . '</span>' : ''),
        'thumbnail' => ['svg' => '<svg width="1" height="1" xmlns="http://www.w3.org/2000/svg"></svg>'],
      ];
    }
    $group = 'craft-radio-group:has(input[name$="[spamScore]"])';
    $card = "$group craft-radio[data-card]";
    $style = <<<CSS
      [data-ui-node="oopspam-score-style"] { display: none; }
      $group { --c-radio-card-min-size: 220px; --c-radio-card-max-size: 260px; max-inline-size: 800px; padding-block-start: 8px; }
      $card { box-sizing: border-box; display: block; align-self: stretch; margin: 0 10px 0 0; padding: 14px; text-align: start; color: var(--c-text-default); background: var(--c-surface-default); border: 1px solid var(--c-form-control-border-color); border-radius: 6px; }
      $group:is([data-wrapped]) craft-radio[data-card] { margin: 0; }
      $card::part(icon) { display: none; }
      $card:not([checked]):not([disabled]):hover { border-color: var(--c-color-accent-border-normal); }
      $card\[checked] { background: var(--c-color-accent-fill-quiet); border-color: var(--c-color-accent-border-normal); box-shadow: inset 0 0 0 1px var(--c-color-accent-border-normal); }
      $card\[focused-visible] { outline: 2px solid var(--c-color-focus-outline); outline-offset: 3px; }
      $card .oopspam-score-heading { display: flex; gap: 8px; align-items: center; min-height: 20px; padding-inline-end: 24px; font-size: 14px; font-weight: 600; line-height: 20px; color: var(--c-text-default); }
      $card .oopspam-score-icon { display: grid; flex: 0 0 20px; place-items: center; color: var(--c-text-quiet); }
      $card\[checked] .oopspam-score-icon { color: var(--c-color-accent-fill-loud); }
      $card .oopspam-score-icon svg { display: block; width: 20px; height: 20px; }
      $card .oopspam-score-description { display: block; margin-top: 8px; font-size: 12px; font-weight: normal; line-height: 18px; color: var(--c-text-quiet); }
      $card .oopspam-score-radio { position: absolute; inset-block-start: 16px; inset-inline-end: 14px; box-sizing: border-box; width: 16px; height: 16px; pointer-events: none; background: var(--c-input-fill, var(--c-surface-default)); border: 1px solid var(--c-form-control-border-color); border-radius: 50%; }
      $card\[checked] .oopspam-score-radio { background: radial-gradient(circle, var(--c-color-accent-fill-loud) 3px, var(--c-surface-default) 3.5px); border-color: var(--c-color-accent-fill-loud); }
      $card .oopspam-score-badge { position: absolute; inset-block-start: 0; inset-inline-end: 12px; transform: translateY(-50%); pointer-events: none; padding: 0 8px; font-size: 11px; font-weight: 600; line-height: 18px; color: var(--c-color-accent-fill-loud); background: var(--c-surface-default); border: 1px solid var(--c-color-accent-border-normal); border-radius: 9px; }
    CSS;
    return [
      TemplateContent::make('oopspam-score-style', '<style>' . str_replace('\\[', '[', $style) . '</style>')->trusted(),
      Field::make(t('Spam Score', category: 'oopspam'), Choice::make('spamScore')
        ->presentation(ChoicePresentation::Radios)
        ->options($options)
        ->value((string) $score))
        ->instructions(t('Block submissions at or above this spam score. 1 is the strictest and 6 the most lenient; generally a score of 3 and above can be considered spam.', category: 'oopspam'))
        ->warning($warning),
    ];
  }

  /** One integration's name cell: its icon, linked title and status badge. */
  private function integrationCell(string $key, array $integration, string $status): string {
    [$label, $tone] = match ($status) {
      'available' => [t('Available', category: 'oopspam'), 'success'],
      'coming-soon' => [t('Coming soon', category: 'oopspam'), 'neutral'],
      'edition' => [t('Requires Craft Team', category: 'oopspam'), 'neutral'],
      'not-installed' => [t('Not installed', category: 'oopspam'), 'neutral'],
      'disabled' => [t('Plugin disabled', category: 'oopspam'), 'neutral'],
      'unsupported' => [t('Unsupported version', category: 'oopspam'), 'warning'],
      'not-enabled' => [t('Not enabled under Integrations', category: 'oopspam'), 'neutral'],
    };
    $badge = in_array($tone, ['success', 'warning'], true)
      ? "color:var(--c-color-$tone-on-quiet);background:var(--c-color-$tone-fill-quiet);border-color:var(--c-color-$tone-border-quiet);"
      : 'color:var(--light-text-color);border-color:var(--hairline-color);';
    $dim = $status === 'available' ? '' : 'opacity:.55;';
    $icon = SettingsHelper::getIntegrationIcon($key, $integration);
    $img = $icon === '' ? '' : '<img src="' . $icon . '" alt="" width="28" height="28" style="display:block;flex:0 0 28px;width:28px;height:28px;object-fit:contain;' . ($dim ? 'filter:grayscale(1);' . $dim : '') . '">';
    $url = Html::encode($integration['url']);
    $link = '<small style="display:block;margin-top:2px;' . $dim . '"><a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a></small>';
    $note = isset($integration['note'])
      ? '<small style="display:block;color:var(--light-text-color);' . $dim . '">' . Html::encode(t($integration['note'], category: 'oopspam')) . '</small>'
      : '';
    return '<span style="display:flex;align-items:center;gap:12px;">' . $img
      . '<span style="min-width:0;"><span style="display:flex;align-items:center;flex-wrap:wrap;gap:4px 8px;">'
      . '<strong style="' . $dim . '">' . Html::encode($integration['title']) . '</strong>'
      . '<span style="display:inline-flex;align-items:center;border:1px solid;border-radius:4px;padding:0 6px;font-size:11px;line-height:1.6;white-space:nowrap;' . $badge . '">' . Html::encode($label) . '</span>'
      . '</span>' . $link . $note . '</span></span>';
  }

  /** Whether an integration is on: as toggled in its table (when the form refreshes), otherwise `$saved`. */
  private function integrationOn(UiContext $context, string $table, string $key, bool $saved): bool {
    $rows = $this->current($context, $table, null);
    if (is_array($rows) && is_array($rows[$key] ?? null) && array_key_exists('enabled', $rows[$key])) {
      return filter_var($rows[$key]['enabled'], FILTER_VALIDATE_BOOLEAN);
    }
    return $saved;
  }

  /**
   * A fixed table of integrations with a lightswitch each. Rows that can't be enabled are dimmed,
   * and their switches are off and disabled; SettingsRequest keeps their saved values.
   *
   * @param array<string, array{0: array, 1: string, 2: bool}> $rows key => [integration, status, on]
   */
  private function integrationTable(string $name, array $rows): Table {
    // Enabled, then available but off, then unavailable; each alphabetically. Rows stay put while toggling, until the page reloads.
    $tier = fn (array $row) => $row[1] !== 'available' ? 2 : ($row[2] ? 0 : 1);
    $sortKey = fn (string $key) => [$tier($rows[$key]), mb_strtolower($rows[$key][0]['title'])];
    uksort($rows, fn ($a, $b) => $sortKey($a) <=> $sortKey($b));
    $value = [];
    foreach ($rows as $key => [$integration, $status, $on]) {
      $available = $status === 'available';
      $minimum = isset($integration['version']) ? t($integration['version'], category: 'oopspam') : '—';
      $value[$key] = [
        'integration' => $this->integrationCell($key, $integration, $status),
        'minimum' => '<span style="white-space:nowrap;' . ($available ? '' : 'opacity:.55;') . '">' . Html::encode($minimum) . '</span>',
        'enabled' => $available && $on,
        'available' => $available ? '1' : '',
      ];
    }
    return Table::make($name)
      ->keyed()
      ->columns([
        'integration' => ['type' => 'html', 'heading' => t('Integration', category: 'oopspam')],
        'minimum' => ['type' => 'html', 'heading' => t('Minimum Version', category: 'oopspam')],
        'enabled' => ['type' => 'lightswitch', 'heading' => t('Enabled', category: 'oopspam'), 'class' => 'thin'],
        'available' => ['type' => 'hidden', 'toggle' => ['enabled']], // disables the switch when empty
      ])
      ->value($value);
  }

  public function boot(): void {
    $s = $this->getSettings();
    $this->wasLogging = $s->enableLogs;
    $this->wasLimiting = $s->enableLimiting;
    Event::listen(function (PluginSettingsSaved $event): void {
      if ($event->plugin->handle !== $this->handle) {
        return;
      }
      $new = $this->getSettings();
      if ($this->wasLogging !== $new->enableLogs) {
        $this->logs->clearLogs();
      }
      if ($this->wasLimiting !== $new->enableLimiting) {
        $this->submissions->clearSubmissions();
      }
    });
    $this->registerIntegrations($s);
  }

  public function getCpNavItem(): NavItem|array|null {
    $s = $this->getSettings();
    $item = parent::getCpNavItem()->label($s->pluginName ?: 'OOPSpam')->icon(null);
    $icon = $this->getBasePath() . '/icon-mask.svg';
    if (file_exists($icon)) {
      $item->iconSvg(file_get_contents($icon));
    }
    if ($s->apiKey) {
      if ($s->enableLogs) {
        $item->add(new NavItem()->id('logs')->label(t('Logs', category: 'oopspam'))->href(cp_url('oopspam/logs')));
      }
      $item->add(new NavItem()->id('reputation')->label(t('Domain Reputation', category: 'oopspam'))->href(cp_url('oopspam/reputation')));
      $item->add(new NavItem()->id('test')->label(t('Test Suite', category: 'oopspam'))->href(cp_url('oopspam/test')));
    }
    if (currentUser()?->isAdmin()) {
      $item->add(new NavItem()->id('settings')->label(t('Settings', category: 'oopspam'))->href(cp_url('settings/plugins/oopspam')));
    }
    return $item;
  }

  public function getSettingsRequestClass(): ?string {
    return SettingsRequest::class;
  }

  public function settingsUi(UiContext $context = new UiContext): ?Ui {
    $s = $this->getSettings();
    $w = fn (string $setting) => UtilityHelper::configWarning($this->handle, $setting);
    $flag = fn (string $key, bool $saved): bool => filter_var($this->current($context, $key, $saved), FILTER_VALIDATE_BOOLEAN);
    $opts = fn (array $list) => array_map(fn ($value, $label) => ['value' => (string) $value, 'label' => $label], array_keys($list), $list);
    $row = fn (string $name, string $label, string $instructions, string $heading, string $add) =>
      Field::make($label, Table::make($name)
        ->columns([0 => ['heading' => $heading, 'width' => '100%', 'control' => Text::make('0')->monospace()]])
        ->allowAdd()->allowDelete()->addRowLabel($add))
        ->instructions($instructions)
        ->warning($w($name));

    $score = max(1, min(6, $this->numeric($context, 'spamScore', $s->spamScore)));
    $logsOn = $flag('enableLogs', $s->enableLogs);
    $limitOn = $flag('enableLimiting', $s->enableLimiting);
    $contextOn = $flag('enableContextual', $s->enableContextual);
    $warnings = fn (string ...$settings): ?string => implode(' ', array_filter(array_map($w, $settings))) ?: null;

    // Integrations: Craft CMS ones are stored in their own boolean settings, plugins in the `integrations` list
    $saved = (array) $this->current($context, 'integrations', $s->integrations);
    $integrationRows = [];
    $enabledPlugins = [];
    foreach (SettingsHelper::getIntegrations() as $group => $list) {
      foreach ($list as $key => $obj) {
        $status = SettingsHelper::getIntegrationStatus($key, $obj);
        $on = $this->integrationOn($context, 'integrationTable', $key, isset($obj['setting'])
          ? $flag($obj['setting'], (bool) $s->{$obj['setting']})
          : in_array($key, $saved, true));
        $integrationRows[$key] = [$obj, $status, $on];
        if ($group !== 'craft' && $status === 'available' && $on) {
          $enabledPlugins[] = $key;
        }
      }
    }
    $saved = (array) $this->current($context, 'contextual', $s->contextual);
    $contextualRows = [];
    foreach (SettingsHelper::getPluginIntegrations() as $key => $obj) {
      $status = SettingsHelper::getIntegrationStatus($key, $obj);
      if ($status === 'available' && !in_array($key, $enabledPlugins, true)) {
        $status = 'not-enabled';
      }
      $contextualRows[$key] = [$obj, $status, $this->integrationOn($context, 'contextualTable', $key, in_array($key, $saved, true))];
    }

    $perPageOptions = [10, 25, 50, 100, 250];
    $perPage = $this->numeric($context, 'logsPerPage', $s->logsPerPage);
    if (!in_array($perPage, $perPageOptions, true)) { // a value saved by an older version stays selectable
      $perPageOptions[] = $perPage;
      sort($perPageOptions);
    }

    return Ui::make([
      Tab::make('general', t('General', category: 'oopspam'), [
        Field::make(t('API Key', category: 'oopspam'), Text::make('apiKey')->textExpanderTriggers(SelectOptions::getEnvTextExpanderTriggers()))
          ->instructions(t('The [OOPSpam](https://oopspam.com/?ref=cloudgray) service API key.', category: 'oopspam'))
          ->required()->warning($w('apiKey')),
        Field::make(t('API Service', category: 'oopspam'), Choice::make('apiService')->options($opts(SettingsHelper::getServices())))
          ->instructions(t('Select the service that you got the API key from.', category: 'oopspam'))
          ->required()->warning($w('apiService')),
        Field::make(t('Enable Logs', category: 'oopspam'), Lightswitch::make('enableLogs')->reactive())
          ->instructions(t('Store spam submission calls and responses in Craft CMS.', category: 'oopspam'))
          ->warning($w('enableLogs')),
        ...($logsOn ? [
          Field::make(t('Debug Mode', category: 'oopspam'), Lightswitch::make('debugMode'))
            ->instructions(t('Check and log submissions as normal, but never block them. Useful for tuning your settings before blocking spam.', category: 'oopspam'))
            ->warning($w('debugMode')),
          Field::make(t('Maximum Logs', category: 'oopspam'), Number::make('maxLogs')->min(1)->max(90)->value($this->numeric($context, 'maxLogs', $s->maxLogs)))
            ->instructions(t('Maximum amount of days to store logs.', category: 'oopspam'))->warning($w('maxLogs')),
          Field::make(t('Default Logs Per Page', category: 'oopspam'), Choice::make('logsPerPage')
          ->options(array_map(fn (int $n) => ['value' => (string) $n, 'label' => (string) $n], $perPageOptions))
          ->withoutPlaceholder()
          ->value((string) $perPage))
          ->instructions(t('How many logs to show per page by default. You can change this on the Logs page.', category: 'oopspam'))
          ->warning($w('logsPerPage')),
        ] : []),
        Field::make(t('Plugin Name', category: 'oopspam'), Text::make('pluginName'))
          ->instructions(t('Override the plugin name (optional).', category: 'oopspam'))->warning($w('pluginName')),
        UtilityHelper::footer('oopspam-footer-general'),
      ]),
      Tab::make('security', t('Security', category: 'oopspam'), [
        ...$this->scoreCards($score, $w('spamScore')),
        Field::make(t('Block Spam Content (AI)', category: 'oopspam'), Lightswitch::make('blockContentSpam'))
          ->instructions(t('Block content that is marked as spam by a machine learning algorithm.', category: 'oopspam'))->warning($w('blockContentSpam')),
        Field::make(t('Block Temporary Emails', category: 'oopspam'), Lightswitch::make('blockTempEmail'))
          ->instructions(t('Block temporary/disposable emails.', category: 'oopspam'))->warning($w('blockTempEmail')),
        Field::make(t('Block VPNs', category: 'oopspam'), Lightswitch::make('blockVPN'))
          ->instructions(t('Block VPN, Proxy, Tor IPs.', category: 'oopspam'))->warning($w('blockVPN')),
        Field::make(t('Block Data Center IPs', category: 'oopspam'), Lightswitch::make('blockDC'))
          ->instructions(t('Block Data Center IPs. Filters out submissions from cloud providers.', category: 'oopspam'))->warning($w('blockDC')),
        Field::make(t('Check Content Length', category: 'oopspam'), Lightswitch::make('checkForLength'))
          ->instructions(t('If the `content` parameter is shorter than 20 characters, it will be considered spam.', category: 'oopspam'))->warning($w('checkForLength')),
        Field::make(t('Log It', category: 'oopspam'), Lightswitch::make('logIt'))
          ->instructions(t('Allows you to view logs in the OOPSpam Dashboard.', category: 'oopspam'))->warning($w('logIt')),
        Field::make(t('URL Friendly', category: 'oopspam'), Lightswitch::make('urlFriendly'))
          ->instructions(t('Make the `content` parameter more link-friendly and reduce the impact of links on the spam score.', category: 'oopspam'))->warning($w('urlFriendly')),
        Field::make(t('Allowed Languages', category: 'oopspam'), Choice::make('allowedLanguages')->multiple()->presentation(ChoicePresentation::Select)->options($opts(SettingsHelper::getLanguages())))
          ->instructions(t('Allow content based on content language.', category: 'oopspam'))->warning($w('allowedLanguages')),
        Field::make(t('Allowed Countries', category: 'oopspam'), Choice::make('allowedCountries')->multiple()->presentation(ChoicePresentation::Select)->options($opts(SettingsHelper::getCountries())))
          ->instructions(t('Allow content only from certain countries.', category: 'oopspam'))->warning($w('allowedCountries')),
        Field::make(t('Blocked Countries', category: 'oopspam'), Choice::make('blockedCountries')->multiple()->presentation(ChoicePresentation::Select)->options($opts(SettingsHelper::getCountries())))
          ->instructions(t('Block content from certain countries.', category: 'oopspam'))->warning($w('blockedCountries')),
        UtilityHelper::footer('oopspam-footer-security'),
      ]),
      Tab::make('rates', t('Rate Limiting', category: 'oopspam'), [
        Field::make(t('Enable Rate Limiting', category: 'oopspam'), Lightswitch::make('enableLimiting')->reactive())
          ->instructions(t('Configure rate limits to control the number of allowed submissions per IP, effectively preventing spam and abuse.', category: 'oopspam'))
          ->warning($w('enableLimiting')),
        ...($limitOn ? [
          Field::make(t('Max Submissions', category: 'oopspam'), Number::make('maxSubmissions')->min(1)->value($this->numeric($context, 'maxSubmissions', $s->maxSubmissions)))
            ->instructions(t('Maximum submissions allowed per IP address per hour.', category: 'oopspam'))->warning($w('maxSubmissions')),
        ] : []),
        UtilityHelper::footer('oopspam-footer-rates'),
      ]),
      Tab::make('integrations', t('Integrations', category: 'oopspam'), [
        Field::make(t('Protected Integrations', category: 'oopspam'), $this->integrationTable('integrationTable', $integrationRows))
          ->instructions(t('Choose which parts of Craft CMS and which plugins are protected from spam. Custom forms require custom programming.', category: 'oopspam'))
          ->warning($warnings('enableUserRegistration', 'enableCommerce', 'integrations')),
        UtilityHelper::footer('oopspam-footer-integrations'),
      ]),
      Tab::make('contextual', t('Contextual (Experimental)', category: 'oopspam'), [
        Callout::make('oopspam-contextual-note', t('Use this feature **only** if your forms include a required text field. It relies on message content to function properly.', category: 'oopspam'))->variant('warning'),
        Field::make(t('Enable Contextual Detection', category: 'oopspam'), Lightswitch::make('enableContextual')->reactive())
          ->instructions(t('Improve spam accuracy by analysing form submissions based on your website’s purpose.', category: 'oopspam'))
          ->warning($w('enableContextual')),
        Group::make('contextual-fields', $contextOn ? [
          Field::make(t('Contextual Content', category: 'oopspam'), Textarea::make('contextualContent')->rows(5))
            ->instructions(t('Briefly describe your website’s purpose or business (2-3 sentences maximum).', category: 'oopspam'))
            ->warning($w('contextualContent')),
          Field::make(t('Contextual Integrations', category: 'oopspam'), $this->integrationTable('contextualTable', $contextualRows))
            ->instructions(t('Customise which integrations use contextual detection. An integration needs enabling under Integrations first.', category: 'oopspam'))
            ->warning($w('contextual')),
        ] : [])->dependsOn('enableContextual'),
        UtilityHelper::footer('oopspam-footer-contextual'),
      ]),
      Tab::make('manual', t('Manual', category: 'oopspam'), [
        $row('blockedEmails', t('Blocked Emails', category: 'oopspam'), t('Block specific email addresses.', category: 'oopspam'), t('Email addresses', category: 'oopspam'), t('Add an email', category: 'oopspam')),
        $row('blockedIPs', t('Blocked IPs', category: 'oopspam'), t('Block specific IP addresses.', category: 'oopspam'), t('IP addresses', category: 'oopspam'), t('Add an IP', category: 'oopspam')),
        $row('allowedEmails', t('Allowed Emails', category: 'oopspam'), t('Allow specific email addresses.', category: 'oopspam'), t('Email addresses', category: 'oopspam'), t('Add an email', category: 'oopspam')),
        $row('allowedIPs', t('Allowed IPs', category: 'oopspam'), t('Allow specific IP addresses.', category: 'oopspam'), t('IP addresses', category: 'oopspam'), t('Add an IP', category: 'oopspam')),
        UtilityHelper::footer('oopspam-footer-manual'),
      ]),
    ]);
  }

  public static function checkSpam(array $params, string $type = ''): bool {
    $plugin = self::getInstance();
    return $plugin->getSettings()->apiKey ? $plugin->antiSpam->checkSpam($params, $type) : true;
  }
  
  public static function testSpam(array $params, string $type = ''): array {
    return self::getInstance()->antiSpam->checkSpam($params, $type, true);
  }
  
  public static function overrideSettings(array $settings): void {
    $s = self::getInstance()->getSettings();
    foreach ($settings as $key => $value) {
      switch ($key) {
        case 'spamScore': case 'maxSubmissions':
          $s->$key = (int) $value; break;
        case 'debugMode': case 'enableLogs': case 'blockContentSpam': case 'blockTempEmail': case 'blockVPN': case 'blockDC':
        case 'checkForLength': case 'logIt': case 'urlFriendly': case 'enableLimiting': case 'enableContextual':
          $s->$key = (bool) $value; break;
        case 'allowedLanguages': case 'allowedCountries': case 'blockedCountries':
        case 'blockedEmails': case 'blockedIPs': case 'allowedEmails': case 'allowedIPs':
          $s->$key = (array) $value; break; // values() normalises row shapes on read
        case 'contextualContent':
          $s->$key = (string) $value; break;
      }
    }
  }
  
  public static function addContent(array &$content, mixed $value): void {
    $toString = static fn(mixed $v): string => (is_scalar($v) || $v instanceof \Stringable) ? trim((string)$v) : '';
    if (is_array($value)) {
      $value = implode(' ', array_filter(array_map($toString, $value), 'strlen'));
    } else {
      $value = $toString($value);
    }
    if ($value !== '') {
      $content[] = $value;
    }
  }

}