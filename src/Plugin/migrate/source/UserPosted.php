<?php

namespace Drupal\usebb2drupal\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Row;

/**
 * User posted UseBB data.
 */
abstract class UserPosted extends UseBBSource {

  /**
   * Add guest poster info.
   *
   * @param Drupal\Core\Database\Query\SelectInterface $query
   *   Select query.
   *
   * @return Drupal\Core\Database\Query\SelectInterface
   *   Select query.
   */
  protected function addGuestInfo(SelectInterface $query) {
    if (!$this->info->isMigrated('user')) {
      // Load poster username so it can be used as poster_guest in prepareRow().
      $query->leftJoin('members', 'm', 'm.id = p.poster_id');
      $query->fields('m', ['name']);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!$this->info->isMigrated('user')) {
      if ($row->getSourceProperty('poster_id')) {
        // Force post as guest with username as guest name.
        $row->setSourceProperty('poster_id', '0');
        $row->setSourceProperty('poster_guest', $row->getSourceProperty('name'));
      }
      $row->setSourceProperty('post_edit_by', '0');
    }
    return parent::prepareRow($row);
  }

}
