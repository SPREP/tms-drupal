<?php

declare(strict_types=1);

namespace Drupal\met_tk;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a met tk entity type.
 */
interface METTKInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
