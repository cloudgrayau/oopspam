<?php
namespace cloudgrayau\oopspam\requests;

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
  
    $this->merge(['settings' => $settings]);
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