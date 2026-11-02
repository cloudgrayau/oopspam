<?php
namespace cloudgrayau\oopspam\services;

class SubmissionService {
  public function checkSubmission(string $ipaddress): bool { return false; }
  public function recordSubmission(string $ipaddress): void {}
}