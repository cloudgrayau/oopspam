<?php

namespace cloudgrayau\oopspam\migrations;

use Craft;
use craft\db\Migration;
use cloudgrayau\oopspam\records\UsageRecord;

/**
 * m241129_121751_settings_1_1 migration.
 */
class m241129_121751_settings_1_1 extends Migration {

  public function safeUp(): bool {
    $schemaVersion = Craft::$app->getProjectConfig()->get('plugins.oopspam.schemaVersion', true);
    if (version_compare($schemaVersion, '1.1.0', '<')) {
      Craft::$app->getProjectConfig()->remove('plugins.oopspam.settings.apiUsage');
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
      Craft::$app->db->schema->refresh();
    }
    return true;
  }

  public function safeDown(): bool {
    $this->dropTableIfExists(UsageRecord::tableName());
    Craft::$app->db->schema->refresh();
    return true;
  }
  
}
