<?php

/**
 * @file
 * Migration batch definition and execution functions.
 */

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\Core\Database\Database;

/**
 * Build an import batch for UseBB forum.
 *
 * @param array $migration_list
 *   List of migrations to run.
 * @param array $db_spec
 *   UseBB database connection specification.
 *
 * @return array
 *   Batch structure.
 */
function usebb2drupal_migrate_batch_build(array $migration_list, array $db_spec) {
  $batch = [
    'title' => t('Migrating UseBB'),
    'operations' => array_map(function($migration_id) use($db_spec) {
      return ['usebb2drupal_migrate_batch_migrate_operation', [$migration_id, $db_spec]];
    }, $migration_list),
    'finished' => 'usebb2drupal_migrate_batch_finished',
    'file' => drupal_get_path('module', 'usebb2drupal') . '/usebb2drupal.batch.inc',
  ];
  return $batch;
}

/**
 * Batch operation: run a migration.
 *
 * @param string $migration_id
 *   Migration ID to run.
 * @param array $db_spec
 *   Database info to connect to the migrated database.
 * @param array $context
 *   Batch context array.
 */
function usebb2drupal_migrate_batch_migrate_operation($migration_id, array $db_spec, array &$context) {
  $type = str_replace(['usebb_', '_'], ['', ' '], $migration_id);
  Database::addConnectionInfo('migrate', 'default', $db_spec);
  $migration = entity_load('migration', $migration_id);
  $source = $migration->getSourcePlugin();
  if (empty($context['sandbox'])) {
    $context['sandbox']['progress'] = 0;
    $context['sandbox']['max'] = $source->count();
  }
  $executable = new MigrateExecutable($migration, new MigrateMessage());
  $result = $executable->import();
  if ($result === MigrationInterface::RESULT_INCOMPLETE) {
    $context['sandbox']['progress'] += $source->getProcessed();
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
 * Batch finished callback.
 *
 * @param bool $success
 *   Whether batch was successful.
 * @param array $results
 *   Results.
 * @param array $operations
 *   Operations.
 */
function usebb2drupal_migrate_batch_finished($success, array $results, array $operations) {
  \Drupal::state()->delete('usebb2drupal.string_to_unicode_charset');
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
