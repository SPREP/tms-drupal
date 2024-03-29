<?php

namespace Drupal\met_api\Plugin\rest\resource;

use Drupal\met_feel_earthquake\Entity\METFeelEarthquake;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

/**
 * Provides the API resource for the mobile App
 *
 * @RestResource(
 *   id = "met_feel_earthquake_api_resource",
 *   label = @Translation("MET API Feel Earthquake Report Resouce"),
 *   uri_paths = {
 *      "create" = "/api/v1/feel-earthquake"
 *   }
 * )
 */
class FeelEarthquakeResource extends ResourceBase {


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

  public function  post($data) {

    $response_code = 201;
    $response_msg = 'Feel the earthquake API endpoint';

    /*
    if (!$this->currentUser->hasPermission('administer site content')) {
      $response_msg = 'Access Denied.';
      $response_code = 403;
      return $this->response($response_msg, $response_code);
    }
    */

    $items = [];
    foreach ($data as $key => $value) {

      $item = METFeelEarthquake::create(
        [
          'field_event' => $value['event_id'],
          'type' => 'feel_earthquake',
          'label' => 'Feel Earthquake',
          'body' => [
            'summary' => '',
            'value' => $value['body'],
            'format' => 'full_html',
          ],
          'field_location' => strtolower($value['location']),
          'field_rate_earthquake' => $value['rate_earthquake'],
          'field_geo_location' => [
            'lat' => $value['lat'],
            'lng' => $value['lng'],
          ],
        ]
      );

      $item->enforceIsNew();
      $item->save();
      $this->logger->notice($this->t("Item with id @id saved! \n", ['@id' => $item->id()]));
      $items[] = $item->id();
    }

    $response_msg = $this->t("New item creates with items : @message", ['@message' => implode(",", $items)]);
    return $this->response($response_msg, $response_code);
  }

  public function response($msg, $code) {
    $response = ['message' => $msg];
    return new ResourceResponse($response, $code);
  }

  public function permissions() {
    return [];
  }

}
