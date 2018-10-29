<?php

namespace Drupal\weather;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Drupal\Component\Serialization\Json;

/**
 * Class WeatherService.
 */
class WeatherService {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Service instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
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
   * Gets the count cities.
   *
   * @return int
   *   The count cities from database.
   */
  public function getCountCities() {
    $query = \Drupal::database()->select('weather_city', 'wc');
    $query->addExpression('COUNT(*)');
    $count = $query->execute()->fetchField();
    return $count;
  }

  /**
   * Gets the cities.
   *
   * @return array
   *   The cities array.
   */
  public function getCitiesFromRemote() {
    $url = drupal_get_path('module', 'weather') . '/res/city.list.json';

    $client = \Drupal::httpClient();
    $request = new Request('GET', $url);
    try {
      $response = $client->send($request);
    }
    catch (RequestException $e) {
      $response = new Response(400);
    }

    $contents = $response->getBody()->getContents();
    $result = Json::decode($contents) ?: [];

    $result = self::getCitiesByCountry($result);
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getCitiesByCountry($result) {
    $return = [];
    $country = \Drupal::config('weather.settings')->get('country');
    foreach ($result as $city) {
      if ($country == $city['country']) {
        $return[] = $city;
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function issetCity($id) {
    $database = \Drupal::database();
    $result = $database->select('weather_city', 'wc')
      ->fields('wc', ['id'])
      ->condition('city_id', $id)
      ->execute()
      ->fetchField();
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function insertCityProcess(&$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;

      $cities = self::getCitiesFromRemote();
      $context['sandbox']['max'] = count($cities);
      $context['sandbox']['cities'] = $cities;
    }
    $limit = 10;
    $result = array_slice($context['sandbox']['cities'], $context['sandbox']['progress'], $limit);

    foreach ($result as $city) {
      if (!self::issetCity($city['id'])) {
        \Drupal::database()
          ->insert('weather_city')
          ->fields([
            'city_id',
            'name',
            'country',
          ])
          ->values([
            $city['id'],
            $city['name'],
            $city['country'],
          ])
          ->execute();
      }

      $context['results'][] = $city['name'];
      $context['sandbox']['progress']++;
      $context['message'] = $city['name'];
    }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function insertCityFinishedCallback($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One city processed.', '@count cities processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    $messenger = \Drupal::messenger();
    $messenger->addMessage($message);
  }

  /**
   * Gets the api url by the city.
   *
   * @param string $city
   *   The city and country code.
   *
   * @return string
   *   The url api.
   */
  protected function getApi($city) {
    $config = $this->getConfig();
    $key = $config->get('key');

    return 'http://api.openweathermap.org/data/2.5/weather?id=' . $city . '&appid=' . $key;
  }

  /**
   * Gets the weather array by the city.
   *
   * @param string $api
   *   The url api.
   *
   * @return array
   *   The array.
   */
  protected function getResultByApi($api) {
    $client = \Drupal::httpClient();
    $request = new Request('GET', $api);
    try {
      $response = $client->send($request);
    }
    catch (RequestException $e) {
      $response = new Response(400);
    }

    $contents = $response->getBody()->getContents();
    return Json::decode($contents) ?: [];
  }

  /**
   * Gets the weather array.
   *
   * @param array $result
   *   The response result.
   * @param string $scale
   *   The temperature scale.
   *
   * @return array
   *   The array.
   */
  protected function getResultConvert(array $result, $scale) {
    $result['main']['temp'] = $this->convertTemp($result['main']['temp'], $scale);
    $result['main']['temp_min'] = $this->convertTemp($result['main']['temp_min'], $scale);
    $result['main']['temp_max'] = $this->convertTemp($result['main']['temp_max'], $scale);
    $result['main']['scale'] = $scale;
    return $result;
  }

  /**
   * Gets the convert temp.
   *
   * @param float $temp
   *   The response temp by Kelvin.
   * @param string $scale
   *   The temperature scale.
   *
   * @return int
   *   The temperature by scale.
   */
  protected function convertTemp($temp, $scale) {
    switch ($scale) {
      case 'F':
        $temp = intval($temp * 9 / 5 - 459.67);
        break;

      case 'C':
        $temp = intval($temp - 273.15);
        break;
    }
    return $temp;
  }

  /**
   * Gets the weather array by the city.
   *
   * @param string $city
   *   The city and country code.
   * @param string $scale
   *   The temperature scale.
   *
   * @return array
   *   The array.
   */
  public function getResponseByCity($city, $scale) {
    if ($city && $scale) {
      $api = $this->getApi($city);
      $result = $this->getResultByApi($api);
      if ($result) {
        $result = $this->getResultConvert($result, $scale);
        return $result;
      }
    }
    return [];
  }

}
