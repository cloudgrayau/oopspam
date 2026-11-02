<?php
namespace cloudgrayau\oopspam\services;

class LogsService {
  public function recordLog(string $endpoint, array $params, array $results, string $type = ''): void {}
  public function updateUsage(array $usage): void {}
  public function getUsage(): array { return ['limit' => 0, 'remaining' => 0]; }
}