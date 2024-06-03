<?php

namespace Drupal\eopts;

/**
 * Trait Utility logs kin to the Drupal root.
 */
trait Utility {

  /**
   * Helper function to log kint.html to the Drupal root.
   *
   * @param mixed $mixed
   *   Anything.
   * @param string $destination
   *   The file destination, default is /tmp/ .
   */
  public function kint($mixed, $destination = '/tmp/') {
    \Drupal::service('eopts.commands')->kint($mixed, $destination);
  }

}
