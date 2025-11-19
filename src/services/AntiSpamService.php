<?php
namespace cloudgrayau\oopspam\services;

use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\helpers\SettingsHelper;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\StringHelper;
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
  
  public function initCommerce(): void {
    $className = '\cloudgrayau\oopspam\integrations\CommerceIntegration';
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
            $obj->parse($integration);
          }
        }
      }
    }
  }
  
  public function checkSpam(array $params, string $type=''): array|bool {    
    $content = $params['content'] ?? '';
    $email = StringHelper::trim($params['email'] ?? '');
    $senderIP = $params['ip'] ?? Craft::$app->request->getUserIP();
    $checkForLength = (isset($params['checkForLength'])) ? (bool)$params['checkForLength'] : (bool)OOPSpam::$plugin->settings->checkForLength;
    if ((isset($params['contextual'])) && ($params['contextual'] == true)){
      $data = [
        'content' => (is_array($content)) ? StringHelper::trim(implode('; ', $content)) : StringHelper::trim($content),
        'context' => (isset($params['context'])) ? $params['context'] : OOPSpam::$plugin->settings->contextualContent,
        'checkForLength' => $checkForLength
      ];
    } else {
      $data = [
        'senderIP' => $senderIP,
        'email' => $email,
        'content' => (is_array($content)) ? StringHelper::trim(implode('; ', $content)) : StringHelper::trim($content),
        'checkForLength' => $checkForLength
      ];
    }
    $endpoint = $this->apiVersion.$this->endpoint;
    
    /* DO MANUAL CHECK */
    $blockedEmails = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->blockedEmails);
    $blockedIPs = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->blockedIPs);
    $allowedEmails = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->allowedEmails);
    $allowedIPs = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->allowedIPs);
      
    if (in_array($email, $allowedEmails) || in_array($senderIP, $allowedIPs)){
      if (OOPSpam::$plugin->settings->enableLogs){
        OOPSpam::$plugin->logs->recordLog($endpoint, $data, [
          'Score' => 0,
          'Reason' => 'Allowed due to manual rules'
        ], $type);
      }
      if (defined('OOPSPAM_TEST')){
        return [
          'data' => $data,
          'results' => [
            'Score' => 0,
            'Reason' => 'Allowed due to manual rules'
          ]
        ];
      }
      return true;
    }  
    if (in_array($email, $blockedEmails) || in_array($senderIP, $blockedIPs)){
      if (OOPSpam::$plugin->settings->enableLogs){
        OOPSpam::$plugin->logs->recordLog($endpoint, $data, [
          'Score' => 6,
          'Reason' => 'Blocked due to manual rules'
        ], $type);
      }
      if (defined('OOPSPAM_TEST')){
        return [
          'data' => $data,
          'results' => [
            'Score' => 6,
            'Reason' => 'Blocked due to manual rules'
          ]
        ];
      }
      return false;
    }
    if ($checkForLength && (StringHelper::count($data['content']) < 20)){
      if (OOPSpam::$plugin->settings->enableLogs){
        OOPSpam::$plugin->logs->recordLog($endpoint, $data, [
          'Score' => 6,
          'Reason' => 'Blocked due to content length; checkForLength'
        ], $type);
      }
      if (defined('OOPSPAM_TEST')){
        return [
          'data' => $data,
          'results' => [
            'Score' => 6,
            'Reason' => 'Blocked due to content length; checkForLength'
          ]
        ];
      }
      return false;
    }
    
    /* DO SERVICE CHECK */
    if ((bool)OOPSpam::$plugin->settings->logIt){
      $data['logIt'] = (bool)OOPSpam::$plugin->settings->logIt;
    }
    if (!isset($data['context'])){ /* if not contextual */
      if ((bool)OOPSpam::$plugin->settings->blockTempEmail){
        $data['blockTempEmail'] = (bool)OOPSpam::$plugin->settings->blockTempEmail;
      }
      if ((bool)OOPSpam::$plugin->settings->blockVPN){
        $data['blockVPN'] = (bool)OOPSpam::$plugin->settings->blockVPN;
      }
      if ((bool)OOPSpam::$plugin->settings->blockDC){
        $data['blockDC'] = (bool)OOPSpam::$plugin->settings->blockDC;
      }
      if ((bool)OOPSpam::$plugin->settings->urlFriendly){
        $data['urlFriendly'] = (bool)OOPSpam::$plugin->settings->urlFriendly;
      }
      if (!empty((array)OOPSpam::$plugin->settings->allowedLanguages)){
        $data['allowedLanguages'] = (array)OOPSpam::$plugin->settings->allowedLanguages;
      }
      if (!empty((array)OOPSpam::$plugin->settings->allowedCountries)){
        $data['allowedCountries'] = (array)OOPSpam::$plugin->settings->allowedCountries;
      }
      if (!empty((array)OOPSpam::$plugin->settings->blockedCountries)){
        $data['blockedCountries'] = (array)OOPSpam::$plugin->settings->blockedCountries;
      }
    }
    $data['source'] = Craft::$app->request->getHostName();
    $result = $this->sendRequest($data, $endpoint);
    
    if (isset($data['context'])){ /* Contextual - Store Email and IP for logs ONLY */
      $data['senderIP'] = $senderIP;
      $data['email'] = $email;
    }
    if ($result['response']){
      OOPSpam::$plugin->logs->updateUsage($result['limits']);
      if (OOPSpam::$plugin->settings->enableLogs){
        OOPSpam::$plugin->logs->recordLog($endpoint, $data, $result['results'], $type);
      }
      if (defined('OOPSPAM_TEST')){
        return [
          'data' => $data,
          'results' => $result['results']
        ];
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
      if (defined('OOPSPAM_TEST')){
        return [
          'data' => $data,
          'results' => $result['error']
        ];
      }
      return false;
    }
    
  }
  
  public function checkReputation(string $domain): array {
    $result = $this->sendRequest([
      'domain' => $domain
    ], $this->apiVersion.'/reputation/domain');
    if ($result['response']){
      OOPSpam::$plugin->logs->updateUsage($result['limits']);
      return $result['results'];
    } else {
      return $result;
    }
  }
  
  public function reportLog(array $params, string $endpoint): array {
    return $this->sendRequest($params, $endpoint);
  }
  
  public function isSpam(array $results): bool {
    if (isset($results['isContentSpam'])){
      if ((bool)OOPSpam::$plugin->settings->blockContentSpam && (string)$results['isContentSpam'] == 'spam'){
        return true;
      }
    }
    if (isset($results['Score'])){
      if ((int)$results['Score'] >= (int)OOPSpam::$plugin->settings->spamScore){
        return true;
      }
    }
    return false;
  }
  
  // Private Methods
  // =========================================================================

  private function sendRequest(array $params, string $endpoint): array {
    try {
      $client = new Client([
        'base_uri' => (OOPSpam::$plugin->settings->apiService == 'rapidapi') ? 'https://oopspam.p.rapidapi.com' : $this->baseUrl
      ]);
      $response = $client->request('POST', $endpoint, [
        'json' => $params,
        'headers' => [
          'Content-Type' => 'application/json',
          'X-Api-Key' => App::parseEnv(OOPSpam::$plugin->settings->apiKey)
        ]
      ]);
      return [
        'response' => true,
        'results' => json_decode((string)$response->getBody()->getContents(), true),
        'limits' => [
          'limit' => $response->getHeader('X-RateLimit-Limit', true)[0],
          'remaining' => $response->getHeader('X-RateLimit-Remaining', true)[0]
        ]
      ];
      return $response;
    } catch (GuzzleException $e) {
      $error = json_decode((string)$e->getResponse()->getBody(), true);
      return [
        'response' => false,
        'error' => $error['error']
      ];
    }
  }
  
}
