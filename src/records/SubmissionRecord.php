<?php
namespace cloudgrayau\oopspam\records;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

class SubmissionRecord extends BaseModel {

  use HasUid;

  protected $table = 'oopspam_submissions';

  protected $primaryKey = null;

  public $incrementing = false;

}