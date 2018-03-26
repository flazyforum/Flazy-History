<?php
/**
 * Функции созднания кэша статистики колличества участиков.
 *
 * @copyright Copyright (C) 2008-2009 Flazy.ru, based on code copyright (C) 2002-2009 PunBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;


function generate_stat_user_cache()
{
	global $forum_db;

	$return = ($hook = get_hook('ch_fn_generate_stat_user_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$query = array(
		'SELECT'	=> 'COUNT(u.id)-1',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.group_id!='.FORUM_UNVERIFIED
	);

	($hook = get_hook('ch_fn_generate_stats_qr_get_user_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$stats['total_users'] = $forum_db->result($result);

	$query = array(
			'SELECT'	=> 'u.id, u.username',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.group_id!='.FORUM_UNVERIFIED,
			'ORDER BY'	=> 'u.registered DESC',
			'LIMIT'		=> '1'
		);

	($hook = get_hook('ch_fn_generate_stats_qr_get_newest_user')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$last_user = $forum_db->fetch_assoc($result);

	$load_info = '$forum_stat_user = array('."\n".
		'\'total_users\'		=> \''.$stats['total_users'].'\','."\n".
		'\'id\'			=> \''.$last_user['id'].'\','."\n".
		'\'username\'		=> \''.$last_user['username'].'\','."\n".
		')';

	$fh = @fopen(FORUM_CACHE_DIR.'cache_stat_user.php', 'wb');
	if (!$fh)
		error('Невозможно записать файл колличестка участников в кэш каталог. Пожалуйста, убедитесь, что PHP имеет доступ на запись в папку \'cache\'.', __FILE__, __LINE__);


	fwrite($fh, '<?php'."\n\n".'define(\'FORUM_STAT_USER_LOADED\', 1);'."\n\n".$load_info.';'."\n\n".'?>');
	fclose($fh);
}

define('FORUM_CACHE_STAT_USER_LOADED', 1);

?>
