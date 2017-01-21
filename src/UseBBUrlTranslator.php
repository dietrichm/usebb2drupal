<?php

namespace Drupal\usebb2drupal;

use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\AlterableInterface;

/**
 * UseBB URL translator service class.
 */
class UseBBUrlTranslator implements UseBBUrlTranslatorInterface {
  protected $info;
  protected $migrationManager;
  protected $database;
  protected $queryFactory;
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(UseBBInfoInterface $info, MigrationPluginManagerInterface $migration_manager, Connection $database, QueryFactory $query_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->info = $info;
    $this->migrationManager = $migration_manager;
    $this->database = $database;
    $this->queryFactory = $query_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($migration_id, array &$context) {
    switch ($migration_id) {
      case 'usebb_forum':
        $entity_type = 'taxonomy_term';
        $field_name = 'description.value';
        break;

      case 'usebb_topic':
        $entity_type = 'node';
        $field_name = 'body.value';
        break;

      case 'usebb_post':
        $entity_type = 'comment';
        $field_name = 'comment_body.value';
        break;

      case 'usebb_user':
        $entity_type = 'user';
        $field_name = 'signature.value';
        break;

      default:
        return;
    }
    if (empty($context['sandbox'])) {
      $context['sandbox']['entity_ids'] = $this->getQuery($migration_id, $entity_type, $field_name)->execute();
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($context['sandbox']['entity_ids']);
    }
    if (empty($context['sandbox']['entity_ids'])) {
      $context['finished'] = 1;
      return;
    }
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $processed = 0;
    foreach ($storage->loadMultiple($context['sandbox']['entity_ids']) as $entity_id => $entity) {
      $this->updateEntity($entity, $field_name);
      $processed++;
      $context['sandbox']['progress']++;
      unset($context['sandbox']['entity_ids'][$entity_id]);
      if ($processed === 5) {
        $context['message'] = t('Translating internal links in @type @progress of @max.', [
          '@type' => str_replace(['usebb_', '_'], ['', ' '], $migration_id),
          '@progress' => $context['sandbox']['progress'],
          '@max' => $context['sandbox']['max'],
        ]);
        break;
      }
    }
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }

  /**
   * Create a query against the database for contents with untranslated links.
   *
   * @param string $migration_id
   *   Migration ID.
   * @param string $entity_type
   *   Entity type.
   * @param string $field_name
   *   Field to query for URLs.
   *
   * @return Drupal\Core\Entity\Query\QueryInterface
   *   Query.
   */
  protected function getQuery($migration_id, $entity_type, $field_name) {
    $query = $this->queryFactory->get($entity_type);
    $group = $query->orConditionGroup();
    foreach ($this->info->getPublicUrls() as $url) {
      $group->condition($field_name, '%' . $this->database->escapeLike('<a href="' . $url) . '%', 'LIKE');
    }
    return $query->condition($group)
      ->addTag('usebb2drupal_url_translator')
      ->addMetaData('usebb2drupal_migration', $migration_id);
  }

  /**
   * Alter the query to add a join upon the migration mapping tables.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   Select query for loading entities.
   */
  public function alterQuery(AlterableInterface $query) {
    $migration = $this->getMigrationInstance($query->getMetaData('usebb2drupal_migration'));
    $id_map_table = $migration->getIdMap()->mapTableName();
    $destid = 1;
    $join_condition = [];
    foreach (array_keys($migration->getDestinationPlugin()->getIds()) as $destination_id) {
      $field = $query->getFields()[$destination_id];
      $join_condition[] = $field['table'] . '.' . $field['field'] . ' = migrate_map.destid' . $destid++;
    }
    $query->join($id_map_table, 'migrate_map', implode(' AND ', $join_condition));
  }

  /**
   * Update URLs in an entity.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   Entity to update.
   * @param string $field_name
   *   Field name containing URLs.
   */
  protected function updateEntity(EntityInterface $entity, $field_name) {
    $entity->usebb_changed = clone $entity->changed;
    if (strpos($field_name, '.') !== FALSE) {
      list($field_name, $property) = explode('.', $field_name, 2);
      $value = $this->translateUrls($entity->$field_name->$property);
      $entity->$field_name->$property = $value;
    }
    else {
      $value = $this->translateUrls($entity->$field_name);
      $entity->$field_name = $value;
    }
    $entity->save();
  }

  /**
   * Translate URLs in a string.
   *
   * @param string $string
   *   String to translate URLs in.
   *
   * @return string
   *   String with updates URLs.
   */
  protected function translateUrls($string) {
    foreach ($this->info->getPublicUrls() as $url) {
      $url = preg_quote($url, '#');
      $string = preg_replace_callback('#<a href="(' . $url . '([^"]*))">(\1</a>)?#', function ($matches) {
        if ($new_path = $this->translatePath($matches[2])) {
          $new_url = base_path() . $new_path;
          return !empty($matches[3])
            ? '<a href="' . $new_url . '">' . $new_path . '</a>'
            : '<a href="' . $new_url . '">';
        }
        return $matches[0];
      }, $string);
    }
    return $string;
  }

  /**
   * Translate UseBB path to Drupal.
   *
   * @param string $path
   *   UseBB path to translate.
   *
   * @return string|null
   *   Drupal migrated path or NULL when not found.
   */
  protected function translatePath($path) {
    // Category.
    if ((preg_match('#^/index\-([0-9]+)\.html?#', $path, $matches) || preg_match('#^/index\.php\?cat=([0-9]+)#', $path, $matches))
        && ($id = $this->getMigratedId('category', $matches[1]))) {
      return 'forum/' . $id;
    }
    // Forum.
    if ((preg_match('#^/forum\-([0-9]+)(\-[0-9]+)?\.html?#', $path, $matches) || preg_match('#^/forum\.php\?id=([0-9]+)(&page=[0-9]+)?#', $path, $matches))
        && ($id = $this->getMigratedId('forum', $matches[1]))) {
      return 'forum/' . $id;
    }
    // Topic.
    if ((preg_match('#^/topic\-([0-9]+)(\-[0-9]+)?\.html?#', $path, $matches) || preg_match('#^/topic\.php\?id=([0-9]+)(&page=[0-9]+)?#', $path, $matches))
        && ($id = $this->getMigratedId('topic', $matches[1]))) {
      return 'node/' . $id;
    }
    // Post.
    if (preg_match('#^/topic\-post([0-9]+)\.html?#', $path, $matches) || preg_match('#^/topic\.php\?post=([0-9]+)#', $path, $matches)) {
      // Post that was migrated to a comment.
      if ($id = $this->getMigratedId('post', $matches[1])) {
        return 'comment/' . $id . '#comment-' . $id;
      }
      // First post included in a topic row and not migrated to a comment.
      if (($topic_id = $this->info->getTopicFromPost($matches[1])) && ($id = $this->getMigratedId('topic', $topic_id))) {
        return 'node/' . $id;
      }
    }
    // Profile.
    if ((preg_match('#^/profile\-([0-9]+)\.html?#', $path, $matches) || preg_match('#^/profile\.php\?id=([0-9]+)#', $path, $matches))
        && ($id = $this->getMigratedId('user', $matches[1]))) {
      return 'user/' . $id;
    }
    // Forum index.
    if (empty($path) || $path === '/' || preg_match('#^/index\.(html|php)#', $path)) {
      return 'forum';
    }
    return NULL;
  }

  /**
   * Get the migrated (destination) ID for a migration row.
   *
   * @param string $migration_id
   *   Migration ID without `usebb_`.
   * @param int $source_id
   *   Source ID.
   *
   * @return int|null
   *   Migrated (destination) ID or NULL when not found.
   */
  protected function getMigratedId($migration_id, $source_id) {
    $ids = $this->getMigrationInstance('usebb_' . $migration_id)->getIdMap()
      ->lookupDestinationId([(int) $source_id]);
    if (empty($ids)) {
      return NULL;
    }
    return (int) reset($ids);
  }

  /**
   * Load a migration plugin instance.
   *
   * @param string $migration_id
   *   Migration ID.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   Migration plugin instance.
   */
  protected function getMigrationInstance($migration_id) {
    static $instances = [];
    if (!isset($instances[$migration_id])) {
      $instances[$migration_id] = $this->migrationManager->createInstance($migration_id);
    }
    return $instances[$migration_id];
  }

}
