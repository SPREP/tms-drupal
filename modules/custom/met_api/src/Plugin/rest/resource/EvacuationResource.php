<?php

namespace Drupal\met_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\image\Entity\ImageStyle;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the API resource for the mobile App.
 *
 * @RestResource(
 *   id = "met_api_evacuation_resource",
 *   label = @Translation("MET API Evacuation Resouce"),
 *   uri_paths = {
 *      "canonical" = "/api/v1/evacuation"
 *   }
 * )
 */
class EvacuationResource extends ResourceBase {

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
      $container->get('current_user'),
    );
  }

  /**
   *
   */
  public function get() {

    $storage = \Drupal::service('entity_type.manager')->getStorage('node');
    $nids = $storage->getQuery()
      ->condition('type', 'evacuation')
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->accessCheck(FALSE)
      ->execute();

    $nodes = $storage->loadMultiple($nids);
    $new_nodes = [];
    foreach ($nodes as $node) {

      // Process location.
      $lat = '';
      $lon = '';
      if ($node->field_geo_location->value != "") {
        [$lat, $lon] = explode(", ", $node->field_geo_location->value);
      }

      // Process photo
      // $large_image = ImageStyle::load('large')->buildUrl($node->field_safe_zone_image->entity->getFileUri());
      $large_image = \Drupal::service('file_url_generator')->generateAbsoluteString($node->field_safe_zone_image->entity->getFileUri());
      $thumb_image = ImageStyle::load('thumbnail')->buildUrl($node->field_safe_zone_image->entity->getFileUri());

      $data = [];
      $data['id'] = (int) $node->id();
      $data['title'] = $node->title->value;
      $data['body'] = $node->body->value != '' ? strip_tags($node->body->value) : $node->body->value;
      $data['image_large'] = $large_image;
      $data['image_small'] = $thumb_image;
      $data['lat'] = (Double) $lat;
      $data['lon'] = (Double) $lon;

      $new_nodes[] = $data;
    }

    $build = [
      '#cache' => [
        'tags' => ['node_list:evacuation'],
      ],
    ];

    return (new ResourceResponse($new_nodes, 200))->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
  }

  /**
   *
   */
  public function permissions() {
    return [];
  }

}
