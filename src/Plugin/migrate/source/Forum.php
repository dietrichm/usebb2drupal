<?php

namespace Drupal\usebb2drupal\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * UseBB forums source from database.
 *
 * @MigrateSource(
 *   id = "usebb_forum"
 * )
 */
class Forum extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('forums', 'f')
      ->fields('f', ['id', 'name', 'cat_id', 'descr', 'sort_id']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('Forum ID.'),
      'name' => $this->t('Forum name.'),
      'cat_id' => $this->t('Parent category ID.'),
      'descr' => $this->t('Forum description.'),
      'sort_id' => $this->t('Sort ID (weight).'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'integer';
    return $ids;
  }

}
