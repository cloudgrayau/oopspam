<?php
namespace cloudgrayau\oopspam\models;

use cloudgrayau\oopspam\helpers\SettingsHelper;

use CraftCms\Cms\Plugin\PluginSettings;
use CraftCms\Cms\Validation\Rules\EnvValueRule;
use Illuminate\Validation\Rule;

class SettingsModel extends PluginSettings {

  /* GENERAL */
  public string $apiKey = '';
  public string $apiService = 'oopspam';
  public bool $enableLogs = true;
  public int $maxLogs = 30;
  public int $logsPerPage = 100;
  public string $pluginName = '';

  /* SECURITY */
  public int $spamScore = 3;
  public bool $blockContentSpam = true;
  public bool $blockTempEmail = true;
  public bool $blockVPN = false;
  public bool $blockDC = false;
  public bool $checkForLength = true;
  public bool $logIt = false;
  public bool $urlFriendly = false;
  public array $allowedLanguages = [];
  public array $allowedCountries = [];
  public array $blockedCountries = [];

  /* RATES */
  public bool $enableLimiting = false;
  public int $maxSubmissions = 5;

  /* INTEGRATIONS */
  public bool $enableUserRegistration = true;
  public bool $enableCommerce = true;
  public bool $enableSubscriptions = false;
  public array $integrations = ['formie', 'freeform', 'contact-form', 'wheelform', 'express-forms', 'comments'];

  /* CONTEXTUAL */
  public bool $enableContextual = false;
  public string $contextualContent = '';
  public array $contextual = ['formie', 'freeform', 'contact-form', 'wheelform', 'express-forms', 'comments'];

  /* MANUAL */
  public array $blockedEmails = [];
  public array $blockedIPs = [];
  public array $allowedEmails = [];
  public array $allowedIPs = [];

  /* OVERRIDES (config file only) */
  public array $forms = [];

  public function getRules(): array {
    $booleans = ['enableLogs', 'blockContentSpam', 'blockTempEmail', 'blockVPN', 'blockDC', 'checkForLength',
      'logIt', 'urlFriendly', 'enableLimiting', 'enableUserRegistration', 'enableCommerce', 'enableSubscriptions',
      'enableContextual'];
    $arrays = ['allowedLanguages', 'allowedCountries', 'blockedCountries', 'integrations', 'contextual',
      'blockedEmails', 'blockedIPs', 'allowedEmails', 'allowedIPs', 'forms'];
    $rules = [
      'apiKey' => new EnvValueRule(['required', 'string']),
      'apiService' => ['required', 'string', Rule::in(array_keys(SettingsHelper::getServices()))],
      'pluginName' => ['string'],
      'maxLogs' => ['integer', 'min:1', 'max:90'],
      'logsPerPage' => ['integer', 'min:10'],
      'maxSubmissions' => ['integer', 'min:1'],
      'spamScore' => ['integer', 'min:1', 'max:6'],
      'contextualContent' => ['string', Rule::requiredIf(fn () => $this->enableContextual)],
    ];
    foreach ($booleans as $property) {
      $rules[$property] = ['boolean'];
    }
    foreach ($arrays as $property) {
      $rules[$property] = ['array'];
    }
    return array_merge(parent::getRules(), $rules);
  }

  /** Manual-list rows normalised to a flat list of trimmed values, however they were set. */
  public function values(string $property): array {
    $values = [];
    foreach ((array) ($this->$property ?? []) as $row) {
      $row = (array) $row;
      $value = isset($row[0]) ? trim((string) $row[0]) : '';
      if ($value !== '') {
        $values[] = $value;
      }
    }
    return $values;
  }

}