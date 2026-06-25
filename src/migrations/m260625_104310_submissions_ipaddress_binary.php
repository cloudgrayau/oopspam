<?php

namespace cloudgrayau\oopspam\migrations;

use Craft;
use craft\db\Migration;
use cloudgrayau\oopspam\records\SubmissionRecord;

/**
 * m260625_104310_submissions_ipaddress_binary migration.
 */
class m260625_104310_submissions_ipaddress_binary extends Migration {

  public function safeUp(): bool {
    if ($this->db->tableExists(SubmissionRecord::tableName())){
      $this->truncateTable(SubmissionRecord::tableName());
      $this->alterColumn(SubmissionRecord::tableName(), 'ipaddress', $this->db->getIsPgsql() ? 'bytea NOT NULL' : 'varbinary(16) NOT NULL');
      Craft::$app->db->schema->refresh();
    }
    return true;
  }

  public function safeDown(): bool {
    if ($this->db->tableExists(SubmissionRecord::tableName())){
      $this->truncateTable(SubmissionRecord::tableName());
      $this->alterColumn(SubmissionRecord::tableName(), 'ipaddress', $this->integer()->unsigned());
      Craft::$app->db->schema->refresh();
    }
    return true;
  }

}
