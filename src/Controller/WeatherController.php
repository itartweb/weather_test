<?php

namespace Drupal\weather\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Html;

/**
 * Class WeatherController.
 *
 * @package Drupal\weather\Controller
 */
class WeatherController extends ControllerBase {

  /**
   * Returns response for the autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function autocomplete(Request $request) {
    $matches = array();
    $string = $request->query->get('q');
    if ($string) {
      $matches = array();
      $query = \Drupal::database()->select('weather_city', 'wc')
        ->fields('wc', array('city_id', 'name', 'country'))
        ->condition('wc.name', '%' . $string . '%', 'LIKE')
        ->range(0, 10);
      $result = $query->execute();

      foreach ($result as $row) {
        $value = Html::escape($row->city_id);
        $label = Html::escape($row->name . ' (' . $row->country . ')');
        $matches[] = ['value' => $value, 'label' => $label];
      }
    }
    return new JsonResponse($matches);
  }

}
