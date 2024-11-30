<?php

namespace cloudgrayau\oopspam\migrations;

use Craft;
use craft\db\Migration;
use cloudgrayau\oopspam\records\LogRecord;
use cloudgrayau\oopspam\records\UsageRecord;

/**
 * Install migration.
 */
class Install extends Migration {
  
  public function safeUp(): bool {
    if (!$this->db->tableExists(LogRecord::tableName())){
      $this->createTable(LogRecord::tableName(), [
        'id' => $this->primaryKey(),
        'type' => $this->tinyText(),
        'endpoint' => $this->tinyText(),
        'params' => $this->json(),
        'response' => $this->json(),
        'isValid' => $this->tinyInteger(1)->defaultValue(0)->notNull(),
        'isSpam' => $this->tinyInteger(1)->defaultValue(0)->notNull(),
        'isReport' => $this->tinyInteger(1)->defaultValue(0)->notNull(),
        'dateCreated' => $this->dateTime()->notNull(),
        'dateUpdated' => $this->dateTime()->notNull(),
        'uid' => $this->uid(),
      ]);
    }
    if (!$this->db->tableExists(UsageRecord::tableName())){
      $result = $this->createTable(UsageRecord::tableName(), [
        'id' => $this->primaryKey(),
        'limit' => $this->integer(),
        'remaining' => $this->integer(),
        'dateCreated' => $this->dateTime()->notNull(),
        'dateUpdated' => $this->dateTime()->notNull(),
        'uid' => $this->uid(),
      ]);
      $usageRecord = new UsageRecord;
      $usageRecord->setAttributes([
        'limit' => 0,
        'remaining' => 0
      ], false); 
      $usageRecord->save();
    }
    Craft::$app->db->schema->refresh();
    return true;
  }

  public function safeDown(): bool {
    $this->dropTableIfExists(LogRecord::tableName());
    $this->dropTableIfExists(UsageRecord::tableName());
    return true;
  }
  
}
