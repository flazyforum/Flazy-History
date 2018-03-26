<?php
/**
 * Общие функции для системы личных сообщений.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

$pm_inbox_full = false;

function pm_deliver_messages()
{
	if (defined('FORUM_PM_DELIVERED_MESSAGES'))
		return;

	global $forum_db, $forum_user, $forum_config, $lang_profile, $pm_inbox_full, $pm_inbox_count;

	$return = ($hook = get_hook('fn_pm_deliver_messages_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	if (!$forum_config['o_pm_inbox_size'])
	{
		// Unlimited Inbox!
		// Deliver all messages that were sent
		$query = array(
			'UPDATE'	=> 'pm',
			'SET'		=> 'status=\'delivered\'',
			'WHERE'		=> 'receiver_id='.$forum_user['id'].' AND status=\'sent\'',
		);

		($hook = get_hook('fn_add_pm_deliver_messages_qr')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
	else
	{
		// How many messages does user have in the Inbox?
		$inbox_count = pm_inbox_count($forum_user['id']);

		if ($inbox_count < $forum_config['o_pm_inbox_size'])
		{
			// What messages will we deliver?
			$query = array(
				'SELECT'	=> 'm.id',
				'FROM'		=> 'pm AS m',
				'WHERE'		=> 'm.receiver_id='.$forum_user['id'].' AND m.status=\'sent\'',
				'ORDER BY'	=> 'm.lastedited_at',
				'LIMIT'		=> ($forum_config['o_pm_inbox_size']-$inbox_count)
				//'LIMIT'		=> (string)($forum_config['o_pm_inbox_size']-$inbox_count),
			);

			($hook = get_hook('fn_pm_deliver_messages_qr')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			// We have to deliver some messages
			if ($forum_db->num_rows($result))
			{
				$ids = '';
				while ($row = $forum_db->fetch_assoc($result))
					$ids .= $row['id'].', ';
 
				// There is some free space in the Inbox
				// Deliver some messages that were sent
				$query = array(
					'UPDATE'	=> 'pm',
					'SET'		=> 'status=\'delivered\'',
					'WHERE'		=> 'id IN ('.substr($ids, 0, -2).')',
				);

				($hook = get_hook('fn_add_pm_deliver_messages_qr_delivered')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				// Clear cached inbox count
				$pm_inbox_count = false;
			}
		}
		else
			$pm_inbox_full = true;
	}	

	($hook = get_hook('fn_pm_deliver_messages_end')) ? eval($hook) : null;

	define('FORUM_PM_DELIVERED_MESSAGES', 1);
}


// Get user id
function pm_get_receiver_id($username, &$errors)
{
	global $lang_profile, $forum_db, $forum_user;

	$return = ($hook = get_hook('fn_pm_get_receiver_id_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$receiver_id = 'NULL';

	if ($username != '')
	{
		$query = array(
			'SELECT'	=> 'id',
			'FROM'		=> 'users',
			'WHERE'		=> 'username=\''.$forum_db->escape($username).'\''
		);

		($hook = get_hook('fn_pm_get_receiver_id_qr')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->num_rows($result))
			list($receiver_id) = $forum_db->fetch_row($result);
		else
			$errors[] = sprintf($lang_profile['Non-existent username'], forum_htmlencode($username));

		if ($forum_user['id'] == $receiver_id)
			$errors[] = $lang_profile['Message to yourself'];
		if ($receiver_id == '1')
			$errors[] = $lang_profile['Message to guest'];
	}

	($hook = get_hook('fn_pm_get_receiver_id_end')) ? eval($hook) : null;

	return $receiver_id;
}

function pm_get_username($id)
{
	global $forum_db;

	$return = ($hook = get_hook('fn_pm_get_username_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$query = array(
		'SELECT'	=> 'username',
		'FROM'		=> 'users',
		'WHERE'		=> 'id='.intval($id),
	);

	($hook = get_hook('fn_pm_get_username_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_db->num_rows($result))
		list($username) = $forum_db->fetch_row($result);
	else
		$username = '';

	($hook = get_hook('fn_pm_get_username_end')) ? eval($hook) : null;

	return $username;
}

$pm_inbox_count = false;

function pm_inbox_count($user_id)
{
	global $forum_db, $pm_inbox_count;

	$return = ($hook = get_hook('fn_pm_inbox_count_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	if ($pm_inbox_count !== false)
		return $pm_inbox_count;

	$query = array(
		'SELECT'	=> 'COUNT(m.id)',
		'FROM'		=> 'pm AS m',
		'WHERE'		=> 'm.receiver_id='.$forum_db->escape($user_id).' AND (m.status=\'read\' OR m.status=\'delivered\') AND m.deleted_by_receiver=0'
	);

	($hook = get_hook('fn_pm_inbox_count_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	list($pm_inbox_count) = $forum_db->fetch_row($result);

	($hook = get_hook('fn_pm_inbox_count_end')) ? eval($hook) : null;

	return $pm_inbox_count;
}

function pm_outbox_count($user_id)
{
	global $forum_db;

	$return = ($hook = get_hook('fn_pm_outbox_count_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$query = array(
		'SELECT'	=> 'COUNT(m.id)',
		'FROM'		=> 'pm AS m',
		'WHERE'		=> 'm.sender_id='.$forum_db->escape($user_id).' AND m.deleted_by_sender=0'
	);

	($hook = get_hook('fn_pm_outbox_count_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	list($count) = $forum_db->fetch_row($result);

	($hook = get_hook('fn_pm_outbox_count_end')) ? eval($hook) : null;

	return $count;
}


function pm_outbox_enough_space($user_id, $ratio = 1, $count = false)
{
	global $forum_config;

	$return = ($hook = get_hook('fn_pm_outbox_enough_space_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	if (!$forum_config['o_pm_outbox_size'])
		return true;

	if ($count === false)
		$count = pm_outbox_count($user_id);

	($hook = get_hook('fn_pm_outbox_enough_space_end')) ? eval($hook) : null;

	return ($count < $ratio * $forum_config['o_pm_outbox_size']);
}

// ACTIONS
function pm_send_message($body, $subject, $receiver_username, &$message_id)
{
	global $forum_db, $lang_profile, $lang_common, $forum_user, $forum_url, $forum_config;

	$return = ($hook = get_hook('fn_pm_send_message')) ? eval($hook) : null;
	if ($return != null)
		return;

	if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(forum_link($forum_url['pm_send'], $forum_user['id'])))
		csrf_confirm_form();

	$errors = array();

	$receiver_id = pm_get_receiver_id($receiver_username, $errors);
	if ($receiver_id == 'NULL' && empty($errors))
		$errors[] = $lang_profile['Empty receiver'];

	// Clean up body from POST
	$body = forum_linebreaks($body);

	if ($body == '')
		$errors[] = $lang_profile['Empty body'];
	else if (utf8_strlen($body) > FORUM_MAX_POSTSIZE)
		$errors[] = sprintf($lang_profile['Too long message'], forum_number_format(utf8_strlen($body)), forum_number_format(FORUM_MAX_POSTSIZE));
	else if (!$forum_config['p_message_all_caps'] && is_all_uppercase($body) && !$forum_page['is_admmod'])
		$body = utf8_ucwords(utf8_strtolower($body));

	// Validate BBCode syntax
	if ($forum_config['p_message_bbcode'] || $forum_config['o_make_links'])
	{
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		$body = preparse_bbcode($body, $errors);
	}

	if (count($errors))
		return $errors;

	if ($message_id !== false)
	{
		// Draft -> Sent
		$query = array(
			'UPDATE'	=> 'pm',
			'SET'		=> 'status=\'sent\', receiver_id='.$receiver_id.', lastedited_at='.time().', subject = \''.$forum_db->escape($subject).'\', body=\''.$forum_db->escape($body).'\'',
			'WHERE'		=> 'id='.$forum_db->escape($message_id).' AND sender_id='.$forum_user['id'].' AND (status=\'draft\' OR status=\'sent\')'
		);

		($hook = get_hook('fn_pm_send_message_update_status_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->affected_rows() != 1)
		{
			$message_id = false;
			$errors[] = $lang_profile['Invalid message send'];
			return $errors;
		}
	}
	else
	{
		// Send new message

		// Verify outbox count
		if (!pm_outbox_enough_space($forum_user['id']))
		{
			$errors[] = sprintf($lang_profile['Outbox full'], $forum_config['o_pm_outbox_size']);
			return $errors;
		}

		// Save to DB
		$query = array(
			'INSERT'	=> 'sender_id, receiver_id, status, lastedited_at, read_at, subject, body',
			'INTO'		=> 'pm',
			'VALUES'	=> $forum_user['id'].', '.$receiver_id.', \'sent\', '.time().', 0, \''.$forum_db->escape($subject).'\', \''.$forum_db->escape($body).'\''
		);

		($hook = get_hook('fn_pm_send_message_insert_save_bd_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'SELECT'	=> 'u.id, u.email, pm_get_mail',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'u.id='.$receiver_id
		);

		($hook = get_hook('fn_pm_send_message_insert_email_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$mail = $forum_db->fetch_assoc($result);

		if ($mail['pm_get_mail'])
		{
			$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_user['language'].'/mail_templates/pm.tpl'));

			// The first row contains the subject
			$first_crlf = strpos($mail_tpl, "\n");
			$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
			$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

			$subject != '' ? $subject : $subject = $lang_profile['Empty mail'];

			$mail_subject = str_replace('<mail_subject>', $subject, $mail_subject);
			$mail_message = str_replace('<replier>', $forum_user['username'], $mail_message);
			$mail_message = str_replace('<board_title>', $forum_config['o_board_title'], $mail_message);
			$mail_message = str_replace('<post_url>', forum_link($forum_url['pm'], array($mail['id'], 'inbox')), $mail_message);
			$mail_message = str_replace('<mail_message>', $body, $mail_message);
			$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

			if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/functions/email.php';

			forum_mail($mail['email'], $mail_subject, $mail_message);
		}
	}

	redirect(forum_link($forum_url['pm'], array($forum_user['id'], 'outbox')), $lang_profile['Message sent']);

	($hook = get_hook('fn_pm_send_message_end')) ? eval($hook) : null;

	return $errors;
}


function pm_save_message($body, $subject, $receiver_username, &$message_id)
{
	global $forum_db, $forum_config, $lang_profile, $forum_user, $forum_url;

	$return = ($hook = get_hook('fn_pm_save_message_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(forum_link($forum_url['pm_send'], $forum_user['id'])))
		csrf_confirm_form();

	$errors = array();

	$receiver_id = pm_get_receiver_id($receiver_username, $errors);

	// Clean up body from POST
	$body = forum_linebreaks($body);

	if ($body == '')
		$errors[] = $lang_profile['Empty body'];
	else if (strlen($body) > FORUM_MAX_POSTSIZE)
		$errors[] = $sprintf($lang_profile['Too long message'], forum_number_format(strlen($body)), forum_number_format(FORUM_MAX_POSTSIZE));
	else if (!$forum_config['p_message_all_caps'] && is_all_uppercase($body) && !$forum_page['is_admmod'])
		$body = utf8_ucwords(utf8_strtolower($body));

	// Validate BBCode syntax
	if ($forum_config['p_message_bbcode'] || $forum_config['o_make_links'])
	{
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		$body = preparse_bbcode($body, $errors);
	}

	// Verify for errors
	if ($body == '' && $subject == '' && $receiver_username == '')
		$errors[] = $lang_profile['Empty all fields'];

	if (count($errors))
		return $errors;

	if ($message_id !== false)
	{
		// Edit message
		$query = array(
			'UPDATE'	=> 'pm',
			'SET'		=> 'status=\'draft\', receiver_id='.$receiver_id.', lastedited_at='.time().', subject=\''.$forum_db->escape($subject).'\', body=\''.$forum_db->escape($body).'\'',
			'WHERE'		=> 'id='.$forum_db->escape($message_id).' AND sender_id='.$forum_user['id'].' AND (status=\'draft\' OR status=\'sent\')'
		);

		($hook = get_hook('fn_pm_save_message_update_status_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->affected_rows() != 1)
		{
			$message_id = false;
			$errors[] = $lang_profile['Invalid message save'];
			return $errors;
		}
	}
	else
	{
		// Save new message

		// Verify outbox count
		if (!pm_outbox_enough_space($forum_user['id']))
		{
			$errors[] = sprintf($lang_profile['Outbox full'], $forum_config['o_pm_outbox_size']);
			return $errors;
		}

		// Save to DB
		$query = array(
			'INSERT'	=> 'sender_id, receiver_id, lastedited_at, read_at, status, subject, body',
			'INTO'		=> 'pm',
			'VALUES'	=> $forum_user['id'].', '.$receiver_id.', '.time().', 0, \'draft\', \''.$forum_db->escape($subject).'\', \''.$forum_db->escape($body).'\''
		);

		($hook = get_hook('fn_pm_save_message_insert_save_bd_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	}
	redirect(forum_link($forum_url['pm'], array($forum_user['id'], 'outbox')), $lang_profile['Message saved']);

	($hook = get_hook('fn_pm_save_message_end')) ? eval($hook) : null;

	return $errors;
}


function pm_preview($receiver, $subject, $body, &$errors)
{
	global $forum_config, $forum_page, $lang_profile, $forum_user;

	$return = ($hook = get_hook('fn_pm_preview')) ? eval($hook) : null;
	if ($return != null)
		return;

	if ($body == '')
		$errors[] = $lang_profile['Empty body'];
	else if (strlen($body) > FORUM_MAX_POSTSIZE)
		$errors[] = $sprintf($lang_profile['Too long message'], forum_number_format(strlen($body)), forum_number_format(FORUM_MAX_POSTSIZE));
	else if (!$forum_config['p_message_all_caps'] && is_all_uppercase($body) && !$forum_page['is_admmod'])
		$body = utf8_ucwords(utf8_strtolower($body));

	// Validate BBCode syntax
	if ($forum_config['p_message_bbcode'] || $forum_config['o_make_links'])
	{
		global $smilies;
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		$body = preparse_bbcode($body, $errors);
	}

	if (count($errors))
		return false;

	$message['sender'] = $forum_user['username'];
	$message['sender_id'] = $forum_user['id'];
	$message['body'] = $body;
	$message['subject'] = $subject;
	$message['status'] = 'draft';
	$message['sent_at'] = time();

	($hook = get_hook('fn_pm_preview')) ? eval($hook) : null;

	if (!defined('FORUM_FUNCTIONS_PM_MESSAGE'))
		require FORUM_ROOT.'include/functions/pm/message.php';

	return pm_message($message, 'inbox');
}


// PAGES
function pm_get_message($id, $type)
{
	global $forum_db, $forum_user;

	$return = ($hook = get_hook('fn_pm_get_message_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	if ($type == 'inbox')
		$condition = 'm.receiver_id='.$forum_user['id'];
	else if ($type == 'outbox')
		$condition = 'm.sender_id='.$forum_user['id'];
	else
		return false;

	// Obtain message
	$query = array(
		'SELECT'	=> 'm.id as id, sender_id, receiver_id, m.status, u0.username AS sender, u1.username AS receiver, read_at AS read_at, lastedited_at AS sent_at, subject, body',
		'FROM'		=> 'pm AS m',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'users AS u0',
				'ON'			=> 'u0.id=sender_id'
			),
			array(
				'LEFT JOIN'		=> 'users AS u1',
				'ON'			=> 'u1.id=receiver_id'
			),
		),
		'WHERE'		=> 'm.id='.$forum_db->escape($id).' AND '.$condition,
	);

	($hook = get_hook('fn_pm_get_message_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_db->num_rows($result) != 1)
		return false;

	$message = $forum_db->fetch_assoc($result);

	// Update the status of an read message
	if ($type == 'inbox' && $message['status'] == 'delivered')
	{
		$query = array(
			'UPDATE'	=> 'pm',
			'SET'		=> 'status=\'read\', read_at='.time(),
			'WHERE'		=> 'id='.$id,
		);

		($hook = get_hook('fn_ppm_get_message_update_status_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	($hook = get_hook('fn_pm_get_message_end')) ? eval($hook) : null;

	return $message;
}


function pm_delete_from_inbox($ids)
{
	global $forum_db, $forum_user;

	$return = ($hook = get_hook('fn_delete_from_inbox_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Typecast to avoid a hacker attack
	foreach ($ids as $key => $id)
		$ids[$key] = (int) $id;

	$query = array(
		'DELETE'	=> 'pm',
		'WHERE'		=> 'id IN('.$forum_db->escape(implode(',', $ids)).') AND receiver_id='.$forum_user['id'].' AND deleted_by_sender=1',
	);

	($hook = get_hook('fn_pm_delete_from_inbox_delete_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'UPDATE'	=> 'pm',
		'SET'		=> 'deleted_by_receiver=1',
		'WHERE'		=> 'id IN('.$forum_db->escape(implode(',', $ids)).') AND receiver_id='.$forum_user['id'],
	);

	($hook = get_hook('fn_pm_delete_from_inbox_update_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	pm_deliver_messages();

	($hook = get_hook('fn_delete_from_inbox_end')) ? eval($hook) : null;
}


function pm_delete_from_outbox($ids)
{
	global $forum_db, $forum_user;

	$return = ($hook = get_hook('fn_pm_delete_from_outbox_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Typecast to avoid a hacker attack
	foreach ($ids as $key => $id)
		$ids[$key] = (int) $id;

	$query = array(
		'DELETE'	=> 'pm',
		'WHERE'		=> 'id IN('.implode(',', $ids).') AND sender_id='.$forum_user['id'].' AND (status=\'draft\' OR status=\'sent\' OR deleted_by_receiver=1)',
	);

	($hook = get_hook('fn_pm_delete_from_outbox_delete_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'UPDATE'	=> 'pm',
		'SET'		=> 'deleted_by_sender=1',
		'WHERE'		=> 'id IN('.implode(',', $ids).') AND sender_id='.$forum_user['id'],
	);

	($hook = get_hook('fn_pm_delete_from_outbox_update_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('fn_pm_delete_from_outbox_end')) ? eval($hook) : null;
}


function pm_delete_message($ids)
{
	global $forum_user, $forum_url, $lang_profile;

	$return = ($hook = get_hook('fn_pm_delete_message')) ? eval($hook) : null;
	if ($return != null)
		return;

	if (isset($_POST['delete_inbox']))
	{
		pm_delete_from_inbox($ids);
		redirect(forum_link($forum_url['pm'], array($forum_user['id'], 'inbox')), $lang_profile['Message deleted']);
	}
	else if (isset($_POST['delete_outbox']))
	{
		pm_delete_from_outbox($ids);
		redirect(forum_link($forum_url['pm'], array($forum_user['id'], 'outbox')), $lang_profile['Message deleted']);
	}

	($hook = get_hook('fn_pm_delete_message_end')) ? eval($hook) : null;

	return false;
}


// DESIGN
function pm_get_page(&$page)
{
	global $forum_url, $forum_user, $lang_common;

	$return = ($hook = get_hook('fn_pm_get_page_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	if ($page == 'write')
	{
		if (isset($_GET['message_id']))
		{
			if (isset($_POST['delete_inbox']) || isset($_POST['delete_outbox']))
			{
				if (!isset($_POST['csrf_token']))
					csrf_confirm_form();

				($hook = get_hook('fn_pm_get_page_delete_inbox_outbox')) ? eval($hook) : null;

				return pm_delete_message(array((int) $_GET['message_id']));
			}
			else
				($hook = get_hook('fn_pm_get_page_delete_else')) ? eval($hook) : null;

				if (!defined('FORUM_FUNCTIONS_PM_EDIT_MESSAGE'))
					require FORUM_ROOT.'include/functions/pm/edit_message.php';

				return pm_edit_message();
		}
		if (isset($_POST['delete']))
		{
			if (isset($_POST['delete_inbox']) || isset($_POST['delete_outbox']))
			{
				if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== generate_form_token(forum_link($forum_url['pm'], array($forum_user['id'], 'write'))))
					csrf_confirm_form();

				($hook = get_hook('fn_pm_get_page_pm_delete_inbox_outbox')) ? eval($hook) : null;

				return pm_delete_message($_POST['delete']);
			}
		}

		($hook = get_hook('fn_pm_get_page_pm_delete')) ? eval($hook) : null;

		if (!defined('FORUM_FUNCTIONS_PM_SEND_FORM'))
			require FORUM_ROOT.'include/functions/pm/send_form.php';

		return pm_send_form();
	}
	else if ($page == 'compose')
	{
		$receiver_id = isset($_GET['receiver_id']) ? (int) $_GET['receiver_id'] : 0;

		($hook = get_hook('fn_pm_get_page_compose')) ? eval($hook) : null;

		if (!defined('FORUM_FUNCTIONS_PM_SEND_FORM'))
			require FORUM_ROOT.'include/functions/pm/send_form.php';

		return pm_send_form(pm_get_username($receiver_id));
	}
	else if ($page == 'outbox')
	{
		if (isset($_GET['message_id']))
		{
			$message = pm_get_message((int) $_GET['message_id'], 'outbox');

			if ($message === false)
				message($lang_common['Bad request']);

			($hook = get_hook('fn_pm_get_page_outbox')) ? eval($hook) : null;

			if (!defined('FORUM_FUNCTIONS_PM_MESSAGE'))
				require FORUM_ROOT.'include/functions/pm/message.php';

			return pm_message($message, 'outbox');
		}

		($hook = get_hook('fn_pm_get_page_message_id_outbox')) ? eval($hook) : null;

		if (!defined('FORUM_FUNCTIONS_PM_OUTBOX'))
			require FORUM_ROOT.'include/functions/pm/outbox.php';

		return pm_outbox();
	}
	else
	{
		$page = 'inbox';
		if (isset($_GET['message_id']))
		{
			$message = pm_get_message((int) $_GET['message_id'], 'inbox');

			if ($message === false)
				message($lang_common['Bad request']);

			($hook = get_hook('fn_pm_get_page_inbox')) ? eval($hook) : null;

			if (!defined('FORUM_FUNCTIONS_PM_MESSAGE'))
				require FORUM_ROOT.'include/functions/pm/message.php';

			return pm_message($message, 'inbox');
		}

		($hook = get_hook('fn_pm_get_page_message_id_inbox')) ? eval($hook) : null;

		if (!defined('FORUM_FUNCTIONS_PM_INTBOX'))
			require FORUM_ROOT.'include/functions/pm/inbox.php';

		return pm_inbox();
	}

	($hook = get_hook('fn_pm_get_page_end')) ? eval($hook) : null;
}
