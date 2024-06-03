<?php

namespace Drupal\Tests\met_tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 *
 */
class MetTestBase extends ExistingSiteBase {

  /**
   *
   */
  protected function setUp(): void {
    parent::setUp();

    // Cause tests to fail if an error is sent to Drupal logs.
    $this->failOnLoggedErrors();
  }

}
