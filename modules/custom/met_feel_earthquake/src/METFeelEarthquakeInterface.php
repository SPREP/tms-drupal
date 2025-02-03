<?php

declare(strict_types=1);

namespace Drupal\met_feel_earthquake;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a feel earthquake entity type.
 */
interface METFeelEarthquakeInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
