<?php

declare(strict_types=1);

namespace Drupal\met_notification;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a met notification entity type.
 */
interface METNotificationInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
