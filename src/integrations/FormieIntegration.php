<?php
namespace cloudgrayau\oopspam\integrations;

use cloudgrayau\oopspam\OOPSpam;

class FormieIntegration {
  
  public function getName(): string {
    return 'Formie';
  }

  public function parse(): void {
    /*$result = OOPSpam::$plugin->antiSpam->checkSpam([
      'email' => 'aaron@cloudgray.com.au',
      'content' => 'Dear Agent, We are a manufacturing company which specializes in supplying Aluminum Rod with Zinc Alloy Rod to customers worldwide, based in Japan, Asia. We have been unable to follow up payments effectively for transactions with debtor customers in your country due to our distant locations, thus our reason for requesting for your services representation.'
    ], $this->getName());*/
  }
  
}

?>