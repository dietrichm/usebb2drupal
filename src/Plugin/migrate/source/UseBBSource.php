<?php

namespace Drupal\usebb2drupal\Plugin\migrate\source;

use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\usebb2drupal\UseBBInfoInterface;

/**
 * Abstract UseBB source injecting the UseBB2Drupal info service.
 */
abstract class UseBBSource extends SqlBase {

  protected $info;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, UseBBInfoInterface $info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->info = $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('usebb2drupal.info')
    );
  }

}
