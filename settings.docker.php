<?php

$settings['config_sync_directory'] = 'config/sync';

$databases['default']['default'] = array(
  'driver' => getenv('DB_DRIVER'),
  'database' => getenv('DB_NAME'),
  'username' => getenv('DB_USER'),
  'password' => getenv('DB_PASSWORD'),
  'host' => getenv('DB_HOST')
);

## DRUPAL 8
$settings['trusted_host_patterns'] = array(
  '\.localhost$', '\.local$', '\.loc$', 'localhost', 'nginx'
);

// For Redis
// $settings['redis.connection']['host'] = 'redis';
// $settings['redis.connection']['port'] = '6379';
// //$settings['redis.connection']['password'] = '';
// $settings['redis.connection']['base'] = 0;
// $settings['redis.connection']['interface'] = 'PhpRedis';
// $settings['cache']['default'] = 'cache.backend.redis';
// $settings['cache']['bins']['bootstrap'] = 'cache.backend.chainedfast';
// $settings['cache']['bins']['discovery'] = 'cache.backend.chainedfast';
// $settings['cache']['bins']['config'] = 'cache.backend.chainedfast';
// $settings['container_yamls'][] = "modules/contrib/redis/example.services.yml";

/**
 * Show all error messages, with backtrace information.
 *
 * In case the error level could not be fetched from the database, as for
 * example the database connection failed, we rely only on this value.
 */
$config['system.logging']['error_level'] = 'verbose';


/**
 * Private file path:
 *
 * A local file system path where private files will be stored. This directory
 * must be absolute, outside of the Drupal installation directory and not
 * accessible over the web.
 *
 * Note: Caches need to be cleared when this value is changed to make the
 * private:// stream wrapper available to the system.
 *
 * See https://www.drupal.org/documentation/modules/file for more information
 * about securing private files.
 */
// $settings['file_private_path'] = '/var/www/private';


/**
 * Disable CSS and JS aggregation in development.
 */
$config['system.performance']['css']['preprocess'] = FALSE;
$config['system.performance']['js']['preprocess'] = FALSE;

/**
 * Skip file system permissions hardening.
 *
 * The system module will periodically check the permissions of your site's
 * site directory to ensure that it is not writable by the website user. For
 * sites that are managed with a version control system, this can cause problems
 * when files in that directory such as settings.php are updated, because the
 * user pulling in the changes won't have permissions to modify files in the
 * directory.
 */
$settings['skip_permissions_hardening'] = TRUE;

/**
 * Exclude modules from configuration synchronization.
 *
 * On config export sync, no config or dependent config of any excluded module
 * is exported. On config import sync, any config of any installed excluded
 * module is ignored. In the exported configuration, it will be as if the
 * excluded module had never been installed. When syncing configuration, if an
 * excluded module is already installed, it will not be uninstalled by the
 * configuration synchronization, and dependent configuration will remain
 * intact. This affects only configuration synchronization; single import and
 * export of configuration are not affected.
 *
 * Drupal does not validate or sanity check the list of excluded modules. For
 * instance, it is your own responsibility to never exclude required modules,
 * because it would mean that the exported configuration can not be imported
 * anymore.
 *
 * This is an advanced feature and using it means opting out of some of the
 * guarantees the configuration synchronization provides. It is not recommended
 * to use this feature with modules that affect Drupal in a major way such as
 * the language or field module.
 */
// $settings['config_exclude_modules'] = [
//   'devel',
//   'config_update_ui',
//   'dblog',
//   'libraries_ui',
//   'styleguide',
//   'tour_ui',
//   'tour_builder',
//   'vmi',
//   'stage_file_proxy'
// ];

$config['system.site']['name'] = "DEV ENVIRONMENT";

// config split settings
$_env_is_live = FALSE;
$config['config_split.config_split.dev']['status'] = !$_env_is_live;
$config['config_split.config_split.live']['status'] = $_env_is_live;

/**
 * Connect to Azure SSO
 */
//$config['openid_connect.settings.windows_aad']['settings']['client_id'] = '90eeb306-9e30-4fab-a1c1-327f013b7249';
//$config['openid_connect.settings.windows_aad']['settings']['client_secret'] = getenv('SSO_CLIENT_SECRET'); // In Prod, set this via wodby.
//$config['openid_connect.settings.windows_aad']['settings']['authorization_endpoint_wa'] = 'https://login.microsoftonline.com/1b892310-cd08-4f86-8b97-4805b93e7397/oauth2/authorize';
//$config['openid_connect.settings.windows_aad']['settings']['token_endpoint_wa'] = 'https://login.microsoftonline.com/1b892310-cd08-4f86-8b97-4805b93e7397/oauth2/token';

// Disables the openid connect login process so that tests run with normal login process.
// $config['openid_connect.settings']['user_login_display'] = 'hidden';

// Google anlytics
// $config['google_analytics.settings']['account'] = isset($_SERVER['GA']) ? $_SERVER['GA'] : 'UA-xxxxxxx-xx';
