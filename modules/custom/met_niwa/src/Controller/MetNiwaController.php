<?php declare(strict_types=1);

namespace Drupal\met_niwa\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Datetime;

/**
 * Returns responses for Met data converter routes.
 */
final class MetNiwaController extends ControllerBase
{

  private $api_base_url;
  private $token_enpoint;
  private $data_enpoint = 'GetChannelList/';

  private static $token = '';

  private $config;

  public function __construct()
  {

    $config = \Drupal::configFactory()->getEditable('met_niwa.settings');
    $this->config = $config;
    $this->api_base_url = $config->get('url');
    $this->token_enpoint = $config->get('endpoint');
  }


  private function callApi($url, $header = ['Content-Type: application/json'])
  {
    if (empty(self::$token)) {

      //check for Cookie
      if (isset($_COOKIE['niwa_api_token'])) {
        self::$token = $_COOKIE['niwa_api_token'];
      } else {
        self::$token = '1';
        $original_url = $url;
        $url = $this->api_base_url . $this->token_enpoint;
        $result = $this->callApi($url, $header);
        self::$token = $result->Token;
        $url = $original_url;
        setcookie("niwa_api_token", $result->Token, time() + 24 * 3600);
      }
    }

    if (self::$token !== '1') {
      $header[] = 'X-Authentication-Token: ' . self::$token;
    }

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    $curl_info = curl_getinfo($curl);

    if ($curl_info['http_code'] == 401) {
      if (isset($_COOKIE['niwa_api_token'])) {
        self::$token = '';
        unset($_COOKIE['niwa_api_token']);
        return $this->callApi($url, $header);
      }
    }

    curl_close($curl);

    return json_decode($result);
  }


