<?php

namespace Drupal\met_api\Plugin\rest\ApiResource;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\rest\resource\EntityResourceAccessTrait;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use http\Exception\BadHeaderException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Provides the API resource for the mobile App.
 *
 * @RestResource(
 *   id = "met_user_resource",
 *   label = @Translation("MET User Account Resouce"),
 *   uri_paths = {
 *     "create" = "/api/v1/user/{type}",
 *     "canonical" = "/api/v1/user/{uid}"
 *   }
 * )
 */
class UserResource extends ResourceBase {
  use StringTranslationTrait;
  use EntityResourceValidationTrait;
  use EntityResourceAccessTrait;

  /**
   * User settings config instance.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $userSettings;

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
   * @param \Drupal\Core\Config\ImmutableConfig $user_settings
   *   A user settings config instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, ImmutableConfig $user_settings, AccountProxyInterface $current_user) {

    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->userSettings = $user_settings;
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
      $container->get('config.factory')->get('user.settings'),
      $container->get('current_user')
    );
  }

  /**
   *
   */
  public function patch(Request $request, $uid) {

    $this->logger->debug('user id uid: ' . $uid);

    $payload = json_decode($request->getContent());

    // $msg = "<pre>" . print_r($payload, true) . "</pre>";
    // $this->logger->debug($msg);
    [$updateField] = $payload;
    $account = User::load($uid);

    if (isset($updateField->photo)) {
      $image = [
        'uri' => $updateField->photo,
      ];
      $account->set('field_user_picture', $image);
    }
    $account->save();
    $photo = $account->get('field_user_picture')->getValue()[0]['uri'];

    $output = [
      'name' => $account->get('name')->value,
      'id' => $account->id(),
      'mail' => $account->get('mail')->value,
      'photo' => $photo != '' ? $photo : 'https://macres-media-storage.s3.ap-southeast-2.amazonaws.com/user.png',
    ];

    $build = ['#cache' => ['max-age' => 0]];

    return (new ResourceResponse($output, 200))->addCacheableDependency($build);

  }

  /**
   *
   */
  public function post(Request $request, $type = NULL) {

    $payload = json_decode($request->getContent());

    switch ($type) {
      case 'login':
        return $this->login($payload);

      case 'register':
        return $this->register($payload);

      case 'logout':
        return $this->logout($payload);

      case 'forgot':
        return $this->forgot($payload);
    }
    throw new BadRequestHttpException('No type was provided or type is unknown');
  }

  /**
   *
   */
  public function logout($data) {

    $user = \Drupal::currentUser();

    if ($user->isAnonymous()) {
      $output = ['message' => 'Anonymous user'];
    }
    else {

      \Drupal::logger('user')
        ->info('Session closed for %name.', [
          '%name' => $user
            ->getAccountName(),
        ]);
      \Drupal::moduleHandler()
        ->invokeAll('user_logout', [
          $user,
        ]);

      \Drupal::service('session_manager')->destroy();
      $user->setAccount(new AnonymousUserSession());
      $output = ['message' => 'Logout success'];
    }

    $build = ['#cache' => ['max-age' => 0]];

    return (new ResourceResponse($output, 200))->addCacheableDependency($build);
  }

  /**
   *
   */
  public function login($data) {
    $data = $data->user;
    if (!isset($data->mail)) {
      throw new BadRequestHttpException('Missing credentials.mail.');
    }
    $user = user_load_by_mail($data->mail);
    if (!$user) {
      throw new BadRequestHttpException('Invalid username or password');
    }

    if (\Drupal::service('user.auth')->authenticate($user->get('name')->value, $data->pass)) {
      user_login_finalize($user);

      $session_manager = \Drupal::service('session_manager');
      $session_id = $session_manager->getId();
      $session_name = $session_manager->getName();
    }
    else {
      throw new BadRequestHttpException('Invalid username or password');
    }

    // Load all impact report and Assistance request belong to this user.
    /*
    $nids = \Drupal::entityQuery('node')
    ->condition('type','request_assistance')
    ->condition('author',$user)
    ->execute();
    $nodes =  \Drupal\node\Entity\Node::loadMultiple($nids);
    $data = [];
    foreach($nodes as $node) {
    $data[] = [
    'id' => $node->id(),
    'title' => $node->getTitle(),
    'time' => $node->get('created'),
    ];
    }
     */

    $photo = $user->get('field_user_picture')->getValue()[0]['uri'];

    $userArr = [
      'name' => $user->get('name')->value,
      'id' => $user->id(),
      'mail' => $user->get('mail')->value,
      'photo' => $photo != '' ? $photo : 'https://macres-media-storage.s3.ap-southeast-2.amazonaws.com/user.png',
    ];

    $build = ['#cache' => ['max-age' => 0]];

    return (new ResourceResponse($userArr, 200))->addCacheableDependency($build);
  }

