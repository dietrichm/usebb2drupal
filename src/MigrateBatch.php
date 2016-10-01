<?php

namespace Drupal\usebb2drupal;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigrateMapSaveEvent;

/**
 * UseBB migration batch.
 *
 * Partially based upon Drupal\migrate_upgrade\MigrateUpgradeRunBatch.
 */
class MigrateBatch {

  /**
   * The processed items for one batch of a given migration.
   *
   * @var int
   */
  protected static $numProcessed = 0;

  /**
   * Ensure we only add the listeners once per request.
   *
   * @var bool
   */
  protected static $listenersAdded = FALSE;

  /**
   * The maximum length in seconds to allow processing in a request.
   *
   * @var int
   */
  protected static $maxExecTime;

  /**
   * Run a single migration batch.
   *
   * @param string $migration_id
   *   Migration ID to run.
   * @param array $context
   *   Batch context array.
   */
  public static function run($migration_id, array &$context) {
    if (!static::$listenersAdded) {
      // Ensure database connection is loaded.
      \Drupal::service('usebb2drupal.info')->getDatabase();

      // Register event listeners.
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->addListener(MigrateEvents::POST_ROW_SAVE, [get_class(), 'onPostRowSave']);
      $event_dispatcher->addListener(MigrateEvents::MAP_SAVE, [get_class(), 'onMapSave']);

      // Set max execution time.
      static::$maxExecTime = ini_get('max_execution_time');
      if (static::$maxExecTime <= 0) {
        static::$maxExecTime = 60;
      }
      // Set an arbitrary threshold of 3 seconds (e.g., if max_execution_time is
      // 45 seconds, we will quit at 42 seconds so a slow item or cleanup
      // overhead don't put us over 45).
      static::$maxExecTime -= 3;

      static::$listenersAdded = TRUE;
    }

    // Number processed in this batch.
    static::$numProcessed = 0;

    $type = str_replace(['usebb_', '_'], ['', ' '], $migration_id);
    $migration = \Drupal::service('plugin.manager.migration')->createInstance($migration_id);
    $source = $migration->getSourcePlugin();
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $source->count();
    }
    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $result = $executable->import();
    if ($result === MigrationInterface::RESULT_INCOMPLETE) {
      $context['sandbox']['progress'] += static::$numProcessed;
      $context['message'] = t('Migrating @type @progress of @max.', [
        '@type' => $type,
        '@progress' => $context['sandbox']['progress'],
        '@max' => $context['sandbox']['max'],
      ]);
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
    else {
      $context['finished'] = 1;
      $context['results'][$type] = $result;
    }
  }

  /**
   * Implementation of the Batch API finished method.
   */
  public static function finished($success, $results, $operations, $elapsed) {
    if ($success) {
      drupal_set_message(t('Executed UseBB migrations for %types.', ['%types' => implode(', ', array_keys($results))]));

      $with_errors = array_filter(array_keys($results), function($type) use($results) {
        return $results[$type] !== MigrationInterface::RESULT_COMPLETED;
      });
      if (count($with_errors)) {
        drupal_set_message(t('The following migration(s) were not completed: %types.', ['%types' => implode(', ', $with_errors)]), 'warning');
      }
    }
    else {
      drupal_set_message(t('A fatal error occurred.'), 'error');
    }
  }

  /**
   * React to item import.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post-save event.
   */
  public static function onPostRowSave(MigratePostRowSaveEvent $event) {
    // We want to interrupt this batch and start a fresh one.
    if ((time() - REQUEST_TIME) > static::$maxExecTime) {
      $event->getMigration()->interruptMigration(MigrationInterface::RESULT_INCOMPLETE);
    }
  }

  /**
   * Count up any map save events.
   *
   * @param \Drupal\migrate\Event\MigrateMapSaveEvent $event
   *   The map event.
   */
  public static function onMapSave(MigrateMapSaveEvent $event) {
    static::$numProcessed++;
  }

}
