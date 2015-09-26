<?php

/**
 * @file
 * Contains \Drupal\usebb2drupal\Form\MigrateForm.
 */

namespace Drupal\usebb2drupal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\usebb2drupal\Utilities\UseBBInfo;
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
      '#markup' => $this->t('Specify the contents to migrate and the path to the UseBB installation and click \'Start migration\'. Existing Drupal content and users will not be modified or removed.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

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
      '#description' => $this->t('The absolute directory where UseBB is installed.'),
      '#default_value' => \Drupal::state()->get('usebb2drupal.source_path'),
    ];

    $form['actions'] = [
      '#type' => 'actions'
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
        $form_state->setRedirect('forum.overview');
        break;

      case 'users':
      default:
        $migration_list = [
          'usebb_user',
          'usebb_user_contact',
        ];
        $form_state->setRedirect('entity.user.collection');
    }
    module_load_include('inc', 'usebb2drupal', 'usebb2drupal.batch');
    $batch = usebb2drupal_migrate_batch_build($migration_list);
    batch_set($batch);
  }

}