  /**
   *
   */
  public function forgot($data) {

    $account = user_load_by_mail($data->user->mail);
    if (!$account) {
      throw new BadRequestHttpException('Unknown email address ' . $data->user->mail);
    }
    $mail = _user_mail_notify('password_reset', $account);

    if (!$mail) {
      throw new BadHeaderException('Unable to send email, please try again later.');
    }

    $msg = 'Please check your email for more information.';
    $build = ['#cache' => ['max-age' => 0]];
    $output = ['message' => $msg];
    return (new ResourceResponse($output, 200))->addCacheableDependency($build);
  }

  /**
   *
   */
  public function register($data) {

    // @todo Check email address (load by mail and see if exist then throw error)
    if (user_load_by_mail($data->mail)) {
      throw new BadRequestHttpException('Email address already used. Use another email.');
    }

    $account = User::create([
      "name" => $data->name,
      "mail" => $data->mail,
      "pass" => $data->pass,
    ]);

    // $this->ensureAccountCanRegister($account);
    // $this->checkEditFieldAccess($account);
    // Make sure that the user entity is valid (email and name are valid).
    $this->validate($account);

    $account->enforceIsNew();
    $account->activate();

    // Create the account.
    $account->save();

    // $this->sendEmailNotifications($account);
    return new ModifiedResourceResponse($account, 200);
  }

  /**
   *
   */
  public function get($uid = NULL) {

    $user = \Drupal::currentUser();

    if ($user->isAnonymous() || $user->id() !== $uid) {
      throw new AccessDeniedHttpException('Access denied');
    }

    if (!is_null($uid)) {
      $rids = ['authenticated'];
      $storage = \Drupal::service('entity_type.manager')->getStorage('user');
      $item = $storage->getQuery()
        ->condition('status', 1)
        ->condition('uid', '1', '!=')
        ->condition('uid', $uid)
        ->accessCheck(FALSE)
        ->execute();

      $user = $storage->loadMultiple($item);

      if (empty($user)) {
        throw new NotFoundHttpException("User with ID '$uid' was not found");
      }

      $build = ['#cache' => ['max-age' => 0]];

      return (new ResourceResponse($user, 200))->addCacheableDependency($build);
    }
    throw new BadRequestHttpException('No user ID was provided');
  }

  /**
   * Ensure the account can be registered in this request.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to register.
   */
  public function ensureAccountCanRegister(UserInterface $account = NULL) {
    if ($account === NULL) {
      throw new BadRequestHttpException('No user account data for registration received.');
    }

    // POSTed user accounts must not have an ID set, because we always want to
    // create new entities here.
    if (!$account->isNew()) {
      throw new BadRequestHttpException('An ID has been set and only new user accounts can be registered.');
    }

    // Only allow anonymous users to register, authenticated users with the
    // necessary permissions can POST a new user to the "user" REST resource.
    // @see \Drupal\rest\Plugin\rest\resource\EntityResource
    if (!$this->currentUser->isAnonymous()) {
      throw new AccessDeniedHttpException('Only anonymous users can register a user.');
    }

    // Verify that the current user can register a user account.
    if ($this->userSettings->get('register') == UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
      throw new AccessDeniedHttpException('You cannot register a new user account.');
    }

    if (!$this->userSettings->get('verify_mail')) {
      if (empty($account->getPassword())) {
        // If no e-mail verification then the user must provide a password.
        throw new UnprocessableEntityHttpException('No password provided.');
      }
    }
    else {
      if (!empty($account->getPassword())) {
        // If e-mail verification required then a password cannot provided.
        // The password will be set when the user logs in.
        throw new UnprocessableEntityHttpException('A Password cannot be specified. It will be generated on login.');
      }
    }
  }

  /**
   *
   */
  public function permissions() {
    return [];
  }

}
