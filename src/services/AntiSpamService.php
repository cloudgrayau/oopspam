<?php
namespace cloudgrayau\oopspam\services;

use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\helpers\SettingsHelper;

use Craft;
use craft\base\Component;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class AntiSpamService extends Component {
  
  public string $apiVersion = '/v1';
  
  private string $baseUrl = 'https://api.oopspam.com';
  private string $endpoint = '/spamdetection';
  
  // Public Methods
  // =========================================================================
  
  public function initRegistration(): void {
    $className = '\cloudgrayau\oopspam\integrations\UserRegistrationIntegration';
    $obj = new $className();
    $obj->parse();
  }
  
  public function initIntegrations(): void {
    $integrations = SettingsHelper::getIntegrations();
    foreach($integrations as $type => $object){
      $objects = array_keys($object);
      foreach($objects as $integration){
        if (in_array($integration, OOPSpam::$plugin->settings->integrations)){
          if (Craft::$app->plugins->isPluginEnabled($integration)){
            $classes = explode('-',$integration);
            $class = implode('', array_map(function($n){
              return ucfirst($n);
            }, $classes));
            $className = '\cloudgrayau\oopspam\integrations\\'.$class.'Integration';
            $obj = new $className();
            $obj->parse();
          }
        }
      }
    }
  }
  
  public function checkSpam(array $params, string $type=''): bool {
    $email = $params['email'] ?? '';
    $ip = Craft::$app->request->getUserIP();
    
    /* DO MANUAL CHECK */
    $blockedEmails = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->blockedEmails);
    $blockedIPs = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->blockedIPs);
    $allowedEmails = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->allowedEmails);
    $allowedIPs = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->allowedIPs);
    if (in_array($email, $blockedEmails) || in_array($ip, $blockedIPs)){
      return false;
    } 
    if (in_array($email, $allowedEmails) || in_array($ip, $allowedIPs)){
      return true;
    }
    
    /* DO SERVICE CHECK */
    $content = $params['content'] ?? '';
    $data = [
      'senderIP' => $ip,
      'email' => $email,
      'content' => (is_array($content)) ? implode('; ', $content) : $content,
      'checkForLength' => (isset($params['checkForLength'])) ? (bool)$params['checkForLength'] : (bool)OOPSpam::$plugin->settings->checkForLength,
      'blockTempEmail' => (bool)OOPSpam::$plugin->settings->blockTempEmail,
      'urlFriendly' => (bool)OOPSpam::$plugin->settings->urlFriendly,
      'allowedLanguages' => (array)OOPSpam::$plugin->settings->allowedLanguages,
      'allowedCountries' => (array)OOPSpam::$plugin->settings->allowedCountries,
      'blockedCountries' => (array)OOPSpam::$plugin->settings->blockedCountries
    ];
    $endpoint = $this->apiVersion.$this->endpoint;
    /*$result = $this->sendRequest($data, $endpoint);*/
    
    $result = [
      'response' => true,
      'results' => [
        'Score' => 2,
        'Details' => [
          'isIPBlocked' => false,
          'isEmailBlocked' => false,
          'langMatch' => true,
          'countryMatch' => false,
          'isContentSpam' => 'nospam',
          'numberOfSpamWords' => 1,
          'spamWords' => [
            'dear'
          ]
        ]
      ],
      'limits' => [
        'limit' => 1000,
        'remaining' => 500
      ]
    ];
    
    /*$result = [
      'response' => false,
      'error' => [
        'This is a test error'
      ]
    ];*/
    
    if ($result['response']){
      Craft::$app->getProjectConfig()->set('plugins.oopspam.settings.apiUsage', $result['limits']); /* UPDATE LIMITS */
      Craft::$app->getProjectConfig()->saveModifiedConfigData();
      if (OOPSpam::$plugin->settings->enableLogs){
        OOPSpam::$plugin->logs->recordLog($endpoint, $data, $result['results'], $type);
      }
      if (!$this->isSpam($result['results'])){
        return true;
      }
      return false;
    } else {
      if (OOPSpam::$plugin->settings->enableLogs){
        OOPSpam::$plugin->logs->recordLog($endpoint, $data, [
          'Error' => $result['error']
        ], $type);
      }
      return false;
    }
    
  }
  
  public function isSpam(array $results): bool {
    if (isset($results['Score'])){
      if ((int)$results['Score'] >= (int)OOPSpam::$plugin->settings->spamScore){
        return true;
      }
    }
    return false;
  }

  public function sendRequest(array $params, string $endpoint): array {
    try {
      $client = new Client([
        'base_uri' => (OOPSpam::$plugin->settings->apiService == 'rapidapi') ? 'https://oopspam.p.rapidapi.com' : $this->baseUrl
      ]);
      $response = $client->request('POST', $endpoint, [
        'json' => $params,
        'headers' => [
          'Content-Type' => 'application/json',
          'X-Api-Key' => OOPSpam::$plugin->settings->apiKey 
        ]
      ]);
      return [
        'response' => true,
        'results' => json_decode($response->getBody()->getContents(), true),
        'limits' => [
          'limit' => $response->getHeader('X-RateLimit-Limit', true)[0],
          'remaining' => $response->getHeader('X-RateLimit-Remaining', true)[0]
        ]
      ];
      return $response;
    } catch (GuzzleException $e) {
      return [
        'response' => false,
        'error' => $e->getMessage()
      ];
    }
  }
  
}