<?php
namespace cloudgrayau\oopspam\services;

use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\helpers\SettingsHelper;

use Craft;
use craft\base\Component;
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
    $content = $params['content'] ?? '';
    $data = [
      'senderIP' => Craft::$app->request->getUserIP(),
      'email' => StringHelper::trim($params['email'] ?? ''),
      'content' => (is_array($content)) ? StringHelper::trim(implode('; ', $content)) : StringHelper::trim($content),
      'checkForLength' => (isset($params['checkForLength'])) ? (bool)$params['checkForLength'] : (bool)OOPSpam::$plugin->settings->checkForLength
    ];
    $endpoint = $this->apiVersion.$this->endpoint;
    
    /* DO MANUAL CHECK */
    $blockedEmails = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->blockedEmails);
    $blockedIPs = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->blockedIPs);
    $allowedEmails = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->allowedEmails);
    $allowedIPs = array_map(['\cloudgrayau\oopspam\helpers\SettingsHelper', 'mapSettings'], OOPSpam::$plugin->settings->allowedIPs);
    if (in_array($data['email'], $allowedEmails) || in_array($data['senderIP'], $allowedIPs)){
      if (OOPSpam::$plugin->settings->enableLogs){
        OOPSpam::$plugin->logs->recordLog($endpoint, $data, [
          'Score' => 0,
          'Reason' => 'Allowed due to manual rules'
        ], $type);
      }
      return true;
    }  
    if (in_array($data['email'], $blockedEmails) || in_array($data['senderIP'], $blockedIPs)){
      if (OOPSpam::$plugin->settings->enableLogs){
        OOPSpam::$plugin->logs->recordLog($endpoint, $data, [
          'Score' => 6,
          'Reason' => 'Blocked due to manual rules'
        ], $type);
      }
      return false;
    }
    if ($data['checkForLength'] && (StringHelper::count($data['content']) < 20)){
      if (OOPSpam::$plugin->settings->enableLogs){
        OOPSpam::$plugin->logs->recordLog($endpoint, $data, [
          'Score' => 6,
          'Reason' => 'Blocked due to content length; checkForLength'
        ], $type);
      }
      return false;
    }
    
    /* DO SERVICE CHECK */
    if ((bool)OOPSpam::$plugin->settings->blockTempEmail){
      $data['blockTempEmail'] = (bool)OOPSpam::$plugin->settings->blockTempEmail;
    }
    if ((bool)OOPSpam::$plugin->settings->logIt){
      $data['logIt'] = (bool)OOPSpam::$plugin->settings->logIt;
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
    $data['source'] = Craft::$app->request->getHostName();
    $result = $this->sendRequest($data, $endpoint);
    
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