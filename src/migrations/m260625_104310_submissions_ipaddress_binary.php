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
      // Truncate first: existing integer IPs can't be cast to a binary type, and
      // the data is being discarded anyway. Drop and re-add the column instead of
      // altering it in place, since Postgres won't implicitly cast int -> bytea.
      $this->truncateTable(SubmissionRecord::tableName());
      $this->dropColumn(SubmissionRecord::tableName(), 'ipaddress');
      $this->addColumn(SubmissionRecord::tableName(), 'ipaddress', $this->db->getIsPgsql() ? 'bytea NOT NULL' : 'varbinary(16) NOT NULL');
      Craft::$app->db->schema->refresh();
    }
    return true;
  }

  public function safeDown(): bool {
    if ($this->db->tableExists(SubmissionRecord::tableName())){
      $this->truncateTable(SubmissionRecord::tableName());
      $this->dropColumn(SubmissionRecord::tableName(), 'ipaddress');
      $this->addColumn(SubmissionRecord::tableName(), 'ipaddress', $this->bigInteger()->unsigned());
      Craft::$app->db->schema->refresh();
    }
    return true;
  }

}
