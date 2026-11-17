<?php
namespace cloudgrayau\oopspam\helpers;

use CraftCms\Cms\Edition;
use CraftCms\Cms\Plugin\Plugins;

class SettingsHelper {
  
  private static array $countryList = [
    'af' => 'Afghanistan',
    'ax' => 'Aland Islands',
    'al' => 'Albania',
    'dz' => 'Algeria',
    'as' => 'American Samoa',
    'ad' => 'Andorra',
    'ao' => 'Angola',
    'ai' => 'Anguilla',
    'aq' => 'Antarctica',
    'ag' => 'Antigua and Barbuda',
    'ar' => 'Argentina',
    'am' => 'Armenia',
    'aw' => 'Aruba',
    'au' => 'Australia',
    'at' => 'Austria',
    'az' => 'Azerbaijan',
    'bs' => 'Bahamas',
    'bh' => 'Bahrain',
    'bd' => 'Bangladesh',
    'bb' => 'Barbados',
    'by' => 'Belarus',
    'be' => 'Belgium',
    'bz' => 'Belize',
    'bj' => 'Benin',
    'bm' => 'Bermuda',
    'bt' => 'Bhutan',
    'bo' => 'Bolivia',
    'bq' => 'Bonaire, Saint Eustatius and Saba',
    'ba' => 'Bosnia and Herzegovina',
    'bw' => 'Botswana',
    'bv' => 'Bouvet Island',
    'br' => 'Brazil',
    'io' => 'British Indian Ocean Territory',
    'vg' => 'British Virgin Islands',
    'bn' => 'Brunei',
    'bg' => 'Bulgaria',
    'bf' => 'Burkina Faso',
    'bi' => 'Burundi',
    'kh' => 'Cambodia',
    'cm' => 'Cameroon',
    'ca' => 'Canada',
    'cv' => 'Cape Verde',
    'ky' => 'Cayman Islands',
    'cf' => 'Central African Republic',
    'td' => 'Chad',
    'cl' => 'Chile',
    'cn' => 'China',
    'cx' => 'Christmas Island',
    'cc' => 'Cocos Islands',
    'co' => 'Colombia',
    'km' => 'Comoros',
    'ck' => 'Cook Islands',
    'cr' => 'Costa Rica',
    'hr' => 'Croatia',
    'cu' => 'Cuba',
    'cw' => 'Curacao',
    'cy' => 'Cyprus',
    'cz' => 'Czech Republic',
    'cd' => 'Democratic Republic of the Congo',
    'dk' => 'Denmark',
    'dj' => 'Djibouti',
    'dm' => 'Dominica',
    'do' => 'Dominican Republic',
    'tl' => 'East Timor',
    'ec' => 'Ecuador',
    'eg' => 'Egypt',
    'sv' => 'El Salvador',
    'gq' => 'Equatorial Guinea',
    'er' => 'Eritrea',
    'ee' => 'Estonia',
    'et' => 'Ethiopia',
    'fk' => 'Falkland Islands',
    'fo' => 'Faroe Islands',
    'fj' => 'Fiji',
    'fi' => 'Finland',
    'fr' => 'France',
    'gf' => 'French Guiana',
    'pf' => 'French Polynesia',
    'tf' => 'French Southern Territories',
    'ga' => 'Gabon',
    'gm' => 'Gambia',
    'ge' => 'Georgia',
    'de' => 'Germany',
    'gh' => 'Ghana',
    'gi' => 'Gibraltar',
    'gr' => 'Greece',
    'gl' => 'Greenland',
    'gd' => 'Grenada',
    'gp' => 'Guadeloupe',
    'gu' => 'Guam',
    'gt' => 'Guatemala',
    'gg' => 'Guernsey',
    'gn' => 'Guinea',
    'gw' => 'Guinea-Bissau',
    'gy' => 'Guyana',
    'ht' => 'Haiti',
    'hm' => 'Heard Island and McDonald Islands',
    'hn' => 'Honduras',
    'hk' => 'Hong Kong',
    'hu' => 'Hungary',
    'is' => 'Iceland',
    'in' => 'India',
    'id' => 'Indonesia',
    'ir' => 'Iran',
    'iq' => 'Iraq',
    'ie' => 'Ireland',
    'im' => 'Isle of Man',
    'il' => 'Israel',
    'it' => 'Italy',
    'ci' => 'Ivory Coast',
    'jm' => 'Jamaica',
    'jp' => 'Japan',
    'je' => 'Jersey',
    'jo' => 'Jordan',
    'kz' => 'Kazakhstan',
    'ke' => 'Kenya',
    'ki' => 'Kiribati',
    'xk' => 'Kosovo',
    'kw' => 'Kuwait',
    'kg' => 'Kyrgyzstan',
    'la' => 'Laos',
    'lv' => 'Latvia',
    'lb' => 'Lebanon',
    'ls' => 'Lesotho',
    'lr' => 'Liberia',
    'ly' => 'Libya',
    'li' => 'Liechtenstein',
    'lt' => 'Lithuania',
    'lu' => 'Luxembourg',
    'mo' => 'Macao',
    'mk' => 'Macedonia',
    'mg' => 'Madagascar',
    'mw' => 'Malawi',
    'my' => 'Malaysia',
    'mv' => 'Maldives',
    'ml' => 'Mali',
    'mt' => 'Malta',
    'mh' => 'Marshall Islands',
    'mq' => 'Martinique',
    'mr' => 'Mauritania',
    'mu' => 'Mauritius',
    'yt' => 'Mayotte',
    'mx' => 'Mexico',
    'fm' => 'Micronesia',
    'md' => 'Moldova',
    'mc' => 'Monaco',
    'mn' => 'Mongolia',
    'me' => 'Montenegro',
    'ms' => 'Montserrat',
    'ma' => 'Morocco',
    'mz' => 'Mozambique',
    'mm' => 'Myanmar',
    'na' => 'Namibia',
    'nr' => 'Nauru',
    'np' => 'Nepal',
    'nl' => 'Netherlands',
    'nc' => 'New Caledonia',
    'nz' => 'New Zealand',
    'ni' => 'Nicaragua',
    'ne' => 'Niger',
    'ng' => 'Nigeria',
    'nu' => 'Niue',
    'nf' => 'Norfolk Island',
    'kp' => 'North Korea',
    'mp' => 'Northern Mariana Islands',
    'no' => 'Norway',
    'om' => 'Oman',
    'pk' => 'Pakistan',
    'pw' => 'Palau',
    'ps' => 'Palestinian Territory',
    'pa' => 'Panama',
    'pg' => 'Papua New Guinea',
    'py' => 'Paraguay',
    'pe' => 'Peru',
    'ph' => 'Philippines',
    'pn' => 'Pitcairn',
    'pl' => 'Poland',
    'pt' => 'Portugal',
    'pr' => 'Puerto Rico',
    'qa' => 'Qatar',
    'cg' => 'Republic of the Congo',
    're' => 'Reunion',
    'ro' => 'Romania',
    'ru' => 'Russia',
    'rw' => 'Rwanda',
    'bl' => 'Saint Barthelemy',
    'sh' => 'Saint Helena',
    'kn' => 'Saint Kitts and Nevis',
    'lc' => 'Saint Lucia',
    'mf' => 'Saint Martin',
    'pm' => 'Saint Pierre and Miquelon',
    'vc' => 'Saint Vincent and the Grenadines',
    'ws' => 'Samoa',
    'sm' => 'San Marino',
    'st' => 'Sao Tome and Principe',
    'sa' => 'Saudi Arabia',
    'sn' => 'Senegal',
    'rs' => 'Serbia',
    'sc' => 'Seychelles',
    'sl' => 'Sierra Leone',
    'sg' => 'Singapore',
    'sx' => 'Sint Maarten',
    'sk' => 'Slovakia',
    'si' => 'Slovenia',
    'sb' => 'Solomon Islands',
    'so' => 'Somalia',
    'za' => 'South Africa',
    'gs' => 'South Georgia and the South Sandwich Islands',
    'kr' => 'South Korea',
    'ss' => 'South Sudan',
    'es' => 'Spain',
    'lk' => 'Sri Lanka',
    'sd' => 'Sudan',
    'sr' => 'Suriname',
    'sj' => 'Svalbard and Jan Mayen',
    'sz' => 'Swaziland',
    'se' => 'Sweden',
    'ch' => 'Switzerland',
    'sy' => 'Syria',
    'tw' => 'Taiwan',
    'tj' => 'Tajikistan',
    'tz' => 'Tanzania',
    'th' => 'Thailand',
    'tg' => 'Togo',
    'tk' => 'Tokelau',
    'to' => 'Tonga',
    'tt' => 'Trinidad and Tobago',
    'tn' => 'Tunisia',
    'tr' => 'Turkey',
    'tm' => 'Turkmenistan',
    'tc' => 'Turks and Caicos Islands',
    'tv' => 'Tuvalu',
    'vi' => 'U.S. Virgin Islands',
    'ug' => 'Uganda',
    'ua' => 'Ukraine',
    'ae' => 'United Arab Emirates',
    'gb' => 'United Kingdom',
    'us' => 'United States',
    'um' => 'United States Minor Outlying Islands',
    'uy' => 'Uruguay',
    'uz' => 'Uzbekistan',
    'vu' => 'Vanuatu',
    'va' => 'Vatican',
    've' => 'Venezuela',
    'vn' => 'Vietnam',
    'wf' => 'Wallis and Futuna',
    'eh' => 'Western Sahara',
    'ye' => 'Yemen',
    'zm' => 'Zambia',
    'zw' => 'Zimbabwe',
  ];
  
