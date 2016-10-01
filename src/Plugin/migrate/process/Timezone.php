<?php

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transform UseBB timezone to Drupal one.
 *
 * @MigrateProcessPlugin(
 *   id = "usebb_timezone"
 * )
 */
class Timezone extends ProcessPluginBase {

  /**
   * UseBB timezones.
   *
   * @var array
   */
  protected static $timezones;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!isset(static::$timezones)) {
      $timezones = ['0' => 'UTC'];
      foreach (\DateTimeZone::listIdentifiers() as $zone) {
        $now = new \DateTime(NULL, new \DateTimeZone($zone));
        $offset = (string) ($now->getOffset() / 3600);
        if (!isset($timezones[$offset])) {
          $timezones[$offset] = $zone;
        }
      }
      static::$timezones = $timezones;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = (string) $value;
    return isset(static::$timezones[$value]) ? static::$timezones[$value] : 'UTC';
  }

}
