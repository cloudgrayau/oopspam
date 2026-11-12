<?php
namespace cloudgrayau\oopspam\records;

use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Shared\Concerns\HasUid;

class LogRecord extends BaseModel {

  use HasUid;

  protected $table = 'oopspam';

  protected $casts = ['isValid' => 'int', 'isSpam' => 'int', 'isReport' => 'int'];

  public function getParams(): array {
    return json_decode((string) $this->params, true) ?: [];
  }

  public function getParam(string $key): mixed {
    return $this->getParams()[$key] ?? '';
  }

  public function getResponses(): array {
    return json_decode((string) $this->response, true) ?: [];
  }

  public function getResponse(string $key): mixed {
    return $this->getResponses()[$key] ?? '';
  }

}