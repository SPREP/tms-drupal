<?php

declare(strict_types=1);

namespace Drupal\met_warning;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a met warning entity type.
 */
interface METWarningInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
