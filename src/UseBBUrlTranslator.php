<?php

namespace Drupal\usebb2drupal;

use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Connection;

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
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $query = $this->getQuery($entity_type, $field_name);
    if (empty($context['sandbox'])) {
      $count_query = clone $query;
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = $count_query->count()->execute();
    }
    $query->range(0, 5);
    $entity_ids = $query->execute();
    if (empty($entity_ids)) {
      $context['finished'] = 1;
      return;
    }
    foreach ($storage->loadMultiple($entity_ids) as $entity) {
      $this->updateEntity($entity, $field_name);
    }
    $context['sandbox']['progress'] += count($entity_ids);
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }

  /**
   * Create a query against the database for contents with untranslated links.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $field_name
   *   Field to query for URLs.
   *
   * @return Drupal\Core\Entity\Query\QueryInterface
   *   Query.
   */
  protected function getQuery($entity_type, $field_name) {
    $query = $this->queryFactory->get($entity_type);
    $group = $query->orConditionGroup();
    foreach ($this->info->getPublicUrls() as $url) {
      $group->condition($field_name, '%' . $this->database->escapeLike('<a href="' . $url) . '%', 'LIKE');
    }
    $query->condition($group);
    return $query;
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
    if ((preg_match('#^/forum\-([0-9]+)\.html?#', $path, $matches) || preg_match('#^/forum\.php\?id=([0-9]+)#', $path, $matches))
        && ($id = $this->getMigratedId('forum', $matches[1]))) {
      return 'forum/' . $id;
    }
    // Topic.
    // TODO: pages?
    if ((preg_match('#^/topic\-([0-9]+)\.html?#', $path, $matches) || preg_match('#^/topic\.php\?id=([0-9]+)#', $path, $matches))
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
    $ids = $this->migrationManager->createInstance('usebb_' . $migration_id)
      ->getIdMap()->lookupDestinationIds([(int) $source_id]);
    if (empty($ids) || empty($ids[0])) {
      return NULL;
    }
    return (int) $ids[0][0];
  }

}
