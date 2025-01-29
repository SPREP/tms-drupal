<?php declare(strict_types = 1);

namespace Drupal\met_spc\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Datetime;

/**
 * Returns responses for Met SPC API routes.
 */
final class MetSpcController extends ControllerBase {

  //Nuku'alofa and Neiafu data endpoint
  private $data_endpoint_base_url;
  public $tbu = 'INT_TP025';
  public $vv = 'INT_TP053';
  private $token;


  public function __construct() {
    $config = \Drupal::configFactory()->getEditable('met_spc.settings');
    $this->data_endpoint_base_url = $config->get('url');
    $this->token = $config->get('token');
  }

  private function callApi($url, $header = ['Content-Type: application/json']) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    //$curl_info = curl_getinfo($curl);

    curl_close($curl);

    return json_decode($result);
  }

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $query_start_date = date('Y-m-d', strtotime("-2 days")); //'2024-01-01/';

    $result = $this->callApi($this->data_endpoint_base_url.$query_start_date.'/'.$this->token);

    $data = [];
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 days'));
    $yesterday = date('Y-m-d', strtotime('-1 days'));
    foreach($result as $info) {

      $tide_date = date('Y-m-d', strtotime($info[5]));

      if ($info[1] == $this->tbu) {
        //check only today tomorrow
        if ($today == $tide_date || $tomorrow == $tide_date || $yesterday == $tide_date){
          array_unshift($info, 'tbu');
          //$time = new Datetime($info[6]);
          //$time->format("D, j M Y G:i:s") . ' +1300';
          //$info[6] = $time;
          $data[] = $info;
        }
      }

      if ($info[1] == $this->vv) {
        //check only today and tomorrow
        if ($today == $tide_date || $tomorrow == $tide_date || $yesterday == $tide_date){
          array_unshift($info, 'vv');
          //$time = new Datetime($info[6]);
          //$time->format("D, j M Y G:i:s") . ' +1300';
          //$info[6] = $time->;
          $data[] = $info;
        }
      }
    }

    //Read the sea information
    $csv_file_name = 'tide.csv';
    $csv_file_absolute_path = \Drupal::service('file_system')->realpath('private://' . $csv_file_name);
    $fp = fopen($csv_file_absolute_path, 'w'); // open in write only mode (write at the start of the file)
    fputcsv($fp, ['tide']);
    foreach ($data as $location => $value) {
      fputcsv($fp, $value);
    }
    fclose($fp);

    $build['content'] = [
      '#type' => 'item',
      '#markup' => "Tide data has been updated",
      '#cache' => [
        'max-age' => 0
      ],
    ];

    return $build;


    //Create new CSV file and put the current weather data there
    $csv_file_name = 'tide.csv';
    $csv_file_absolute_path = \Drupal::service('file_system')->realpath('private://' . $csv_file_name);

    $fp = fopen($csv_file_absolute_path, 'w'); // open in write only mode (write at the start of the file)
    fputcsv($fp, ['tide']);
    foreach ($data as $location => $value) {
      fputcsv($fp, $value);
    }
    fclose($fp);

    $build['content'] = [
      '#type' => 'item',
      '#markup' => "Tide data has been updated",
      '#cache' => [
        'max-age' => 0
      ],
    ];

    return $build;
  }

}
