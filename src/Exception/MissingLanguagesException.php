<?php
/**
 * @file
 * Contains \Drupal\usebb2drupal\Exception\MissingDatabaseTablesException.
 */

namespace Drupal\usebb2drupal\Exception;

use \Exception;

/**
 * Exception upon wrong table prefix or missing database tables.
 */
class MissingDatabaseTablesException extends Exception { }
