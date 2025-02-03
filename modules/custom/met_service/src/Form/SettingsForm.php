<?php

declare(strict_types=1);

namespace Drupal\met_service\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure MET Service settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'met_service_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['met_service.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['websocket'] = [
      '#type' => 'fieldset',
      '#title' => t('TMS Websocket Connection'),
    // Added.
      '#collapsible' => TRUE,
    // Added.
      '#collapsed' => FALSE,
    ];

    $form['websocket']['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host'),
      '#default_value' => $this->config('met_service.settings')->get('host'),
      '#required' => TRUE,
    ];
    $form['websocket']['port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Port'),
      '#default_value' => $this->config('met_service.settings')->get('port'),
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if ($form_state->getValue('example') === 'wrong') {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('The value is not correct.'),
    //     );
    //   }
    // @endcode
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('met_service.settings')
      ->set('host', $form_state->getValue('host'))
      ->set('port', $form_state->getValue('port'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
