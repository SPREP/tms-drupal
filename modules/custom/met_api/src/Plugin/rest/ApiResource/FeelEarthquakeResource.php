<?php

namespace Drupal\met_api\Plugin\rest\ApiResource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\met_feel_earthquake\Entity\METFeelEarthquake;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the API resource for the mobile App.
 *
 * @RestResource(
 *   id = "met_feel_earthquake_api_resource",
 *   label = @Translation("MET API Feel Earthquake Report Resouce"),
 *   uri_paths = {
 *     "create" = "/api/v1/feel-earthquake",
 *     "canonical" = "/api/v1/feel-earthquake/{id}"
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

  /**
   *
   */
  public function get($id) {

    $storage = \Drupal::service('entity_type.manager')->getStorage('met_feel_earthquake');
    $item = $storage->load($id);

    // Process location.
    $lat = 0;
    $lon = 0;
    if ($item->field_geo_location->value != "") {
      [$lat, $lon] = explode(", ", $item->field_geo_location->value);
    }

    $item = [
      'id' => $item->id->value,
      'lat' => $lat,
      'lon' => $lon,
      'rate' => $item->field_rate_earthquake->value,
      'location' => $item->field_location->value,
    ];

    return (new ResourceResponse($item, 200));
  }

  /**
   *
   */
  public function post($data) {

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

      // Check permission.
      $check = $item->access('create', $this->currentUser);

      if (!$check) {
        \Drupal::logger('MET API')->notice('Access denied, trying to create MET feel earthquake');
        $response_msg = 'Access Denied.';
        $response_code = 403;
        return $this->response($response_msg, $response_code);
      }

      $item->enforceIsNew();
      $item->access('create', $this->currentUser);
      $item->save();
      $this->logger->notice($this->t("Item with id @id saved! \n", ['@id' => $item->id()]));
      $items[] = $item->id();
    }

    $response_msg = $this->t("New item creates with items : @message", ['@message' => implode(",", $items)]);

    // We only notify the monitoring system if the report is new and has not been assigned to an event.
    if (empty($data[0]['event_id'])) {

      // Pass data to websocket server to deliver
      // ---------------------------------------------.
      $current_time = \Drupal::time()->getCurrentTime();

      // Rate
      // $ratings = [1 => 'Weak', 2 => 'Light', 3 => 'Medium', 4 => 'Strong', 5 => 'Major', 6 => 'Severe'];.
      $p = [
        'rate' => $data[0]['rate_earthquake'],
        'body' => 'body',
        'location' => $data[0]['location'],
        'date' => date('d/m/Y', $current_time),
        'time' => date('h:i a', $current_time),
      ];

      $payload = [
        'action' => 'message',
        'username' => 'drupal',
        'etype' => 'met_feel_earthquake',
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
    }

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
      'MET API permission for feel an earthquake report' => [
        'title' => $this->t('MET API permission for Feel and Earthquake Report'),
        'description' => $this->t('This is a permission to allow access to MET API feel earthquake report'),
        'restrict access' => TRUE,
      ],
    ];
  }

}
