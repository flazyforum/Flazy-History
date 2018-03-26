<?php
/**
 * Страница исходящих сообщений.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

function pm_outbox()
{
	global $forum_db, $forum_config, $forum_url, $forum_user, $lang_profile, $pm_outbox_count;

	$return = ($hook = get_hook('fn_pm_outbox_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['pm'], array($forum_user['id'], 'outbox')));

	// How much messages do we have?
	$forum_page['count'] = pm_outbox_count($forum_user['id']);
	$pm_outbox_count = $forum_page['count'];
	if (!pm_outbox_enough_space($forum_user['id'], 1, $forum_page['count']))
		$forum_page['full_box'] = sprintf($lang_profile['Outbox full'], $forum_config['o_pm_outbox_size']);
	else if (!pm_outbox_enough_space($forum_user['id'], 0.75, $forum_page['count']))
		$forum_page['full_box'] = sprintf($lang_profile['Outbox almost full'], $forum_config['o_pm_outbox_size']);

	// Determine the topic offset (based on $_GET['p'])
	$forum_page['num_pages'] = ceil($forum_page['count'] / $forum_user['disp_topics']);
	$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
	$forum_page['start_from'] = $forum_user['disp_topics'] * ($forum_page['page'] - 1);
	$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_topics']), ($forum_page['count']));

	// Setup the form
	$forum_page['type'] = 'outbox';
	$forum_page['heading'] = $lang_profile['Outbox'];
	$forum_page['user_role'] = $lang_profile['Receiver'];

	($hook = get_hook('fn_pm_outbox_pre_qr_ge')) ? eval($hook) : null;

	$query = array(
		'SELECT'	=> 'm.id, m.status, m.receiver_id AS user_id, m.subject, m.body, m.lastedited_at AS sent_at, u.username',
		'FROM'		=> 'pm AS m',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'users AS u',
				'ON'		=> 'u.id=m.receiver_id'
			),
		),
		'WHERE'		=> 'm.sender_id='.$forum_user['id'].' AND m.deleted_by_sender=0',
		'ORDER BY'	=> 'm.lastedited_at DESC',
		'LIMIT'		=> $forum_page['start_from'].', '.$forum_user['disp_topics']
	);

	($hook = get_hook('fn_pm_outbox_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$messages = array();
	while ($row = $forum_db->fetch_assoc($result))
		$messages[] = $row;

	$forum_page['list'] = $messages;

	($hook = get_hook('fn_pm_outbox_end')) ? eval($hook) : null;

	if (!defined('FORUM_FUNCTIONS_PM_BOX'))
		require FORUM_ROOT.'include/functions/pm/box.php';

	return pm_box($forum_page);
}

define('FORUM_FUNCTIONS_PM_OUTBOX', 1);
