<?php
/**
 * Загружает надлежащий класс слоя базы данных.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

// Return current timestamp (with microseconds) as a float (used in dblayer)
if (defined('FORUM_SHOW_QUERIES'))
{
	function get_microtime()
	{
		list($usec, $sec) = explode(' ', microtime());
		return ((float)$usec + (float)$sec);
	}
}

// Загрузить соответствующий класс БД
switch ($db_type)
{
	case 'mysql':
		require FORUM_ROOT.'include/dblayer/mysql.php';
		break;

	case 'mysqli':
		require FORUM_ROOT.'include/dblayer/mysqli.php';
		break;

	case 'mysql_innodb':
		require FORUM_ROOT.'include/dblayer/mysql_innodb.php';
		break;

	case 'mysqli_innodb':
		require FORUM_ROOT.'include/dblayer/mysqli_innodb.php';
		break;

	case 'pgsql':
		require FORUM_ROOT.'include/dblayer/pgsql.php';
		break;

	case 'sqlite':
		require FORUM_ROOT.'include/dblayer/sqlite.php';
		break;

	default:
		error('\''.$db_type.'\' - не правильный тип базы данных. Пожалуйста, проверьте настройки в config.php.', __FILE__, __LINE__);
		break;
}

// Create the database adapter object (and open/connect to/select db)
$forum_db = new DBLayer($db_host, $db_username, $db_password, $db_name, $db_prefix, $p_connect);
