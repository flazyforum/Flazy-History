<?php
/**
 * Загрузка данных (например: функции, база данных, конфигурационные данные, и т.д.), необходимые для работы форума.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
{
	header('Content-type: text/html; charset=utf-8');
	exit('Константа FORUM_ROOT должны быть определена и ссылаться на действующий корневой каталог Flazy.');
}

// Define the version and database revision that this code was written for
define('FORUM_VERSION', '0.4');
define('FORUM_DB_REVISION', 7);

// Загрузить скрипт с функциями
require FORUM_ROOT.'include/functions/common.php';

// Загрузить скрипт с классами
require FORUM_ROOT.'include/class/common.php';

// Загрузить функции UTF-8
require FORUM_ROOT.'include/utf8/utf8.php';
require FORUM_ROOT.'include/utf8/ucwords.php';
require FORUM_ROOT.'include/utf8/trim.php';

// Обратный эффект register_globals
forum_unregister_globals();

// Ignore any user abort requests
ignore_user_abort(true);

// Attempt to load the configuration file config.php
if (file_exists(FORUM_ROOT.'include/config.php'))
	include FORUM_ROOT.'include/config.php';

// If we have the 1.2 constant defined, define the proper 1.3 constant so we don't get
// an incorrect "need to install" message
if (defined('PUN') || defined('FORUM'))
	define('FORUM', 1);

if (!defined('FORUM'))
	error('Файл \'config.php\' не существует или поврежден. Пожалуйста, запустите <a href="'.FORUM_ROOT.'admin/install.php">install.php</a>, чтобы установить Flazy.');

// Block prefetch requests
if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
{
	header('HTTP/1.1 403 Prefetching Forbidden');

	// Send no-cache headers
	header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Pragma: no-cache'); // For HTTP/1.0 compability
	exit;
}

// Record the start time (will be used to calculate the generation time for the page)
list($usec, $sec) = explode(' ', microtime());
$forum_start = ((float)$usec + (float)$sec);

// Make sure PHP reports all errors except E_NOTICE. Forum supports E_ALL, but a lot of scripts it may interact with, do not.
if (defined('FORUM_DEBUG'))
	error_reporting(E_ALL);
else
	error_reporting(E_ALL ^ E_NOTICE);

// Устанавливаем локаль для функций преобразования строк
setlocale(LC_CTYPE, 'ru_RU.UTF8');

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', FORUM_ROOT.'cache/');

// Construct REQUEST_URI if it isn't set (or if it's set improperly)
if (!isset($_SERVER['REQUEST_URI']) || (!empty($_SERVER['QUERY_STRING']) && strpos($_SERVER['REQUEST_URI'], '?') === false))
	$_SERVER['REQUEST_URI'] = (isset($_SERVER['PHP_SELF']) ? str_replace(array('%26', '%3D', '%2F'), array('&', '=', '/'), rawurlencode($_SERVER['PHP_SELF'])) : '').(!empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '');

// Load DB abstraction layer and connect
require FORUM_ROOT.'include/dblayer/common.php';

// Start a transaction
$forum_db->start_transaction();

// Load cached config
if (file_exists(FORUM_CACHE_DIR.'cache_config.php'))
	include FORUM_CACHE_DIR.'cache_config.php';

if (!defined('FORUM_CONFIG_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_config_cache();
	require FORUM_CACHE_DIR.'cache_config.php';
}

// If the request_uri is invalid try fix it
if (!defined('FORUM_IGNORE_REQUEST_URI'))
	forum_fix_request_uri();

if (!isset($base_url))
{
	// Make an educated guess regarding base_url
	$base_url_guess = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://').preg_replace('/:80$/', '', $_SERVER['HTTP_HOST']).str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
	if (substr($base_url_guess, -1) == '/')
		$base_url_guess = substr($base_url_guess, 0, -1);

	$base_url = $base_url_guess;
}

// Load hooks
if (file_exists(FORUM_CACHE_DIR.'cache_hooks.php'))
	include FORUM_CACHE_DIR.'cache_hooks.php';

if (!defined('FORUM_HOOKS_LOADED'))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_hooks_cache();
	require FORUM_CACHE_DIR.'cache_hooks.php';
}

// Define a few commonly used constants
define('FORUM_UNVERIFIED', 0);
define('FORUM_ADMIN', 1);
define('FORUM_GUEST', 2);

// A good place to add common functions for your extension
($hook = get_hook('es_essentials')) ? eval($hook) : null;

if (!defined('FORUM_MAX_POSTSIZE'))
	define('FORUM_MAX_POSTSIZE', 65535);

if (!defined('FORUM_SEARCH_MIN_WORD'))
	define('FORUM_SEARCH_MIN_WORD', 3);
if (!defined('FORUM_SEARCH_MAX_WORD'))
	define('FORUM_SEARCH_MAX_WORD', 20);

define('FORUM_ESSENTIALS_LOADED', 1);
