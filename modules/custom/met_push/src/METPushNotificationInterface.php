<?php

declare(strict_types=1);

namespace Drupal\met_push;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a push notification entity type.
 */
interface METPushNotificationInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
