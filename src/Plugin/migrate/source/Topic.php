<?php

namespace Drupal\usebb2drupal\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * UseBB topics source from database.
 *
 * @MigrateSource(
 *   id = "usebb_topic"
 * )
 */
class Topic extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('topics', 't')
      ->fields('t', [
        'id',
        'forum_id',
        'topic_title',
        'status_locked',
        'status_sticky',
      ])
      ->fields('p', [
        'poster_id',
        'content',
        'post_time',
        'post_edit_time',
        'post_edit_by',
        'enable_bbcode',
        'enable_html',
      ]);
    $query->join('posts', 'p', 'p.id = t.first_post_id');
    $query->orderBy('t.id', 'ASC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('Topic ID.'),
      'forum_id' => $this->t('Forum ID.'),
      'topic_title' => $this->t('Topic title.'),
      'status_locked' => $this->t('Locked status.'),
      'status_sticky' => $this->t('Sticky status.'),
      'poster_id' => $this->t('User ID.'),
      'content' => $this->t('Content.'),
      'post_time' => $this->t('Created date.'),
      'post_edit_time' => $this->t('Changed date.'),
      'post_edit_by' => $this->t('Last edit by user.'),
      'enable_bbcode' => $this->t('Enable BBCode.'),
      'enable_html' => $this->t('Enable HTML.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'integer';
    $ids['id']['alias'] = 't';
    return $ids;
  }

}
