<?php

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Calculate post edit time from edit or creation timestamp.
 *
 * @MigrateProcessPlugin(
 *   id = "usebb_post_edit_time"
 * )
 */
class PostEditTime extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value) && $row->hasSourceProperty('post_time')) {
      $value = $row->getSourceProperty('post_time');
    }
    return $value;
  }

}
