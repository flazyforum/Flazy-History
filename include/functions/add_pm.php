<?php
/**
 * Создать новое личное сообщение.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

// Создать новое сообщение
function add_pm($post_info, &$new_pid)
{
	global $forum_db, $db_type, $forum_config, $forum_user, $lang_common, $lang_pm, $forum_url;

	$return = ($hook = get_hook('fn_fl_add_pm_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Add the pm
	$query = array(
		'INSERT'	=> 'sender_id, receiver_id, status, edited, subject, message',
		'INTO'		=> 'pm',
		'VALUES'	=> $forum_user['id'].', '.$post_info['receiver_id'].', \''.$post_info['status'].'\', '.$post_info['posted'].', \''.$forum_db->escape($post_info['subject']).'\', \''.$forum_db->escape($post_info['message']).'\''
	);

	($hook = get_hook('fn_fl_add_post_qr_add_post')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
	$new_pid = $forum_db->insert_id();

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> 'pm_outbox=pm_outbox+1',
		'WHERE'		=> 'id='.$forum_user['id'],
	);

	($hook = get_hook('fn_fl_add_pm_qr_increment_pm_outbox')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($post_info['status'] == 'sent')
	{
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'pm_inbox=pm_inbox+1, pm_new=pm_new+1',
			'WHERE'		=> 'id='.$post_info['receiver_id'],
		);

		($hook = get_hook('fn_fl_add_pm_qr_increment_pm_new')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_config['o_pm_get_mail'])
		{
			if ($post_info['mail'])
			{
				$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_user['language'].'/mail_templates/pm.tpl'));

				// The first row contains the subject
				$first_crlf = strpos($mail_tpl, "\n");
				$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
				$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

				$post_info['subject'] != '' ? $post_info['subject'] : $post_info['subject'] = $lang_pm['Empty mail'];

				$mail_subject = str_replace('<mail_subject>', $post_info['subject'], $mail_subject);
				$mail_message = str_replace('<replier>', $forum_user['username'], $mail_message);
				$mail_message = str_replace('<board_title>', $forum_config['o_board_title'], $mail_message);
				$mail_message = str_replace('<post_url>', forum_link($forum_url['pm'], $forum_user['id']), $mail_message);
				$mail_message = str_replace('<mail_message>', $post_info['message'], $mail_message);
				$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

				if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
					require FORUM_ROOT.'include/functions/email.php';

				forum_mail($post_info['email'], $mail_subject, $mail_message);
			}
		}
	}

	($hook = get_hook('fn_fl_add_pm_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_ADD_PM', 1);
