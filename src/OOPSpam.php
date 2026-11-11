<?php
namespace cloudgrayau\oopspam;

use cloudgrayau\oopspam\helpers\SettingsHelper;
use cloudgrayau\oopspam\models\SettingsModel;
use cloudgrayau\oopspam\requests\SettingsRequest;
use cloudgrayau\oopspam\services\{AntiSpamService, LogsService, SubmissionService};
use cloudgrayau\utils\UtilityHelper;

use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\SelectOptions;
use CraftCms\Cms\Ui\{Ui, UiContext};
use CraftCms\Cms\Ui\Controls\{Choice, Lightswitch, Number, Range, Table, Text, Textarea};
use CraftCms\Cms\Ui\Enums\ChoicePresentation;
use CraftCms\Cms\Ui\Nodes\{Callout, Field, Group, MarkdownContent, Tab};
use CraftCms\Cms\Plugin\{Plugin, PluginSettings, Plugins};

use function CraftCms\Cms\{cp_url, currentUser, t};

class OOPSpam extends Plugin {

  public string $schemaVersion = '2.0.0';
  public bool $hasCpSettings = true;
  public bool $hasCpSection = true;
  
  public AntiSpamService $antiSpam { get => $this->antiSpam ??= new AntiSpamService; }
  public LogsService $logs { get => $this->logs ??= new LogsService; }
  public SubmissionService $submissions { get => $this->submissions ??= new SubmissionService; }

