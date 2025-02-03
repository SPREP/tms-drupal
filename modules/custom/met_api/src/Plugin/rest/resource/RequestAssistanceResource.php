<?php

namespace Drupal\met_api\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the API resource for the mobile App.
 *
 * @RestResource(
 *   id = "met_api_request_assistance_resource",
 *   label = @Translation("MET API Request Assistance Resouce"),
 *   uri_paths = {
 *      "create" = "/api/v1/request-assistance"
 *   }
 * )
 */
class RequestAssistanceResource extends ResourceBase {


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
  public function jsonFormat($value) {
    return ['uri' => $value];
  }

  /**
   *
   */
  public function post($data) {

    $response_code = 201;
    $response_msg = 'Request assistance API endpoint';

    /*
    if (!$this->currentUser->hasPermission('administer site content')) {
    $response_msg = 'Access Denied.';
    $response_code = 403;
    return $this->response($response_msg, $response_code);
    }
     */

    $nodes = [];
    foreach ($data as $key => $value) {

      $images = array_map([$this, 'jsonFormat'], $value['images']);

      $node = Node::create(
        [
          'type' => 'request_assistance',
          'title' => 'Request Assistance',
          'field_full_name' => $value['full_name'],
          'field_phone_number' => $value['phone'],
          'field_location' => strtolower($value['location']),
          'body' => [
            'summary' => '',
            'value' => $value['body'],
            'format' => 'full_html',
          ],

          'field_assistance_with' => $value['assistance_with'],
          'field_needed_now' => $value['needed_now'],
          'field_images' => $images,
          'field_event' => $value['event_id'],
          'field_have_water' => $value['have_water'],
          'field_have_house_shelter' => $value['have_house'],
          'field_have_food' => $value['have_food'],
          'field_geo_location' => [
            'lat' => $value['lat'],
            'lng' => $value['lon'],
          ],
          'field_village' => $value['village'],
        ]
      );

      // Check access permission.
      $check = $node->access('create', $this->currentUser);

      if (!$check) {
        \Drupal::logger('MET API')->notice('Access denied, trying to create Assistance Report');
        $response_msg = 'Access Denied.';
        $response_code = 403;
        return $this->response($response_msg, $response_code);
      }

      $node->enforceIsNew();
      $node->save();
      $node->access('create', $this->currentUser);
      $this->logger->notice($this->t("Node with nid @nid saved! \n", ['@nid' => $node->id()]));
      $nodes[] = $node->id();
    }

    $response_msg = $this->t("New Nodes creates with nids : @message", ['@message' => implode(",", $nodes)]);

    // Pass data to websocket server to deliver
    // ---------------------------------------------.
    $current_time = \Drupal::time()->getCurrentTime();

    // Get village name from term id.
    $term = \Drupal::service('entity_type.manager')->getStorage('taxonomy_term')->load($data[0]['village']);
    $village = $term->getName();

    // Get event name from event id.
    $event = \Drupal::service('entity_type.manager')->getStorage('node')->load($data[0]['event_id']);
    $event_name = $event->getTitle();

    $p = [
      'name' => $data[0]['full_name'],
      'phone' => $data[0]['phone'],
      'location' => $data[0]['location'],
      'have_shelter' => $data[0]['have_house'],
      'assistance_with' => $data[0]['assistance_with'],
      'body' => $data[0]['body'],
      'needed_now' => $data[0]['needed_now'],
      'have_water' => $data[0]['have_water'],
      'have_food' => $data[0]['have_food'],
      'photo' => $data[0]['images'],
      'event_id' => $event_name,
      'lat' => $data[0]['lat'],
      'lon' => $data[0]['lon'],
      'village' => $village,
      'date' => date('d/m/Y', $current_time),
      'time' => date('h:i a', $current_time),
      'type' => 'Request Assistance',
    // <-- unique id
      'id' => $nodes[0] . 'ra',
    ];

    $payload = [
      'action' => 'message',
      'username' => 'drupal',
      'etype' => 'request_assistance',
      'userrole' => 'tms',
      'payload' => $p,
    ];

    $tms_socket_service = \Drupal::service('met_service.tms_socket');
    $tms_socket_service->send($payload);

    // Close the websocket connection.
    $payload = [
      'action' => 'left',
      'username' => 'drupal',
      'message' => 'left',
    ];

    $tms_socket_service->send($payload);

    return $this->response($response_msg, $response_code);
  }

  /**
   *
   */
  public function response($msg, $code) {
    $response = ['message' => $msg];
    return new ResourceResponse($response, $code);
  }

  /**
   *
   */
  public function permissions() {
    return [];
  }

}
