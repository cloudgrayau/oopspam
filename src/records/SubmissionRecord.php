<?php
namespace cloudgrayau\oopspam\records;

use craft\db\ActiveRecord;

class SubmissionRecord extends ActiveRecord {
  
  // Public Methods
  // =========================================================================

  public static function tableName(): string {
    return '{{%oopspam_submissions}}';
  }
  
}