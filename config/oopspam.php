<?php
/**
 * OOPSpam config
 *
 * Publish this file to `config/craft/oopspam.php` and change the settings there;
 * values set here override the ones saved in the control panel.
 */

return [
  'apiKey' => '',
  'apiService' => 'oopspam', /* oopspam|rapidapi*/
  'enableLogs' => true,
  'debugMode' => false,
  'maxLogs' => 30, /* 1-90 */
  'logsPerPage' => 100,
  'pluginName' => '',
  'spamScore' => 3, /* 1-6 */
  'blockContentSpam' => true,
  'blockTempEmail' => true,
  'blockVPN' => false,
  'blockDC' => false,
  'checkForLength' => true,
  'logIt' => false,
  'urlFriendly' => false,
  'allowedLanguages' => [ /* Two-letter language codes */
  ],
  'allowedCountries' => [ /* Two-letter country codes */
  ],
  'blockedCountries' => [ /* Two-letter country codes */
  ],
  'enableUserRegistration' => true,
  'enableCommerce' => true,
  'enableLimiting' => false,
  'maxSubmissions' => 3,
  'integrations' => [
    'contact-form',
    'easy-form',
    'formable',
    'formie',
    'freeform',
    'wheelform',
    'comments'
  ],
  'enableContextual' => false,
  'contextualContent' => '',
  'contextual' => [
    'contact-form',
    'easy-form',
    'formable',
    'formie',
    'freeform',
    'wheelform',
    'comments'
  ],
  'blockedEmails' => [
  ],
  'blockedIPs' => [
  ],
  'allowedEmails' => [
  ],
  'allowedIPs' => [
  ],
  'forms' => [
    'form-handle' => [
      'disabled' => false,
      'fields' => [
        'fromName',
        'message'
      ],
      'spamScore' => 3,
      // extra settings
    ]
  ]
];
