<?php
/**
 * Удалить тему и все сообщения в ней.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

// Удалить тему и все сообщения в ней
function delete_topic($topic_id, $forum_id, $poll)
{
	global $forum_db, $db_type, $cur_post, $forum_user;

	$return = ($hook = get_hook('fn_delete_topic_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Create an array of forum IDs that need to be synced
	$forum_ids = array($forum_id);
	$query = array(
		'SELECT'	=> 't.forum_id',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.moved_to='.$topic_id
	);

	($hook = get_hook('fn_delete_topic_qr_get_forums_to_sync')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	while ($row = $forum_db->fetch_row($result))
		$forum_ids[] = $row[0];

	// Delete the topic and any redirect topics
	$query = array(
		'DELETE'	=> 'topics',
		'WHERE'		=> 'id='.$topic_id.' OR moved_to='.$topic_id
	);

	($hook = get_hook('fn_delete_topic_qr_delete_topic')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($poll != '')
	{
		$query = array(
			'DELETE'	=> 'voting',
			'WHERE'		=> 'topic_id='.$topic_id
		);

		($hook = get_hook('fn_delete_topic_qr_delete_voting')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'DELETE'	=> 'answers',
			'WHERE'		=> 'topic_id='.$topic_id
		);

		($hook = get_hook('fn_delete_topic_qr_delete_answers')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	// Create a list of the post ID's in this topic
	$query = array(
		'SELECT'	=> 'p.id',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.topic_id='.$topic_id
	);

	($hook = get_hook('fn_delete_topic_qr_get_posts_to_delete')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$post_ids = array();
	while ($row = $forum_db->fetch_row($result))
		$post_ids[] = $row[0];

	// Make sure we have a list of post ID's
	if (!empty($post_ids))
	{
		// Delete posts in topic
		$query = array(
			'DELETE'	=> 'posts',
			'WHERE'		=> 'topic_id='.$topic_id
		);

		($hook = get_hook('fn_delete_topic_qr_delete_topic_posts')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/search_idx.php';

		strip_search_index($post_ids);
	}

	// Delete any subscriptions for this topic
	$query = array(
		'DELETE'	=> 'subscriptions',
		'WHERE'		=> 'topic_id='.$topic_id
	);

	($hook = get_hook('fn_delete_topic_qr_delete_topic_subscriptions')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!defined('FORUM_FUNCTIONS_SYNS'))
		require FORUM_ROOT.'include/functions/synchronize.php';

	foreach ($forum_ids as $cur_forum_id)
		sync_forum($cur_forum_id);

	($hook = get_hook('fn_delete_topic_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_DELETE_TOPIC', 1);
