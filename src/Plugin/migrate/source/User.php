<?php

namespace Drupal\usebb2drupal\Plugin\migrate\source;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\usebb2drupal\UseBBInfoInterface;

/**
 * UseBB users source from database.
 *
 * @MigrateSource(
 *   id = "usebb_user"
 * )
 */
class User extends SqlBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\usebb2drupal\UseBBInfoInterface
   */
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

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('members', 'm')
      ->fields('m', [
        'id',
        'name',
        'email',
        'email_show',
        'passwd',
        'regdate',
        'level',
        'active',
        'banned',
        'last_login',
        'last_pageview',
        'language',
        'timezone',
        'real_name',
        'signature',
        'birthday',
        'location',
        'website',
        'occupation',
        'interests',
        'msnm',
        'yahoom',
        'aim',
        'icq',
        'jabber',
        'skype',
      ]);
    $query->orderBy('m.id', 'ASC');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Last_login as fallback to last_pageview.
    if (empty($row->getSourceProperty('last_pageview'))) {
      $row->setSourceProperty('last_pageview', $row->getSourceProperty('last_login'));
    }
    // Active = active && !banned.
    $row->setSourceProperty('active', $row->getSourceProperty('active') && !$row->getSourceProperty('banned'));
    // Birthday in proper YYYY-MM-DD format.
    if ($birthday = $row->getSourceProperty('birthday')) {
      $year = substr($birthday, 0, 4);
      $month = substr($birthday, 4, 2);
      $day = substr($birthday, 6, 2);
      $row->setSourceProperty('birthday', sprintf('%04d-%02d-%02d', $year, $month, $day));
    }
    else {
      $row->setSourceProperty('birthday', NULL);
    }
    // Language name to langcode.
    $langcode = $this->info->getLanguageCode($row->getSourceProperty('language'));
    $row->setSourceProperty('language', $langcode);
    // Create the language if not already created.
    if (!entity_load('configurable_language', $langcode)) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }
    // Roles.
    $roles = [
      ['target_id' => 'migrated_usebb_user'],
    ];
    switch ($row->getSourceProperty('level')) {
      case 3:
        $roles[] = ['target_id' => 'administrator'];
    }
    $row->setSourceProperty('roles', $roles);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('User ID.'),
      'name' => $this->t('User name.'),
      'email' => $this->t('E-mail address.'),
      'email_show' => $this->t('Show e-mail address.'),
      'passwd' => $this->t('MD5 password hash.'),
      'regdate' => $this->t('Registration date.'),
      'level' => $this->t('User level.'),
      'active' => $this->t('Activation status.'),
      'banned' => $this->t('Banned status.'),
      'last_login' => $this->t('Last login date.'),
      'last_pageview' => $this->t('Last page view date.'),
      'language' => $this->t('Language.'),
      'timezone' => $this->t('Timezone.'),
      'real_name' => $this->t('Real name.'),
      'signature' => $this->t('Signature.'),
      'birthday' => $this->t('Birthday.'),
      'location' => $this->t('Location.'),
      'website' => $this->t('Website.'),
      'occupation' => $this->t('Occupation.'),
      'interests' => $this->t('Interests.'),
      'msnm' => $this->t('Windows Live Messenger.'),
      'yahoom' => $this->t('Yahoo! Messenger.'),
      'aim' => $this->t('AIM.'),
      'icq' => $this->t('ICQ.'),
      'jabber' => $this->t('Jabber/XMPP.'),
      'skype' => $this->t('Skype.'),
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
