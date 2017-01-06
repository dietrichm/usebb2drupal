<?php

namespace Drupal\usebb2drupal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\usebb2drupal\Exception\InvalidSourcePathException;
use Drupal\usebb2drupal\Exception\InvalidConfigFileException;
use Drupal\usebb2drupal\Exception\MissingDatabaseTablesException;
use Drupal\usebb2drupal\Exception\MissingLanguagesException;
use \PDOException;

/**
 * UseBB migrate form.
 */
class MigrateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'usebb2drupal_migrate';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (Unicode::getStatus() !== Unicode::STATUS_MULTIBYTE) {
      drupal_set_message($this->t('This version of PHP does not support multibyte encodings.'), 'error');
      return $form;
    }

    $form['intro'] = [
      '#markup' => $this->t("Specify the contents to migrate and the path to the UseBB installation and click 'Start migration'. Existing Drupal content and users will not be modified or removed. Additionally, provide the public URLs to the UseBB forum so that internal links can be translated."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    if (!\Drupal::moduleHandler()->moduleExists('signature')) {
      $form['signatures'] = [
        '#markup' => $this->t('To migrate user signatures, download and enable the <a href=":url">signature module</a> <em>before</em> starting the migration.', [':url' => 'https://www.drupal.org/project/signature']),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    $form['migrate_types'] = [
      '#type' => 'radios',
      '#title' => $this->t('Migrate'),
      '#options' => [
        'all' => $this->t('Structure, content and users.'),
        'users' => $this->t('Users only.'),
      ],
      '#default_value' => 'all',
    ];

    $form['source_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UseBB installation directory'),
      '#required' => TRUE,
      '#description' => $this->t('The absolute directory on the server where UseBB is installed.'),
      '#default_value' => \Drupal::state()->get('usebb2drupal.source_path'),
    ];

    $public_urls = \Drupal::state()->get('usebb2drupal.public_urls', []);
    $form['public_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Public UseBB forum URLs'),
      '#description' => $this->t('Public URLs on which the UseBB forum can or could be accessed. Only fill in the URL(s) to the forum index. This will correct internal URLs in forum descriptions, topics, posts and user signatures to the new Drupal URLs. Leaving this empty disables the URL correction.'),
      '#default_value' => implode("\n", $public_urls),
      '#rows' => !empty($public_urls) ? count($public_urls) + 1 : 3,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start migration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $source_path = $form_state->getValue('source_path');
    \Drupal::state()->set('usebb2drupal.source_path', $source_path);
    try {
      $info = \Drupal::service('usebb2drupal.info');
      $info->getDatabase();
      $info->getLanguages();
    }
    catch (InvalidSourcePathException $e) {
      $form_state->setError($form['source_path'], $this->t('The source path %source_path does not exist or has no readable config.php.', ['%source_path' => $source_path]));
    }
    catch (InvalidConfigFileException $e) {
      $form_state->setError($form['source_path'], $this->t('The config.php file contains no UseBB configuration.'));
    }
    catch (PDOException $e) {
      $form_state->setError($form['source_path'], $this->t('Unable to access the database with the credentials specified in config.php.'));
    }
    catch (MissingDatabaseTablesException $e) {
      $form_state->setError($form['source_path'], $this->t('No UseBB database tables were found, or the defined table prefix is wrong.'));
    }
    catch (MissingLanguagesException $e) {
      $form_state->setError($form['source_path'], $this->t('The language files are not present in the UseBB directory.'));
    }

    if ($public_urls = $form_state->getValue('public_urls')) {
      foreach (preg_split('#[\r\n]+#', $public_urls) as $public_url) {
        if (!UrlHelper::isValid($public_url, TRUE)) {
          $form_state->setError($form['public_urls'], $this->t('The public URL %url is not a valid URL.', ['%url' => $public_url]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($public_urls = $form_state->getValue('public_urls')) {
      $public_urls = array_unique(array_map(function ($url) {
        $url = preg_replace('#index\.(html|php)$#', '', $url);
        if (substr($url, -1) === '/') {
          $url = substr($url, 0, -1);
        }
        return $url;
      }, preg_split('#[\r\n]+#', $public_urls)));
    }
    else {
      $public_urls = [];
    }
    \Drupal::state()->set('usebb2drupal.public_urls', $public_urls);

    switch ($form_state->getValue('migrate_types')) {
      case 'all':
        $migration_list = [
          'usebb_user',
          'usebb_user_contact',
          'usebb_category',
          'usebb_forum',
          'usebb_topic',
          'usebb_post',
        ];
        $url_translation_migrations = [
          'usebb_forum',
          'usebb_topic',
          'usebb_post',
        ];
        $form_state->setRedirect('forum.overview');
        break;

      case 'users':
      default:
        $migration_list = [
          'usebb_user',
          'usebb_user_contact',
        ];
        $url_translation_migrations = [];
        $form_state->setRedirect('entity.user.collection');
    }

    if (\Drupal::service('usebb2drupal.info')->getConfig('enable_ip_bans')) {
      $migration_list[] = 'usebb_ban';
    }
    else {
      drupal_set_message(t('Since IP address banning is disabled in the UseBB configuration, no IP address bans have been migrated.'));
    }

    $batch = [
      'title' => t('Migrating UseBB'),
      'operations' => array_map(function ($migration_id) {
        return [
          ['Drupal\usebb2drupal\MigrateBatch', 'run'],
          [$migration_id],
        ];
      }, $migration_list),
      'finished' => ['Drupal\usebb2drupal\MigrateBatch', 'finished'],
    ];

    if (!empty($public_urls)) {
      if (\Drupal::moduleHandler()->moduleExists('signature')) {
        $url_translation_migrations[] = 'usebb_user';
      }
      foreach ($url_translation_migrations as $migration_id) {
        $batch['operations'][] = [
          ['Drupal\usebb2drupal\MigrateBatch', 'translateUrls'],
          [$migration_id],
        ];
      }
    }

    batch_set($batch);
  }

}
