<?php
namespace cloudgrayau\oopspam\records;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

class UsageRecord extends BaseModel {

  use HasUid;

  protected $table = 'oopspam_usage';

  protected $casts = ['limit' => 'int', 'remaining' => 'int'];

}