<?php
namespace cloudgrayau\oopspam\helpers;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Support\Facades\DB;

/**
 * OOPSpam's own rows (logs, rate-limit submissions, usage) have to survive the transaction they're written in.
 * Spam checks often run inside one, e.g. the CP element editor wraps a user save in a transaction and rolls it
 * back when the check blocks the save, which would roll the log back with it.
 */
class Writes {

  /**
   * The connection to write on: the default one normally, or a separate connection to the same database
   * while a transaction is open, so the write commits immediately.
   */
  public static function writeConnection(): string {
    $default = DB::getDefaultConnection();
    $connection = DB::connection($default);
    if ($connection->transactionLevel() === 0 || !in_array($connection->getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
      return $default;
    }
    $name = "oopspam-$default";
    if (config("database.connections.$name") === null) {
      config(["database.connections.$name" => config("database.connections.$default")]);
    }
    return $name;
  }

  /**
   * @template T of Model
   * @param class-string<T> $model
   * @return Builder<T>
   */
  public static function query(string $model): Builder {
    return $model::on(self::writeConnection());
  }

}
