<?php

namespace Drupal\usebb2drupal;

use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\AlterableInterface;

/**
 * UseBB URL translator service interface.
 */
interface UseBBUrlTranslatorInterface {

  /**
   * Construct a UseBB URL translator service.
   *
   * @param UseBBInfoInterface $info
   *   The UseBB info service.
   * @param Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_manager
   *   The migration plugin manager.
   * @param Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The query factory.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(UseBBInfoInterface $info, MigrationPluginManagerInterface $migration_manager, Connection $database, QueryFactory $query_factory, EntityTypeManagerInterface $entity_type_manager);

  /**
   * Translate internal UseBB URLs in a migrated content type.
   *
   * @param string $migration_id
   *   Migration ID.
   * @param array $context
   *   Batch context array.
   */
  public function execute($migration_id, array &$context);

  /**
   * Alter the query to add a join upon the migration mapping tables.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   Select query for loading entities.
   */
  public function alterQuery(AlterableInterface $query);

}
