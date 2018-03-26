<?php
/**
 * Удаление одного сообщения.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

// Удаление одного сообщения
function delete_post($post_id, $topic_id, $forum_id)
{
	global $forum_db, $db_type, $cur_post, $forum_user;

	$return = ($hook = get_hook('fn_delete_post_start')) ? eval($hook) : null;
	if ($return != null)
		return;
	
	// Delete the post
	$query = array(
		'DELETE'	=> 'posts',
		'WHERE'		=> 'id='.$post_id
	);

	($hook = get_hook('fn_delete_post_qr_delete_post')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/search_idx.php';

	strip_search_index($post_id);

	if (!defined('FORUM_FUNCTIONS_SYNS'))
		require FORUM_ROOT.'include/functions/synchronize.php';

	sync_topic($topic_id);
	sync_forum($forum_id);

	//Время последнего сообщения
	$query = array(
		'SELECT'	=> 'p.posted',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.poster_id='.$cur_post['poster_id'],
		'ORDER BY'	=> 'p.id DESC',
		'LIMIT'		=> '1'
	);

	($hook = get_hook('fn_posted_qr_delete_post')) ? eval($hook) : null;	
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$time_messages = $forum_db->result($result);
	
	// Обновим данные
	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'num_posts=num_posts-1, last_post='.$time_messages,
		'WHERE'		=> 'id='.$cur_post['poster_id']
	);

	($hook = get_hook('fn_update_num_posts_qr_delete_post')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('fn_delete_post_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_DELETE_POST', 1);
