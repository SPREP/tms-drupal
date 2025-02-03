<?php

declare(strict_types=1);

namespace Drupal\met_test\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Met test routes.
 */
final class MetTestController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
