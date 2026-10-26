<?php
namespace cloudgrayau\oopspam;

use CraftCms\Cms\Plugin\Plugin;

class OOPSpam extends Plugin {

  public string $schemaVersion = '2.0.0';
  public bool $hasCpSettings = false;
  public bool $hasCpSection = false;

  public function boot(): void {
  }

}