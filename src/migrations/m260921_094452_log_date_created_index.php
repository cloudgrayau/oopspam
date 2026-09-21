<?php

namespace cloudgrayau\oopspam\migrations;

use cloudgrayau\oopspam\records\LogRecord;
use craft\db\Migration;

/**
 * m260921_094452_log_date_created_index migration.
 */
class m260921_094452_log_date_created_index extends Migration {

  public function safeUp(): bool {
    $this->createIndexIfMissing(LogRecord::tableName(), ['dateCreated', 'id']);
    return true;
  }

  public function safeDown(): bool {
    $this->dropIndexIfExists(LogRecord::tableName(), ['dateCreated', 'id']);
    return true;
  }

}
