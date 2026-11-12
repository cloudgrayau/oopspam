<?php
namespace cloudgrayau\oopspam\console;

use cloudgrayau\oopspam\OOPSpam;

use CraftCms\Cms\Console\CraftCommand;
use Illuminate\Console\Attributes\{Description, Signature};
use Illuminate\Console\Command;

#[Signature('oopspam:gc')]
#[Description('Deletes expired OOPSpam logs and rate-limit records')]
class GcCommand extends Command {

  use CraftCommand;

  public function handle(): int {
    $plugin = OOPSpam::getInstance();
    $plugin->logs->gcLogs();
    $plugin->submissions->gcSubmissions();
    $this->info('OOPSpam garbage collection complete.');
    return self::SUCCESS;
  }

}