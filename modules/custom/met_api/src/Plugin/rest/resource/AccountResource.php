<?php

namespace Drupal\met_api\Plugin\rest\resource;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\Plugin\rest\resource\EntityResourceAccessTrait;
use Drupal\rest\Plugin\rest\resource\EntityResourceValidationTrait;
use Drupal\rest\ResourceResponse;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * Provides the API resource for the mobile App.
 *
 * @RestResource(
 *   id = "met_account_resource",
 *   label = @Translation("MET User Account Resouce"),
 *   serialization_class = "Drupal\user\Entity\User",
 *   uri_paths = {
 *     "create" = "/api/v1/user",
 *     "canonical" = "/api/v1/user/{uid}"
 *   }
 * )
 */
class AccountResource extends ResourceBase {
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
  public function patch(AccountInterface $account) {
    // Create the account.
    $account->save();
    return new ModifiedResourceResponse($account, 200);
  }

  /**
   *
   */
  public function post(AccountInterface $account) {

    $this->ensureAccountCanRegister($account);

    $this->checkEditFieldAccess($account);

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
