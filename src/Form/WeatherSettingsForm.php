<?php

namespace Drupal\weather\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\weather\WeatherService;

/**
 * Class WeatherSettingsForm.
 */
class WeatherSettingsForm extends ConfigFormBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The weather service.
   *
   * @var \Drupal\weather\WeatherService
   */
  protected $weatherService;

  /**
   * Content Locker Service instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\weather\WeatherService $weather_service
   *   The weather service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, WeatherService $weather_service) {
    parent::__construct($config_factory);

    $this->configFactory = $config_factory;
    $this->weatherService = $weather_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('weather.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'weather_settings_form';
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'weather.settings',
    ];
  }

  /**
   * Gets the configuration object when needed.
   *
   * @return object
   *   The config object.
   */
  protected function getConfig() {
    return $this->configFactory->get('weather.settings');
  }

  /**
   * Get options of Country codes.
   *
   * @return array
   *   The countries options array.
   */
  protected function getCountryOptions() {
    return [
      'UA' => 'Ukraine',
      'US' => 'United States',
      'GB' => 'Great Britain',
      'DE' => 'Germany',
    ];
  }

  /**
   * Builds the settings form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The parent form with the form elements added.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfig();
    $key = !empty($config->get('key')) ? $config->get('key') : '';
    $country = !empty($config->get('country')) ? $config->get('country') : 'UA';

    $form['default'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default'),
    ];

    $form['default']['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('The api key. Ex: f47c5dc1ae564c92faf2816b5e3b4c2d'),
      '#default_value' => $key,
    ];

    $form['cities'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cities'),
    ];

    $count_cities = $this->weatherService->getCountCities();
    $message = $this->t('The count cities in database: @count', ['@count' => $count_cities]);
    $form['cities']['message'] = [
      '#type' => 'markup',
      '#markup' => $message,
    ];

    $form['cities']['country'] = [
      '#type' => 'select',
      '#options' => $this->getCountryOptions(),
      '#title' => $this->t('Country'),
      '#default_value' => $country,
      '#required' => TRUE,
    ];

    $form['cities']['submit'] = [
      '#type' => 'submit',
      '#name' => 'submit',
      '#weight' => 100,
      '#value' => $this->t('Update cities database'),
      '#submit' => ['::submitUpdateCities'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submits the updating cities database.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitUpdateCities(array &$form, FormStateInterface $form_state) {
    $this->config('weather.settings')
      ->set('country', $form_state->getValue('country'))
      ->save();

    $batch = [
      'title' => $this->t('Updating weather cities database...'),
      'operations' => [
        [
          '\Drupal\weather\WeatherService::insertCityProcess',
          [],
        ],
      ],
      'finished' => '\Drupal\weather\WeatherService::insertCityFinishedCallback',
    ];
    batch_set($batch);
  }

  /**
   * Submits the settings form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('weather.settings')
      ->set('key', $form_state->getValue('key'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
