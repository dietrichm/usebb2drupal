<?php

/**
 * @file
 * Contains \Drupal\usebb2drupal\Plugin\migrate\process\Website.
 */

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transform UseBB website to Drupal one.
 *
 * @MigrateProcessPlugin(
 *   id = "usebb_website"
 * )
 */
class Website extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    if (!($url = parse_url($value)) || empty($url['host'])) {
      return NULL;
    }
    $host = $url['host'];
    return [
      'uri' => $value,
      'title' => strpos($host, 'www.') === 0 ? substr($host, 4) : $host,
      'options' => [],
    ];
  }

}
