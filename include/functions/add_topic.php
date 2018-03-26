<?php
/**
 * Создать новую тему.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

// Создать новую тему
function add_topic($post_info, &$new_tid, &$new_pid)
{
	global $forum_db, $db_type, $forum_config, $lang_common;

	$return = ($hook = get_hook('fn_add_topic_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Добавить тему
	$query = array(
		'INSERT'	=> 'poster, poster_id, subject, description, question, posted, last_post, last_poster, last_poster_id, read_unvote, revote, poll_created, days_count, votes_count, forum_id',
		'INTO'		=> 'topics',
		'VALUES'	=> '\''.$forum_db->escape($post_info['poster']).'\', '.$post_info['poster_id'].', \''.$forum_db->escape($post_info['subject']).'\', \''.$forum_db->escape($post_info['description']).'\', \''.$forum_db->escape($post_info['question']).'\', '.$post_info['posted'].', '.$post_info['posted'].', \''.$forum_db->escape($post_info['poster']).'\', '.$post_info['poster_id'].', '.$post_info['read_unvote'].', '.$post_info['revote'].', '.$post_info['poll_created'].', \''.$post_info['days'].'\', \''.$post_info['votes'].'\', '.$post_info['forum_id']
	);

	($hook = get_hook('fn_add_topic_qr_add_topic')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
	$new_tid = $forum_db->insert_id();

	// Validate of pull_answers
	if (!empty($post_info['question']) && $post_info['answers'] != '')
	{
		$answ = array();
		$count_answers = count($post_info['answers']);
		for ($ans_num = 0; $ans_num < $count_answers; $ans_num++)
		{
			 $ans = forum_trim($post_info['answers'][$ans_num]);
			 if (!empty($ans))
				$answ[] = $ans;
		}
		if (!empty($answ))
			$answ = array_unique($answ);

		if (!empty($answ) && count($answ) > 1)
		{
			// Add answers to DB
			foreach ($answ as $ans)
			{
				$query = array(
					'INSERT'	=> 'topic_id, answer',
					'INTO'		=> 'answers',
					'VALUES'	=> $new_tid.', \''.$forum_db->escape($ans).'\''
				);

				($hook = get_hook('fn_add_topic_qr_add_poll_answer')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
	}

	// To subscribe or not to subscribe, that ...
	if (!$post_info['is_guest'] && $post_info['subscribe'])
	{
		$query = array(
			'INSERT'	=> 'user_id, topic_id',
			'INTO'		=> 'subscriptions',
			'VALUES'	=> $post_info['poster_id'].' ,'.$new_tid
		);

		($hook = get_hook('fn_add_topic_qr_add_subscription')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	// Create the post ("topic post")
	$query = array(
		'INSERT'	=> 'poster, poster_id, poster_ip, message, hide_smilies, posted, topic_id',
		'INTO'		=> 'posts',
		'VALUES'	=> '\''.$forum_db->escape($post_info['poster']).'\', '.$post_info['poster_id'].', \''.$forum_db->escape(get_remote_address()).'\', \''.$forum_db->escape($post_info['message']).'\', '.$post_info['hide_smilies'].', '.$post_info['posted'].', '.$new_tid
	);

	// If it's a guest post, there might be an e-mail address we need to include
	if ($post_info['is_guest'] && $post_info['poster_email'] != null)
	{
		$query['INSERT'] .= ', poster_email';
		$query['VALUES'] .= ', \''.$forum_db->escape($post_info['poster_email']).'\'';
	}

	($hook = get_hook('fn_add_topic_qr_add_topic_post')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
	$new_pid = $forum_db->insert_id();

	// Update the topic with last_post_id and first_post_id
	$query = array(
		'UPDATE'	=> 'topics',
		'SET'		=> 'last_post_id='.$new_pid.', first_post_id='.$new_pid,
		'WHERE'		=> 'id='.$new_tid
	);

	($hook = get_hook('fn_add_topic_qr_update_topic')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/search_idx.php';

	update_search_index('post', $new_pid, $post_info['message'], $post_info['subject'], $post_info['description']);

	if (!defined('FORUM_FUNCTIONS_SYNS'))
		require FORUM_ROOT.'include/functions/synchronize.php';

	sync_forum($post_info['forum_id']);

	// Increment his/her post count & last post time
	if (isset($post_info['update_user']) && $post_info['update_user'])
	{
		if ($post_info['is_guest'])
		{
			$query = array(
				'UPDATE'	=> 'online',
				'SET'		=> 'last_post='.$post_info['posted'],
				'WHERE'		=> 'ident=\''.$forum_db->escape(get_remote_address()).'\''
			);
		}
		else
		{
			$query = array(
				'UPDATE'	=> 'users',
				'SET'		=> 'last_post='.$post_info['posted'],
				'WHERE'		=> 'id='.$post_info['poster_id']
			);

			if ($post_info['counter'])
				$query['SET'] .= ', num_posts=num_posts+1';
		}
	
		($hook = get_hook('fn_add_topic_qr_update_last_post')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
      	}

      	// If the posting user is logged in update his/her unread indicator
      	if (!$post_info['is_guest'] && isset($post_info['update_unread']) && $post_info['update_unread'])
      	{
      		$tracked_topics = get_tracked_topics();
      		$tracked_topics['topics'][$new_tid] = time();
      		set_tracked_topics($tracked_topics);
      	}

	($hook = get_hook('fn_add_topic_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_ADD_TOPIC', 1);
