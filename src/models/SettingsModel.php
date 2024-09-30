<?php
namespace cloudgrayau\oopspam\models;

use craft\base\Model;
use craft\validators\ArrayValidator;

class SettingsModel extends Model {
  
  // Editable Variables
  // =========================================================================
  
  /* READ ONLY */
  public array $apiUsage = [
    'limit' => 0,
    'remaining' => 0
  ];
  
  /* GENERAL */
  public string $apiKey = '';
  public string $apiService = 'oopspam';
  public bool $enableLogs = true;
  public int $maxLogs = 30;
  public string $pluginName = '';
  
  /* SECURITY */
  public int $spamScore = 3;
  public bool $blockTempEmail = false;
  public bool $checkForLength = true;
  public bool $logIt = false;
  public bool $urlFriendly = false;
  public array $allowedLanguages = [
    'en'
  ];
  public array $allowedCountries = [];
  public array $blockedCountries = [];
  
  /* INTEGRATIONS */
  public bool $enableUserRegistration = true;
  public array $integrations = [
    'formie',
    'freeform',
    'contact-form',
    'wheelform',
    'express-forms',
    'comments'
  ];
  
  /* MANUAL */
  public array $blockedEmails = [];
  public array $blockedIPs = [];
  public array $allowedEmails = [];
  public array $allowedIPs = [
    ['127.0.0.1']
  ];
  
  // Public Methods
  // =========================================================================

  public function rules(): array {
    return [
      [['apiKey','apiService'], 'required'],
      [['apiKey','apiService','pluginName'], 'string'],
      [['enableUserRegistration','blockTempEmail','checkForLength','logIt','urlFriendly'], 'boolean'],
      [['allowedLanguages','allowedCountries','blockedCountries','integrations','blockedEmails','blockedIPs','allowedEmails','allowedIPs'], ArrayValidator::class],
      ['maxLogs', 'integer', 'min' => 1, 'max' => 90],
      ['spamScore', 'integer', 'min' => 1, 'max' => 6],
    ];
  }
  
}