<?php

namespace Drupal\met_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the API resource for the mobile App.
 *
 * @RestResource(
 *   id = "met_api_weather_resource",
 *   label = @Translation("MET API Weather Resouce"),
 *   uri_paths = {
 *      "canonical" = "/api/v1/weather"
 *   }
 * )
 */
class WeatherResource extends ResourceBase {

  use StringTranslationTrait;

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('MET API'),
      $container->get('current_user')
    );
  }

  /**
   *
   */
  public function compassToDegree($value) {

  }

  /**
   *
   */
  public function get() {

    // This is the weather forecast that upload by TMS in CSV file format.
    $csv_file_name = 'weather.csv';
    $absolute_path = \Drupal::service('file_system')->realpath('private://' . $csv_file_name);
    $file = fopen($absolute_path, "r");

    $data = [];
    $type = '';
    while (!feof($file)) {
      while (($lines = fgetcsv($file, 1000, ",")) !== FALSE) {
        if (!is_null($lines[0])) {

          if (count($lines) == 1) {
            $type = $lines[0];
            continue;
          }
          $data[$type][] = array_map('trim', $lines);
        }
      }
    }

    fclose($file);

    // Because NIWA API doesn't provide visibility and weather condition,
    // we can get that from the METAR file on MET service website.
    $metar = [];

    $csv_file_name = 'weather_metar.csv';
    $absolute_path = \Drupal::service('file_system')->realpath('private://' . $csv_file_name);
    $file = fopen($absolute_path, "r");
    while (!feof($file)) {
      while (($lines = fgetcsv($file, 1000, ",")) !== FALSE) {
        if (!is_null($lines[0])) {

          if (count($lines) == 1) {
            $type = $lines[0];
            continue;
          }
          $metar[$lines[0]]['icon'] = $lines[1];
          $metar[$lines[0]]['visibility'] = $lines[7];
        }
      }
    }

    fclose($file);

    // Get the live weather data from CSV.
    $csv_file_name = 'live_weather.csv';
    $absolute_path = \Drupal::service('file_system')->realpath('private://' . $csv_file_name);
    $file = fopen($absolute_path, "r");

    $type = '';
    while (!feof($file)) {
      while (($lines = fgetcsv($file, 1000, ",")) !== FALSE) {
        if (!is_null($lines[0])) {

          if (count($lines) == 1) {
            $type = $lines[0];
            continue;
          }

          if ($metar[$lines[0]]['icon'] != '') {
            $lines[1] = $metar[$lines[0]]['icon'];
          }

          if ($metar[$lines[0]]['visibility'] != '') {
            $lines[7] = $metar[$lines[0]]['visibility'];
          }

          $data[$type][] = array_map('trim', $lines);
        }
      }
    }

    fclose($file);

    // Read the sea information.
    $csv_file_name = 'live_sea.csv';
    $absolute_path = \Drupal::service('file_system')->realpath('private://' . $csv_file_name);
    $file = fopen($absolute_path, "r");

    $type = '';
    while (!feof($file)) {
      while (($lines = fgetcsv($file, 1000, ",")) !== FALSE) {
        if (!is_null($lines[0])) {

          if (count($lines) == 1) {
            $type = $lines[0];
            continue;
          }

          $data[$type][] = array_map('trim', $lines);
        }
      }
    }
    fclose($file);

    // Read tide information.
    $csv_file_name = 'tide.csv';
    $absolute_path = \Drupal::service('file_system')->realpath('private://' . $csv_file_name);
    $file = fopen($absolute_path, "r");

    $type = '';
    while (!feof($file)) {
      while (($lines = fgetcsv($file, 1000, ",")) !== FALSE) {
        if (!is_null($lines[0])) {

          if (count($lines) == 1) {
            $type = $lines[0];
            continue;
          }

          $data[$type][] = array_map('trim', $lines);
        }
      }
    }
    fclose($file);

    $build = ['#cache' => ['max-age' => 0]];

    return (new ResourceResponse($data, 200))->addCacheableDependency($build);
  }

  /**
   *
   */
  public function permissions() {
    return [];
  }

}
