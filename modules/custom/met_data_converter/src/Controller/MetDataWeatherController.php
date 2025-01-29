<?php

declare(strict_types=1);

namespace Drupal\met_data_converter\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for MET Data Converter routes.
 */
final class MetDataWeatherController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $source = [
     // '10days' => '',
      '24hrs' => "https://met.gov.to/forecast/web/24hr_forecast.csv",
      '3hrs' => "https://met.gov.to/forecast/web/3hr_forecast.csv"
    ];

    $data = [];

    foreach ($source as $title => $source_url) {
      $file = fopen($source_url,'r');
      $data[] = [$title];
      while (($line = fgetcsv($file)) !== FALSE) {
        $data[] = $line;
      }
      fclose($file);
    }


    $csv_file_name = 'weather.csv';
    $absolute_path = \Drupal::service('file_system')->realpath('private://' . $csv_file_name);
    $fp = fopen($absolute_path, 'w'); // open in write only mode (write at the start of the file)
    $valid_lines = [0, 4, 5, 6, 7, 8, 17, 21, 22, 23, 24 ,25];
    foreach ($data as $index => $line) {
      if(!in_array($index, $valid_lines))continue;

      //Modify the 3hrs data, to put location on the front
      switch($index) {
        case 21:
          array_unshift($line, 'tbu');
          break;
        case 22:
          array_unshift($line, 'hpp');
          break;
        case 23:
          array_unshift($line, 'vv');
          break;
        case 24:
          array_unshift($line, 'nfo');
          break;
        case 25:
          array_unshift($line, 'ntt');
          break;
      }

      //data adjustment
      $line[0] = strtolower($line[0]);
      if ($line[0] == 'hp')$line[0] = 'hpp';
      //write to csv
      $line[0] = strtolower($line[0]);
      fputcsv($fp, $line);
    }

    //10 days
    $file_path = \Drupal::service('file_system')->realpath('private://10days.csv');
    $file = fopen($file_path,'r');
    while (($line = fgetcsv($file)) !== FALSE) {
      if(!is_null($line[0])){
        $line[0] = strtolower($line[0]);
      }
      fputcsv($fp, $line);
    }
    fclose($file);
    //end 10 days

    fclose($fp);

    $build['content'] = [
      '#type' => 'item',
      '#markup' => 'Weather data has been imported.'//print_r($data, true),
    ];

    return $build;
  }

}
