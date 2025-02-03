<?php

namespace Drupal\Tests\met_tests\Functional;

use Drupal\node\NodeInterface;

/**
 *
 */
class BundleAccessTest extends MetTestBase {

  /**
   * Provides test data for testCRUD().
   */
  public function crudTestProvider() {
    return [
      // @todo Add tests for these entity types.
      // ['anonymous', 'met-feel-earthquake', ''],
      // ['anonymous', 'met-tk', ''],
      // ['anonymous', 'met-warning', ''],

      // Anonymous cannot access content.
      ['anonymous', 'article', ''],
      ['anonymous', 'page', 'R'],
      ['anonymous', 'evacuation', ''],
      ['anonymous', 'event', ''],
      ['anonymous', 'event_report', ''],
      ['anonymous', 'impact_report', ''],
      ['anonymous', 'push_notification', ''],
      ['anonymous', 'request_assistance', ''],

      // // Authenticated users can read.
      // ['authenticated', 'article', ''],
      // ['authenticated', 'page', ''],
      // ['authenticated', 'evacuation', ''],
      // ['authenticated', 'event', ''],
      // ['authenticated', 'push_notification', ''],

      // // Authenticated users can create and read.
      // ['authenticated', 'event_report', ''],
      // ['authenticated', 'impact_report', ''],
      // ['authenticated', 'request_assistance', ''],

      // @todo add roles for tms, ndrmo, geology.
      // Admin user
      ['administrator', 'article', 'CRUD'],
      ['administrator', 'page', 'CRUD'],
      ['administrator', 'evacuation', 'CRUD'],
      ['administrator', 'event', 'CRUD'],
      ['administrator', 'event_report', 'CRUD'],
      ['administrator', 'impact_report', 'CRUD'],
      ['administrator', 'push_notification', 'CRUD'],
      ['administrator', 'request_assistance', 'CRUD'],
    ];
  }

  /**
   * Tests CRUD functionality for a particular content type.
   *
   * @param string $role
   *   The role to test.
   * @param string $bundle
   *   The content type to test.
   * @param string $permissions
   *   A string of characters indicating which operations are allowed. C =
   *   create, R = read, U = update, D = delete.
   *
   * @dataProvider crudTestProvider
   */
  public function testCRUD($role, $bundle, $permissions) {
    if ($role !== 'anonymous') {
      $account = $this->createUser([], 'test-' . $role, FALSE);
      $account->set('roles', $role);
      $account->save();
      $this->drupalLogin($account);
    }

    $this->drupalGet("node/add/$bundle");
    $expected_status = (strpos($permissions, 'C') !== FALSE) ? 200 : 403;
    $this->assertSession()->statusCodeEquals($expected_status);
    $edit = [
      'type' => $bundle,
      'status' => NodeInterface::PUBLISHED,
      'title' => 'Test ' . $bundle,
    ];

    $node = $this->createNode($edit);
    $this->drupalGet($node->toUrl());

    $expected_status = (strpos($permissions, 'R') !== FALSE) ? 200 : 403;
    $this->assertSession()->statusCodeEquals($expected_status);
    if (strpos($permissions, 'U') !== FALSE) {
      $this->clickLink('Edit');
      $this->assertSession()->statusCodeEquals(200);
    }
    $this->drupalGet('node/' . $node->id() . '/delete');
    $expected_status = (strpos($permissions, 'D') !== FALSE) ? 200 : 403;
    $this->assertSession()->statusCodeEquals($expected_status);
  }

}