  /**
   * Builds the response.
   */
  public function __invoke(): array
  {

    // Map the required item to its IDs
    $channels = [
      'temp' => "Air Temp(AVG)",
      'humidity' => "RH(AVG)",
      'barometer' => "MSL BP(AVG)",
      'wind_direction' => "Gust WD(RAW)",
      'wind_speed' => "Wind Speed Knots",
      'solar_radiation' => "Solar Radiation(AVG)"
    ];

    $weather_data = [
      'tbu' => [
        ['label' => "Fua'amotu", 'node_id' => 7628, 'channels' => $channels],
        ['label' => 'Toloa', 'node_id' => 7627, 'channels' => $channels],
        ['label' => "Nuku'alofa", 'node_id' => 7885, 'channels' => $channels],
        ['label' => "Mo'unga 'Olive", 'node_id' => 7549, 'channels' => $channels],
        ['label' => 'Kolovai', 'node_id' => 9589, 'channels' => $channels],
        ['label' => 'Lapaha', 'node_id' => 7683, 'channels' => $channels],
        ['label' => "'Atele", 'node_id' => 13802, 'channels' => $channels],
        ['label' => 'Fatai', 'node_id' => 7632, 'channels' => $channels],
      ],
      'eua' => [
        ['label' => 'Kaufana', 'node_id' => 7620, 'channels' => $channels],
        ['label' => 'Hango', 'node_id' => 7619, 'channels' => $channels],
      ],
      'hpp' => [
        ['label' => 'Nomuka', 'node_id' => 7622, 'channels' => $channels],
        ['label' => 'Pilolevu Airport', 'node_id' => 7624, 'channels' => $channels],
        ['label' => "Ha'ano", 'node_id' => 7621, 'channels' => $channels],
        ['label' => "Lifuka", 'node_id' => 7623, 'channels' => $channels],
        ['label' => "Tofua", 'node_id' => 9577, 'channels' => $channels],
      ],
      'vv' => [
        ['label' => "Lupepau'u Airport", 'node_id' => 7633, 'channels' => $channels],
        ['label' => "Fangatongo", 'node_id' => 7631, 'channels' => $channels],
        ['label' => "Koloa", 'node_id' => 7659, 'channels' => $channels],
        ['label' => "Longomapu", 'node_id' => 7630, 'channels' => $channels],
      ],
      'nfo' => [
        ['label' => "Niuafo'ou", 'node_id' => 7547, 'channels' => $channels],
      ],
      'ntt' => [
        ['label' => "Niuatoputapu", 'node_id' => 7548, 'channels' => $channels],
      ],
    ];

    $sea_info = [
      'tbu' => [
        'node_id' => 12528,
        'channels' => ['temp' => 273417, 'level' => 273416]
      ],
      'eua' => [
        'node_id' => 12528,
        'channels' => ['temp' => 273417, 'level' => 273416]
      ],
      'hpp' => [
        'node_id' => 11761,
        'channels' => ['temp' => 265150, 'level' => 265149]
      ],
      'vv' => [
        'node_id' => 7869,
        'channels' => ['temp' => 167560, 'level' => 167559]
      ],
      'ntt' => [
        'node_id' => 7614,
        'channels' => ['temp' => 159545, 'level' => 159544]
      ],
    ];


    $data = [];
    $selected_stations_id = [
      $this->config->get('tbu'),
      $this->config->get('vv'),
      $this->config->get('hpp'),
      7548, //ntt
      7547, //nfo
      $this->config->get('eua')
    ];
    foreach ($weather_data as $location => $stations) {
      foreach ($stations as $info) {
        if (!in_array($info['node_id'], $selected_stations_id))continue;

        $url = $this->api_base_url . $this->data_enpoint . $info['node_id'];
        $result = $this->callApi($url);

        $temperature = '';
        $humidity = '';
        $barometer = '';
        $wind_direction = '';
        $wind_direction_degree = 0;
        $wind_speed = '';
        $time = '';
        $solar_radiation = '';
        foreach ($result->GetChannelListResult as $channel) {
          $time = $channel->LastTime;
          switch ($channel->Name) {
            case $info['channels']['temp']:
              $temperature = $channel->LastValue;
              break;
            case $info['channels']['humidity']:
              $humidity = $channel->LastValue;
              break;
            case $info['channels']['barometer']:
              $barometer = $channel->LastValue;
              break;
            case $info['channels']['wind_direction']:
              $wind_direction = $this->degreeToCompass($channel->LastValue);
              $wind_direction_degree = $channel->LastValue;
              break;
            case $info['channels']['wind_speed']:
              $wind_speed = $channel->LastValue;
              break;
            case $info['channels']['solar_radiation']:
              $solar_radiation = $channel->LastValue;
              break;
          }
        }

        $time = new DateTime($time);

        //Convert knots to k/h
        $wind_speed = $wind_speed > 0 ? round($wind_speed * 1.852) : $wind_speed;

        $data[$location] = [
          'location' => $location,
          'icon' => "0",
          'temperature' => $temperature != '' ? round((float)$temperature) : 0,
          'humidity' => round((float)$humidity),
          'barometer' => $barometer,
          'wind_direction' => $wind_direction,
          'wind_speed' => $wind_speed,
          'visibility' => "",
          'observed_date' => $time->format("D, j M Y G:i:s") . ' +1300',
          'wind_direction_degree' => $wind_direction_degree,
          'solar_radiation' => $solar_radiation,
          'station' => $info['label'],
        ];

        sleep(2);

      }

    }

    //Create new CSV file and put the current weather data there
    $csv_file_name = 'live_weather.csv';
    $csv_file_absolute_path = \Drupal::service('file_system')->realpath('private://' . $csv_file_name);

    $fp = fopen($csv_file_absolute_path, 'w'); // open in write only mode (write at the start of the file)
    fputcsv($fp, ['weather']);
    foreach ($data as $location => $value) {
      fputcsv($fp, $value);
    }
    fclose($fp);


    //sea data
    $sea_data = [];
    foreach ($sea_info as $location => $info) {

      $url = $this->api_base_url . $this->data_enpoint . $info['node_id'];
      $result = $this->callApi($url);

      $sea_level = 0.0;
      $sea_temp = 0.0;
      $observation_time = '';
      foreach ($result->GetChannelListResult as $channel) {
        switch ($channel->ID) {
          case $info['channels']['temp']:
            $sea_temp = round((float)$channel->LastValue);
            $observation_date = $channel->LastTime;
            break;
          case $info['channels']['level']:
            $sea_level = $channel->LastValue;
            break;
        }
      }

      $time = new Datetime($observation_time);
      $sea_data[$location] = [$location, $sea_level, $sea_temp, $time->format("D, j M Y G:i:s") . ' +1300',];
    }
    //Create new CSV file and put the current sea level data there
    $csv_file_name = 'live_sea.csv';
    $csv_file_absolute_path = \Drupal::service('file_system')->realpath('private://' . $csv_file_name);

    $fp = fopen($csv_file_absolute_path, 'w'); // open in write only mode (write at the start of the file)
    fputcsv($fp, ['sea']);
    foreach ($sea_data as $location => $value) {
      fputcsv($fp, $value);
    }
    fclose($fp);

    $build['content'] = [
      '#type' => 'item',
      '#markup' => "Live Weather forecast has been updated",
      '#cache' => [
        'max-age' => 0
      ],
    ];

    return $build;
  }

  function degreeToCompass($degree)
  {
    $value = floor(($degree / 22.5) + 0.5);
    $compass = ["N", "NNE", "NE", "ENE", "E", "ESE", "SE", "SSE", "S", "SSW", "SW", "WSW", "W", "WNW", "NW", "NNW"];
    return $compass[($value % 16)];
  }

  function getWeatherIcon($data)
  {
    if (!empty($data['present_weather'])) {
      switch ($data['present_weather']) {
        case 'RA':
          return 7;
        case 'DZ':
          return 5;
        case 'TS':
          return 9;
        case 'SH':
          return 6;
        default:
          return 7;
      }
    } else {

      if (!isset($data['clouds'])) return 0;
      $total = is_array($data['clouds']) ? count($data['clouds']) - 1 : 0;
      switch ($data['clouds'][$total]['amount']) {
        case 'FEW':
          return 1;
        case 'SCT':
          return 2;
        case 'BKN':
        case 'OVC':
          return 3;
        default:
          return 0;
      }
    }
  }
}