  private static $languageList = [
    'af' => 'Afrikaans',
    'sq' => 'Albanian',
    'ar' => 'Arabic',
    'eu' => 'Basque',
    'be' => 'Belarusian',
    'bn' => 'Bengali',
    'nb' => 'Bokmal',
    'bg' => 'Bulgarian',
    'ca' => 'Catalan',
    'zh' => 'Chinese',
    'hr' => 'Croatian',
    'cs' => 'Czech',
    'da' => 'Danish',
    'nl' => 'Dutch',
    'en' => 'English',
    'et' => 'Estonian',
    'fi' => 'Finnish',
    'fr' => 'French',
    'de' => 'German',
    'el' => 'Greek',
    'gu' => 'Gujarati',
    'he' => 'Hebrew',
    'hi' => 'Hindi',
    'hu' => 'Hungarian',
    'is' => 'Icelandic',
    'id' => 'Indonesian',
    'ga' => 'Irish',
    'it' => 'Italian',
    'ja' => 'Japanese',
    'ko' => 'Korean',
    'la' => 'Latin',
    'lv' => 'Latvian',
    'lt' => 'Lithuanian',
    'ms' => 'Malay',
    'no' => 'Norwegian',
    'nn' => 'Nynorsk',
    'fa' => 'Persian',
    'pl' => 'Polish',
    'pt' => 'Portuguese',
    'pa' => 'Punjabi',
    'ro' => 'Romanian',
    'ru' => 'Russian',
    'sk' => 'Slovak',
    'sl' => 'Slovene',
    'so' => 'Somali',
    'es' => 'Spanish',
    'sv' => 'Swedish',
    'tl' => 'Tagalog',
    'ta' => 'Tamil',
    'te' => 'Telugu',
    'th' => 'Thai',
    'tr' => 'Turkish',
    'ur' => 'Urdu',
    'vi' => 'Vietnamese',
    'cy' => 'Welsh'
  ];
  
