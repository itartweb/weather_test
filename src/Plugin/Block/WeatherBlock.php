<?php

namespace Drupal\weather\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Weather block class.
 *
 * @Block(
 *   id = "weather_block",
 *   admin_label = @Translation("Weather"),
 * )
 */
class WeatherBlock extends BlockBase {

  /**
   * Default config.
   */
  public function defaultConfiguration() {
    return array(
      'city' => '',
      'scale' => 'F',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#description' => $this->t('The city.'),
      '#default_value' => $config['city'],
      '#autocomplete_route_name' => 'weather.weather_city_autocomplete',
      '#required' => TRUE,
    ];

    $form['scale'] = [
      '#type' => 'radios',
      '#options' => ['C' => 'Celsius', 'F' => 'Fahrenheit'],
      '#title' => $this->t('The temperature scale'),
      '#default_value' => $config['scale'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
    // Some Validate.
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['city'] = $form_state->getValue('city');
    $this->configuration['scale'] = $form_state->getValue('scale');
  }

  /**
   * {@inheritdoc}
   */
  public function uriIcon($icon) {
    return 'http://openweathermap.org/img/w/' . $icon . '.png';
  }

  /**
   * {@inheritdoc}
   */
  public function formatDate($timestamp) {
    return \Drupal::service('date.formatter')->format($timestamp, 'custom', 'H:i:s');
  }

  /**
   * {@inheritdoc}
   */
  public function renderScale($scale) {
    switch ($scale) {
      case 'C':
        $output = '&#8451;';
        break;

      case 'F':
        $output = '&#8457;';
        break;
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function preBuild($result) {
    $renderer = [
      'name' => $result['name'],
      'temp' => $result['main']['temp'],
      'scale' => $this->renderScale($result['main']['scale']),
      'pressure' => $result['main']['pressure'],
      'humidity' => $result['main']['humidity'],
      'description' => $result['weather'][0]['description'],
      'icon' => $this->uriIcon($result['weather'][0]['icon']),
      'wind_speed' => $result['wind']['speed'],
      'sunrise' => $this->formatDate($result['sys']['sunrise']),
      'sunset' => $this->formatDate($result['sys']['sunset']),
    ];
    return $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $renderer = [];

    if ($config['city']) {
      $result = \Drupal::service('weather.service')->getResponseByCity($config['city'], $config['scale']);
      $renderer = $this->preBuild($result);
    }

    $block = [
      '#theme' => 'weather',
      '#weather' => $renderer,
    ];

    $build['#cache']['max-age'] = 0;

    $block['#attached']['library'][] = 'weather/weather.css';

    return $block;
  }

}
