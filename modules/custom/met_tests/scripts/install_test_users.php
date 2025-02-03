<?php

/**
 * @file
 * Creates test users.
 */

use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;

// Create a user for each Drupal role.
// Named test-[role].
foreach (Role::loadMultiple() as $global_role) {

  if ($global_role->id() == 'anonymous') {
    continue;
  }

  $username = 'test-' . $global_role->id();
  $user = create_user_if_not_exists($username);
  if ($global_role->id() != 'authenticated') {
    $user->set('roles', [$global_role->id()]);
  }
  $user->save();
}

Drupal::state()->set('test_users_installed', TRUE);

/**
 * Create/get a user.
 */
function create_user_if_not_exists($username) {
  $user_storage = \Drupal::entityTypeManager()->getStorage('user');
  $users = $user_storage->loadByProperties(['name' => $username]);
  $user = reset($users);
  if (!($user instanceof UserInterface)) {
    $user = $user_storage->create([
      'name' => $username,
      'mail' =>
      $username . '@example.com',
    ]);
  }
  $user->setPassword('testing');
  $user->activate();
  $user->save();
  return $user;
}
