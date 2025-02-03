<?php

namespace Drupal\met_api\Plugin\rest\ApiResource;

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
 *   id = "met_api_village_resource",
 *   label = @Translation("MET API Village Resouce"),
 *   uri_paths = {
 *      "canonical" = "/api/v1/village/{rid}"
 *   }
 * )
 */
class VillageResource extends ResourceBase {

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
  public function get($rid) {
    $vocabulary = "cities";
    $child_terms = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->loadTree(
      $vocabulary,
      $rid,
      NULL,
      FALSE);

    $data = [];
    foreach ($child_terms as $term) {
      $output = [];
      $output['id'] = $term->tid;
      $output['name'] = $term->name;
      $data[] = $output;
    }

    $build = [
      '#cache' => [
        'tags' => ['term_list:cities'],
      ],
    ];

    return (new ResourceResponse($data, 200))->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
  }

  /**
   *
   */
  public function permissions() {
    return [];
  }

}
