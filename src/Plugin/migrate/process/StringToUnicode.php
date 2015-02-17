<?php

/**
 * @file
 * Contains \Drupal\usebb2drupal\Plugin\migrate\process\StringToUnicode.
 */

namespace Drupal\usebb2drupal\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Unicode;


/**
 * Convert string with HTML entities to Unicode.
 *
 * @MigrateProcessPlugin(
 *   id = "usebb_string_to_unicode"
 * )
 */
class StringToUnicode extends ProcessPluginBase {

  /**
   * Source character set.
   *
   * @var string
   */
  protected static $charset;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (!isset(static::$charset)) {
      static::$charset = \Drupal::state()->get('usebb2drupal.string_to_unicode_charset', 'ISO-8859-1');
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see http://php.net/html_entity_decode
   */
  public function transform($value, MigrateExecutable $migrate_executable, Row $row, $destination_property) {
    // Attempt conversion - use when succeeded, otherwise ignore.
    // Also ignore when resulting Unicode string has more chars than original,
    // in which case the post already was in Unicode.
    if (($unicode_string = Unicode::convertToUtf8($value, static::$charset)) !== FALSE
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
