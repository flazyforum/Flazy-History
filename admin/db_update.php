<?php
/**
 * Скрипт обновления базы данных.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


define('UPDATE_TO', '0.4');
define('UPDATE_TO_DB_REVISION', 7);

// The number of items to process per pageview (lower this if the update script times out during UTF-8 conversion)
define('PER_PAGE', 300);

define('MIN_MYSQL_VERSION', '4.1.2');

header('Content-Type: text/html; charset=utf-8');

// Make sure we are running at least PHP 4.3.0
if (!function_exists('version_compare') || version_compare(PHP_VERSION, MIN_PHP_VERSION, '<'))
	exit('Ваша версия PHP '.PHP_VERSION.'. Чтобы правильно работать, Flazy требуется  хотя бы PHP '.MIN_PHP_VERSION.'. Вам необходимо обновить PHP, и только тогда вы сможите прожолжить установку.');


define('FORUM_ROOT', '../');

// Attempt to load the configuration file config.php
if (file_exists(FORUM_ROOT.'include/config.php'))
	include FORUM_ROOT.'include/config.php';


// If FORUM isn't defined, config.php is missing or corrupt or we are outside the root directory
if (!defined('FORUM'))
	exit('Не могу найти config.php, вы уверены, что он существует?');

// Enable debug mode
if (!defined('FORUM_DEBUG'))
	define('FORUM_DEBUG', 1);

// Turn on full PHP error reporting
error_reporting(E_ALL);

// Turn off magic_quotes_runtime
set_magic_quotes_runtime(0);

// Turn off PHP time limit
@set_time_limit(0);

// If a cookie name is not specified in config.php, we use the default (forum_cookie)
if (empty($cookie_name))
	$cookie_name = 'flazy_cookie';

// If the cache directory is not specified, we use the default setting
if (!defined('FORUM_CACHE_DIR'))
	define('FORUM_CACHE_DIR', FORUM_ROOT.'cache/');

// Load the functions script
require FORUM_ROOT.'include/functions/common.php';

// Load UTF-8 functions
require FORUM_ROOT.'include/utf8/utf8.php';
require FORUM_ROOT.'include/utf8/ucwords.php';
require FORUM_ROOT.'include/utf8/trim.php';

// Strip out "bad" UTF-8 characters
forum_remove_bad_characters();

// If the request_uri is invalid try fix it
if (!defined('FORUM_IGNORE_REQUEST_URI'))
	forum_fix_request_uri();

// Instruct DB abstraction layer that we don't want it to "SET NAMES". If we need to, we'll do it ourselves below.
define('FORUM_NO_SET_NAMES', 1);

// Load DB abstraction layer and try to connect
require FORUM_ROOT.'include/dblayer/common.php';

// Check current version
$query = array(
	'SELECT'	=> 'conf_value',
	'FROM'		=> 'config',
	'WHERE'		=> 'conf_name = \'o_cur_version\''
);

$result = $forum_db->query_build($query);
$cur_version = $forum_db->result($result);

if (version_compare($cur_version, '0.3', '<'))
	error('Version mismatch. The database \''.$db_name.'\' doesn\'t seem to be running a Flazy database schema supported by this update script.', __FILE__, __LINE__);

// If we've already done charset conversion in a previous update, we have to do SET NAMES
$forum_db->set_names(strpos($cur_version, '0.3') === 0 ? 'utf8' : 'latin1');

// If MySQL, make sure it's at least 4.1.2
if ($db_type == 'mysql' || $db_type == 'mysqli')
{
	$mysql_info = $forum_db->get_version();
	if (version_compare($mysql_info['version'], MIN_MYSQL_VERSION, '<'))
		error('	Вы используете MySQL '.$mysql_version.'. Flazy '.UPDATE_TO.' требует, по минимум MySQL '.MIN_MYSQL_VERSION.' для правильной работы. Сначало вы должны обновить MySQL и тольо тогда вы сможете продолжить');
}

// Get the forum config
$query = array(
	'SELECT'	=> '*',
	'FROM'		=> 'config'
);

$result = $forum_db->query_build($query);
while ($cur_config_item = $forum_db->fetch_row($result))
	$forum_config[$cur_config_item[0]] = $cur_config_item[1];

// Check the database revision and the current version
if (isset($forum_config['o_database_revision']) && $forum_config['o_database_revision'] >= UPDATE_TO_DB_REVISION && version_compare($forum_config['o_cur_version'], UPDATE_TO, '>='))
	error('Ваша база данных не нуждается в обновлении.');

// If $base_url isn't set, use o_base_url from config
if (!isset($base_url))
	$base_url = $forum_config['o_base_url'];

// There's no $forum_user, but we need the style element
// We default to Oxygen if the default style is invalid.
if (file_exists(FORUM_ROOT.'style/'.$forum_config['o_default_style'].'/'.$forum_config['o_default_style'].'.php'))
	$forum_user['style'] = $forum_config['o_default_style'];
else
{
	$forum_user['style'] = 'Flazy_Cold';

	$query = array(
		'UPDATE'	=> 'config',
		'SET'		=> 'conf_value=\'Flazy_Cold\'',
		'WHERE'		=> 'conf_name=\'o_default_style\''
	);

	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

$maintenance_message = $forum_config['o_maintenance_message'];

if(empty($style_url))
	$style_url = $base_url;

// Empty all output buffers and stop buffering
while (@ob_end_clean());


$stage = isset($_GET['stage']) ? $_GET['stage'] : '';
$old_charset = isset($_GET['req_old_charset']) ? str_replace('ISO8859', 'ISO-8859', strtoupper($_GET['req_old_charset'])) : 'ISO-8859-1';
$start_at = isset($_GET['start_at']) ? intval($_GET['start_at']) : 0;
$query_str = '';

switch ($stage)
{
	// Show form
	case '':
	
	define ('FORUM_PAGE', 'dbupdate');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Обновление Базы Данных Flazy</title>
<?php

// Include the stylesheets
require FORUM_ROOT.'style/'.$forum_user['style'].'/'.$forum_user['style'].'.php';

?>
<script type="text/javascript" src="<?php echo $base_url ?>/include/js/common.js"></script>
</head>
<body>

<div id="brd-update" class="brd-page">
<div id="brd-wrap" class="brd">

<div id="brd-head" class="gen-content">
	<p id="brd-title"><strong>Обновление Базы Данных Flazy</strong></p>
	<p id="brd-desc">Обновление таблиц БД</p>
</div>

<div id="brd-main" class="main basic">

	<div class="main-head">
		<h1 class="hn"><span>Обновление Базы Данных Flazy: Выполните обновление.</span></h1>
	</div>

	<div class="main-content frm">
		<div class="ct-box info-box">
			<ul class="spaced">
				<li class="warn"><span><strong>Внимание!</strong> Процедура обновления может занять от нескольких секунд до нескольких минут (или, в крайнем случае, часов) в зависимости от скорости сервера, размера базы данных форума, и числа требуемых изменений.</span></li>
				<li><span>Не забудьте сделать резервную копию данных перед тем, как продолжить.</span></li>
				<li><span>Прочитали ли вы <a href="http://flazy.ru/flazy/wiki/obnovlenie#obnovlenie_do_novoj_versii"><span>инструкциию по обновлению</span></a>? Если нет, обязательно прочитайте.</span></li>
			</ul>
		</div>
<?php

	$current_url = get_current_url();

?>
		<form class="frm-form" method="get" accept-charset="utf-8" action="<?php echo $current_url ?>">
			<div class="hidden">
				<input type="hidden" name="stage" value="start" />
			</div>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="start" value="Начать обновление" /></span>
			</div>
		</form>
	</div>

</div>

</div>
</div>
</body>
</html>
<?php

		break;

	// Start by updating the database structure
	case 'start':

		// Включение техобслуживания
		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\'1\'',
			'WHERE'		=> 'conf_name=\'o_maintenance\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// p_poll_min_posts
		$query = array(
			'SELECT'	=>	'1',
			'FROM'		=>	'config',
			'WHERE'		=>	'conf_name="p_poll_min_posts"'
		);

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (!$forum_db->num_rows($result))
		{
			$query = array(
				'INSERT'    => 'conf_name, conf_value',
				'INTO'      => 'config',
				'VALUES'    => '\'p_poll_min_posts\', \'0\''
			);

			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}

		// o_show_ua_info
		$query = array(
			'SELECT'	=>	'1',
			'FROM'		=>	'config',
			'WHERE'		=>	'conf_name="o_show_ua_info"'
		);

		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (!$forum_db->num_rows($result))
		{
			$query = array(
				'INSERT'    => 'conf_name, conf_value',
				'INTO'      => 'config',
				'VALUES'    => '\'o_show_ua_info\', \'1\''
			);

			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}

		// reported
		$query = array(
			'SELECT'	=>	'reported',
			'FROM'		=>	'posts',
		);

		$result = $forum_db->query_build($query);

		if (!$forum_db->num_rows($result))
			$forum_db->query('ALTER TABLE '.$forum_db->prefix.'posts ADD reported TINYINT(1) DEFAULT 0 NOT NULL AFTER topic_id') or error(__FILE__, __LINE__);

		// voted
		$query = array(
			'SELECT'	=>	'voted',
			'FROM'		=>	'voting',
		);

		$result = $forum_db->query_build($query);

		if (!$forum_db->num_rows($result))
			$forum_db->query('ALTER TABLE '.$forum_db->prefix.'voting ADD voted INT(10) UNSIGNED DEFAULT 0 NOT NULL AFTER answer_id') or error(__FILE__, __LINE__);

		// Соц. Сети
		$query = array(
			'SELECT'	=>	'vkontakte',
			'FROM'		=>	'users',
		);

		$result = $forum_db->query_build($query);

		if (!$forum_db->num_rows($result))
			$forum_db->query('ALTER TABLE '.$forum_db->prefix.'users ADD vkontakte VARCHAR(12) AFTER magent') or error(__FILE__, __LINE__);

		$query = array(
			'SELECT'	=>	'classmates',
			'FROM'		=>	'users',
		);

		$result = $forum_db->query_build($query);

		if (!$forum_db->num_rows($result))
			$forum_db->query('ALTER TABLE '.$forum_db->prefix.'users ADD classmates VARCHAR(80) AFTER vkontakte') or error(__FILE__, __LINE__);

		$query = array(
			'SELECT'	=>	'mirtesen',
			'FROM'		=>	'users',
		);

		$result = $forum_db->query_build($query);

		if (!$forum_db->num_rows($result))
			$forum_db->query('ALTER TABLE '.$forum_db->prefix.'users ADD mirtesen VARCHAR(12) AFTER classmates') or error(__FILE__, __LINE__);

		$query = array(
			'SELECT'	=>	'moikrug',
			'FROM'		=>	'users',
		);

		$result = $forum_db->query_build($query);

		if (!$forum_db->num_rows($result))
			$forum_db->query('ALTER TABLE '.$forum_db->prefix.'users ADD moikrug VARCHAR(30) AFTER mirtesen') or error(__FILE__, __LINE__);

		// Включение техобслуживания
		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\'На форуме ведутся профилактические работы. Соблюдайте спокойствие. В ближайшее время форум возобновит свою работу. Спасибо за понимание!\'',
			'WHERE'		=> 'conf_name=\'o_maintenance_message\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query_str = '?stage=finish';

		break;

	case 'finish':
		// Now we're definitely using UTF-8, so we convert the output properly
		$forum_db->set_names('utf8');

		// We update the version number
		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\''.UPDATE_TO.'\'',
			'WHERE'		=> 'conf_name=\'o_cur_version\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// And the database revision number
		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\''.UPDATE_TO_DB_REVISION.'\'',
			'WHERE'		=> 'conf_name=\'o_database_revision\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Отключение техобслуживания
		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\'0\'',
			'WHERE'		=> 'conf_name=\'o_maintenance\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'UPDATE'	=> 'config',
			'SET'		=> 'conf_value=\''.$maintenance_message.'\'',
			'WHERE'		=> 'conf_name=\'o_maintenance_message\''
		);

		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		// Empty the PHP cache
		forum_clear_cache();


	define ('FORUM_PAGE', 'dbupdate-finish');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Обновление Базы Данных Flazy</title>
<?php

// Include the stylesheets
require FORUM_ROOT.'style/'.$forum_user['style'].'/'.$forum_user['style'].'.php';

?>
<script type="text/javascript" src="<?php echo $base_url ?>/include/js/common.js"></script>
</head>
<body>

<div id="brd-update" class="brd-page">
<div id="brd-wrap" class="brd">

<div id="brd-head" class="gen-content">
	<p id="brd-title"><strong>Обновление Базы Данных Flazy</strong></p>
	<p id="brd-desc">Обновление таблиц БД</p>
</div>

<div id="brd-main" class="main basic">

	<div class="main-head">
		<h1 class="hn"><span>Обновление Базы Данных Flazy завершено!</span></h1>
	</div>

	<div class="main-content frm">
		<div class="ct-box info-box">
			<p>База вашего форума обнавлена успешно и вы можете удалить все исправления форума, так как они включены в этот релиз.</p>
			<p>Теперь вы можете перейти на <a href="<?php echo $base_url ?>/index.php">главную страница форума</a>.</p>
		</div>
	</div>

</div>

</div>
</div>
</body>
</html>
<?php

		break;
}

$forum_db->end_transaction();
$forum_db->close();

if ($query_str != '')
	exit('<script type="text/javascript">window.location="db_update.php'.$query_str.'"</script><br />JavaScript, кажется, отлючён. <a href="db_update.php'.$query_str.'">Нажмите для продолжения</a>.');
