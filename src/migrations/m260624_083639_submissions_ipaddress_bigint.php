<?php

namespace cloudgrayau\oopspam\migrations;

use Craft;
use craft\db\Migration;
use cloudgrayau\oopspam\records\SubmissionRecord;

/**
 * m260624_083639_submissions_ipaddress_bigint migration.
 */
class m260624_083639_submissions_ipaddress_bigint extends Migration {

  public function safeUp(): bool {
    if ($this->db->tableExists(SubmissionRecord::tableName())){
      $this->alterColumn(SubmissionRecord::tableName(), 'ipaddress', $this->bigInteger()->unsigned());
      Craft::$app->db->schema->refresh();
    }
    return true;
  }

  public function safeDown(): bool {
    if ($this->db->tableExists(SubmissionRecord::tableName())){
      $this->alterColumn(SubmissionRecord::tableName(), 'ipaddress', $this->integer()->unsigned());
      Craft::$app->db->schema->refresh();
    }
    return true;
  }

}
