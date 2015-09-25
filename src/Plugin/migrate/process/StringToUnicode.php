<?php

/**
 * @file
 * Contains \Drupal\usebb2drupal\Plugin\migrate\process\StringToUnicode.
 */

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Unicode;
use Drupal\usebb2drupal\UseBBInfoInterface;

/**
 * Convert string with HTML entities to Unicode.
 *
 * @MigrateProcessPlugin(
 *   id = "usebb_string_to_unicode"
 * )
 */
class StringToUnicode extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\usebb2drupal\UseBBInfoInterface
   */
  protected $info;

  /**
   * Constructs a StringToUnicode plugin.
   *
   * @param array $configuration
   *  The plugin configuration.
   * @param string $plugin_id
   *  The plugin ID.
   * @param mixed $plugin_definition
   *  The plugin definition.
   * @param \Drupal\usebb2drupal\UseBBInfoInterface $info
   *  The UseBB info service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, UseBBInfoInterface $info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->info = $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('usebb2drupal.info')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/html_entity_decode
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Attempt conversion - use when succeeded, otherwise ignore.
    // Also ignore when resulting Unicode string has more chars than original,
    // in which case the post already was in Unicode.
    $charset = $this->info->getEncoding();
    if (($unicode_string = Unicode::convertToUtf8($value, $charset)) !== FALSE
        && Unicode::strlen($value) >= Unicode::strlen($unicode_string)) {
      $value = $unicode_string;
    }
    // Replace &#...; with Unicode character. Requires mbstring.
    if (strpos($value, '&#') !== FALSE && function_exists('mb_convert_encoding')) {
      $value = preg_replace_callback('/(&#[0-9]+;)/', function ($m) {
        return mb_convert_encoding($m[1], 'UTF-8', 'HTML-ENTITIES');
      }, $value);
    }
    return $value;
  }

}
