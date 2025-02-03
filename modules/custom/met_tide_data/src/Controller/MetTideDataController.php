<?php

declare(strict_types=1);

namespace Drupal\met_tide_data\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use HungCP\PhpSimpleHtmlDom\HtmlDomParser;

/**
 * Returns responses for Met tide data routes.
 */
final class MetTideDataController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {
    $cur_date = date('m/j/Y');

    $urls = [];
    $urls['tbu'] = 'http://www.bom.gov.au/australia/tides/print.php?aac=INT_TP025&type=tide&date=' . $cur_date . '&region=TON&tz=Pacific/Tongatapu&tz_js=+13&days=7';
    $urls['vv'] = 'http://www.bom.gov.au/australia/tides/print.php?aac=INT_TP053&type=tide&date=' . $cur_date . '&region=TON&tz=Pacific/Tongatapu&tz_js=+13&days=7';

    $user_agents = [
      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36",
      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36",
      "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:90.0) Gecko/20100101 Firefox/90.0",
      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36",
      "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36",
      "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36",
      "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
      "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.2 Safari/605.1.15",
      "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15",
      "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:91.0) Gecko/20100101 Firefox/91.0",
    ];

    /** @var Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    // $date_formatter = \Drupal::service('date.formatter');
    $data = [];
    foreach ($urls as $location => $url) {

      // Get random user agent.
      $user_agent = $user_agents[rand(0, count($user_agents) - 1)];

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
      $exec = curl_exec($ch);

      $dom = HtmlDomParser::str_get_html($exec);

      foreach ($dom->find('.tide-day') as $elm) {
        $str_day = $elm->find('<h3>', 0)->innertext;

        foreach ($elm->find('.low-tide') as $low) {
          $low->getAllAttributes();
          if (isset($low->attr['data-time-local'])) {
            $date = new DrupalDateTime($low->attr['data-time-local']);
            $day = $date->format('d/m/Y');
            $data[$location]['low'][$day][] = $low->innertext;
          }
        }

        foreach ($elm->find('.high-tide') as $high) {
          $high->getAllAttributes();
          if (isset($high->attr['data-time-local'])) {
            $date = new DrupalDateTime($high->attr['data-time-local']);
            $day = $date->format('d/m/Y');
            $data[$location]['high'][$day][] = $high->innertext;
          }

        }
      }
    }

    $file_name = 'tide.json';
    $json_absolute_path = \Drupal::service('file_system')->realpath('public://' . $file_name);
    $data = serialize($data);
    file_put_contents($json_absolute_path, $data);

    $build['content'] = [
      '#type' => 'item',
      '#markup' => 'Tide task completed ',
    ];

    return $build;
  }

}
