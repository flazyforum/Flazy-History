<?php
/**
 * Добавить нового пользователя.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

// Adds a new user. The username must be passed through validate_username() first.
function add_user($user_info, & $new_uid)
{
	global $forum_db, $base_url, $lang_common, $forum_config, $forum_user, $forum_url;

	$return = ($hook = get_hook('fn_add_user_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Add the user
	$query = array(
		'INSERT'	=> 'username, group_id, password, email, email_setting, timezone, dst, language, style, registered, registration_ip, last_visit, salt, activate_key, user_agent',
		'INTO'		=> 'users',
		'VALUES'	=> '\''.$forum_db->escape($user_info['username']).'\', '.$user_info['group_id'].', \''.$forum_db->escape($user_info['password_hash']).'\', \''.$forum_db->escape($user_info['email']).'\', '.$user_info['email_setting'].', '.floatval($user_info['timezone']).', '.$user_info['dst'].', \''.$forum_db->escape($user_info['language']).'\', \''.$forum_db->escape($user_info['style']).'\', '.$user_info['registered'].', \''.$forum_db->escape($user_info['registration_ip']).'\', '.$user_info['registered'].', \''.$forum_db->escape($user_info['salt']).'\', '.$user_info['activate_key'].',\''.$user_info['user_agent'].'\''
	);

	($hook = get_hook('fn_add_user_qr_insert_user')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
	$new_uid = $forum_db->insert_id();

	// Must the user verify the registration?
	if ($user_info['require_verification'])
	{
		// Load the "welcome" template
		$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_user['language'].'/mail_templates/welcome.tpl'));

		// The first row contains the subject
		$first_crlf = strpos($mail_tpl, "\n");
		$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<board_title>', $forum_config['o_board_title'], $mail_subject);
		$mail_message = str_replace('<base_url>', $base_url.'/', $mail_message);
		$mail_message = str_replace('<username>', $user_info['username'], $mail_message);
		$mail_message = str_replace('<activation_url>', str_replace('&amp;', '&', forum_link($forum_url['change_password_key'], array($new_uid, substr($user_info['activate_key'], 1, -1)))), $mail_message);
		$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

		($hook = get_hook('fn_add_user_send_verification')) ? eval($hook) : null;

		forum_mail($user_info['email'], $mail_subject, $mail_message);
	}

	// Should we alert people on the admin mailing list that a new user has registered?
	if ($user_info['notify_admins'] && $forum_config['o_mailing_list'] != '')
	{
		$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_user['language'].'/mail_templates/new_user.tpl'));

		$first_crlf = strpos($mail_tpl, "\n");
		$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
		$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

		$mail_subject = str_replace('<mail_subject>', $lang_common['New user notification'], $mail_subject);
		$mail_message = str_replace('<user>', $forum_user['username'], $mail_message);
		$mail_message = str_replace('<board>', $base_url, $mail_message);
		$mail_message = str_replace('<profile_user>', forum_link($forum_url['user'], $new_uid), $mail_message);
		$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

		($hook = get_hook('fn_add_user_send_new_user')) ? eval($hook) : null;

		forum_mail($forum_config['o_mailing_list'], $mail_subject, $mail_message);
	}

	// Regenerate cache
	if (!defined('FORUM_CACHE_STAT_USER_LOADED'))
		require FORUM_ROOT.'include/cache/stat_user.php';

	generate_stat_user_cache();

	($hook = get_hook('fn_add_user_end')) ? eval($hook) : null;
}

define('FORUM_FUNCTIONS_ADD_USER', 1);
