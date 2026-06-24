<?php

namespace cloudgrayau\oopspam\migrations;

use Craft;
use craft\db\Migration;
use cloudgrayau\oopspam\records\SubmissionRecord;

/**
 * m251128_041612_settings_1_5 migration.
 */
class m251128_041612_settings_1_5 extends Migration {

    public function safeUp(): bool {
      if (!$this->db->tableExists(SubmissionRecord::tableName())){
        $this->createTable(SubmissionRecord::tableName(), [
          'ipaddress' => $this->bigInteger()->unsigned(),
          'dateCreated' => $this->dateTime()->notNull(),
          'dateUpdated' => $this->dateTime()->notNull(),
          'uid' => $this->uid(),
        ]);
        Craft::$app->db->schema->refresh();
      }
      return true;
    }

    public function safeDown(): bool {
      $this->dropTableIfExists(SubmissionRecord::tableName());
      Craft::$app->db->schema->refresh();
      return true;
    }
}
