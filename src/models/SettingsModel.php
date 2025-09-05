<?php
namespace cloudgrayau\oopspam\models;

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
  public bool $blockTempEmail = false;
  public bool $blockVPN = false;
  public bool $blockDC = false;
  public bool $checkForLength = true;
  public bool $logIt = false;
  public bool $urlFriendly = false;
  public array $allowedLanguages = [];
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
    return [
      [['apiKey','apiService'], 'required'],
      [['apiKey','apiService','pluginName'], 'string'],
      [['enableUserRegistration','enableContextual','blockTempEmail','blockVPN','blockDC','checkForLength','logIt','urlFriendly'], 'boolean'],
      [['allowedLanguages','allowedCountries','blockedCountries','integrations','contextual','blockedEmails','blockedIPs','allowedEmails','allowedIPs'], ArrayValidator::class],
      ['maxLogs', 'integer', 'min' => 1, 'max' => 90],
      ['spamScore', 'integer', 'min' => 1, 'max' => 6],
      ['contextualContent', 'validateContextual']
    ];
  }
  
  public function validateContextual($attribute): bool {
    $value = trim($this->$attribute);
    if ($this->enableContextual){
      if (empty($value)){
        $this->addError($attribute, 'Contextual content is required for contextual analysis');
        return false;
      }
    }
    return true;
  }
  
}