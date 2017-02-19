<?php

namespace Drupal\usebb2drupal\Plugin\migrate\source;

/**
 * UseBB IP address bans source from database.
 *
 * @MigrateSource(
 *   id = "usebb_ban"
 * )
 */
class Ban extends UseBBSource {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('bans', 'b')
      ->fields('b', ['id', 'ip_addr'])
      ->condition('b.ip_addr', '', '<>')
      ->condition('b.ip_addr', '%*%', 'NOT LIKE');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('Ban ID.'),
      'ip_addr' => $this->t('Banned IP address.'),
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
