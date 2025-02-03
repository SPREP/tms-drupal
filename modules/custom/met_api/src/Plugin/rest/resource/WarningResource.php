<?php

namespace Drupal\met_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the API resource for the mobile App.
 *
 * @RestResource(
 *   id = "met_api_warning_resource",
 *   label = @Translation("MET API Warning Resouce"),
 *   uri_paths = {
 *      "canonical" = "/api/v1/warning/{lng}"
 *   }
 * )
 */
class WarningResource extends ResourceBase {
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

  /**
   *
   */
  public function get($lng = 'en') {

    $storage = \Drupal::service('entity_type.manager')->getStorage('met_warning');
    $items = $storage->getQuery()
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, 10)
      ->execute();

    $items = $storage->loadMultiple($items);
    $new_items = [];
    foreach ($items as $item) {

      $data = [];
      foreach ($item->field_language as $p) {
        $ent = $p->entity;
        if ($lng == 'en' && $ent->type->target_id == 'warning_english') {
          $data['body'] = strip_tags($ent->field_body->value);
          $data['title'] = strip_tags($ent->field_title->value);
        }
        if ($lng == 'to' && $ent->type->target_id == 'warning_tongan') {
          $data['body'] = strip_tags($ent->field_body->value);
          $data['title'] = strip_tags($ent->field_title->value);
        }
      }

      $data['id'] = $item->id();
      $data['level'] = $item->field_level->value;
      $data['target_location'] = $item->field_location;
      $data['time'] = \Drupal::service('date.formatter')->format($item->created->value, 'custom', 'h:i a');
      $data['date'] = \Drupal::service('date.formatter')->format($item->created->value, 'custom', 'd/m/Y');
      $data['timestamp'] = $item->created->value;

      $new_items[$item->id()] = $data;
    }

    $build = [
      '#cache' => [
        'tags' => ['met_warning_list'],
      ],
    ];

    return (new ResourceResponse($new_items, 200))->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
  }

  /**
   *
   */
  public function permissions() {
    return [];
  }

}
