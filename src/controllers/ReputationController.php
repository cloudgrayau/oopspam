<?php
namespace cloudgrayau\oopspam\controllers;

use cloudgrayau\oopspam\controllers\concerns\OOPSpamScreens;
use cloudgrayau\oopspam\helpers\ResultsHelper;
use cloudgrayau\oopspam\helpers\UsageHelper;
use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\utils\UtilityHelper;

use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use Illuminate\Http\Request;

use function CraftCms\Cms\{site_url, t};

class ReputationController {

  use OOPSpamScreens;

  public function show(): CpScreenResponse {
    return $this->screen((string) parse_url(site_url(), PHP_URL_HOST));
  }

  public function run(Request $request): CpScreenResponse {
    $request->validate(['domain' => ['required', 'string', 'max:255']]);
    $domain = $this->normalise((string) $request->input('domain'));
    if (!$this->isValid($domain)) {
      return $this->screen($domain, [], ['domain' => t('Domain name is not valid', category: 'oopspam')]);
    }
    $results = OOPSpam::getInstance()->antiSpam->checkReputation($domain);
    if (isset($results['error'])) {
      $error = $results['error'];
      $results['error'] = t('Error: {code}', ['code' => is_array($error) ? ($error['code'] ?? 'Unknown') : $error], 'oopspam');
      $status = 'Failed';
    } else {
      $status = array_key_exists('Blocked', $results) ? ($results['Blocked'] ? 'Spam' : 'Pass') : 'Failed';
    }
    return $this->screen($domain, ResultsHelper::nodes('reputation', $status, ['Response' => $results]));
  }

  private function screen(string $domain, array $nodes = [], array $errors = []): CpScreenResponse {
    $form = Ui::make([
      ...array_filter([UsageHelper::node('oopspam-usage-reputation')]),
      Field::make(t('Domain Name', category: 'oopspam'), Text::make('domain')->placeholder('example.com'))
        ->instructions(t('Query the reputation of a domain.', category: 'oopspam'))->required(),
      ...$nodes,
      UtilityHelper::footer('oopspam-footer-reputation'),
    ]);
    return $this->formScreen(t('Domain Reputation', category: 'oopspam'), 'reputation', 'oopspam/reputation', t('Query', category: 'oopspam'), $form, ['domain' => $domain], $errors);
  }

  private function normalise(string $input): string {
    $input = trim($input);
    $host = preg_match('#^[a-z][a-z0-9+.-]*://#i', $input) ? (string) parse_url($input, PHP_URL_HOST) : $input;
    return strtolower((string) preg_replace('/^www\./i', '', trim($host)));
  }

  private function isValid(string $domain): bool {
    return $domain !== ''
      && str_contains($domain, '.')
      && filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
      && (checkdnsrr($domain, 'A') || checkdnsrr($domain, 'MX'));
  }

}