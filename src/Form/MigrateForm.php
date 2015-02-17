<?php

/**
 * @file
 * Contains \Drupal\usebb2drupal\Form\MigrateForm.
 */

namespace Drupal\usebb2drupal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Database;

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
      '#markup' => $this->t('Import UseBB forum structure, content and users by filling in the connection settings to the UseBB database and clicking \'Start migration\'. Existing Drupal content and users will not be modified or removed.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $form['connection'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Connection settings'),
    ];
    $form['connection']['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#default_value' => 'localhost',
      '#required' => TRUE,
    ];
    $form['connection']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#required' => TRUE,
    ];
    $form['connection']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
    ];
    $form['connection']['database'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Database name'),
      '#required' => TRUE,
    ];
    $form['connection']['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table prefix'),
      '#default_value' => 'usebb_',
    ];

    $form['source_encoding'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source content encoding'),
      '#default_value' => 'ISO-8859-1',
      '#required' => TRUE,
      '#description' => $this->t('Specify the base encoding primarily used on the UseBB set-up. This can be found in the translations file.'),
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
    $values = $form_state->getValues();
    $db_spec = [
      'driver' => 'mysql',
      'database' => $values['database'],
      'username' => $values['username'],
      'password' => $values['password'],
      'host' => $values['host'],
      'prefix' => $values['prefix'],
    ];
    Database::addConnectionInfo('migrate', 'default', $db_spec);
    try {
      $conn = Database::getConnection('default', 'migrate');
      if (!$conn->schema()->tableExists('members')) {
        $form_state->setError($form['connection']['prefix'], $this->t('No user table found. Please check the table prefix.'));
      }
    }
    catch (\Exception $e) {
      $form_state->setError($form['connection'], $this->t('There was an error connecting to the UseBB database: %error. Please check the connection settings.', ['%error' => $e->getMessage()]));
    }
    if (function_exists('mb_list_encodings') && !in_array($values['source_encoding'], mb_list_encodings())) {
      $form_state->setError($form['source_encoding'], $this->t('Specified encoding %encoding does not exist or is not supported.', ['%encoding' => $values['source_encoding']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::state()->set('usebb2drupal.string_to_unicode_charset', $form_state->getValue('source_encoding'));
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
    $batch = usebb2drupal_migrate_batch_build($migration_list, Database::getConnectionInfo('migrate')['default']);
    batch_set($batch);
  }

}