  public function boot(): void {
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

  #[\Override]
  protected static function createSettings(): PluginSettings {
    return new SettingsModel();
  }
  
  public function getSettingsRequestClass(): ?string {
    return SettingsRequest::class;
  }

  public function settingsUi(UiContext $context = new UiContext): ?Ui {
    $s = $this->getSettings();
    $w = fn (string $setting) => UtilityHelper::configWarning($this->handle, $setting);
    $opts = fn (array $list) => array_map(fn ($value, $label) => ['value' => (string) $value, 'label' => $label], array_keys($list), $list);
    $row = fn (string $name, string $label, string $instructions, string $heading, string $add) =>
      Field::make($label, Table::make($name)
        ->columns([0 => ['heading' => $heading, 'width' => '100%', 'control' => Text::make('0')->monospace()]])
        ->allowAdd()->allowDelete()->addRowLabel($add))
        ->instructions($instructions)
        ->warning($w($name));

    $score = max(1, min(6, (int) $this->current($context, 'spamScore', $s->spamScore)));
    $logsOn = (bool) $this->current($context, 'enableLogs', $s->enableLogs);
    $limitOn = (bool) $this->current($context, 'enableLimiting', $s->enableLimiting);
    $contextOn = (bool) $this->current($context, 'enableContextual', $s->enableContextual);
    $enabled = (array) $this->current($context, 'integrations', $s->integrations);

    // Form integrations that are both supported and installed
    $forms = [];
    foreach (SettingsHelper::getIntegrations()['forms'] as $key => $obj) {
      if ($this->pluginEnabled($key)) {
        $forms[$key] = $obj;
      }
    }
    $integrationOptions = fn (array $list) => array_values(array_map(fn ($key, $obj) => [
      'value' => $key,
      'label' => $obj['title'],
      'labelHtml' => '<strong>' . $obj['title'] . '</strong> <small>' . ($obj['information'] ?? '') . '</small><br><small>' . $obj['url'] . '</small>',
    ], array_keys($list), $list));

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
        Group::make('logs-fields', $logsOn ? [
          Field::make(t('Maximum Logs', category: 'oopspam'), Number::make('maxLogs')->min(1)->max(90))
            ->instructions(t('Maximum amount of days to store logs.', category: 'oopspam'))->warning($w('maxLogs')),
          Field::make(t('Logs Per Page', category: 'oopspam'), Number::make('logsPerPage')->min(10))
            ->instructions(t('Maximum logs to display per page.', category: 'oopspam'))->warning($w('logsPerPage')),
        ] : [])->dependsOn('enableLogs'),
        Field::make(t('Plugin Name', category: 'oopspam'), Text::make('pluginName'))
          ->instructions(t('Override the plugin name (optional).', category: 'oopspam'))->warning($w('pluginName')),
        UtilityHelper::footer('oopspam-footer-general'),
      ]),
      Tab::make('security', t('Security', category: 'oopspam'), [
        Field::make(t('Spam Score', category: 'oopspam'), Range::make('spamScore')->min(1)->max(6)->step(1)->reactive())
          ->instructions(t('Block the overall spam score based on the parameters. The higher the score, the more likely it is to be spam. 1 is the strictest and 6 the most lenient; generally a score of 3 and above can be considered spam.', category: 'oopspam'))
          ->warning($w('spamScore')),
        Callout::make('oopspam-score-label', sprintf('**%d** – %s', $score, SettingsHelper::getScores()[$score]))
          ->variant('info'),
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
        Group::make('rate-fields', $limitOn ? [
          Field::make(t('Max Submissions', category: 'oopspam'), Number::make('maxSubmissions')->min(1))
            ->instructions(t('Maximum submissions allowed per IP address per hour.', category: 'oopspam'))->warning($w('maxSubmissions')),
        ] : [])->dependsOn('enableLimiting'),
        UtilityHelper::footer('oopspam-footer-rates'),
      ]),
      Tab::make('integrations', t('Integrations', category: 'oopspam'), array_values(array_filter([
        MarkdownContent::make('oopspam-craft-heading', '## Craft CMS')->displayInPane(false),
        Field::make(t('Protect User Registrations', category: 'oopspam'), Lightswitch::make('enableUserRegistration'))
          ->instructions(t('Protect user registrations from spam.', category: 'oopspam'))->warning($w('enableUserRegistration')),
        $this->pluginEnabled('commerce') ? Field::make(t('Protect Commerce', category: 'oopspam'), Lightswitch::make('enableCommerce'))
          ->instructions(t('Protect commerce orders from spam.', category: 'oopspam'))->warning($w('enableCommerce')) : null,
        $this->pluginEnabled('commerce') ? Field::make(t('Protect Subscriptions', category: 'oopspam'), Lightswitch::make('enableSubscriptions'))
          ->instructions(t('Protect commerce subscriptions from spam.', category: 'oopspam'))->warning($w('enableSubscriptions')) : null,
        MarkdownContent::make('oopspam-forms-heading', '## Forms')->displayInPane(false),
        $forms
          ? Field::make(t('Protected form plugins', category: 'oopspam'), Choice::make('integrations')->multiple()->options($integrationOptions($forms)))
              ->instructions(t('The current form plugins/systems are protected. Custom forms require custom programming.', category: 'oopspam'))
              ->warning($w('integrations'))
          : Callout::make('oopspam-no-forms', t('No supported form plugins are installed. Custom forms require custom programming.', category: 'oopspam')),
        UtilityHelper::footer('oopspam-footer-integrations'),
      ]))),
      Tab::make('contextual', t('Contextual (Experimental)', category: 'oopspam'), [
        Callout::make('oopspam-contextual-note', t('Use this feature **only** if your forms include a required text field. It relies on message content to function properly.', category: 'oopspam'))->variant('warning'),
        Field::make(t('Enable Contextual Detection', category: 'oopspam'), Lightswitch::make('enableContextual')->reactive())
          ->instructions(t('Improve spam accuracy by analysing form submissions based on your website’s purpose.', category: 'oopspam'))
          ->warning($w('enableContextual')),
        Group::make('contextual-fields', $contextOn ? array_values(array_filter([
          Field::make(t('Contextual Content', category: 'oopspam'), Textarea::make('contextualContent')->rows(5))
            ->instructions(t('Briefly describe your website’s purpose or business (2-3 sentences maximum).', category: 'oopspam'))
            ->warning($w('contextualContent')),
          ($list = array_intersect_key($forms, array_flip($enabled)))
            ? Field::make(t('Contextual Integrations', category: 'oopspam'), Choice::make('contextual')->multiple()->options($integrationOptions($list)))
                ->instructions(t('Customise which enabled integrations contextual detection is enabled for.', category: 'oopspam'))
                ->warning($w('contextual'))
            : null,
        ])) : [])->dependsOn('enableContextual'),
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

  private function pluginEnabled(string $handle): bool {
    return app(Plugins::class)->isPluginEnabled($handle);
  }

  private function current(UiContext $context, string $key, mixed $saved): mixed {
    return $context->values['settings'][$key] ?? $context->values[$key] ?? $saved;
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
        case 'enableLogs': case 'blockContentSpam': case 'blockTempEmail': case 'blockVPN': case 'blockDC':
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