<?php

namespace Drupal\Tests\eopts\Unit;

use Drupal\Tests\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Eopts unit yaml config test.
 *
 * @group eopts_unit
 */
class YamlConfigTest extends UnitTestCase {

  /**
   * TestAnything.
   */
  public function testConfig() {
    $this->markTestSkipped('Skipping test until configs are added to repo.');

    $config_dir = $this->root . '/config/site';

    $data = Yaml::parse(file_get_contents($config_dir . '/core.extension.yml'));
    $core_extension_modules = $data['module'];

    // An assertion to make sure our yml is being read in ok.
    $this->assertTrue(array_key_exists('eopts', $core_extension_modules), "eopts not found in " . $config_dir . '/core.extension.yml');

    // Make sure these modules are not enabled.
    $assert_disabled = [
      'browsersync',
      'devel',
      'kint',
    ];

    foreach ($assert_disabled as $module_name) {
      $this->assertFalse(array_key_exists($module_name, $core_extension_modules), $module_name . " should not be enabled in core.extension.yml");
    }

    // Make sure these Yaml files don't exist;.
    $this->assertFalse(file_exists($config_dir . '/devel.settings.yml'));
    $this->assertFalse(file_exists($config_dir . '/devel.toolbar.settings.yml'));
    $this->assertFalse(file_exists($config_dir . '/system.menu.devel.yml'));
  }

}
