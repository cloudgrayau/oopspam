<?php
namespace cloudgrayau\oopspam\integrations;

use cloudgrayau\oopspam\OOPSpam;

/**
 * Base class for OOPSpam integrations. Each one hooks into another part of Craft (or a form plugin)
 * in register(), and calls passes() with the submission's details.
 */
abstract class Integration {

  /** Checks a submission. `true` means it's allowed; API failures fail open. */
  protected function passes(array $params): bool {
    return OOPSpam::getInstance()->antiSpam->checkSpam($params, $this->name());
  }

  /** Only front-end web requests are checked: not the control panel or console commands. */
  protected function isFrontEndRequest(): bool {
    return !app()->runningInConsole() && !request()->isCpRequest();
  }

  /** The name shown in the logs' Integration column. */
  abstract public function name(): string;

  /** Registers the integration's event listeners. Called once, when the plugin boots. */
  abstract public function register(): void;

}
