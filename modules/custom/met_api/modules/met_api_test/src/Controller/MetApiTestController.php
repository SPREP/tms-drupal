<?php

declare(strict_types=1);

namespace Drupal\met_api_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Met api test routes.
 */
final class MetApiTestController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly Connection $connection,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('database'),
    );
  }

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
