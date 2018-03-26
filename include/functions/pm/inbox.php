<?php
/**
 * Страница входящих сообщений.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

function pm_inbox_enough_space($user_id, $ratio = 1, $count = false)
{
	global $forum_config;

	$return = ($hook = get_hook('fn_pm_inbox_enough_space_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	if (!$forum_config['o_pm_inbox_size'])
		return true;

	if ($count === false)
		$count = pm_inbox_count($user_id);

	($hook = get_hook('fn_pm_inbox_enough_space_end')) ? eval($hook) : null;

	return ($count < $ratio * $forum_config['o_pm_inbox_size']);
}

function pm_inbox()
{
	global $forum_db, $forum_config, $forum_user, $forum_url, $lang_common, $lang_profile;

	$return = ($hook = get_hook('fn_pm_inbox_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['pm'], array($forum_user['id'], 'index')));

	pm_deliver_messages();

	// How many messages do we have?
	$forum_page['count'] = pm_inbox_count($forum_user['id']);
	if (!pm_inbox_enough_space($forum_user['id'], 1, $forum_page['count']))
		$forum_page['full_box'] = sprintf($lang_profile['Inbox full'], $forum_config['o_pm_inbox_size']);
	else if (!pm_inbox_enough_space($forum_user['id'], 0.75, $forum_page['count']))
		$forum_page['full_box'] = sprintf($lang_profile['Inbox almost full'], $forum_config['o_pm_inbox_size']);

	// Determine the topic offset (based on $_GET['p'])
	$forum_page['num_pages'] = ceil($forum_page['count'] / $forum_user['disp_topics']);
	$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
	$forum_page['start_from'] = $forum_user['disp_topics'] * ($forum_page['page'] - 1);
	$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_topics']), ($forum_page['count']));

	// Setup the form
	$forum_page['type'] = 'inbox';
	$forum_page['heading'] = $lang_profile['Inbox'];
	$forum_page['user_role'] = $lang_profile['Sender'];

	($hook = get_hook('fn_pm_inbox_pre_qr_get')) ? eval($hook) : null;

	$query = array(
		'SELECT'	=> 'm.id, m.status, m.sender_id AS user_id, m.subject, m.body, m.lastedited_at AS sent_at, u.username',
		'FROM'		=> 'pm AS m',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'users AS u',
				'ON'		=> '(u.id=m.sender_id)'
			),
		),
		'WHERE'		=> 'm.receiver_id='.$forum_user['id'].' AND m.deleted_by_receiver=0 AND (m.status=\'delivered\' OR m.status=\'read\')',
		'ORDER BY'	=> 'm.lastedited_at DESC',
		'LIMIT'		=> $forum_page['start_from'].', '.$forum_user['disp_topics']
	);

	($hook = get_hook('fn_pm_inbox_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$messages = array();
	while ($row = $forum_db->fetch_assoc($result))
		$messages[] = $row;

	$forum_page['list'] = $messages;

	($hook = get_hook('fn_pm_inbox_end')) ? eval($hook) : null;

	if (!defined('FORUM_FUNCTIONS_PM_BOX'))
		require FORUM_ROOT.'include/functions/pm/box.php';

	return pm_box($forum_page);
}

define('FORUM_FUNCTIONS_PM_INBOX', 1);