  private static $serviceList = [
    'oopspam' => 'OOPSpam',
    'rapidapi' => 'RapidAPI' 
  ];
  
  /**
   * Every integration, grouped as the settings page shows them. Keys:
   * - `title`: the name; `url`: the link shown under it; `icon`: an SVG in the root `resources/integrations` folder (defaults to the key)
   * - `version`: the minimum shown in the settings; `minimum`: the version actually enforced
   * - `plugin`: the plugin it needs (defaults to the key for `forms` and `comments`; none for Craft itself)
   * - `setting`: a boolean setting it's stored in, instead of the `integrations` list
   * - `edition`: the Craft edition it needs; `note`: a short line under the title
   * - `comingSoon`: not ported to Craft 6 yet, so it's shown dimmed and can't be enabled
   */
  private static $integrationList = [
    'craft' => [
      'user-registration' => [
        'title' => 'User Registration',
        'url' => 'https://craftcms.com/',
        'icon' => 'craft',
        'version' => 'Team edition',
        'edition' => 'team',
        'setting' => 'enableUserRegistration',
      ],
      'commerce' => [
        'title' => 'Commerce Orders',
        'url' => 'https://plugins.craftcms.com/commerce',
        'plugin' => 'commerce',
        'setting' => 'enableCommerce',
        'comingSoon' => true,
      ],
    ],
    'forms' => [
      'contact-form' => [
        'title' => 'Contact Form',
        'url' => 'https://plugins.craftcms.com/contact-form',
        'version' => '4.0.0',
        'minimum' => '4.0.0',
      ],
      'easy-form' => [
        'title' => 'Easy Form',
        'url' => 'https://plugins.craftcms.com/easy-form',
        'comingSoon' => true,
      ],
      'formable' => [
        'title' => 'Formable',
        'url' => 'https://plugins.craftcms.com/formable',
        'comingSoon' => true,
      ],
      'formie' => [
        'title' => 'Formie',
        'url' => 'https://plugins.craftcms.com/formie',
        'comingSoon' => true,
      ],
      'freeform' => [
        'title' => 'Freeform',
        'url' => 'https://plugins.craftcms.com/freeform',
        'version' => '6.0.0',
        'minimum' => '6.0.0-beta',
      ],
      'wheelform' => [
        'title' => 'Wheel Form',
        'url' => 'https://plugins.craftcms.com/wheelform',
        'comingSoon' => true,
      ],
    ],
    'comments' => [
      'comments' => [
        'title' => 'Comments',
        'url' => 'https://plugins.craftcms.com/comments',
        'comingSoon' => true,
      ],
    ],
  ];

