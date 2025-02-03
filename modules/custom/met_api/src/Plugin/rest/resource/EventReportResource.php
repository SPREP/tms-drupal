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
 *   id = "met_event_report_api_resource",
 *   label = @Translation("MET API Event Report Resouce"),
 *   uri_paths = {
 *      "create" = "/api/v1/event-report"
 *   }
 * )
 */
class EventReportResource extends ResourceBase {


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
    $response_msg = 'Event Report API endpoint';

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
          'type' => 'event_report',
          'title' => $value['title'],
          'body' => [
            'summary' => '',
            'value' => $value['body'],
            'format' => 'full_html',
          ],
          'field_images' => $images,
          'field_geo_location' => [
            'lat' => $value['lat'],
            'lng' => $value['lng'],
          ],
        ]
      );

      // Check permission.
      $check = $node->access('create', $this->currentUser);

      if (!$check) {
        \Drupal::logger('MET API')->notice('Access denied, trying to create Event Report');
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

    $p = [
      'title' => $data[0]['title'],
      'body' => $data[0]['body'],
      'lat' => "{$data[0]['lat']}",
      'lon' => "{$data[0]['lng']}",
      'date' => date('d/m/Y', $current_time),
      'time' => date('h:i a', $current_time),
      'photo' => $data[0]['images'],
      'type' => 'Event Report',
      'id' => $nodes[0],
    ];

    $payload = [
      'action' => 'message',
      'username' => 'drupal',
      'etype' => 'met_event_report',
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
    return [
      'MET API permission for event report' => [
        'title' => $this->t('MET API permission for Event Report'),
        'description' => $this->t('This is a permission to allow access to MET API event report'),
        'restrict access' => TRUE,
      ],
    ];
  }

}
