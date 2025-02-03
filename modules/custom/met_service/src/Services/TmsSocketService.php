<?php

namespace Drupal\met_service\Services;

use Paragi\PhpWebsocket\Client;
use Paragi\PhpWebsocket\ConnectionException;

/**
 *
 */
class TmsSocketService {

  protected int $port;
  protected string $host;

  public function __construct() {

    $config = \Drupal::configFactory()->getEditable('met_service.settings');

    // 8080; //5123
    $this->port = $config->get('port');
    // 'host.docker.internal'; //'host.docker.internal';  //app.met.gov.to
    $this->host = $config->get('host');
  }

  /**
   *
   */
  public function send($payload) {

    $payload = json_encode($payload);
    try {
      $str_err = '';
      $sp = new Client($this->host, $this->port, '', $str_err, 10, TRUE);
      $sp->write($payload);
      return TRUE;
    }
    catch (ConnectionException $e) {
      return FALSE;
    }
  }

}
