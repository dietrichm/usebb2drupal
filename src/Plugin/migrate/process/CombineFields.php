<?php

/**
 * @file
 * Contains \Drupal\usebb2drupal\Plugin\migrate\process\CombineFields.
 */

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Combine fields into single field.
 *
 * @MigrateProcessPlugin(
 *   id = "usebb_combine_fields"
 * )
 */
class CombineFields extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $fields = $this->configuration['fields'];
    return array_filter(array_map(function($key) use($fields, $row) {
      $value = $row->getSourceProperty($key);
      if (empty($value)) {
        return NULL;
      }
      return ['value' => sprintf('%s: %s', $fields[$key], $value)];
    }, array_keys($fields)));
  }

}
