<?php
/**
 * OOPSpam config.php
 *
 * This file exists only as a template for the OOPSpam settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'oopspam.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
  'apiKey' => '',
  'apiService' => 'oopspam', /* oopspam|rapidapi*/
  'enableLogs' => true,
  'maxLogs' => 30, /* 1-90 */
  'pluginName' => '',
  'spamScore' => 3, /* 0-6 */
  'blockTempEmail' => false,
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
  'integrations' => [
    'formie',
    'freeform',
    'contact-form',
    'wheelform',
    'express-forms',
    'comments'
  ],
  'blockedEmails' => [
  ],
  'blockedIPs' => [
  ],
  'allowedEmails' => [
  ],
  'allowedIPs' => [
  ]
];
