<?php

namespace Drupal\met_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the API resource for the mobile App
 *
 * @RestResource(
 *   id = "met_api_notification_resource",
 *   label = @Translation("MET API Notification Resouce"),
 *   uri_paths = {
 *      "canonical" = "/api/v1/notification"
 *   }
 * )
 */
class NotificationResource extends ResourceBase {
  use StringTranslationTrait;

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a Drupal\rest\Plugin\ResourceBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('MET API'),
      $container->get('current_user')
    );
  }

  public function get() {

    $storage = \Drupal::service('entity_type.manager')->getStorage('met_notification');
    $items = $storage->getQuery()
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->execute();

    $items =  $storage->loadMultiple($items);
    $new_items = [];
    foreach($items as $item) {
      $data = [];
      $data['id'] = $item->id();
      $data['title'] = $item->title->value;
      $data['body'] = $item->body->value;
      $data['level'] = $item->field_level->value;
      $data['target_location'] = $item->field_location;
      $data['time'] = $item->created->value;

      $new_items[$item->id()] = $data;
    }
    $build = ['#cache' => ['max-age' => 0]];

    return (new ResourceResponse($new_items, 200))->addCacheableDependency($build);
  }

  public function permissions() {
    return [];
  }

}
