<?php

declare(strict_types=1);

namespace Drupal\Tests\met_niwa\Unit;

use Drupal\met_niwa\Controller\MetNiwaController;
use Drupal\Tests\UnitTestCase;

/**
 * Test description.
 *
 * @group met_niwa
 */
final class MetNiwaControllerTest extends UnitTestCase {

  public $metNiwaController;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->metNiwaController = new MetNiwaController();

  }

  /**
   * Test conversion function degree to compass.
   *
   * @return void
   */
  public function testDegreeToCompass(): void {
    $compass = $this->metNiwaController->degreeToCompass(45);
    self::assertEquals('NE', $compass);
  }

}
