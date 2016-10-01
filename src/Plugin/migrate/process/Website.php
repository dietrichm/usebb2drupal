<?php

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
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
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
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
