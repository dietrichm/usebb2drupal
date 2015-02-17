<?php

/**
 * @file
 * Contains \Drupal\usebb2drupal\Plugin\migrate\source\Category.
 */

namespace Drupal\usebb2drupal\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * UseBB categories source from database.
 *
 * @MigrateSource(
 *   id = "usebb_category"
 * )
 */
class Category extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('cats', 'c')
      ->fields('c', ['id', 'name', 'sort_id']);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('Category ID.'),
      'name' => $this->t('Category name.'),
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
