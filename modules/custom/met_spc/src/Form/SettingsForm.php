<?php

declare(strict_types=1);

namespace Drupal\met_spc\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure MET SPC settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'met_spc_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['met_spc.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['spc'] = [
      '#type' => 'fieldset',
      '#title' => t('SPC API Connection'),
    // Added.
      '#collapsible' => TRUE,
    // Added.
      '#collapsed' => FALSE,
    ];

    $form['spc']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $this->config('met_spc.settings')->get('url'),
    ];

    $form['spc']['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token'),
      '#default_value' => $this->config('met_spc.settings')->get('token'),
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
    $this->config('met_spc.settings')
      ->set('url', $form_state->getValue('url'))
      ->set('token', $form_state->getValue('token'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
