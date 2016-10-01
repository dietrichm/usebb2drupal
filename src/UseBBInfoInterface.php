<?php

namespace Drupal\usebb2drupal;

use Drupal\Core\State\StateInterface;

/**
 * UseBB info service interface.
 */
interface UseBBInfoInterface {
  /**
   * Construct a UseBB info service.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @throw \Drupal\usebb2drupal\Exception\InvalidSourcePathException
   *   When the source path is invalid.
   * @throw \Drupal\usebb2drupal\Exception\InvalidConfigFileException
   *   When the config file is invalid.
   */
  public function __construct(StateInterface $state);

  /**
   * Get the database connection.
   *
   * @return \Drupal\Core\Database\Connection
   *   UseBB database connection
   * @throw \PDOException
   *   When a database connection could not be made.
   * @throw \Drupal\usebb2drupal\Exception\MissingDatabaseTablesException
   *   When the database tables are missing or the prefix is wrong.
   */
  public function getDatabase();

  /**
   * Get a UseBB config value.
   *
   * @param string $key
   *   Config key
   * @return mixed
   *   Config value
   */
  public function getConfig($key);

  /**
   * Get the array of languages available.
   *
   * @return array
   *   (Full) language name to language info (character_encoding, language_code,
   *   text_direction).
   * @throws \Drupal\usebb2drupal\Exception\MissingLanguagesException
   *   When the language files are missing.
   */
  public function getLanguages();

  /**
   * Get the language code for a language or the default one.
   *
   * @param string $language
   *   Language name (optional)
   * @return string
   *   Language code
   */
  public function getLanguageCode($language = NULL);

  /**
   * Get the encoding for a language or the default one.
   *
   * @param string $language
   *   Language name (optional)
   * @return string
   *   Encoding
   */
  public function getEncoding($language = NULL);
}
