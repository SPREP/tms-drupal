<?php

namespace Drupal\Tests\met_tests\Functional;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Class MetTestBase.
 */
class MetTestBase extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Cause tests to fail if an error is sent to Drupal logs.
    $this->failOnLoggedErrors();
  }

}
