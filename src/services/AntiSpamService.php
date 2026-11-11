<?php
namespace cloudgrayau\oopspam\services;

use cloudgrayau\oopspam\models\SettingsModel;
use cloudgrayau\oopspam\OOPSpam;

use CraftCms\Cms\Support\Env;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AntiSpamService {

  public string $apiVersion = '/v1';
  private string $baseUrl = 'https://api.oopspam.com';
  private string $endpoint = '/spamdetection';

  public function checkSpam(array $params, string $type = '', bool $test = false): array|bool {
    $settings = $this->settings();
    $plugin = OOPSpam::getInstance();
    $content = $params['content'] ?? '';
    $email = trim((string) ($params['email'] ?? ''));
    $senderIP = (string) ($params['ip'] ?? request()->ip());
    $checkForLength = isset($params['checkForLength']) ? (bool) $params['checkForLength'] : $settings->checkForLength;
    $text = trim(is_array($content) ? implode('; ', array_filter($content)) : (string) $content);
    if (!empty($params['contextual'])) {
      $data = [
        'content' => $text,
        'context' => $params['context'] ?? $settings->contextualContent,
        'checkForLength' => $checkForLength,
      ];
    } else {
      $data = ['senderIP' => $senderIP, 'email' => $email, 'content' => $text, 'checkForLength' => $checkForLength];
    }
    $endpoint = $this->apiVersion . $this->endpoint;

    /* Manual rules */
    $emailKey = mb_strtolower($email);
    $ipKey = $this->normaliseIp($senderIP);
    $match = fn (array $emails, array $ips) =>
      in_array($emailKey, array_map('mb_strtolower', $emails), true)
      || in_array($ipKey, array_map($this->normaliseIp(...), $ips), true);
    
    if ($match($settings->values('allowedEmails'), $settings->values('allowedIPs'))) {
      return $this->conclude($endpoint, $data, ['Score' => 0, 'Reason' => 'Allowed due to manual rules'], $type, $test, true);
    }
    if ($match($settings->values('blockedEmails'), $settings->values('blockedIPs'))) {
      return $this->conclude($endpoint, $data, ['Score' => 6, 'Reason' => 'Blocked due to manual rules'], $type, $test, false);
    }

    /* Rate limits (checked after manual rules; never during a test) */
    if (!$test && $settings->enableLimiting) {
      $ip = (string) request()->ip();
      $limited = $plugin->submissions->checkSubmission($ip);
      $plugin->submissions->recordSubmission($ip);
      if ($limited) {
        return $this->conclude($endpoint, $data, ['Score' => 6, 'Reason' => 'Blocked due to too many submissions; rateLimit'], $type, false, false);
      }
    }

    /* Content length */
    if ($checkForLength && mb_strlen($data['content']) < 20) {
      return $this->conclude($endpoint, $data, ['Score' => 6, 'Reason' => 'Blocked due to content length; checkForLength'], $type, $test, false);
    }

    /* Service check */
    if ($settings->logIt) {
      $data['logIt'] = true;
    }
    if (!isset($data['context'])) { // not contextual
      foreach (['blockTempEmail', 'blockVPN', 'blockDC', 'urlFriendly'] as $flag) {
        if ($settings->$flag) {
          $data[$flag] = true;
        }
      }
      foreach (['allowedLanguages', 'allowedCountries', 'blockedCountries'] as $list) {
        if (!empty($settings->$list)) {
          $data[$list] = (array) $settings->$list;
        }
      }
    }
    $data['source'] = request()->getHost();
    $result = $this->sendRequest($data, $endpoint);

    if (isset($data['context'])) { // contextual: store email and IP for logs only
      $data['senderIP'] = $senderIP;
      $data['email'] = $email;
    }
    if ($result['response']) {
      $plugin->logs->updateUsage($result['limits']);
      if ($settings->enableLogs) {
        $plugin->logs->recordLog($endpoint, $data, $result['results'], $type);
      }
      return $test ? ['data' => $data, 'results' => $result['results']] : !$this->isSpam($result['results']);
    }
    if ($settings->enableLogs) {
      $plugin->logs->recordLog($endpoint, $data, ['Error' => $result['error']], $type);
    }
    return $test ? ['data' => $data, 'results' => $result['error']] : true; // fail open
  }

  public function checkReputation(string $domain): array {
    $result = $this->sendRequest(['domain' => $domain], $this->apiVersion . '/reputation/domain');
    if ($result['response']) {
      OOPSpam::getInstance()->logs->updateUsage($result['limits']);
      return $result['results'];
    }
    return $result;
  }

  public function reportLog(array $params, string $endpoint): array {
    return $this->sendRequest($params, $endpoint);
  }

  public function isSpam(array $results): bool {
    $settings = $this->settings();
    if (isset($results['isContentSpam']) && $settings->blockContentSpam && (string) $results['isContentSpam'] === 'spam') {
      return true;
    }
    return isset($results['Score']) && (int) $results['Score'] >= $settings->spamScore;
  }

  private function conclude(string $endpoint, array $data, array $results, string $type, bool $test, bool $pass): array|bool {
    if ($this->settings()->enableLogs) {
      OOPSpam::getInstance()->logs->recordLog($endpoint, $data, $results, $type);
    }
    return $test ? ['data' => $data, 'results' => $results] : $pass;
  }

  private function sendRequest(array $params, string $endpoint): array {
    $settings = $this->settings();
    try {
      $apiKey = (string) Env::parse($settings->apiKey);
      if ($apiKey === '') {
        throw new \Exception('API Key is not set. Please set your API key in the plugin settings.');
      }
      $response = Http::baseUrl($settings->apiService === 'rapidapi' ? 'https://oopspam.p.rapidapi.com' : $this->baseUrl)
        ->timeout(10)->connectTimeout(5)->asJson()->acceptJson()
        ->withHeaders(['X-Api-Key' => $apiKey])
        ->post($endpoint, $params);
      if ($response->failed()) {
        $body = $response->json();
        $error = is_array($body) ? ($body['error'] ?? null) : null;
        Log::warning('OOPSpam request failed: HTTP ' . $response->status());
        return ['response' => false, 'error' => $error ?? ('HTTP ' . $response->status())];
      }
      return [
        'response' => true,
        'results' => $response->json() ?? [],
        'limits' => [
          'limit' => (int) $response->header('X-RateLimit-Limit'),
          'remaining' => (int) $response->header('X-RateLimit-Remaining'),
        ],
      ];
    } catch (ConnectionException $e) {
      Log::warning('OOPSpam request failed: ' . $e->getMessage());
      return ['response' => false, 'error' => $e->getMessage()];
    } catch (\Throwable $e) {
      return ['response' => false, 'error' => $e->getMessage()];
    }
  }
  
  private function normaliseIp(string $ip): string {
    $ip = trim($ip);
    $binary = @inet_pton($ip);
    if ($binary === false) {
      return $ip;
    }
    if (strlen($binary) === 16 && str_starts_with($binary, str_repeat("\0", 10) . "\xff\xff")) {
      $binary = substr($binary, 12);
    }
    return (string) inet_ntop($binary);
  }

  private function settings(): SettingsModel {
    return OOPSpam::getInstance()->getSettings();
  }

}