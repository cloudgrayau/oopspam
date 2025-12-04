<?php
namespace cloudgrayau\oopspam\models;

use Craft;
use craft\base\Model;
use craft\validators\ArrayValidator;

class SettingsModel extends Model {
  
  // Editable Variables
  // =========================================================================
  
  /* GENERAL */
  public string $apiKey = '';
  public string $apiService = 'oopspam';
  public bool $enableLogs = true;
  public int $maxLogs = 30;
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
  public int $maxSubmissions = 3;
  
  /* INTEGRATIONS */
  public bool $enableUserRegistration = true;
  public bool $enableCommerce = true;
  public array $integrations = [
    'formie',
    'freeform',
    'contact-form',
    'wheelform',
    'express-forms',
    'comments'
  ];
  
  /* CONTEXTUAL */
  public bool $enableContextual = false;
  public string $contextualContent = '';
  public array $contextual = [
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
  public array $allowedIPs = [];
  
  // Public Methods
  // =========================================================================

  public function rules(): array {    
    if (!Craft::$app->getRequest()->getBodyParam('settings[integrations]')){
      $this->integrations = [''];
    }
    if (!Craft::$app->getRequest()->getBodyParam('settings[contextual]')){
      $this->contextual  = [''];
    }
    $rules = [
      [['apiKey','apiService'], 'required'],
      [['apiKey','apiService','contextualContent','pluginName'], 'string'],
      [['enableUserRegistration','enableCommerce','enableContextual','blockTempEmail','blockVPN','blockDC','checkForLength','logIt','urlFriendly','enableLimiting'], 'boolean'],
      [['allowedLanguages','allowedCountries','blockedCountries','integrations','contextual','blockedEmails','blockedIPs','allowedEmails','allowedIPs'], ArrayValidator::class],
      ['maxLogs', 'integer', 'min' => 1, 'max' => 90],
      ['maxSubmissions', 'integer', 'min' => 1],
      ['spamScore', 'integer', 'min' => 1, 'max' => 6]
    ];
    if ($this->enableContextual){
      $rules[] = [['contextualContent'], 'required'];
    }
    return $rules;
  }
  
}