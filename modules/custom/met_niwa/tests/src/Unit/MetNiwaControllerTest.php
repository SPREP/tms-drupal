<?php

declare(strict_types=1);

namespace Drupal\Tests\met_niwa\Unit;



use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\met_niwa\Controller\MetNiwaController;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
   * Test conversion function degree to compass
   * @return void
   */
  public function testDegreeToCompass(): void {
    $compass = $this->metNiwaController->degreeToCompass(45);
    self::assertEquals('NE', $compass);
  }

}
