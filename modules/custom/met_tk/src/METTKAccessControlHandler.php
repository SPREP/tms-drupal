<?php

declare(strict_types=1);

namespace Drupal\met_tk;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the met tk entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class METTKAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return match($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, ['view met tk', 'administer met tk types'], 'OR'),
      'update' => AccessResult::allowedIfHasPermissions($account, ['edit met tk', 'administer met tk types'], 'OR'),
      'delete' => AccessResult::allowedIfHasPermissions($account, ['delete met tk', 'administer met tk types'], 'OR'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create met tk', 'administer met tk types'], 'OR');
  }

}
