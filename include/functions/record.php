<?php
/**
 * Рекорд одновременно прибывания на форуме.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

// Функция записи в БД числа пользователей
function record($num_users, $type)
{
	global $forum_db, $forum_config;

	$return = ($hook = get_hook('fn_record_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$query = array(
		'UPDATE'	=> 'config',
		'SET'		=> 'conf_value='.$num_users,
		'WHERE'		=> 'conf_name=\'c_max_'.$type.'\''
	);

	($hook = get_hook('fn_record_qr')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_config_cache();
	// Данные без кэша
	$forum_config['c_'.$type] = $num_users;

	($hook = get_hook('fn_record_end')) ? eval($hook) : null;
}

if (FORUM_PAGE != 'index' && $forum_config['o_record'])
{
	// Fetch users online info and generate strings for output
	$query = array(
		'SELECT'	=> 'o.user_id, o.ident',
		'FROM'		=> 'online AS o',
		'WHERE'		=> 'o.idle=0',
		'ORDER BY'	=> 'o.ident'
	);

	($hook = get_hook('fn_user_online_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$forum_page['num_guests'] = $forum_page['num_users'] = 0;

	$users = array();
	while ($forum_user_record = $forum_db->fetch_assoc($result))
	{
		if ($forum_user_record['user_id'] > 1)
		{
			$users[] = $forum_user_record['ident'];
			++$forum_page['num_users'];
		}
		else
			++$forum_page['num_guests'];
	}
}

if ($forum_config['o_record'])
{
	if ($forum_page['num_users'] > $forum_config['c_max_users'])
		record($forum_page['num_users'], 'users');
	if ($forum_page['num_guests'] > $forum_config['c_max_guests'])
		record($forum_page['num_guests'], 'guests');
	$forum_page['max_total_users'] = $forum_page['num_users'] + $forum_page['num_guests'];
	if ($forum_page['max_total_users'] > $forum_config['c_max_total_users'])
		record($forum_page['max_total_users'], 'total_users');

}

define('FORUM_FUNCTIONS_RECORD', 1);
