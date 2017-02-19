<?php

namespace Drupal\usebb2drupal\Plugin\migrate\source;

/**
 * UseBB posts source from database.
 *
 * @MigrateSource(
 *   id = "usebb_post"
 * )
 */
class Post extends UserPosted {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('posts', 'p')
      ->fields('p', [
        'id',
        'topic_id',
        'poster_id',
        'poster_guest',
        'poster_ip_addr',
        'content',
        'post_time',
        'post_edit_time',
        'post_edit_by',
        'enable_bbcode',
        'enable_html',
      ]);
    $query->join('topics', 't', 't.id = p.topic_id AND t.first_post_id != p.id');
    $query->orderBy('p.id', 'ASC');
    return $this->addGuestInfo($query);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('Post ID.'),
      'topic_id' => $this->t('Topic ID.'),
      'poster_id' => $this->t('User ID.'),
      'poster_guest' => $this->t('Guest name.'),
      'poster_ip_addr' => $this->t('User/guest IP address.'),
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
    $ids['id']['alias'] = 'p';
    return $ids;
  }

}
