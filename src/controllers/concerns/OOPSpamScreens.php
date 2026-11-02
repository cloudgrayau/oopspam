<?php
namespace cloudgrayau\oopspam\controllers\concerns;

use cloudgrayau\oopspam\OOPSpam;

use CraftCms\Cms\Cp\Data\ActionItem;
use CraftCms\Cms\Form\{Form, FormContext, FormResolver};
use CraftCms\Cms\Http\Responses\CpScreenResponse;

use function CraftCms\Cms\cp_url;

trait OOPSpamScreens {

  protected function formScreen(string $title, string $selected, string $url, string $submitLabel, Form $form, array $values, array $errors = [], ?string $refreshUrl = null): CpScreenResponse {
    $name = OOPSpam::getInstance()->getSettings()->pluginName ?: 'OOPSpam';
    $props = [
      'form' => app(FormResolver::class)->resolve($form, new FormContext(values: $values, errors: $errors, refreshable: $refreshUrl !== null)),
      'submit' => ['method' => 'post', 'url' => cp_url($url)],
    ];
    if ($refreshUrl !== null) {
      $props['refreshUrl'] = cp_url($refreshUrl);
    }
    return new CpScreenResponse()
      ->title($title)
      ->selectedSubnavItem($selected)
      ->submitButtonLabel($submitLabel)
      ->crumbs([new ActionItem()->label($name)->href(cp_url('oopspam')), new ActionItem()->label($title)])
      ->inertiaPage('Form', $props);
  }

}