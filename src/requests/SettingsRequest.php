<?php
namespace cloudgrayau\oopspam\requests;

use cloudgrayau\oopspam\OOPSpam;
use cloudgrayau\oopspam\helpers\SettingsHelper;
use cloudgrayau\oopspam\models\SettingsModel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingsRequest extends FormRequest {

  protected function prepareForValidation(): void {
    $settings = (array) $this->input('settings', []);
  
    foreach (['pluginName', 'contextualContent'] as $key) {
      if (array_key_exists($key, $settings) && $settings[$key] === null) {
        $settings[$key] = '';
      }
    }
  
    // Manual lists: drop blank rows and trim the rest, keeping the [[value], ...] shape
    foreach (['blockedEmails', 'blockedIPs', 'allowedEmails', 'allowedIPs'] as $key) {
      if (!array_key_exists($key, $settings)) {
        continue;
      }
      $rows = [];
      foreach ((array) $settings[$key] as $row) {
        $value = is_array($row) ? ($row[0] ?? (count($row) ? reset($row) : '')) : $row;
        $value = is_scalar($value) ? trim((string) $value) : '';
        if ($value !== '') {
          $rows[] = [$value];
        }
      }
      $settings[$key] = $rows;
    }
  
    // Integration tables: back to the boolean settings and handle lists they're stored as
    $saved = OOPSpam::getInstance()->getSettings();
    if (is_array($settings['integrationTable'] ?? null)) {
      $list = (array) $saved->integrations;
      foreach (SettingsHelper::getIntegrations() as $integrations) {
        foreach ($integrations as $key => $integration) {
          $on = $this->tableValue($settings['integrationTable'], $key, $integration);
          if ($on === null) { // couldn't be changed, so it keeps its saved value
            continue;
          }
          if (isset($integration['setting'])) {
            $settings[$integration['setting']] = $on;
          } else {
            $list = $this->toggle($list, $key, $on);
          }
        }
      }
      $settings['integrations'] = $list;
    }
    if (is_array($settings['contextualTable'] ?? null)) {
      $list = (array) $saved->contextual;
      foreach (SettingsHelper::getPluginIntegrations() as $key => $integration) {
        $on = $this->tableValue($settings['contextualTable'], $key, $integration);
        if ($on !== null) {
          $list = $this->toggle($list, $key, $on);
        }
      }
      $settings['contextual'] = $list;
    }
    unset($settings['integrationTable'], $settings['contextualTable']);

    $this->merge(['settings' => $settings]);
  }

  /** A table row's switch, or null when the row was disabled (or missing). */
  private function tableValue(array $rows, string $key, array $integration): ?bool {
    if (!is_array($rows[$key] ?? null) || SettingsHelper::getIntegrationStatus($key, $integration) !== 'available') {
      return null;
    }
    return filter_var($rows[$key]['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
  }

  private function toggle(array $list, string $key, bool $on): array {
    $list = array_values(array_filter($list, fn ($handle) => $handle !== $key));
    if ($on) {
      $list[] = $key;
    }
    return $list;
  }

  public function authorize(): bool {
    return true; // access is enforced by the route middleware
  }

  public function rules(): array {
    $rules = [];
    foreach ((new SettingsModel)->getRules() as $key => $rule) {
      if (is_string($key)) {
        $rules["settings.$key"] = $rule;
      }
    }
    // The model's version reads its own default; this one reads what was submitted
    $rules['settings.contextualContent'] = ['nullable', 'string', Rule::requiredIf(fn () => $this->boolean('settings.enableContextual'))];
    return $rules;
  }

}