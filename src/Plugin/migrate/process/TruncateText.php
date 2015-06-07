<?php

/**
 * @file
 * Contains \Drupal\usebb2drupal\Plugin\migrate\process\TruncateText.
 */

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Unicode;


/**
 * Truncates text to the specified length.
 *
 * @MigrateProcessPlugin(
 *   id = "usebb_truncate_text"
 * )
 */
class TruncateText extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * @see https://www.drupal.org/node/2279655
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = Unicode::truncate($value, $this->configuration['length']);
    return rtrim(preg_replace('/(?:<(?!.+>)|&(?!.+;)).*$/us', '', $value));
  }

}