  private static array $iconUris = [];
  
  // Public Methods
  // =========================================================================
  
  public static function getCountries(): array {
    return self::$countryList;
  }
  
  public static function getLanguages(): array {
    return self::$languageList;
  }
  
  public static function getServices(): array {
    return self::$serviceList;
  }
  
  public static function getIntegrations(): array {
    return self::$integrationList;
  }

  /** Form and comment integrations (the ones stored in the `integrations` list), keyed by plugin handle. */
  public static function getPluginIntegrations(): array {
    return self::$integrationList['forms'] + self::$integrationList['comments'];
  }

  /**
   * `available`, or why the integration can't be enabled: `coming-soon`, `edition`, `not-installed`,
   * `disabled` (the plugin is installed but disabled) or `unsupported` (below the minimum version).
   */
  public static function getIntegrationStatus(string $key, array $integration): string {
    if (!empty($integration['comingSoon'])) {
      return 'coming-soon';
    }
    if (($integration['edition'] ?? null) === 'team' && !Edition::isAtLeast(Edition::Team)) {
      return 'edition';
    }
    $handle = $integration['plugin'] ?? (isset($integration['setting']) ? null : $key);
    if ($handle === null) {
      return 'available';
    }
    $plugins = app(Plugins::class);
    if (!$plugins->isPluginInstalled($handle)) {
      return 'not-installed';
    }
    if (!$plugins->isPluginEnabled($handle)) {
      return 'disabled';
    }
    $minimum = $integration['minimum'] ?? null;
    if ($minimum !== null && version_compare((string) $plugins->getPlugin($handle)?->version, $minimum, '<')) {
      return 'unsupported';
    }
    return 'available';
  }

  /** The integration's icon as a data URI, so it doesn't need publishing as an asset. */
  public static function getIntegrationIcon(string $key, array $integration): string {
    $name = $integration['icon'] ?? $key;
    if (!isset(self::$iconUris[$name])) {
      $path = dirname(__DIR__, 2) . '/resources/integrations/' . basename($name) . '.svg';
      self::$iconUris[$name] = is_file($path) ? 'data:image/svg+xml;base64,' . base64_encode((string) file_get_contents($path)) : '';
    }
    return self::$iconUris[$name];
  }
  
  public static function mapSettings(array $n): string {
    return (isset($n[0])) ? $n[0] : '';
  }
  
  public static function getScores(): array {
    return [
      1 => 'Extremely strict',
      2 => 'Very strict',
      3 => 'Moderate (recommended)',
      4 => 'Slightly lenient',
      5 => 'Lenient',
      6 => 'Very lenient',
    ];
  }
  
}