<?php

/**
 * @file
 * Migration batch definition and execution functions.
 */

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Entity\MigrationInterface;

/**
 * Build an import batch for UseBB forum.
 *
 * @param array $migration_list
 *   List of migrations to run.
 *
 * @return array
 *   Batch structure.
 */
function usebb2drupal_migrate_batch_build(array $migration_list) {
  $batch = [
    'title' => t('Migrating UseBB'),
    'operations' => array_map(function($migration_id) {
      return ['usebb2drupal_migrate_batch_migrate_operation', [$migration_id]];
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
 * @param array $context
 *   Batch context array.
 */
function usebb2drupal_migrate_batch_migrate_operation($migration_id, array &$context) {
  // Ensure database connection is loaded.
  \Drupal::service('usebb2drupal.info')->getDatabase();

  $type = str_replace(['usebb_', '_'], ['', ' '], $migration_id);
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
