<?php

/**
 * @file
 * Contains \Drupal\usebb2drupal\Plugin\migrate\process\SplitValues.
 */

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Split value with separators into distinct values.
 *
 * @MigrateProcessPlugin(
 *   id = "usebb_split_values"
 * )
 */
class SplitValues extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return array_map(function($value) {
      return ['value' => trim($value)];
    }, preg_split('#\s*,\s*#', $value));
  }

}
