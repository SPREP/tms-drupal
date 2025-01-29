<?php declare(strict_types = 1);

namespace Drupal\met_niwa\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure MET NIWA settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'met_niwa_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['met_niwa.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['niwa'] = [
      '#type' => 'fieldset',
      '#title' => t('NIWA API Connection'),
      '#collapsible' => TRUE, // Added
      '#collapsed' => FALSE,  // Added
    ];

    $form['niwa']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $this->config('met_niwa.settings')->get('url'),
      ];

      $form['niwa']['endpoint'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Endpoint'),
        '#default_value' => $this->config('met_niwa.settings')->get('endpoint'),
    ];


    $form['stations'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('AWS Stations'),
      '#collapsible' => TRUE, // Added
      '#collapsed' => FALSE,  // Added
    ];

    $form['stations']['info'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Available AWS stations.  You can switch the station here to show on mobile App'),
    ];

    $form['stations']['tbu'] = [
      '#title' => $this->t('Tongatapu'),
      '#type' => 'select',
      '#options' => [
        7628 => 'Fuamotu',
        7627 => 'Toloa',
        7885 => 'Nukualofa',
        7549 => 'Mounga Olive',
        9589 => 'Kolovai',
        7683 => 'Lapaha',
        13802 => 'Atele',
        7632 => 'Fatai'
      ],
      '#default_value' => $this->config('met_niwa.settings')->get('tbu'),
    ];

    $form['stations']['eua'] = [
      '#title' => $this->t('Eua'),
      '#type' => 'select',
      '#options' => [
        7620 => 'Kaufana',
        7619 => 'Hango',
      ],
      '#default_value' => $this->config('met_niwa.settings')->get('eua'),
    ];

    $form['stations']['hpp'] = [
      '#title' => $this->t('Haapai'),
      '#type' => 'select',
      '#options' => [
        7622 => 'Nomuka',
        7624 => 'Pilolevu Airport',
        7621 => 'Haano',
        7623 => 'Lifuka',
        9577 => 'Tofua'
      ],
      '#default_value' => $this->config('met_niwa.settings')->get('hpp'),
    ];

    $form['stations']['vv'] = [
      '#title' => $this->t('Vavau'),
      '#type' => 'select',
      '#options' => [
        7633 => 'Lupepauu Airport',
        7631 => 'Fangatongo',
        7659 => 'Koloa',
        7630 => 'Longomapu'
      ],
      '#default_value' => $this->config('met_niwa.settings')->get('vv'),
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
    $this->config('met_niwa.settings')
      ->set('url', $form_state->getValue('url'))
      ->set('endpoint', $form_state->getValue('endpoint'))

      ->set('tbu', $form_state->getValue('tbu'))
      ->set('vv', $form_state->getValue('vv'))
      ->set('hpp', $form_state->getValue('hpp'))
      ->set('eua', $form_state->getValue('eua'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
