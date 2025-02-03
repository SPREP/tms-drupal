<?php

namespace Drupal\met_api\Plugin\rest\resource;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;
use Drupal\met_tk\Entity\METTK;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the API resource for the Traditional Knowledge in mobile App.
 *
 * @RestResource(
 *   id = "met_api_tk",
 *   label = @Translation("MET Traditional Knowledge Resouce"),
 *   uri_paths = {
 *     "create" = "/api/v1/tk",
 *     "canonical" = "/api/v1/tk/{flag}"
 *   }
 * )
 */
class TkResource extends ResourceBase {
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
  public function get($flag = 'tk') {
    switch ($flag) {
      case 'tk':
        return $this->getTk();

      case 'ind':
        return $this->getIndicators();
    }
  }

  /**
   *
   */
  public function post($data) {

    // Create TK Content.
    $response_code = 201;

    /*
    if (!$this->currentUser->hasPermission('administer site content')) {
    $response_msg = 'Access Denied.';
    $response_code = 403;
    return $this->response($response_msg, $response_code);
    }
     */

    $items = [];
    foreach ($data as $key => $value) {

      $item = METTK::create(
        [
          'field_photo' => $value['image'],
          'label' => $value['label'],
          'body' => [
            'summary' => '',
            'value' => $value['description'],
            'format' => 'full_html',
          ],
          'field_indicator' => ['target_id' => $value['indicator']],
          'field_geo_location' => [
            'lat' => $value['lat'],
            'lng' => $value['lng'],
          ],
          'field_time' => $value['date'],
          'status' => 0,
        ]
      );

      $check = $item->access('create', $this->currentUser);

      if (!$check) {
        \Drupal::logger('MET API')->notice('Access denied, trying to create MET TK');
        $response_msg = 'Access Denied.';
        $response_code = 403;
        return $this->response($response_msg, $response_code);
      }

      $item->enforceIsNew();
      $item->save();
      $item->access('create', $this->currentUser);
      $this->logger->notice($this->t("TK content with id @id saved! \n", ['@id' => $item->id()]));
      $items[] = $item->id();
    }

    $response_msg = $this->t("New TK content creates with items : @message", ['@message' => implode(",", $items)]);
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
  public function getIndicators() {

    $vid = 'tk_indicators';
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);

    $data = [];
    $weight = 0;
    foreach ($terms as $term) {
      $term = Term::load($term->tid);

      // Get indicator photo.
      $fid = $term->get('field_photo')->target_id;
      $file = File::load($fid);
      $photo_url = $file->createFileUrl(FALSE);

      $desc = $term->get('description')->getValue()[0]['value'];

      $data[] = [
        'name' => $term->getName(),
        'desc' => strip_tags($desc),
        'photo' => $photo_url,
        'id' => (int) $term->id(),
        'weight' => ++$weight,
      ];
    }

    $build = [
      '#cache' => [
        'tags' => ['taxonomy_term_list:tk_indicators'],
      ],
    ];

    return (new ResourceResponse($data, 200))->addCacheableDependency(CacheableMetadata::createFromRenderArray($build));
  }

  /**
   *
   */
  public function getTk() {

    $storage = \Drupal::service('entity_type.manager')->getStorage('met_tk');
    $items = $storage->getQuery()
      ->condition('status', 1)
      ->accessCheck(FALSE)
      ->sort('created', 'DESC')
      ->range(0, 500)
      ->execute();

    $items = $storage->loadMultiple($items);
    $new_items = [];
    foreach ($items as $item) {

      $data = [];

      // Process location.
      $lat = 0;
      $lon = 0;
      if ($item->field_geo_location->value != "") {
        [$lat, $lon] = explode(", ", $item->field_geo_location->value);
      }
      // Get indicator from taxonomy.
      $termid = $item->get('field_indicator')->target_id;
      $term = Term::load($termid);

      // Get indicator photo.
      $fid = $term->get('field_photo')->target_id;
      $file = File::load($fid);
      $photo_url = $file->createFileUrl(FALSE);

      $desc = $term->get('description')->getValue()[0]['value'];

      $data['indicator'] = [
        'name' => $term->getName(),
        'desc' => strip_tags($desc),
        'photo' => $photo_url,
        'id' => (int) $term->id(),
      ];

      // Get tk photo.
      $image = $item->get('field_photo')->getValue()[0]['uri'];
      $photo = $image != NULL ? $image : '';

      // Get author.
      $author = $item->getOwner()->getDisplayName();
      $uid = $item->getOwner()->Id();
      // Get author image.
      $user = User::load($uid);
      $author_photo = $user->get('field_user_picture')->getValue()[0]['uri'];

      $data['id'] = (int) $item->id();
      $data['lat'] = (double) $lat;
      $data['lon'] = (double) $lon;
      // <-- use time in the image
      $data['time'] = $item->get('field_time')->value;
      // <-- use date in the image
      $data['date'] = $item->get('field_time')->value;
      $data['timestamp'] = $item->created->value;
      $data['photo'] = $photo;
      $data['title'] = $item->get('label')->value;
      $data['author_name'] = $author;
      $data['author_image'] = $author_photo != '' ? $author_photo : 'https://macres-media-storage.s3.ap-southeast-2.amazonaws.com/user.png';

      $new_items[] = $data;
    }
    $build = ['#cache' => ['max-age' => 0]];

    return (new ResourceResponse($new_items, 200))->addCacheableDependency($build);
  }

  /**
   *
   */
  public function permissions() {
    return [];
  }

}
