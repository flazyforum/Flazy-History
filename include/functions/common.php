<?php
/**
 * Общие функции используемые на форуме.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */


// Checks if a word is a valid searchable word
function validate_search_word($word)
{
	global $forum_user;
	static $stopwords;
	
	$return = ($hook = get_hook('fn_validate_search_word_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if (!isset($stopwords))
	{
		if (file_exists(FORUM_ROOT.'lang/'.$forum_user['language'].'/stopwords.txt'))
		{
			$stopwords = file(FORUM_ROOT.'lang/'.$forum_user['language'].'/stopwords.txt');
			$stopwords = array_map('forum_trim', $stopwords);
			$stopwords = array_filter($stopwords);
		}
		else
			$stopwords = array();

		($hook = get_hook('fn_validate_search_word_modify_stopwords')) ? eval($hook) : null;
	}

	$num_chars = utf8_strlen($word);

	$return = ($hook = get_hook('fn_validate_search_word_end')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return $num_chars >= FORUM_SEARCH_MIN_WORD && $num_chars <= FORUM_SEARCH_MAX_WORD && !in_array($word, $stopwords);
}

// Converts the CDATA end sequence ]]> into ]]&gt;
function escape_cdata($str)
{
	return str_replace(']]>', ']]&gt;', $str);
}
 

// Возвращяет список предустанолвеных URL схем
function get_scheme_packs()
 {
  	$schemes = array();

	if($handle = opendir(FORUM_ROOT.'include/url'))
	{
		while (false !== ($dirname = readdir($handle)))
		{
			$dirname =  FORUM_ROOT.'include/url/'.$dirname;
			if (is_dir($dirname) && file_exists($dirname.'/forum_urls.php'))
				$schemes[] = basename($dirname);
		}
		closedir($handle);
	}
	
	($hook = get_hook('fn_get_scheme_packs_end')) ? eval($hook) : null;

	return $schemes;
}
 

// Return a list of all styles installed
function get_style_packs()
{
 	$styles = array();

	if($handle = opendir(FORUM_ROOT.'style'))
	{
		while (false !== ($dirname = readdir($handle)))
		{
			$dirname =  FORUM_ROOT.'style/'.$dirname;
			$tempname = basename($dirname);
			if (is_dir($dirname) && file_exists($dirname.'/'.$tempname.'.php'))
				$styles[] = $tempname;
		}
		closedir($handle);
	}
 
 	($hook = get_hook('fn_get_style_packs_end')) ? eval($hook) : null;

	return $styles;
}

// Return a list of all language packs installed
function get_language_packs()
{
 	$lang = array();

	if ($handle = opendir(FORUM_ROOT.'lang'))
	{
		while (false !== ($dirname = readdir($handle)))
		{
			$dirname =  FORUM_ROOT.'lang/'.$dirname;
			if (is_dir($dirname) && file_exists($dirname.'/common.php'))
				$lang[] = basename($dirname);
		}
		closedir($handle);
	}

	($hook = get_hook('fn_get_language_packs_end')) ? eval($hook) : null;

	return $lang;
}


// Return all code blocks that hook into $hook_id
function get_hook($hook_id)
{
	global $hooks;

	return !defined('FORUM_DISABLE_HOOKS') && isset($hooks[$hook_id]) ? implode("\n", $hooks[$hook_id]) : false;
}


// Authenticates the provided username and password against the user database
// $user can be either a user ID (integer) or a username (string)
// $password can be either a plaintext password or a password hash including salt ($password_is_hash must be set accordingly)
function authenticate_user($user, $password, $password_is_hash = false)
{
	global $forum_db, $forum_user;

	$return = ($hook = get_hook('fn_authenticate_user_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Check if there's a user matching $user and $password
	$query = array(
		'SELECT'	=> 'u.*, g.*, o.logged, o.idle, o.csrf_token, o.prev_url',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'groups AS g',
				'ON'		=> 'g.g_id=u.group_id'
			),
			array(
				'LEFT JOIN'	=> 'online AS o',
				'ON'		=> 'o.user_id=u.id'
			)
		)
	);

	// Are we looking for a user ID or a username?
	$query['WHERE'] = is_int($user) ? 'u.id='.intval($user) : 'u.username=\''.$forum_db->escape($user).'\'';

	($hook = get_hook('fn_authenticate_user_qr_get_user')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$forum_user = $forum_db->fetch_assoc($result);

	if (!isset($forum_user['id']) ||
		($password_is_hash && $password != $forum_user['password']) ||
		(!$password_is_hash && forum_hash($password, $forum_user['salt']) != $forum_user['password']))
		set_default_user();

	($hook = get_hook('fn_authenticate_user_end')) ? eval($hook) : null;
}


// Attempt to login with the user ID and password hash from the cookie
function cookie_login(&$forum_user)
{
	global $forum_db, $db_type, $forum_config, $cookie_name, $cookie_path, $cookie_domain, $cookie_secure, $forum_time_formats, $forum_date_formats;

	$now = time();
	$expire = $now + 1209600; // The cookie expires after 14 days

	// We assume it's a guest
	$cookie = array('user_id' => 1, 'password_hash' => 'Guest', 'expiration_time' => 0, 'expire_hash' => 'Guest');

	$return = ($hook = get_hook('fn_cookie_login_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// If a cookie is set, we get the user_id and password hash from it
	if (isset($_COOKIE[$cookie_name]))
		@list($cookie['user_id'], $cookie['password_hash'], $cookie['expiration_time'], $cookie['expire_hash']) = @explode('|', base64_decode($_COOKIE[$cookie_name]));

	($hook = get_hook('fn_cookie_login_fetch_cookie')) ? eval($hook) : null;

	// If this a cookie for a logged in user and it shouldn't have already expired
	if (intval($cookie['user_id']) > 1 && intval($cookie['expiration_time']) > $now)
	{
		authenticate_user(intval($cookie['user_id']), $cookie['password_hash'], true);

		$user_agent = false;
		if (!empty($forum_user['user_agent']) && !empty($_SERVER['HTTP_USER_AGENT']))
		{
			if (str_replace(' ', '', $_SERVER['HTTP_USER_AGENT']) != $forum_user['user_agent'])
				$user_agent = true;
		}
		if (empty($_SERVER['HTTP_USER_AGENT']))
			$user_agent = true;

		$security = false;
		if ($forum_user['security_ip'] && substr(get_remote_address(), 0, strlen($forum_user['security_ip'])) != $forum_user['security_ip'])
			$security = true;
		
		// We now validate the cookie hash
		if ($cookie['expire_hash'] !== sha1($forum_user['salt'].$forum_user['password'].forum_hash(intval($cookie['expiration_time']), $forum_user['salt'])) || $user_agent || $security )
			set_default_user();

		// If we got back the default user, the login failed
		if ($forum_user['id'] == '1')
		{
			forum_setcookie($cookie_name, base64_encode('1|'.random_key(8, false, true).'|'.$expire.'|'.random_key(8, false, true)), $expire);
			return;
		}

		// Send a new, updated cookie with a new expiration timestamp
		$expire = (intval($cookie['expiration_time']) > $now + $forum_config['o_timeout_visit']) ? $now + 1209600 : $now + $forum_config['o_timeout_visit'];
		forum_setcookie($cookie_name, base64_encode($forum_user['id'].'|'.$forum_user['password'].'|'.$expire.'|'.sha1($forum_user['salt'].$forum_user['password'].forum_hash($expire, $forum_user['salt']))), $expire);

		// Set a default language if the user selected language no longer exists
		if (!file_exists(FORUM_ROOT.'lang/'.$forum_user['language'].'/common.php'))
			$forum_user['language'] = $forum_config['o_default_lang'];

		// Set a default style if the user selected style no longer exists
		if (!file_exists(FORUM_ROOT.'style/'.$forum_user['style'].'/'.$forum_user['style'].'.php'))
			$forum_user['style'] = $forum_config['o_default_style'];

		if (!$forum_user['disp_topics'])
			$forum_user['disp_topics'] = $forum_config['o_disp_topics_default'];
		if (!$forum_user['disp_posts'])
			$forum_user['disp_posts'] = $forum_config['o_disp_posts_default'];

		// Check user has a valid date and time format
		if (!isset($forum_time_formats[$forum_user['time_format']]))
			$forum_user['time_format'] = 0;
		if (!isset($forum_date_formats[$forum_user['date_format']]))
			$forum_user['date_format'] = 0;

		// Define this if you want this visit to affect the online list and the users last visit data
		if (!defined('FORUM_QUIET_VISIT'))
		{
			// Update the online list
			if (!$forum_user['logged'])
			{
				$forum_user['logged'] = $now;
				$forum_user['csrf_token'] = random_key(40, false, true);
				$forum_user['prev_url'] = get_current_url(255);

				// REPLACE INTO avoids a user having two rows in the online table
				$query = array(
					'REPLACE'	=> 'user_id, ident, logged, csrf_token',
					'INTO'		=> 'online',
					'VALUES'	=> $forum_user['id'].', \''.$forum_db->escape($forum_user['username']).'\', '.$forum_user['logged'].', \''.$forum_user['csrf_token'].'\'',
					'UNIQUE'	=> 'user_id='.$forum_user['id']
				);

				if ($forum_user['prev_url'] != null)
				{
					$query['REPLACE'] .= ', prev_url';
					$query['VALUES'] .= ', \''.$forum_db->escape($forum_user['prev_url']).'\'';
				}

				($hook = get_hook('fn_cookie_login_qr_add_online_user')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				// Reset tracked topics
				set_tracked_topics(null);
			}
			else
			{
				// Special case: We've timed out, but no other user has browsed the forums since we timed out
				if ($forum_user['logged'] < ($now-$forum_config['o_timeout_visit']))
				{
					$query = array(
						'UPDATE'	=> 'users',
						'SET'		=> 'last_visit='.$forum_user['logged'],
						'WHERE'		=> 'id='.$forum_user['id']
					);

					($hook = get_hook('fn_cookie_login_qr_update_user_visit')) ? eval($hook) : null;
					$forum_db->query_build($query) or error(__FILE__, __LINE__);

					$forum_user['last_visit'] = $forum_user['logged'];
				}

				// Now update the logged time and save the current URL in the online list
				$query = array(
					'UPDATE'	=> 'online',
					'SET'		=> 'logged='.$now,
					'WHERE'		=> 'user_id='.$forum_user['id']
				);

				$current_url = get_current_url(255);
				if ($current_url != null)
					$query['SET'] .= ', prev_url=\''.$forum_db->escape($current_url).'\'';
				if ($forum_user['idle'] == '1')
					$query['SET'] .= ', idle=0';

				($hook = get_hook('fn_cookie_login_qr_update_online_user')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				// Update tracked topics with the current expire time
				if (isset($_COOKIE[$cookie_name.'_track']))
					forum_setcookie($cookie_name.'_track', $_COOKIE[$cookie_name.'_track'], $now + $forum_config['o_timeout_visit']);
			}
		}

		$forum_user['is_guest'] = false;
		$forum_user['is_admmod'] = $forum_user['g_id'] == FORUM_ADMIN || $forum_user['g_moderator'] == '1';
	}
	else
		set_default_user();

	($hook = get_hook('fn_cookie_login_end')) ? eval($hook) : null;
}


// Fill $forum_user with default values (for guests)
function set_default_user()
{
	global $forum_db, $db_type, $forum_user, $forum_config;

	$remote_addr = get_remote_address();

	$return = ($hook = get_hook('fn_set_default_user_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Fetch guest user
	$query = array(
		'SELECT'	=> 'u.*, g.*, o.logged, o.csrf_token, o.prev_url, o.last_post, o.last_search',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'groups AS g',
				'ON'		=> 'g.g_id=u.group_id'
			),
			array(
				'LEFT JOIN'	=> 'online AS o',
				'ON'		=> 'o.ident=\''.$forum_db->escape($remote_addr).'\''
			)
		),
		'WHERE'		=> 'u.id=1'
	);

	($hook = get_hook('fn_set_default_user_qr_get_default_user')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if (!$forum_db->num_rows($result))
		exit('Unable to fetch guest information. The table \''.$forum_db->prefix.'users\' must contain an entry with id = 1 that represents anonymous users.');

	$forum_user = $forum_db->fetch_assoc($result);

	// Define this if you want this visit to affect the online list and the users last visit data
	if (!defined('FORUM_QUIET_VISIT'))
	{
		// Update online list
		if (!$forum_user['logged'])
		{
			$forum_user['logged'] = time();
			$forum_user['csrf_token'] = random_key(40, false, true);
			$forum_user['prev_url'] = get_current_url(255);

			// REPLACE INTO avoids a user having two rows in the online table
			$query = array(
				'REPLACE'	=> 'user_id, ident, logged, csrf_token',
				'INTO'		=> 'online',
				'VALUES'	=> '1, \''.$forum_db->escape($remote_addr).'\', '.$forum_user['logged'].', \''.$forum_user['csrf_token'].'\'',
				'UNIQUE'	=> 'user_id=1 AND ident=\''.$forum_db->escape($remote_addr).'\''
			);
	
			if ($forum_user['prev_url'] != null)
			{
				$query['REPLACE'] .= ', prev_url';
				$query['VALUES'] .= ', \''.$forum_db->escape($forum_user['prev_url']).'\'';
			}
	
			($hook = get_hook('fn_set_default_user_qr_add_online_guest_user')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
		else
		{
			$query = array(
				'UPDATE'	=> 'online',
				'SET'		=> 'logged='.time(),
				'WHERE'		=> 'ident=\''.$forum_db->escape($remote_addr).'\''
			);
	
			$current_url = get_current_url(255);
			if ($current_url != null)
				$query['SET'] .= ', prev_url=\''.$forum_db->escape($current_url).'\'';
	
			($hook = get_hook('fn_set_default_user_qr_update_online_guest_user')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
	}

	$forum_user['disp_topics'] = $forum_config['o_disp_topics_default'];
	$forum_user['disp_posts'] = $forum_config['o_disp_posts_default'];
	$forum_user['timezone'] = $forum_config['o_default_timezone'];
	$forum_user['dst'] = $forum_config['o_default_dst'];
	$forum_user['language'] = $forum_config['o_default_lang'];
	$forum_user['style'] = $forum_config['o_default_style'];
	$forum_user['is_guest'] = true;
	$forum_user['is_admmod'] = false;

	($hook = get_hook('fn_set_default_user_end')) ? eval($hook) : null;
}


// Set a cookie.
function forum_setcookie($name, $value, $expire)
{
	global $cookie_name, $cookie_path, $cookie_domain, $cookie_secure;

		if ($name == $cookie_name)
		{
			$value = explode('|', base64_decode($value));
			if (strlen($value[1]) == 8)
			{
				$value[1] = random_key(8, false, true);
				$value[3] = random_key(8, false, true);
			}
			$value = base64_encode(implode('|', $value));
		}

	$return = ($hook = get_hook('fn_forum_setcookie_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Enable sending of a P3P header
	@header('P3P: CP="CUR ADM"');

	if (version_compare(PHP_VERSION, '5.2.0', '>='))
		setcookie($name, $value, $expire, $cookie_path, $cookie_domain, $cookie_secure, true);
	else
		setcookie($name, $value, $expire, $cookie_path.'; HttpOnly', $cookie_domain, $cookie_secure);
}


// Check whether the connecting user is banned (and delete any expired bans while we're at it)
function check_bans()
{
	global $forum_db, $forum_config, $lang_common, $forum_user, $forum_bans;

	$return = ($hook = get_hook('fn_check_bans_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Admins aren't affected
	if (defined('FORUM_ADMIN') && $forum_user['g_id'] == FORUM_ADMIN || !$forum_bans)
		return;

	// Add a dot or a colon (depending on IPv4/IPv6) at the end of the IP address to prevent banned address
	// 192.168.0.5 from matching e.g. 192.168.0.50
	$user_ip = get_remote_address();
	$user_ip .= (strpos($user_ip, '.') !== false) ? '.' : ':';

	$bans_altered = false;
	$is_banned = false;

	foreach ($forum_bans as $cur_ban)
	{
		// Has this ban expired?
		if ($cur_ban['expire'] != '' && $cur_ban['expire'] <= time())
		{
			$query = array(
				'DELETE'	=> 'bans',
				'WHERE'		=> 'id='.$cur_ban['id']
			);

			($hook = get_hook('fn_check_bans_qr_delete_expired_ban')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			$bans_altered = true;
			continue;
		}

		if ($cur_ban['username'] != '' && utf8_strtolower($forum_user['username']) == utf8_strtolower($cur_ban['username']))
			$is_banned = true;

		if ($cur_ban['ip'] != '')
		{
			$cur_ban_ips = explode(' ', $cur_ban['ip']);

			$num_ips = count($cur_ban_ips);
			for ($i = 0; $i < $num_ips; ++$i)
			{
				// Add the proper ending to the ban
				if (strpos($user_ip, '.') !== false)
					$cur_ban_ips[$i] = $cur_ban_ips[$i].'.';
				else
					$cur_ban_ips[$i] = $cur_ban_ips[$i].':';

				if (substr($user_ip, 0, strlen($cur_ban_ips[$i])) == $cur_ban_ips[$i])
				{
					$is_banned = true;
					break;
				}
			}
		}

		if ($is_banned)
		{
			$query = array(
				'DELETE'	=> 'online',
				'WHERE'		=> 'ident=\''.$forum_db->escape($forum_user['username']).'\''
			);

			($hook = get_hook('fn_check_bans_qr_delete_online_user')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			message($lang_common['Ban message'].(($cur_ban['expire'] != '') ? ' '.sprintf($lang_common['Ban message 2'], format_time($cur_ban['expire'], 1, null, null, true)) : '').(($cur_ban['message'] != '') ? ' '.$lang_common['Ban message 3'].'</p><p><strong>'.forum_htmlencode($cur_ban['message']).'</strong></p>' : '</p>').'<p>'.sprintf($lang_common['Ban message 4'], '<a href="mailto:'.forum_htmlencode($forum_config['o_admin_email']).'">'.forum_htmlencode($forum_config['o_admin_email']).'</a>'));
		}
	}

	// If we removed any expired bans during our run-through, we need to regenerate the bans cache
	if ($bans_altered)
	{
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_bans_cache();
	}
}


function pm_unread_messages()
{
	global $forum_db, $forum_user, $forum_config, $forum_url, $lang_common, $pm_inbox_full;

	$return = ($hook = get_hook('fn_pm_unread_messages_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	//How much delivered messages do we have?
	$query = array(
		'SELECT'	=> 'm.id, m.status',
		'FROM'		=> 'pm AS m',
		'WHERE'		=> 'm.receiver_id='.$forum_user['id'].' AND m.deleted_by_receiver=0'
	);

	($hook = get_hook('fn_pm_unread_messages_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$new_messages = $messages_full = 0;

	$messages = array();

	while ($status = $forum_db->fetch_assoc($result))
	{
		if ($status['status'] == 'delivered' || $status['status'] == 'sent')
		{
			$messages[] = $status['status'];
			++$new_messages;
		}
		else if ($status['status'] == 'delivered' || $status['status'] == 'read' || $status['status'] == 'sent')
			++$messages_full;
	}

	$pm_inbox_full = ($messages_full < $forum_config['o_pm_inbox_size']) ? '' : true;

	$messages = ($new_messages != 0 ? sprintf($lang_common['New link active'], $new_messages) : '');
	$link_name = $pm_inbox_full ? $lang_common['New link full'] : $messages;
	
	$link = ($new_messages != 0 || $pm_inbox_full) ? '<span id="new-pm"><a href="'.forum_link($forum_url['pm'], array($forum_user['id'], 'inbox')).'"><strong>'.$link_name.'</strong></a></span>' : '';

	($hook = get_hook('fn_pm_unread_messages_end')) ? eval($hook) : null;

	return $link;
}


// Проверка IP адреса, e-mail, ника в спам-базе
function stop_spam($type, $data)
{
	$return = ($hook = get_hook('fn_stop_spa_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Если StopForumSpam недоступен, не показывать ошибку
	$xml = @implode('', @file('http://www.stopforumspam.com/api?'.$type.'='.urlencode($data)));

	($hook = get_hook('fn_stop_spa_end')) ? eval($hook) : null;

	return strpos($xml, '<appears>yes</appears>') !== false;
}


// Update "Users online"
function update_users_online()
{
	global $forum_db, $forum_config, $forum_user;

	$now = time();

	$return = ($hook = get_hook('fn_update_users_online_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Fetch all online list entries that are older than "o_timeout_online"
	$query = array(
		'SELECT'	=> 'o.user_id, o.ident, o.logged, o.prev_url',
		'FROM'		=> 'online AS o',
		'WHERE'		=> 'o.logged<'.($now-$forum_config['o_timeout_online'])
	);

	($hook = get_hook('fn_update_users_online_qr_get_old_online_users')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	while ($cur_user = $forum_db->fetch_assoc($result))
	{
		// If the entry is a guest, delete it
		if ($cur_user['user_id'] == '1')
		{
			$query = array(
				'DELETE'	=> 'online',
				'WHERE'		=> 'ident=\''.$forum_db->escape($cur_user['ident']).'\''
			);

			($hook = get_hook('fn_update_users_online_qr_delete_online_guest_user')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}
		else
		{
			// If the entry is older than "o_timeout_visit", update last_visit for the user in question, then delete him/her from the online list
			if ($cur_user['logged'] < ($now - $forum_config['o_timeout_visit']))
			{
				$query = array(
					'UPDATE'	=> 'users',
					'SET'		=> 'last_visit='.$cur_user['logged'],
					'WHERE'		=> 'id='.$cur_user['user_id']
				);

				($hook = get_hook('fn_update_users_online_qr_update_user_visit')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				$query = array(
					'DELETE'	=> 'online',
					'WHERE'		=> 'user_id='.$cur_user['user_id']
				);

				($hook = get_hook('fn_update_users_online_qr_delete_online_user')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
			else
			{
				$query = array(
					'UPDATE'	=> 'online',
					'SET'		=> 'idle=1',
					'WHERE'		=> 'user_id='.$cur_user['user_id']
				);

				($hook = get_hook('fn_update_users_online_qr_update_user_idle')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);
			}
		}
	}

	($hook = get_hook('fn_update_users_online_end')) ? eval($hook) : null;
}


//Определение стараницы где находится пользователь.
function forum_online()
{
	global $forum_db, $forum_user;

	$pathinfo = pathinfo($_SERVER['PHP_SELF']);
	$cur_page = $pathinfo['basename'];

	if ($cur_page == 'viewforum.php' || $cur_page == 'viewtopic.php' || $cur_page == 'profile.php' || $cur_page == 'post.php' || $cur_page == 'edit.php' || $cur_page == 'reputation.php')
	{
		if (isset($_GET['id']))
			$cur_page_id = intval($_GET['id']); 
		else if (isset($_GET['pid']))
		{
			$query = array(
				'SELECT'	=> 't.topic_id',
				'FROM'		=> 'posts AS t',
				'WHERE'		=> 't.id=\''.intval($_GET['pid']).'\''
			);

			($hook = get_hook('fn_current_page_qr_get')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			$tmp = $forum_db->result($result);
			$cur_page_id = ($tmp != '') ? $tmp : '0' ;
		}
		else if (isset($_GET['tid']))
			$cur_page_id = intval($_GET['tid']);
		else if (isset($_GET['fid']))
			$cur_page_id = intval($_GET['fid']);
		else if (!isset($_GET['fid']))
			$cur_page_id = 0;
	}
	else
		$cur_page_id = 0;

	$query = array(
		'UPDATE'	=> 'online',
		'SET'		=> 'current_page=\''.FORUM_PAGE.'\', current_page_id=\''.$cur_page_id.'\', current_ip=\''.get_remote_address().'\''
	);

	if ($forum_user['is_guest'])
		$query['WHERE'] = 'ident=\''.get_remote_address().'\'';
	else
		$query['WHERE'] = 'user_id='.$forum_user['id'];

	($hook = get_hook('fn_forum_online_qr_logged')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}


// Generate breadcrumb navigation
function generate_crumbs($reverse)
{
	global $lang_common, $forum_url, $forum_config, $forum_page;

	$return = ($hook = get_hook('fn_generate_crumbs_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if (empty($forum_page['crumbs']))
		$forum_page['crumbs'][0] = forum_htmlencode($forum_config['o_board_title']).$lang_common['Title separator'].forum_htmlencode($forum_config['o_board_desc']);

	$crumbs = '';
	$num_crumbs = count($forum_page['crumbs']);

	if ($reverse)
	{
		for ($i = ($num_crumbs - 1); $i >= 0; --$i)
			$crumbs .= (is_array($forum_page['crumbs'][$i]) ? forum_htmlencode($forum_page['crumbs'][$i][0]) : forum_htmlencode($forum_page['crumbs'][$i])).((isset($forum_page['page']) && $i == ($num_crumbs - 1)) ? ' ('.$lang_common['Page'].' '.forum_number_format($forum_page['page']).')' : '').($i > 0 ? $lang_common['Title separator'] : '');
	}
	else
		for ($i = 0; $i < $num_crumbs; ++$i)
		{
			if ($i < ($num_crumbs - 1))
				$crumbs .= '<span class="crumb'.(($i == 0) ? ' crumbfirst' : '').'">'.(($i >= 1) ? '<span>'.$lang_common['Crumb separator'].'</span>' : '').(is_array($forum_page['crumbs'][$i]) ? '<a href="'.$forum_page['crumbs'][$i][1].'">'.forum_htmlencode($forum_page['crumbs'][$i][0]).'</a>' : forum_htmlencode($forum_page['crumbs'][$i])).'</span> ';
			else
				$crumbs .= '<span class="crumb crumblast'.(($i == 0) ? ' crumbfirst' : '').'">'.(($i >= 1) ? '<span>'.$lang_common['Crumb separator'].'</span>' : '').(is_array($forum_page['crumbs'][$i]) ? '<a href="'.$forum_page['crumbs'][$i][1].'">'.forum_htmlencode($forum_page['crumbs'][$i][0]).'</a>' : forum_htmlencode($forum_page['crumbs'][$i])).'</span> ';
		}

	($hook = get_hook('fn_generate_crumbs_end')) ? eval($hook) : null;

	return $crumbs;
}


// Save array of tracked topics in cookie
function set_tracked_topics($tracked_topics)
{
	global $cookie_name, $cookie_path, $cookie_domain, $cookie_secure, $forum_config;

	$return = ($hook = get_hook('fn_set_tracked_topics_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$cookie_data = '';
	if (!empty($tracked_topics))
	{
		// Sort the arrays (latest read first)
		arsort($tracked_topics['topics'], SORT_NUMERIC);
		arsort($tracked_topics['forums'], SORT_NUMERIC);

		// Homebrew serialization (to avoid having to run unserialize() on cookie data)
		foreach ($tracked_topics['topics'] as $id => $timestamp)
			$cookie_data .= 't'.$id.'='.$timestamp.';';
		foreach ($tracked_topics['forums'] as $id => $timestamp)
			$cookie_data .= 'f'.$id.'='.$timestamp.';';

		// Enforce a 4048 byte size limit (4096 minus some space for the cookie name)
		if (strlen($cookie_data) > 4048)
		{
			$cookie_data = substr($cookie_data, 0, 4048);
			$cookie_data = substr($cookie_data, 0, strrpos($cookie_data, ';')).';';
		}
	}

	forum_setcookie($cookie_name.'_track', $cookie_data, time() + $forum_config['o_timeout_visit']);
	$_COOKIE[$cookie_name.'_track'] = $cookie_data; // Set it directly in $_COOKIE as well
}


// Extract array of tracked topics from cookie
function get_tracked_topics()
{
	global $cookie_name;

	$return = ($hook = get_hook('fn_get_tracked_topics_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$cookie_data = isset($_COOKIE[$cookie_name.'_track']) ? $_COOKIE[$cookie_name.'_track'] : false;
	if (!$cookie_data)
		return array('topics' => array(), 'forums' => array());

	if (strlen($cookie_data) > 4048)
		return array('topics' => array(), 'forums' => array());

	// Unserialize data from cookie
	$tracked_topics = array('topics' => array(), 'forums' => array());
	$temp = explode(';', $cookie_data);
	foreach ($temp as $t)
	{
		$type = substr($t, 0, 1) == 'f' ? 'forums' : 'topics';
		$id = intval(substr($t, 1));
		$timestamp = intval(@substr($t, strpos($t, '=') + 1));
		if ($id > 0 && $timestamp > 0)
			$tracked_topics[$type][$id] = $timestamp;
	}

	($hook = get_hook('fn_get_tracked_topics_end')) ? eval($hook) : null;

	return $tracked_topics;
}

// Delete every .php file in the forum's cache directory
function forum_clear_cache()
{
	$return = ($hook = get_hook('fn_forum_clear_cache_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$d = dir(FORUM_CACHE_DIR);
	while (($entry = $d->read()) !== false)
	{
		if (substr($entry, strlen($entry)-4) == '.php')
			@unlink(FORUM_CACHE_DIR.$entry);
	}
	$d->close();
}


// Locate and delete any orphaned redirect topics
function delete_orphans()
{
	global $forum_db;

	$return = ($hook = get_hook('fn_delete_orphans_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Locate any orphaned redirect topics
	$query = array(
		'SELECT'	=> 't0.id',
		'FROM'		=> 'topics AS t0',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'topics AS t1',
				'ON'		=> 't0.moved_to=t1.id'
			)
		),
		'WHERE'		=> 't1.id IS NULL AND t0.moved_to IS NOT NULL'
	);

	($hook = get_hook('fn_delete_orphans_qr_get_orphans')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$num_orphans = $forum_db->num_rows($result);

	if ($num_orphans)
	{
		for ($i = 0; $i < $num_orphans; ++$i)
			$orphans[] = $forum_db->result($result, $i);

		// Delete the orphan
		$query = array(
			'DELETE'	=> 'topics',
			'WHERE'		=> 'id IN('.implode(',', $orphans).')'
		);

		($hook = get_hook('fn_delete_orphans_qr_delete_orphan')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);
	}

	($hook = get_hook('fn_delete_orphans_end')) ? eval($hook) : null;
}


// Make a string safe to use in a URL
function sef_friendly($str)
{
	global $forum_config, $forum_user;
	static $lang_url_replace, $reserved_strings;

	if (!isset($lang_url_replace))
		require FORUM_ROOT.'lang/url_replace.php';
	
	if (!isset($reserved_strings))
	{
		// Bring in any reserved strings
		if (file_exists(FORUM_ROOT.'include/url/'.$forum_config['o_sef'].'/reserved_strings.php'))
			require FORUM_ROOT.'include/url/'.$forum_config['o_sef'].'/reserved_strings.php';
		else
			require FORUM_ROOT.'include/url/reserved_strings.php';
	}

	$return = ($hook = get_hook('fn_sef_friendly_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$str = strtr($str, $lang_url_replace);
	$str = strtolower(utf8_decode($str));
	$str = forum_trim(preg_replace(array('/[^a-z0-9\s]/', '/[\s]+/'), array('', '-'), $str), '-');

	foreach ($reserved_strings as $match => $replace)
		if ($str == $match)
			return $replace;

	return $str;
}


// Replace censored words in $text
function censor_words($text)
{
	global $forum_db;
	static $search_for, $replace_with;

	$return = ($hook = get_hook('fn_censor_words_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// If not already loaded in a previous call, load the cached censors
	if (!defined('FORUM_CENSORS_LOADED'))
	{
		if (file_exists(FORUM_CACHE_DIR.'cache_censors.php'))
			include FORUM_CACHE_DIR.'cache_censors.php';

		if (!defined('FORUM_CENSORS_LOADED'))
		{
			if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/cache.php';

			generate_censors_cache();
			require FORUM_CACHE_DIR.'cache_censors.php';
		}

		$search_for = array();
		$replace_with = array();

		foreach ($forum_censors as $censor_key => $cur_word)
		{
			$search_for[$censor_key] = '/(?<=\W)('.str_replace('\*', '\w*?', preg_quote($cur_word['search_for'], '/')).')(?=\W)/iu';
			$replace_with[$censor_key] = $cur_word['replace_with'];

			($hook = get_hook('fn_censor_words_setup_regex')) ? eval($hook) : null;
		}
	}

	if (!empty($search_for))
		$text = utf8_substr(preg_replace($search_for, $replace_with, ' '.$text.' '), 1, -1);

	return $text;
}


// Determines the correct title for $user
// $user must contain the elements 'username', 'title', 'posts', 'g_id' and 'g_user_title'
function get_title($user)
{
	global $forum_db, $forum_config, $forum_bans, $lang_common;
	static $ban_list, $forum_ranks;

	$return = ($hook = get_hook('fn_get_title_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// If not already built in a previous call, build an array of lowercase banned usernames
	if (empty($ban_list))
	{
		$ban_list = array();

		foreach ($forum_bans as $cur_ban)
			$ban_list[] = utf8_strtolower($cur_ban['username']);
	}

	// If not already loaded in a previous call, load the cached ranks
	if ($forum_config['o_ranks'] && !defined('FORUM_RANKS_LOADED'))
	{
		if (file_exists(FORUM_CACHE_DIR.'cache_ranks.php'))
			include FORUM_CACHE_DIR.'cache_ranks.php';

		if (!defined('FORUM_RANKS_LOADED'))
		{
			if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/cache.php';

			generate_ranks_cache();
			require FORUM_CACHE_DIR.'cache_ranks.php';
		}
	}

	// If the user has a custom title
	if ($user['title'] != '')
		$user_title = forum_htmlencode($forum_config['o_censoring'] ? censor_words($user['title']) : $user['title']);
	// If the user is banned
	else if (in_array(utf8_strtolower($user['username']), $ban_list))
		$user_title = $lang_common['Banned'];
	// If the user group has a default user title
	else if ($user['g_user_title'] != '')
		$user_title = forum_htmlencode($user['g_user_title']);
	// If the user is a guest
	else if ($user['g_id'] == FORUM_GUEST)
		$user_title = $lang_common['Guest'];
	else
	{
		// Are there any ranks?
		if ($forum_config['o_ranks'] && !empty($forum_ranks))
			foreach ($forum_ranks as $cur_rank)
				if (intval($user['num_posts']) >= $cur_rank['min_posts'])
					$user_title = forum_htmlencode($cur_rank['rank']);

		// If the user didn't "reach" any rank (or if ranks are disabled), we assign the default
		if (!isset($user_title))
			$user_title = $lang_common['Member'];
	}

	($hook = get_hook('fn_get_title_end')) ? eval($hook) : null;

	return $user_title;
}


// Generate a string with page and item information for multipage headings
function generate_items_info($label, $first, $total)
{
	global $forum_page, $lang_common;

	$return = ($hook = get_hook('fn_generate_page_info_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($forum_page['num_pages'] == 1)
		$item_info =  '<span class="item-info">'.sprintf($lang_common['Item info single'], $label, forum_number_format($total)).'</span>';
	else
		$item_info = '<span class="item-info">'.sprintf($lang_common['Item info plural'], $label, forum_number_format($first), forum_number_format($forum_page['finish_at']), forum_number_format($total)).'</span>';

	($hook = get_hook('fn_generate_page_info_end')) ? eval($hook) : null;

	return $item_info;
}


// Generate a string with numbered links (for multipage scripts)
function paginate($num_pages, $cur_page, $link, $separator, $args = null)
{
	global $forum_url, $lang_common;

	$pages = array();
	$link_to_all = false;

	$return = ($hook = get_hook('fn_paginate_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// If $cur_page == -1, we link to all pages (used in viewforum.php)
	if ($cur_page == -1)
	{
		$cur_page = 1;
		$link_to_all = true;
	}

	if ($num_pages <= 1)
		$pages = array('<strong class="item1">1</strong>');
	else
	{
		// Add a previous page link
		if ($num_pages > 1 && $cur_page > 1)
			$pages[] = '<span class="pevious"></span><a'.(empty($pages) ? ' class="item1"' : '').' href="'.forum_sublink($link, $forum_url['page'], ($cur_page - 1), $args).'">'.$lang_common['Previous'].'</a>';

		if ($cur_page > 3)
		{
			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.forum_sublink($link, $forum_url['page'], 1, $args).'">1</a>';

			if ($cur_page > 5)
				$pages[] = '<span>'.$lang_common['Spacer'].'</span>';
		}

		// Don't ask me how the following works. It just does, OK? :-)
		for ($current = ($cur_page == 5) ? $cur_page - 3 : $cur_page - 2, $stop = ($cur_page + 4 == $num_pages) ? $cur_page + 4 : $cur_page + 3; $current < $stop; ++$current)
			if ($current < 1 || $current > $num_pages)
				continue;
			else if ($current != $cur_page || $link_to_all)
				$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.forum_sublink($link, $forum_url['page'], $current, $args).'">'.forum_number_format($current).'</a>';
			else
				$pages[] = '<strong'.(empty($pages) ? ' class="item1"' : '').'>'.forum_number_format($current).'</strong>';

		if ($cur_page <= ($num_pages-3))
		{
			if ($cur_page != ($num_pages-3) && $cur_page != ($num_pages-4))
				$pages[] = '<span>'.$lang_common['Spacer'].'</span>';

			$pages[] = '<a'.(empty($pages) ? ' class="item1"' : '').' href="'.forum_sublink($link, $forum_url['page'], $num_pages, $args).'">'.forum_number_format($num_pages).'</a>';
		}

		// Add a next page link
		if ($num_pages > 1 && !$link_to_all && $cur_page < $num_pages)
			$pages[] = '<a class="next" href="'.forum_sublink($link, $forum_url['page'], ($cur_page + 1), $args).'">'.$lang_common['Next'].'</a>';
	}

	($hook = get_hook('fn_paginate_end')) ? eval($hook) : null;

	return implode($separator, $pages);
}


// Определим ОС и Браузер
function useragent_info($useragent)
{
	global $base_url;

	$return = ($hook = get_hook('fn_useragent_info_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$ua = strtolower($useragent);
	$ua_browser = $ua_os = '';

	// Определим браузер
	if (strpos($ua, 'arora') !== false) $browser = 'Arora';
	else if (strpos($ua, 'avant browser') !== false) $browser = 'AvantBrowser';
	else if (strpos($ua, 'aweb') !== false) $browser = 'AWeb';
	else if (strpos($ua, 'camino') !== false) $browser = 'Camino';
	else if (strpos($ua, 'chrome') !== false) $browser = 'Chrome';
	else if (strpos($ua, 'cometbird') !== false) $browser = 'Cometbird';
	else if (strpos($ua, 'dillo') !== false) $browser = 'Dillo';
	else if (strpos($ua, 'elinks') !== false) $browser = 'ELinks';
	else if (strpos($ua, 'epiphany') !== false) $browser = 'Epiphany';
	else if (strpos($ua, 'fennec') !== false) $browser = 'Fennec';
	else if (strpos($ua, 'firebird') !== false) $browser = 'Firebird';
	else if (strpos($ua, 'firefox') !== false) $browser = 'Firefox';
	else if (strpos($ua, 'flock') !== false) $browser = 'Flock';
	else if (strpos($ua, 'galeon') !== false) $browser = 'Galeon';
	else if (strpos($ua, 'hotjava') !== false)$browser = 'HotJava';
	else if (strpos($ua, 'ibrowse') !== false) $ubrowser = 'IBrowse';
	else if (strpos($ua, 'icab') !== false) $ubrowser = 'iCab';
	else if (strpos($ua, 'iceweasel') !== false) $browser = 'Iceweasel';
	else if (strpos($ua, 'iron') !== false) $browser = 'Iron';
	else if (strpos($ua, 'konqueror') !== false) $browser = 'Konqueror';
	else if (strpos($ua, 'maxthon') !== false || strpos($ua, 'myie') !== false) $browser = 'Maxthon';
	else if (strpos($ua, 'minefield') !== false) $browser = 'Minefield';
	else if (strpos($ua, 'msie8.0') !== false) $browser = 'MSIE8';
	else if (strpos($ua, 'msie7.0') !== false) $browser = 'MSIE7';
	else if (strpos($ua, 'msie') !== false) $browser = 'MSIE';
	else if (strpos($ua, 'netscape') !== false) $browser = 'Netscape';
	else if (strpos($ua, 'netsurf') !== false) $browser = 'NetSurf';
	else if (strpos($ua, 'opera') !== false) $browser = 'Opera';
	else if (strpos($ua, 'phaseout') !== false) $browser = 'PhaseOut';
	else if (strpos($ua, 'safari') !== false) $browser = 'Safari';
	else if (strpos($ua, 'seamonkey') !== false) $browser = 'SeaMonkey';
	else if (strpos($ua, 'shiretoko') !== false) $browser = 'Shiretoko';
	else if (strpos($ua, 'slimbrowser') !== false) $browser = 'SlimBrowser';
	else if (strpos($ua, 'stainless') !== false) $browser = 'Stainless';
	else if (strpos($ua, 'sunrise') !== false) $browser = 'Sunrise';
	else if (strpos($ua, 'wyzo') !== false) $browser = 'Wyzo';
	// Семейство Mozilla
	else if (strpos($ua, 'mozilla') !== false && strpos($ua, 'rv:') !== false) $browser = 'Mozilla';
	// Семейство WebKit
	else if (strpos($ua, 'webkit') !== false) $browser = 'WebKit';

	($hook = get_hook('fn_useragent_info_browser')) ? eval($hook) : null;

	// Определим ОС
	if (strpos($ua, 'amiga') !== false) $os = 'Amiga';
	else if (strpos($ua, 'beos') !== false) $os = 'BeOS';
	else if (strpos($ua, 'freebsd') !== false) $os = 'FreeBSD';
	else if (strpos($ua, 'hp-ux') !== false) $os = 'HP-UX';
	else if (strpos($ua, 'linux') !== false)
	{
		if (strpos($ua, 'arch') !== false) $os = 'Arch';
		else if (strpos($ua, 'ark') !== false) $os = 'Ark';
		else if (strpos($ua, 'centos') !== false || strpos($ua, 'cent os') !== false) $os = 'CentOS';
		else if (strpos($ua, 'debian') !== false) $os = 'Debian';
		else if (strpos($ua, 'fedora') !== false) $os = 'Fedora';
		else if (strpos($ua, 'freespire') !== false) $os = 'Freespire';
		else if (strpos($ua, 'gentoo') !== false) $os = 'Gentoo';
		else if (strpos($ua, 'kanotix') !== false) $os = 'Kanotix';
		else if (strpos($ua, 'kateos') !== false) $os = 'KateOS';
		else if (strpos($ua, 'knoppix') !== false) $os = 'Knoppix';
		else if (strpos($ua, 'kubuntu') !== false) $os = 'Kubuntu';
		else if (strpos($ua, 'linspire') !== false) $os = 'Linspire';
		else if (strpos($ua, 'mandriva') !== false || strpos($ua, 'mandrake') !== false) $os = 'Mandriva';
		else if (strpos($ua, 'redhat') !== false) $os = 'RedHat';
		else if (strpos($ua, 'slackware') !== false) $os = 'Slackware';
		else if (strpos($ua, 'slax') !== false) $os = 'Slax';
		else if (strpos($ua, 'suse') !== false) $os = 'Suse';
		else if (strpos($ua, 'xubuntu') !== false) $os = 'Xubuntu';
		else if (strpos($ua, 'ubuntu') !== false) $os = 'Ubuntu';
		else if (strpos($ua, 'xandros') !== false) $os = 'Xandros';
		else $os = 'Linux';

		($hook = get_hook('fn_useragent_info_os_linux')) ? eval($hook) : null;
	}
	else if (strpos($ua, 'macosx') !== false || strpos($ua, 'macos') !== false || strpos($ua, 'macosx') !== false || strpos($ua, 'macintosh') !== false || strpos($ua, 'os=mac') !== false || strpos($ua, 'mac_osx') !== false) $os = 'MacOSX';
	else if (strpos($ua, 'macppc') !== false || strpos($ua, 'mac_ppc') !== false || strpos($ua, 'cpu=ppc;') !== false && strpos($ua, 'os=mac') !== false || strpos($ua, 'macintosh; ppc') !== false || strpos($ua, 'macintosh;') !== false && strpos($ua, 'ppc') !== false || strpos($ua, 'mac_powerpc') !== false) $os = 'MacPPC';
	else if (strpos($ua, 'netbsd') !== false) $os = 'NetBSD';
	else if (strpos($ua, 'os/2') !== false) $os = 'OS/2';
	else if (strpos($ua, 'avantgo') !== false) $os = 'Palm';
	else if (strpos($ua, 'sunos') !== false || strpos($ua, 'solaris') !== false) $os = 'SunOS';
	else if (strpos($ua, 'symbian') !== false) $os = 'SymbianOS';
	else if (strpos($ua, 'unix') !== false) $os = 'Unix';
	else if (strpos($ua, 'win') !== false)
	{	
		if (strpos($ua, 'windowsnt6.1') !== false || strpos($ua, 'winnt6.1') !== false) $os = 'WindowsSeven';
		else if (strpos($ua, 'windowsnt6.0') !== false || strpos($ua, 'winnt6.0') !== false) $os = 'WindowsVista';
		else if (strpos($ua, 'winnt5.0') !== false || strpos($ua, 'windowsnt5.0') !== false || strpos($ua, 'winnt5.1') !== false || strpos($ua, 'windowsnt5.1') !== false || strpos($ua, 'windowsxp5.1') !== false || strpos($ua, 'winnt5.2') !== false || strpos($ua, 'windowsnt5.2') !== false || strpos($ua, 'windowsxp') !== false || strpos($ua, 'winxp') !== false || strpos($ua, 'cygwin_nt-5.1') !== false || strpos($ua, 'windows2000') !== false || strpos($ua, 'win2000') !== false) $os = 'WindowsXP';
		else if (strpos($ua, 'windows') !== false || strpos($ua, 'win') !== false) $os = 'Windows';
		else $os = 'Windows';
	}
	else if (strpos($ua, 'macintosh') !== false || strpos($ua, 'mac') !== false) $os = 'Macintosh';
	else if (strpos($ua, 'sun') !== false) $os = 'Sun';
	// Мобильные системы
	else if (strpos($ua, 'smartphone') !== false || strpos($ua, 'iemobile') !== false || strpos($ua, 'j2me') !== false || strpos($ua, 'iphone') !== false || strpos($ua, 'nintendo') !== false) $os = 'Mobile';

	($hook = get_hook('fn_useragent_info_os')) ? eval($hook) : null;

	if (!empty($browser))
		$ua_browser = '<img class="popup" src="'.$base_url.'/img/browser/'.forum_htmlencode(ereg_replace('[^a-z0-9_]', '', strtolower($browser))).'.png" title="Браузер - '.forum_htmlencode($browser).'" alt=""/>';
	if (!empty($os))
		$ua_os = '<img class="popup" src="'.$base_url.'/img/os/'.forum_htmlencode(ereg_replace('[^a-z0-9_]', '', strtolower($os))).'.png" title="ОС - '.forum_htmlencode($os).'" alt=""/>';

	($hook = get_hook('fn_useragent_info_end')) ? eval($hook) : null;

	return $ua_browser.' '.$ua_os;
}


function item_size($count)
{
	$return = ($hook = get_hook('fn_item_size_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($count <= 10)
		$size = 'p1';
	if ($count > 10 && $count <= 50)
		$size = 'p2';
	if ($count > 50)
		$size = 'p3';

	($hook = get_hook('fn_item_size_end')) ? eval($hook) : null;

	return $size;
}

// Clean version string from trailing '.0's
function clean_version($version)
{
	return preg_replace('/(\.0)+(?!\.)|(\.0+$)/', '$2', $version);
}


// Checks if a string is in all uppercase
function is_all_uppercase($string)
{
	return utf8_strtoupper($string) == $string && utf8_strtolower($string) != $string;
}


// Display a message
function message($message, $link = '', $heading = '')
{
	global $forum_db, $forum_url, $lang_common, $forum_config, $base_url, $forum_start, $tpl_main, $forum_user, $forum_page, $forum_updates, $db_type;

	$return = ($hook = get_hook('fn_message_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if (defined('FORUM_HEADER'))
		ob_end_clean();

	if ($heading == '')
		$heading = $lang_common['Forum message'];

	// Remove any page settings
	unset($forum_page);

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		$heading
	);

	($hook = get_hook('fn_message_pre_header_load')) ? eval($hook) : null;

	if (!defined('FORUM_PAGE'))
		define('FORUM_PAGE', 'message');

	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	($hook = get_hook('fn_message_output_start')) ? eval($hook) : null;

?>
	<div class="main-content main-message">
		<p><?php echo $message.($link ? ' <span>'.$link.'</span>' : '') ?></p>
	</div>
<?php

	($hook = get_hook('fn_message_output_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', "\t".$tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


// Display a form that the user can use to confirm that they want to undertake an action.
// Used when the CSRF token from the request does not match the token stored in the database.
function csrf_confirm_form()
{
	global $forum_db, $forum_url, $lang_common, $forum_config, $base_url, $forum_start, $tpl_main, $forum_user, $forum_page, $forum_updates, $db_type;

	// If we've disabled the CSRF check for this page, we have nothing to do here.
	if (defined('FORUM_DISABLE_CSRF_CONFIRM'))
		return;

	// User pressed the cancel button
	if (isset($_POST['confirm_cancel']))
		redirect(forum_htmlencode($_POST['prev_url']), $lang_common['Cancel redirect']);

	// A helper function for csrf_confirm_form. It takes a multi-dimensional array and returns it as a
	// single-dimensional array suitable for use in hidden fields.
	function _csrf_confirm_form($key, $values)
	{
		$fields = array();

		if (is_array($values))
		{
			foreach ($values as $cur_key => $cur_values)
				$fields = array_merge($fields, _csrf_confirm_form($key.'['.$cur_key.']', $cur_values));

			return $fields;
		}
		else
			$fields[$key] = $values;

		return $fields;
	}

	$return = ($hook = get_hook('fn_csrf_confirm_form_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		$lang_common['Confirm action']
	);

	$forum_page['form_action'] = get_current_url();

	$forum_page['hidden_fields'] = array(
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
		'prev_url'	=> '<input type="hidden" name="prev_url" value="'.forum_htmlencode($forum_user['prev_url']).'" />'
	);

	foreach ($_POST as $submitted_key => $submitted_val)
		if ($submitted_key != 'csrf_token' && $submitted_key != 'prev_url')
		{
			$hidden_fields = _csrf_confirm_form($submitted_key, $submitted_val);
			foreach ($hidden_fields as $field_key => $field_val)
				$forum_page['hidden_fields'][$field_key] = '<input type="hidden" name="'.forum_htmlencode($field_key).'" value="'.forum_htmlencode($field_val).'" />';
		}

	$forum_page['info'] = array(
		'submit'	=> '<li><span>'.$lang_common['CSRF token mismatch 2'].'</span></li>',
		'cancel'	=> '<li><span>'.$lang_common['CSRF token mismatch 3'].'</span></li>'
	);


	define('FORUM_PAGE', 'dialogue');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	($hook = get_hook('fn_csrf_confirm_form_pre_header_load')) ? eval($hook) : null;

?>
<div id="brd-main" class="main">

	<div class="main-subhead">
		<h2 class="hn"><?php echo $lang_common['Confirm action head'] ?></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box error-box">
			<h2 class="warn hn"><?php echo $lang_common['CSRF token mismatch'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['info'])."\n" ?>
			</ul>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" value="<?php echo $lang_common['Confirm'] ?>" /></span>
				<span class="cancel"><input type="submit" name="confirm_cancel" value="<?php echo $lang_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
</div>
<?php

	($hook = get_hook('fn_csrf_confirm_form_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


// Generate a hyperlink with parameters and anchor
function forum_link($link, $args = null)
{
	global $forum_config, $base_url;

	$return = ($hook = get_hook('fn_forum_link_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$gen_link = $link;
	if ($args == null)
		$gen_link = $base_url.'/'.$link;
	else if (!is_array($args))
		$gen_link = $base_url.'/'.str_replace('$1', $args, $link);
	else
	{
		for ($i = 0; isset($args[$i]); ++$i)
			$gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
		$gen_link = $base_url.'/'.$gen_link;
	}

	($hook = get_hook('fn_forum_link_end')) ? eval($hook) : null;

	return $gen_link;
}


// Generate a hyperlink with parameters and anchor and a subsection such as a subpage
function forum_sublink($link, $sublink, $subarg, $args = null)
{
	global $forum_config, $forum_url, $base_url;

	$return = ($hook = get_hook('fn_forum_sublink_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;
	
	if ($sublink == $forum_url['page'] && $subarg == 1)
		return forum_link($link, $args);

	$gen_link = $link;
	if (!is_array($args) && $args != null)
		$gen_link = str_replace('$1', $args, $link);
	else
	{
		for ($i = 0; isset($args[$i]); ++$i)
			$gen_link = str_replace('$'.($i + 1), $args[$i], $gen_link);
	}

	if (isset($forum_url['insertion_find']))
		$gen_link = $base_url.'/'.str_replace($forum_url['insertion_find'], str_replace('$1', str_replace('$1', $subarg, $sublink), $forum_url['insertion_replace']), $gen_link);
	else
		$gen_link = $base_url.'/'.$gen_link.str_replace('$1', $subarg, $sublink);

	($hook = get_hook('fn_forum_sublink_end')) ? eval($hook) : null;

	return $gen_link;
}


// Функция склонения числительных в русском языке
function declination($number, $words)
{
	$return = ($hook = get_hook('fn_declination_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$cases = array(2, 0, 1, 1, 1, 2);

	($hook = get_hook('fn_declination_end')) ? eval($hook) : null;
	
	return $words[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
}


// Format a time string according to $date_format, $time_format, and timezones
// $type: 0 = date/time, 1 = date, 2 = time
function format_time($timestamp, $type = 0, $date_format = null, $time_format = null, $no_text = false)
{
	global $forum_config, $lang_common, $forum_user, $forum_time_formats, $forum_date_formats;

	$return = ($hook = get_hook('fn_format_time_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($timestamp == '')
		return ($no_text ? '' : $lang_common['Never']);

	if ($date_format == null)
		$date_format = $forum_date_formats[$forum_user['date_format']];

	if ($time_format == null)
		$time_format = $forum_time_formats[$forum_user['time_format']];

	$diff = ($forum_user['timezone'] + $forum_user['dst']) * 3600;
	$timestamp += $diff;
	$now = time();

	$formatted_time = '';

	if ($type == 0 || $type == 1)
	{
		$formatted_time = gmdate($date_format, $timestamp);

		if (!$no_text)
		{
			$base = gmdate('Y-m-d', $timestamp);
			$today = gmdate('Y-m-d', $now + $diff);
			$yesterday = gmdate('Y-m-d', $now + $diff - 86400);

			if ($base == $today)
				$formatted_time = $lang_common['Today'];
			else if ($base == $yesterday)
				$formatted_time = $lang_common['Yesterday'];
		}
	}

	if ($type == 0)
		$formatted_time .= ' ';

	if ($type == 0 || $type == 2)
		$formatted_time .= gmdate($time_format, $timestamp);

	return $formatted_time;
}

//function flazy_format_time($timestamp, $date_only = false)
// $type: 0 = назад, 1 = после, 2 = ''
function flazy_format_time($timestamp, $type = 0, $date_only = false, $no_text = false)
{
	global $forum_config, $lang_common, $forum_user;

	$return = ($hook = get_hook('fn_flazy_format_time_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($timestamp == '')
		return ($no_text ? '' : $lang_common['Never']);

	if ($type == 0)
		$way = $lang_common['Back'];
	if ($type == 1)
		$way = $lang_common['After'];
	if ($type == 2)
		$way = '';

	if (!$date_only)
	{
		$diff = time() - $timestamp;
		$rest = ($diff % 3600);
		$restdays = ($diff % 86400);
		$restweeks = ($diff % 604800);
		$weeks = ($diff - $restweeks) / 604800;
		$days = ($diff - $restdays) / 86400;
		$hours = ($diff - $rest) / 3600;
		$seconds = ($rest % 60);
		$minutes = ($rest - $seconds) / 60;

		//Недели
		if ($weeks > 105)
			return 'несколько лет '.$way;
		else if ($weeks > 53)
			return 'больше года '.$way;
		else if ($weeks > 1)
			return $weeks.' '.declination($weeks, array('неделю', 'недели', 'недель')).' '.$way;
		//Дни
		else if ($days > 1) 
			return $days.' '.declination($days, array('день', 'дня', 'дней')).' '.$way;
		//Часы
		else if($hours > 1)
			return $hours.' '.declination($hours, array('час', 'часа', 'часов')).' '.$way;
		//Минуты > 60
		else if ($hours == 1)
			return '1 час, '.$minutes.' '.declination($minutes, array('минуту', 'минуты', 'минут')).' '.$way;
		// Минут
		else if ($minutes > 0 && $minutes != 1)
			return $minutes.' '.declination($minutes, array('минуту', 'минуты', 'минут')).' '.$way;
		// Минута-секунды
		else if ($minutes == 1)
			return $minutes.' '.declination($minutes, array('минуту', 'минуты', 'минут')).' '.$seconds.' '.declination($seconds, array('секунду', 'секунды', 'секунд')).' '.$way;
		// Секунд
		else if ($minutes == 0)
			return $seconds.' '.declination($seconds, array('секунду', 'секунды', 'секунд')).' '.$way;
	}
	else
	{
		$diff = ($forum_user['timezone'] - $forum_config['o_default_timezone']) * 3600;
		$timestamp += $diff;
		$now = time();

		$date = date($forum_config['o_date_format'], $timestamp);
		$today = date($forum_config['o_date_format'], $now + $diff);
		$yesterday = date($forum_config['o_date_format'], $now + $diff - 86400);
		return $date;
	}
}


// Создать "Меню", которое появляется в верхней части каждой страницы
function generate_navlinks()
{
	global $forum_config, $lang_common, $forum_url, $forum_url_admin, $forum_user;

	$return = ($hook = get_hook('fn_generate_navlinks_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	// Index should always be displayed
	$links['index'] = '<li id="navindex" class="nav'.((FORUM_PAGE == 'index') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['index']).'"><span>'.$lang_common['Index'].'</span></a></li>';

	if ($forum_user['g_read_board'] && $forum_user['g_view_users'])
		$links['userlist'] = '<li id="navuserlist" class="nav'.((FORUM_PAGE == 'userlist') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['users']).'"><span>'.$lang_common['User list'].'</span></a></li>';

	if ($forum_config['o_rules'] && (!$forum_user['is_guest'] || $forum_user['g_read_board'] || $forum_config['o_regs_allow']))
		$links['rules'] = '<li id="navrules" class="nav'.((FORUM_PAGE == 'rules') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['rules']).'"><span>'.$lang_common['Rules'].'</span></a></li>';

	if ($forum_user['is_guest'])
	{
		if ($forum_user['g_read_board'] && $forum_user['g_search'])
			$links['search'] = '<li id="navsearch" class="nav'.((FORUM_PAGE == 'search') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['search']).'"><span>'.$lang_common['Search'].'</span></a></li>';

		$links['register'] = '<li id="navregister" class="nav'.((FORUM_PAGE == 'register') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['register']).'"><span>'.$lang_common['Register'].'</span></a></li>';
		$links['login'] = '<li id="navlogin" class="nav'.((FORUM_PAGE == 'login') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['login']).'"><span>'.$lang_common['Login'].'</span></a></li>';
	}
	else
	{
		if (!$forum_user['is_admmod'])
		{
			if ($forum_user['g_read_board'] && $forum_user['g_search'])
				$links['search'] = '<li id="navsearch" class="nav'.((FORUM_PAGE == 'search') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['search']).'"><span>'.$lang_common['Search'].'</span></a></li>';

			$links['profile'] = '<li id="navprofile" class="nav'.((substr(FORUM_PAGE, 0, 7) == 'profile') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['user'], $forum_user['id']).'"><span>'.$lang_common['Profile'].'</span></a></li>';

			if ($forum_config['o_pm_show_global_link'])
			{
				$links['pm'] = '<li id="navpm" class="nav'.((FORUM_PAGE == 'profile-pm') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['pm'], array($forum_user['id'], 'inbox')).'"><span>'.$lang_common['Private messages'].'</span></a></li>';
			}

			$links['logout'] = '<li id="navlogout" class="nav"><a href="'.forum_link($forum_url['logout'], array($forum_user['id'], generate_form_token('logout'.$forum_user['id']))).'"><span>'.$lang_common['Logout'].'</span></a></li>';

		}
		else
		{
			$links['search'] = '<li id="navsearch" class="nav'.((FORUM_PAGE == 'search') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['search']).'"><span>'.$lang_common['Search'].'</span></a></li>';

			$links['profile'] = '<li id="navprofile" class="nav'.((substr(FORUM_PAGE, 0, 7) == 'profile') ? ' isactive' : '').'"><a href="'.forum_link($forum_url['user'], $forum_user['id']).'"><span>'.$lang_common['Profile'].'</span></a></li>';

			$links['logout'] = '<li id="navlogout" class="nav"><a href="'.forum_link($forum_url['logout'], array($forum_user['id'], generate_form_token('logout'.$forum_user['id']))).'"><span>'.$lang_common['Logout'].'</span></a></li>';
			$links['admin'] = '<li id="navadmin" class="nav'.((substr(FORUM_PAGE, 0, 5) == 'admin') ? ' isactive' : '').'"><a href="'.forum_link('admin/admin.php').'"><span>'.$lang_common['Admin'].'</span></a></li>';
		}
	}

	// Are there any additional navlinks we should insert into the array before imploding it?
	if ($forum_config['o_additional_navlinks'] != '' && preg_match_all('#([0-9]+)\s*=\s*(.*?)\n#s', $forum_config['o_additional_navlinks']."\n", $extra_links))
	{
		// Insert any additional links into the $links array (at the correct index)
		$num_links = count($extra_links[1]);
		for ($i = 0; $i < $num_links; ++$i)
			array_insert($links, (int)$extra_links[1][$i], '<li id="navextra'.($i + 1).'">'.$extra_links[2][$i].'</li>');
	}


	($hook = get_hook('fn_generate_navlinks_end')) ? eval($hook) : null;

	return implode("\n\t\t", $links);
}


// A wrapper for PHP's number_format function
function forum_number_format($number, $decimals = 0)
{
	global $lang_common;

	$return = ($hook = get_hook('fn_forum_number_format_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return number_format($number, $decimals, $lang_common['lang_decimal_point'], $lang_common['lang_thousands_sep']);
}


// Generate a random key of length $len
function random_key($len, $readable = false, $hash = false)
{
	$key = '';

	$return = ($hook = get_hook('fn_random_key_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($hash)
		$key = substr(sha1(uniqid(rand(), true)), 0, $len);
	else if ($readable)
	{
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

		for ($i = 0; $i < $len; ++$i)
			$key .= substr($chars, (mt_rand() % strlen($chars)), 1);
	}
	else
		for ($i = 0; $i < $len; ++$i)
			$key .= chr(mt_rand(33, 126));

	($hook = get_hook('fn_random_key_end')) ? eval($hook) : null;

	return $key;
}


// Generates a valid CSRF token for use when submitting a form to $target_url
// $target_url should be an absolute URL and it should be exactly the URL that the user is going to
// Alternately, if the form token is going to be used in GET (which would mean the token is going to be
// a part of the URL itself), $target_url may be a plain string containing information related to the URL.
function generate_form_token($target_url)
{
	global $forum_user;

	$return = ($hook = get_hook('fn_generate_form_token_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return sha1(str_replace('&amp;', '&', $target_url).$forum_user['csrf_token']);
}


// Generates a salted, SHA-1 hash of $str
function forum_hash($str, $salt)
{
	$return = ($hook = get_hook('fn_forum_hash_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return sha1($salt.sha1($str));
}


// Try to determine the correct remote IP-address
function get_remote_address()
{
	$return = ($hook = get_hook('fn_get_remote_address_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return $_SERVER['REMOTE_ADDR'];
}


// Имя для input
function input_name($name)
{
	$return = ($hook = get_hook('fn_input_name_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return sha1(get_remote_address().$name.$_SERVER['SERVER_ADDR']);
}


// Try to determine the current URL
function get_current_url($max_length = 0)
{
	global $base_url;

	$return = ($hook = get_hook('fn_get_current_url_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$protocol = (!isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off') ? 'http://' : 'https://';
	$port = (isset($_SERVER['SERVER_PORT']) && (($_SERVER['SERVER_PORT'] != '80' && $protocol == 'http://') || ($_SERVER['SERVER_PORT'] != '443' && $protocol == 'https://')) && strpos($_SERVER['HTTP_HOST'], ':') === false) ? ':'.$_SERVER['SERVER_PORT'] : '';

	$url = urldecode($protocol.$_SERVER['HTTP_HOST'].$port.$_SERVER['REQUEST_URI']);

	if (strlen($url) <= $max_length || $max_length == 0)
		return !defined('NO_PREV_URL') ? $url : null;

	// We can't find a short enough url
	return null;
}


// Check current URL and redirect if required
function confirm_current_url($url)
{
	if (defined('FORUM_DISABLE_URL_CONFIRM'))
		return;

	// Clean up the URL so that it should match the rules we have
	$url = str_replace('&amp;', '&', urldecode($url));
	
	$return = ($hook = get_hook('fn_confirm_current_url_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$hash = strpos($url,'#');
	if ($hash !== false)
		$url = substr($url, 0, $hash);

	$current_url = get_current_url();
	if ($url != $current_url && $url.'?login=1' != $current_url && $url.'&login=1' != $current_url)
	{
		header('HTTP/1.1 301 Moved Permanently'); 
		header('Location: '.$url);
	}
}


// Кодирует содержимое $str, чтобы они были безопасны для вывода на страницу
function forum_htmlencode($str)
{
	$return = ($hook = get_hook('fn_forum_htmlencode_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}


// Convert \r\n and \r to \n
function forum_linebreaks($str)
{
	return str_replace(array("\r\n", "\r"), "\n", $str);
}


// Trim whitespace including non-breaking space
function forum_trim($str, $charlist = " \t\n\r\x0b\xc2\xa0")
{
	return utf8_trim($str, $charlist);
}


// Inserts $element into $input at $offset
// $offset can be either a numerical offset to insert at (eg: 0 inserts at the beginning of the array)
// or a string, which is the key that the new element should be inserted before
// $key is optional: it's used when inserting a new key/value pair into an associative array
function array_insert(&$input, $offset, $element, $key = null)
{
	if ($key == null)
		$key = $offset;

	// Determine the proper offset if we're using a string
	if (!is_int($offset))
		$offset = array_search($offset, array_keys($input), true);

	// Out of bounds checks
	if ($offset > count($input))
		$offset = count($input);
	else if ($offset < 0)
		$offset = 0;

	$input = array_merge(array_slice($input, 0, $offset), array($key => $element), array_slice($input, $offset));
}


// Display $message and redirect user to $destination_url
function redirect($destination_url, $message)
{
	global $forum_db, $forum_config, $lang_common, $forum_user, $base_url;

	define('FORUM_PAGE', 'redirect');

	($hook = get_hook('fn_redirect_start')) ? eval($hook) : null;

	// Prefix with base_url (unless it's there already)
	if (strpos($destination_url, 'http://') !== 0 && strpos($destination_url, 'https://') !== 0 && strpos($destination_url, '/') !== 0)
		$destination_url = $base_url.'/'.$destination_url;

	// Do a little spring cleaning
	$destination_url = preg_replace('/([\r\n])|(%0[ad])|(;[\s]*data[\s]*:)/i', '', $destination_url);

	// If the delay is 0 seconds, we might as well skip the redirect all together
	if (!$forum_config['o_redirect_delay'])
		header('Location: '.str_replace('&amp;', '&', $destination_url));

	// Send no-cache headers
	header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
	header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
	header('Cache-Control: post-check=0, pre-check=0', false);
	header('Content-type: text/html; charset=utf-8');
	header('Pragma: no-cache'); // For HTTP/1.0 compability

	// Load the redirect template
	if (file_exists(FORUM_ROOT.'style/'.$forum_user['style'].'/redirect.tpl'))
		$tpl_path = FORUM_ROOT.'style/'.$forum_user['style'].'/redirect.tpl';
	else
		$tpl_path = FORUM_ROOT.'include/template/redirect.tpl';

	($hook = get_hook('fn_redirect_pre_template_loaded')) ? eval($hook) : null;

	$tpl_redir = forum_trim(file_get_contents($tpl_path));

	($hook = get_hook('fn_redirect_template_loaded')) ? eval($hook) : null;

	// START SUBST - <!-- forum_local -->
	$tpl_redir = str_replace('<!-- forum_local -->', 'xml:lang="'.$lang_common['lang_identifier'].'" lang="'.$lang_common['lang_identifier'].'" dir="'.$lang_common['lang_direction'].'"', $tpl_redir);
	// END SUBST - <!-- forum_local -->


	// START SUBST - <!-- forum_head -->
	$forum_head['refresh'] = '<meta http-equiv="refresh" content="'.$forum_config['o_redirect_delay'].';URL='.str_replace(array('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $destination_url).'" />';
	$forum_head['title'] = '<title>'.$lang_common['Redirecting'].$lang_common['Title separator'].forum_htmlencode($forum_config['o_board_title']).'</title>';
	$forum_head['favicon'] = '<link rel="shortcut icon" type="image/x-icon" href="'.$base_url.'/favicon.ico" />';

	ob_start();

	if(empty($style_url))
		$style_url = $base_url.'/style/gzip.php?style='.$base_url;

	// Include stylesheets
	require FORUM_ROOT.'style/'.$forum_user['style'].'/'.$forum_user['style'].'.php';

	$head_temp = forum_trim(ob_get_contents());
	$num_temp = 0;
	foreach (explode("\n", $head_temp) as $style_temp)
		$forum_head['style'.$num_temp++] = $style_temp;

	ob_end_clean();

	($hook = get_hook('fn_redirect_head')) ? eval($hook) : null;

	$tpl_redir = str_replace('<!-- forum_head -->', implode("\n",$forum_head), $tpl_redir);
	unset($forum_head);
	// END SUBST - <!-- forum_head -->


	// START SUBST - <!-- forum_redir_main -->
	ob_start();

?>
<div id="brd-main" class="main basic">

	<div class="main-head">
		<h1 class="hn"><span><?php echo $message ?></span></h1>
	</div>
	<div class="main-content main-message">
		<p><?php printf($lang_common['Forwarding info'], $forum_config['o_redirect_delay'], intval($forum_config['o_redirect_delay']) == 1 ? $lang_common['second'] : $lang_common['seconds']) ?><span> <a href="<?php echo $destination_url ?>"><?php echo $lang_common['Click redirect'] ?></a></span></p>
	</div>

</div>
<?php

	$tpl_temp = "\t".forum_trim(ob_get_contents());
	$tpl_redir = str_replace('<!-- forum_redir_main -->', $tpl_temp, $tpl_redir);
	ob_end_clean();
	// END SUBST - <!-- forum_redir_main -->


	// START SUBST - <!-- forum_debug -->
	if (defined('FORUM_SHOW_QUERIES'))
	{
		if (!defined('FORUM_FUNCTIONS_GET_SAVED_QUERIES'))
			require FORUM_ROOT.'include/functions/get_saved_queries.php';
		$tpl_redir = str_replace('<!-- forum_debug -->', get_saved_queries(), $tpl_redir);
	}

	// End the transaction
	$forum_db->end_transaction();
	// END SUBST - <!-- forum_debug -->


	// START SUBST - <!-- forum_include "*" -->
	while (preg_match('#<!-- ?forum_include "([^/\\\\]*?)" ?-->#', $tpl_redir, $cur_include))
	{
		if (!file_exists(FORUM_ROOT.'include/user/'.$cur_include[1]))
			error('Unable to process user include &lt;!-- forum_include "'.forum_htmlencode($cur_include[1]).'" --&gt; from template redirect.tpl. There is no such file in folder /include/user/.');

		ob_start();
		include FORUM_ROOT.'include/user/'.$cur_include[1];
		$tpl_temp = ob_get_contents();
		$tpl_redir = str_replace($cur_include[0], $tpl_temp, $tpl_redir);
		ob_end_clean();
	}
	// END SUBST - <!-- forum_include "*" -->


	// Close the db connection (and free up any result data)
	$forum_db->close();

	exit($tpl_redir);
}


// Display a simple error message
function error()
{
	global $forum_config, $base_url, $request_uri;

	if (!headers_sent())
	{
		// if no HTTP responce code is set we send 503
		if (!defined('FORUM_HTTP_RESPONSE_CODE_SET'))
			header('HTTP/1.1 503 Service Temporarily Unavailable');
		header('Content-type: text/html; charset=utf-8');
	}

	/*
		Parse input parameters. Possible function signatures:
		error('Error message.');
		error(__FILE__, __LINE__);
		error('Error message.', __FILE__, __LINE__);
	*/
	$num_args = func_num_args();
	if ($num_args == 3)
	{
		$message = func_get_arg(0);
		$file = func_get_arg(1);
		$line = func_get_arg(2);
	}
	else if ($num_args == 2)
	{
		$file = func_get_arg(0);
		$line = func_get_arg(1);
	}
	else if ($num_args == 1)
		$message = func_get_arg(0);

	// Set a default title and gzip setting if the script failed before $forum_config could be populated
	if (empty($forum_config))
	{
		$forum_config['o_board_title'] = 'Flazy';
		$forum_config['o_gzip'] = '0';
	}

	// Empty all output buffers and stop buffering
	while (@ob_end_clean());

	// "Restart" output buffering if we are using ob_gzhandler (since the gzip header is already sent)
	if (!empty($forum_config['o_gzip']) && extension_loaded('zlib') && !empty($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false))
		ob_start('ob_gzhandler');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru" dir="ltr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Ошибка - <?php echo forum_htmlencode($forum_config['o_board_title']) ?></title>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo $base_url ?>'/favicon.ico" />
<style>
body {
	background:#000;
	color:#fff;
	font: 90%/130% courier, sans-serif;
}
strong {
	color:#82E600;
}
span {
	color:#DD9E09;
}
a {
	color:#FF0A89;
}
</style>
</head>
<body>
<h1>Kernel panic:~$ Произошла ошибка <blink>_</blink></h1>
<?php

	if (isset($message))
		echo '<p>'.$message.'</p>'."\n";

	if ($num_args > 1)
	{
		if (defined('FORUM_DEBUG'))
		{
			if (isset($file) && isset($line))
				echo '<p><strong>flazy:~$</strong><span> &#8594; Panic:</span> Ошибка в строке  '.$line.' в файле <em>'.$file.'</em></p>'."\n";

			$db_error = isset($GLOBALS['forum_db']) ?  $GLOBALS['forum_db']->error() : array(); 
			if (!empty($db_error['error_msg']))
			{
				echo '<p><strong>flazy:~$</strong><span> &#8594; База данных сообщила:</span> '.forum_htmlencode($db_error['error_msg']).(($db_error['error_no']) ? ' (Errno: '.$db_error['error_no'].')' : '').'.</p>'."\n";

				if ($db_error['error_sql'] != '')
					echo '<p><strong>flazy:~$</strong><span> &#8594; Ошибка запроса:</span> '.forum_htmlencode($db_error['error_sql']).'</p>'."\n";
			}
		}
		else
			echo '<p><strong>flazy:~$</strong><span> &#8594; Примечание:</span> Более подробную информацию об ошибке (необходимую для решения проблемы) можно получить включив "DEBUG mode". Что бы его включить откройте файл config.php в текстовом редакторе и раскоментируйте строчку "define(\'FORUM_DEBUG\', 1);" и повторно загрузите файл. После того как Вы решите проблему, рекомендуем, отключить "DEBUG mode".</p>'."\n";
	}

?>
</body>
</html>
<?php

	// If a database connection was established (before this error) we close it
	if (isset($GLOBALS['forum_db']))
		$GLOBALS['forum_db']->close();

	exit;
}


// Fix the REQUEST_URI if we can, since both IIS6 and IIS7 break it
function forum_fix_request_uri()
{
	global $forum_config;
	
	if (!isset($_SERVER['REQUEST_URI']) || (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) && strpos($_SERVER['REQUEST_URI'], '?') === false))
	{
		// Workaround for a bug in IIS7
		if (isset($_SERVER['HTTP_X_ORIGINAL_URL']))
			$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
		
		// IIS6 also doesn't set REQUEST_URI, If we are using the default SEF URL scheme then we can work around it
		else if (!isset($forum_config) || $forum_config['o_sef'] == 'Default')
		{
			$requested_page = str_replace(array('%26', '%3D', '%2F', '%3F'), array('&', '=', '/', '?'), rawurlencode($_SERVER['PHP_SELF']));
			$_SERVER['REQUEST_URI'] = $requested_page.(isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : '');
		}
		
		// Otherwise I am not aware of a work around...
		else
			error('На веб-сервере, который вы используете, не правильно настроина переменная REQUEST_URI. Это обычно означает, что вы используете IIS6, или неисправленный IIS7. Пожалуйста, либо отключить SEF URL, либо обновите IIS7 и установите любые доступные патчи или попробуйте другой веб-сервер.');
	}
}


// Unset any variables instantiated as a result of register_globals being enabled
function forum_unregister_globals()
{
	$register_globals = @ini_get('register_globals');
	if ($register_globals === "" || $register_globals === "0" || strtolower($register_globals) === "off")
		return;

	// Prevent script.php?GLOBALS[foo]=bar
	if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS']))
		exit('Нам кока-колу, два гамбургера и... большу картошку');

	// Variables that shouldn't be unset
	$no_unset = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');

	// Remove elements in $GLOBALS that are present in any of the superglobals
	$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());
	foreach ($input as $k => $v)
		if (!in_array($k, $no_unset) && isset($GLOBALS[$k]))
		{
			unset($GLOBALS[$k]);
			unset($GLOBALS[$k]); // Double unset to circumvent the zend_hash_del_key_or_index hole in PHP <4.4.3 and <5.1.4
		}
}


// Removes any "bad" characters (characters which mess with the display of a page, are invisible, etc) from user input
function forum_remove_bad_characters()
{
	global $bad_utf8_chars;

	$bad_utf8_chars = array("\0", "\xc2\xad", "\xcc\xb7", "\xcc\xb8", "\xe1\x85\x9F", "\xe1\x85\xA0", "\xe2\x80\x80", "\xe2\x80\x81", "\xe2\x80\x82", "\xe2\x80\x83", "\xe2\x80\x84", "\xe2\x80\x85", "\xe2\x80\x86", "\xe2\x80\x87", "\xe2\x80\x88", "\xe2\x80\x89", "\xe2\x80\x8a", "\xe2\x80\x8b", "\xe2\x80\x8e", "\xe2\x80\x8f", "\xe2\x80\xaa", "\xe2\x80\xab", "\xe2\x80\xac", "\xe2\x80\xad", "\xe2\x80\xae", "\xe2\x80\xaf", "\xe2\x81\x9f", "\xe3\x80\x80", "\xe3\x85\xa4", "\xef\xbb\xbf", "\xef\xbe\xa0", "\xef\xbf\xb9", "\xef\xbf\xba", "\xef\xbf\xbb", "\xE2\x80\x8D");

	($hook = get_hook('fn_remove_bad_characters_start')) ? eval($hook) : null;

	function _forum_remove_bad_characters($array)
	{
		global $bad_utf8_chars;
		return is_array($array) ? array_map('_forum_remove_bad_characters', $array) : str_replace($bad_utf8_chars, '', $array);
	}

	$_GET = _forum_remove_bad_characters($_GET);
	$_POST = _forum_remove_bad_characters($_POST);
	$_COOKIE = _forum_remove_bad_characters($_COOKIE);
	$_REQUEST = _forum_remove_bad_characters($_REQUEST);
}


// DEBUG FUNCTIONS BELOW

// Extract part of a template file
function extract_part($whole, $start, $end)
{
   $start_pos = stripos($whole, $start) + strlen($start);

   $end_pos = stripos($whole, $end, $start_pos + 1);

   return substr($whole, $start_pos, $end_pos - $start_pos);
}

// Dump contents of variable(s)
function dump()
{
	echo '<pre>';

	$num_args = func_num_args();

	for ($i = 0; $i < $num_args; ++$i)
	{
		print_r(func_get_arg($i));
		echo "\n\n";
	}

	echo '</pre>';
	exit;
}
