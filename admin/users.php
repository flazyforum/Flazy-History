<?php
/**
 * Страница поиска участников.
 *
 * Allows administrators or moderators to search the existing users based on various criteria.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', '../');
require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'include/functions/admin.php';

($hook = get_hook('aus_start')) ? eval($hook) : null;

if (!$forum_user['is_admmod'])
	message($lang_common['No permission']);

// Load the admin.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_common.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_users.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_bans.php';


// Show IP statistics for a certain user ID
if (isset($_GET['ip_stats']))
{
	$ip_stats = intval($_GET['ip_stats']);
	if ($ip_stats < 1)
		message($lang_common['Bad request']);

	($hook = get_hook('aus_ip_stats_selected')) ? eval($hook) : null;

	$query = array(
		'SELECT'	=> 'p.poster_ip, MAX(p.posted) AS last_used, COUNT(p.id) AS used_times',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.poster_id='.$ip_stats,
		'GROUP BY'	=> 'p.poster_ip',
		'ORDER BY'	=> 'last_used DESC'
	);

	($hook = get_hook('aus_ip_stats_qr_get_user_ips')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$forum_page['num_users'] = $forum_db->num_rows($result);

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php'))
	);

	if ($forum_user['g_id'] == FORUM_ADMIN)
		$forum_page['crumbs'][] = array($lang_admin_common['Users'], forum_link('admin/users.php'));
	$forum_page['crumbs'][] = array($lang_admin_common['Searches'], forum_link('admin/users.php'));
	$forum_page['crumbs'][] = $lang_admin_users['User search results'];

	($hook = get_hook('aus_ip_stats_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-iresults');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	// Set up table headers
	$forum_page['table_header'] = array();
	$forum_page['table_header']['ip'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['IP address'].'</th>';
	$forum_page['table_header']['lastused'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['Last used'].'</th>';
	$forum_page['table_header']['timesfound'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['Times found'].'</th>';
	$forum_page['table_header']['actions'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['Actions'].'</th>';

	($hook = get_hook('aus_ip_stats_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
<?php

	if (!empty($forum_page['main_head_options']))
		echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_head_options']).'</p>';

?>
		<h2 class="hn"><span><?php printf($lang_admin_users['IP addresses found'], $forum_page['num_users']) ?></span></h2>
	</div>
	<div class="main-content main-forum">
		<table cellspacing="0">
			<thead>
				<tr>
					<?php echo implode("\n\t\t\t\t", $forum_page['table_header'])."\n" ?>
				</tr>
			</thead>
			<tbody>
<?php

	if ($forum_page['num_users'])
	{
		$forum_page['item_count'] = 0;

		while ($cur_ip = $forum_db->fetch_assoc($result))
		{
			++$forum_page['item_count'];

			$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even');
			if ($forum_page['item_count'] == 1)
				$forum_page['item_style'] .= ' row1';

			($hook = get_hook('aus_ip_stats_pre_row_generation')) ? eval($hook) : null;

			$forum_page['table_row'] = array();
			$forum_page['table_row']['ip'] = '<td class="tc'.count($forum_page['table_row']).'"><a href="'.forum_link($forum_url['get_host'], forum_htmlencode($cur_ip['poster_ip'])).'">'.forum_htmlencode($cur_ip['poster_ip']).'</a></td>';
			$forum_page['table_row']['lastused'] = '<td class="tc'.count($forum_page['table_row']).'">'.format_time($cur_ip['last_used']).'</td>';
			$forum_page['table_row']['timesfound'] = '<td class="tc'.count($forum_page['table_row']).'">'.$cur_ip['used_times'].'</td>';
			$forum_page['table_row']['actions'] = '<td class="tc'.count($forum_page['table_row']).'"><a href="'.forum_link('admin/users.php').'?show_users='.forum_htmlencode($cur_ip['poster_ip']).'">'.$lang_admin_users['Find more users'].'</a></td>';

			($hook = get_hook('aus_ip_stats_pre_row_output')) ? eval($hook) : null;

?>
				<tr class="<?php echo $forum_page['item_style'] ?>">
					<?php echo implode("\n\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php

		}
	}
	else
	{
		($hook = get_hook('aus_ip_stats_pre_no_results_row_generation')) ? eval($hook) : null;

		$forum_page['table_row'] = array();
		$forum_page['table_row']['ip'] = '<td class="tc'.count($forum_page['table_row']).'">'.$lang_admin_users['No posts by user'].'</td>';
		$forum_page['table_row']['lastused'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
		$forum_page['table_row']['timesfound'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
		$forum_page['table_row']['actions'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';

		($hook = get_hook('aus_ip_stats_pre_no_results_row_output')) ? eval($hook) : null;

?>
				<tr class="odd row1">
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php

	}

?>
			</tbody>
		</table>
	</div>
	<div class="main-subhead">
<?php

	if (!empty($forum_page['main_foot_options']))
		echo "\n\t\t".'<h2 class="hn">'.implode(' ', $forum_page['main_foot_options']).'</h2>';
	else
		echo "\n\t\t".'<h2 class="hn">&nbsp;</h2>';

?>
		<p class="ct-options options"><span><?php printf($lang_admin_users['IP addresses found'], $forum_page['num_users']) ?></span></p>
	</div>
<?php

	($hook = get_hook('aus_ip_stats_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


// Show users that have at one time posted with the specified IP address
else if (isset($_GET['show_users']))
{
	$ip = $_GET['show_users'];

	if (empty($ip) || (!preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/', $ip) && !preg_match('/^((([0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}:[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){5}:([0-9A-Fa-f]{1,4}:)?[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){4}:([0-9A-Fa-f]{1,4}:){0,2}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){3}:([0-9A-Fa-f]{1,4}:){0,3}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){2}:([0-9A-Fa-f]{1,4}:){0,4}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){6}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(([0-9A-Fa-f]{1,4}:){0,5}:((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|(::([0-9A-Fa-f]{1,4}:){0,5}((\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b)\.){3}(\b((25[0-5])|(1\d{2})|(2[0-4]\d)|(\d{1,2}))\b))|([0-9A-Fa-f]{1,4}::([0-9A-Fa-f]{1,4}:){0,5}[0-9A-Fa-f]{1,4})|(::([0-9A-Fa-f]{1,4}:){0,6}[0-9A-Fa-f]{1,4})|(([0-9A-Fa-f]{1,4}:){1,7}:))$/', $ip)))
		message($lang_admin_users['Invalid IP address']);

	($hook = get_hook('aus_show_users_selected')) ? eval($hook) : null;

	// Load the misc.php language file
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/misc.php';

	$query = array(
		'SELECT'	=> 'DISTINCT p.poster_id, p.poster',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.poster_ip=\''.$forum_db->escape($ip).'\'',
		'ORDER BY'	=> 'p.poster DESC'
	);

	($hook = get_hook('aus_show_users_qr_get_users_matching_ip')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$forum_page['num_users'] = $forum_db->num_rows($result);

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php'))
	);

	if ($forum_user['g_id'] == FORUM_ADMIN)
		$forum_page['crumbs'][] = array($lang_admin_common['Users'], forum_link('admin/users.php'));
	$forum_page['crumbs'][] = array($lang_admin_common['Searches'], forum_link('admin/users.php'));
	$forum_page['crumbs'][] = $lang_admin_users['User search results'];

	($hook = get_hook('aus_show_users_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-uresults');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	// Set up table headers
	$forum_page['table_header'] = array();
	$forum_page['table_header']['username'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['User information'].'</th>';
	$forum_page['table_header']['title'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['Title column'].'</th>';
	$forum_page['table_header']['posts'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['Posts'].'</th>';
	$forum_page['table_header']['actions'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['Actions'].'</th>';
	$forum_page['table_header']['select'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_misc['Select'] .'</th>';

	if ($forum_page['num_users'] > 0)
		$forum_page['main_head_options']['select'] = $forum_page['main_foot_options']['select'] = '<a href="#" onclick="return Forum.toggleCheckboxes(document.getElementById(\'aus-show-users-results-form\'))">'.$lang_admin_common['Select all'].'</a>';

	($hook = get_hook('aus_show_users_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf($lang_admin_users['Users found'], $forum_page['num_users']) ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php

	if (!empty($forum_page['main_head_options']))
		echo "\n\t\t".'<p class="ct-options options">'.implode(' ', $forum_page['main_head_options']).'</p>';

?>
	</div>
	<form id="aus-show-users-results-form" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/users.php') ?>?action=modify_users">
	<div class="main-content main-frm">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/users.php').'?action=modify_users') ?>" />
		</div>
		<table cellspacing="0">
			<thead>
				<tr>
					<?php echo implode("\n\t\t\t\t", $forum_page['table_header'])."\n" ?>
				</tr>
			</thead>
			<tbody>
<?php

	if ($forum_page['num_users'] > 0)
	{
		$forum_page['item_count'] = 0;

		// Loop through users and print out some info
		for ($i = 0; $i < $forum_page['num_users']; ++$i)
		{
			list($poster_id, $poster) = $forum_db->fetch_row($result);

			$query = array(
				'SELECT'	=> 'u.id, u.username, u.email, u.title, u.num_posts, u.admin_note, g.g_id, g.g_user_title',
				'FROM'		=> 'users AS u',
				'JOINS'		=> array(
					array(
						'INNER JOIN'	=> 'groups AS g',
						'ON'		=> 'g.g_id=u.group_id'
					)
				),
				'WHERE'		=> 'u.id>1 AND u.id='.$poster_id
			);

			($hook = get_hook('aus_show_users_qr_get_user_details')) ? eval($hook) : null;
			$result2 = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			++$forum_page['item_count'];

			$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even');
			if ($forum_page['item_count'] == 1)
				$forum_page['item_style'] .= ' row1';

			($hook = get_hook('aus_show_users_pre_row_generation')) ? eval($hook) : null;

			if ($user_data = $forum_db->fetch_assoc($result2))
			{
				$forum_page['table_row'] = array();
				$forum_page['table_row']['username'] = '<td class="tc'.count($forum_page['table_row']).'"><span>'.$lang_admin_users['Username'].' <a href="'.forum_link($forum_url['user'], $user_data['id']).'">'.forum_htmlencode($user_data['username']).'</a></span> <span class="usermail">'.$lang_admin_users['E-mail'].' <a href="mailto:'.forum_htmlencode($user_data['email']).'">'.forum_htmlencode($user_data['email']).'</a></span>'.(($user_data['admin_note'] != '') ? '<span class="usernote">'.$lang_admin_users['Admin note'].' '.forum_htmlencode($user_data['admin_note']).'</span>' : '').'</td>';
				$forum_page['table_row']['title'] = '<td class="tc'.count($forum_page['table_row']).'">'.get_title($user_data).'</td>';
				$forum_page['table_row']['posts'] = '<td class="tc'.count($forum_page['table_row']).'">'.forum_number_format($user_data['num_posts']).'</td>';
				$forum_page['table_row']['actions'] = '<td class="tc'.count($forum_page['table_row']).'"><span><a href="'.forum_link('admin/users.php').'?ip_stats='.$user_data['id'].'">'.$lang_admin_users['View IP stats'].'</a></span> <span><a href="'.forum_link($forum_url['search_user_posts'], $user_data['id']).'">'.$lang_admin_users['Show posts'].'</a></span></td>';
				$forum_page['table_row']['select'] = '<td class="tc'.count($forum_page['table_row']).'"><input type="checkbox" name="users['.$user_data['id'].']" value="1" /></td>';
			}
			else
			{
				$forum_page['table_row'] = array();
				$forum_page['table_row']['username'] = '<td class="tc'.count($forum_page['table_row']).'">'.forum_htmlencode($poster).'</td>';
				$forum_page['table_row']['title'] = '<td class="tc'.count($forum_page['table_row']).'">'.$lang_admin_users['Guest'].'</td>';
				$forum_page['table_row']['posts'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
				$forum_page['table_row']['actions'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
				$forum_page['table_row']['select'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
			}

			($hook = get_hook('aus_show_users_pre_row_output')) ? eval($hook) : null;

?>
				<tr class="<?php echo $forum_page['item_style'] ?>">
					<?php echo implode("\n\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php

		}
	}
	else
	{
		($hook = get_hook('aus_show_users_pre_no_results_row_generation')) ? eval($hook) : null;

		$forum_page['table_row'] = array();
		$forum_page['table_row']['username'] = '<td class="tc'.count($forum_page['table_row']).'">'.$lang_admin_users['Cannot find IP'].'</td>';
		$forum_page['table_row']['title'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
		$forum_page['table_row']['posts'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
		$forum_page['table_row']['actions'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
		$forum_page['table_row']['select'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';

		($hook = get_hook('aus_show_users_pre_no_results_row_output')) ? eval($hook) : null;

?>
				<tr class="odd row1">
					<?php echo implode("\n\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php

	}

?>
			</tbody>
		</table>
	</div>
<?php

	// Setup control buttons
	$forum_page['mod_options'] = array();

	if ($forum_page['num_users'] > 0)
	{
		if ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] && $forum_user['g_mod_ban_users']))
			$forum_page['mod_options']['ban'] = '<span class="submit'.((empty($forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="ban_users" value="'.$lang_admin_users['Ban'].'" /></span>';

		if ($forum_user['g_id'] == FORUM_ADMIN)
		{
			$forum_page['mod_options']['delete'] = '<span class="submit'.((empty($forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="delete_users" value="'.$lang_admin_common['Delete'].'" /></span>';
			$forum_page['mod_options']['change_group'] = '<span class="submit'.((empty($forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="change_group" value="'.$lang_admin_users['Change group'].'" /></span>';
		}
	}

	($hook = get_hook('aus_show_users_pre_moderation_buttons')) ? eval($hook) : null;

	if (!empty($forum_page['mod_options']))
	{
?>
	<div class="main-options gen-content">
		<p class="options"><?php echo implode(' ', $forum_page['mod_options']) ?></p>
	</div>
<?php

	}

?>
	</form>
	<div class="main-subhead">
		<h2 class="hn"><span><?php printf($lang_admin_users['Users found'], $forum_page['num_users']) ?></span></h2>
	</div>
	<div class="main-content main-frm">
<?php

	if (!empty($forum_page['main_head_options']))
		echo "\n\t\t".'<p class="ct-options options">'.implode(' ', $forum_page['main_head_options']).'</p>';

?>
	</div>
<?php

	($hook = get_hook('aus_show_users_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


else if (isset($_POST['delete_users']) || isset($_POST['delete_users_comply']) || isset($_POST['delete_users_cancel']))
{
	// User pressed the cancel button
	if (isset($_POST['delete_users_cancel']))
		redirect(forum_link('admin/users.php'), $lang_admin_common['Cancel redirect']);

	if ($forum_user['g_id'] != FORUM_ADMIN)
		message($lang_common['No permission']);

	if (empty($_POST['users']))
		message($lang_admin_users['No users selected']);

	($hook = get_hook('aus_delete_users_selected')) ? eval($hook) : null;

	if (!is_array($_POST['users']))
		$users = explode(',', $_POST['users']);
	else
		$users = array_keys($_POST['users']);

	$users = array_map('intval', $users);

	// We check to make sure there are no administrators in this list
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.id IN ('.implode(',', $users).') AND u.group_id='.FORUM_ADMIN
	);

	($hook = get_hook('aus_delete_users_qr_check_for_admins')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if ($forum_db->num_rows($result) > 0)
		message($lang_admin_users['Delete admin message']);

	if (isset($_POST['delete_users_comply']))
	{
		($hook = get_hook('aus_delete_users_form_submitted')) ? eval($hook) : null;

		foreach ($users as $id)
		{
			// We don't want to delete the Guest user
			if ($id > 1)
			{
				if (!defined('FORUM_FUNCTIONS_DELETE_USER'))
					require FORUM_ROOT.'include/functions/delete_user.php';

				delete_user($id, isset($_POST['delete_posts']));
			}
		}

		($hook = get_hook('aus_delete_users_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link('admin/users.php'), $lang_admin_users['Users deleted'].' '.$lang_admin_common['Redirect']);
	}

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php'))
	);

	if ($forum_user['g_id'] == FORUM_ADMIN)
		$forum_page['crumbs'][] = array($lang_admin_common['Users'], forum_link('admin/users.php'));
	$forum_page['crumbs'][] = array($lang_admin_common['Searches'], forum_link('admin/users.php'));
	$forum_page['crumbs'][] = $lang_admin_users['Ban users'];

	($hook = get_hook('aus_delete_users_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-users');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	($hook = get_hook('aus_delete_users_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_users['Confirm delete'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box warn-box">
			<p class="warn"><?php echo $lang_admin_users['Delete warning'] ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/users.php') ?>?action=modify_users">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/users.php').'?action=modify_users') ?>" />
				<input type="hidden" name="users" value="<?php echo implode(',', $users) ?>" />
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_users['Delete posts legend'] ?></span></legend>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="fld<?php echo ++$forum_page['fld_count'] ?>" name="delete_posts" value="1" checked="checked" /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Delete posts'] ?></span> <?php echo $lang_admin_users['Delete posts label'] ?></label>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="delete_users_comply" value="<?php echo $lang_admin_users['Delete users'] ?>" /></span>
				<span class="cancel"><input type="submit" name="delete_users_cancel" value="<?php echo $lang_admin_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('aus_delete_users_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


else if (isset($_POST['ban_users']) || isset($_POST['ban_users_comply']))
{
	if ($forum_user['g_id'] != FORUM_ADMIN && ($forum_user['g_moderator'] != '1' || $forum_user['g_mod_ban_users'] == '0'))
		message($lang_common['No permission']);

	if (empty($_POST['users']))
		message($lang_admin_users['No users selected']);

	($hook = get_hook('aus_ban_users_selected')) ? eval($hook) : null;

	if (!is_array($_POST['users']))
		$users = explode(',', $_POST['users']);
	else
		$users = array_keys($_POST['users']);

	$users = array_map('intval', $users);

	// We check to make sure there are no administrators in this list
	$query = array(
		'SELECT'	=> '1',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.id IN ('.implode(',', $users).') AND u.group_id='.FORUM_ADMIN
	);

	($hook = get_hook('aus_ban_users_qr_check_for_admins')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if ($forum_db->num_rows($result) > 0)
		message($lang_admin_users['Ban admin message']);

	if (isset($_POST['ban_users_comply']))
	{
		$ban_message = forum_trim($_POST['ban_message']);
		$ban_expire = forum_trim($_POST['ban_expire']);

		($hook = get_hook('aus_ban_users_form_submitted')) ? eval($hook) : null;

		if ($ban_expire != '' && $ban_expire != 'Never')
		{
			$ban_expire = strtotime($ban_expire);

			if ($ban_expire == -1 || $ban_expire <= time())
				message($lang_admin_bans['Invalid expire message']);
		}
		else
			$ban_expire = 'NULL';

		$ban_message = ($ban_message != '') ? '\''.$forum_db->escape($ban_message).'\'' : 'NULL';  'NULL';

		// Get the latest IPs for the posters and store them for a little later
		$query = array(
			'SELECT'	=> 'p.poster_id, p.poster_ip',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.poster_id IN ('.implode(',', $users).') AND p.poster_id>1',
			'ORDER BY'	=> 'p.posted ASC'
		);

		($hook = get_hook('aus_ban_users_qr_get_latest_user_ips')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$ips = array();
		while ($cur_post = $forum_db->fetch_assoc($result))
			$ips[$cur_post['poster_id']] = $cur_post['poster_ip'];

		// Get the rest of the data for the posters, merge in the IP information, create a ban
		$query = array(
			'SELECT'	=> 'u.id, u.username, u.email, u.registration_ip',
			'FROM'		=> 'users AS u',
			'WHERE'		=> 'id IN ('.implode(',', $users).') AND id>1'
		);

		($hook = get_hook('aus_ban_users_qr_get_users')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		while ($cur_user = $forum_db->fetch_assoc($result))
		{
			$ban_ip = isset($ips[$cur_user['id']]) ? $ips[$cur_user['id']] : $cur_user['registration_ip'];

			$query = array(
				'INSERT'	=> 'username, ip, email, message, expire, ban_creator',
				'INTO'		=> 'bans',
				'VALUES'	=> '\''.$forum_db->escape($cur_user['username']).'\', \''.$ban_ip.'\', \''.$forum_db->escape($cur_user['email']).'\', '.$ban_message.', '.$ban_expire.', '.$forum_user['id']
			);

			($hook = get_hook('aus_ban_users_qr_add_ban')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);
		}

		// Regenerate the bans cache
		if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/cache.php';

		generate_bans_cache();

		($hook = get_hook('aus_ban_users_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link('admin/users.php'), $lang_admin_users['Users banned'].' '.$lang_admin_common['Redirect']);
	}

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php')),
		array($lang_admin_common['Users'], forum_link('admin/users.php')),
		array($lang_admin_common['Searches'], forum_link('admin/users.php')),
		$lang_admin_users['Ban users']
	);

	($hook = get_hook('aus_ban_users_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-users');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	($hook = get_hook('aus_ban_users_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_users['Ban users'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<div class="ct-box">
			<p><?php echo $lang_admin_users['Mass ban info'] ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/users.php') ?>?action=modify_users">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/users.php').'?action=modify_users') ?>" />
				<input type="hidden" name="users" value="<?php echo implode(',', $users) ?>" />
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_users['Ban settings legend'] ?></span></legend>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_bans['Ban message label'] ?></span> <small><?php echo $lang_admin_bans['Ban message help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="ban_message" size="50" maxlength="255" class="inputbox" /></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Expire date'] ?></span> <small><?php echo $lang_admin_users['Expire date info'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="ban_expire" size="17" maxlength="10" class="inputbox" /></span>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="ban_users_comply" value="<?php echo $lang_admin_users['Ban'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('aus_ban_users_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


else if (isset($_POST['change_group']) || isset($_POST['change_group_comply']) || isset($_POST['change_group_cancel']))
{
	if ($forum_user['g_id'] != FORUM_ADMIN)
		message($lang_common['No permission']);

	// User pressed the cancel button
	if (isset($_POST['change_group_cancel']))
		redirect(forum_link('admin/users.php'), $lang_admin_common['Cancel redirect']);

	if (empty($_POST['users']))
		message($lang_admin_users['No users selected']);

	($hook = get_hook('aus_change_group_selected')) ? eval($hook) : null;

	if (!is_array($_POST['users']))
		$users = explode(',', $_POST['users']);
	else
		$users = array_keys($_POST['users']);

	$users = array_map('intval', $users);

	if (isset($_POST['change_group_comply']))
	{
		$move_to_group = intval($_POST['move_to_group']);

		($hook = get_hook('aus_change_group_form_submitted')) ? eval($hook) : null;

		// We need some information on the group
		$query = array(
			'SELECT'	=> 'g.g_moderator',
			'FROM'		=> 'groups AS g',
			'WHERE'		=> 'g.g_id='.$move_to_group
		);

		($hook = get_hook('aus_change_group_qr_get_group_moderator_status')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		if ($move_to_group == FORUM_GUEST || !$forum_db->num_rows($result))
			message($lang_common['Bad request']);

		$group_is_mod = $forum_db->result($result);

		// Move users
		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'group_id='.$move_to_group,
			'WHERE'		=> 'id IN ('.implode(',', $users).') AND id>1'
		);

		($hook = get_hook('aus_change_group_qr_change_user_group')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($move_to_group != FORUM_ADMIN && $group_is_mod == '0')
			clean_forum_moderators();

		($hook = get_hook('aus_change_group_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link('admin/users.php'), $lang_admin_users['User groups updated'].' '.$lang_admin_common['Redirect']);
	}

	// Setup form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php')),
		array($lang_admin_common['Users'], forum_link('admin/users.php')),
		array($lang_admin_common['Searches'], forum_link('admin/users.php')),
		$lang_admin_users['Change group']
	);

	($hook = get_hook('aus_change_group_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-users');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	($hook = get_hook('aus_change_group_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_users['Change group head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/users.php') ?>?action=modify_users">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/users.php').'?action=modify_users') ?>" />
				<input type="hidden" name="users" value="<?php echo implode(',', $users) ?>" />
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><span><?php echo $lang_admin_users['Move users legend'] ?></span></legend>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Move users to label'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="move_to_group">
<?php

	$query = array(
		'SELECT'	=> 'g.g_id, g.g_title',
		'FROM'		=> 'groups AS g',
		'WHERE'		=> 'g.g_id!='.FORUM_GUEST,
		'ORDER BY'	=> 'g.g_title'
	);

	($hook = get_hook('aus_change_group_qr_get_groups')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	while ($cur_group = $forum_db->fetch_assoc($result))
	{
		if ($cur_group['g_id'] == $forum_config['o_default_user_group']) // Pre-select the default Members group
			echo "\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'" selected="selected">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
		else
			echo "\t\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";
	}

?>
						</select></span>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="change_group_comply" value="<?php echo $lang_admin_users['Change group'] ?>" /></span>
				<span class="cancel"><input type="submit" name="change_group_cancel" value="<?php echo $lang_admin_common['Cancel'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('aus_change_group_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


else if (isset($_POST['find_user']))
{
	$form = $_POST['form'];
	$form['username'] = $_POST['username'];

	($hook = get_hook('aus_find_user_selected')) ? eval($hook) : null;

	// forum_trim() all elements in $form
	$form = array_map('trim', $form);
	$conditions = array();

	$posts_greater = forum_trim($_POST['posts_greater']);
	$posts_less = forum_trim($_POST['posts_less']);
	$last_post_after = forum_trim($_POST['last_post_after']);
	$last_post_before = forum_trim($_POST['last_post_before']);
	$registered_after = forum_trim($_POST['registered_after']);
	$registered_before = forum_trim($_POST['registered_before']);
	$order_by = isset($_POST['order_by']) ? forum_trim($_POST['order_by']) : null;
	$direction = isset($_POST['direction']) ? forum_trim($_POST['direction']) : null;
	if ($order_by == null || $direction == null)
		message($lang_common['Bad request']);
	if (!in_array($order_by, array('username', 'email', 'num_posts', 'num_posts', 'registered')) || !in_array($direction, array('ASC', 'DESC')))
		message($lang_common['Bad request']);

	$user_group = $_POST['user_group'];

	if ((!empty($posts_greater) || !empty($posts_less)) && !ctype_digit($posts_greater.$posts_less))
		message($lang_admin_users['Non numeric value message']);

	// Try to convert date/time to timestamps
	if ($last_post_after != '')
		$last_post_after = strtotime($last_post_after);
	if ($last_post_before != '')
		$last_post_before = strtotime($last_post_before);
	if ($registered_after != '')
		$registered_after = strtotime($registered_after);
	if ($registered_before != '')
		$registered_before = strtotime($registered_before);

	if ($last_post_after == -1 || $last_post_before == -1 || $registered_after == -1 || $registered_before == -1)
		message($lang_admin_users['Invalid date/time message']);

	if ($last_post_after != '')
		$conditions[] = 'u.last_post>'.$last_post_after;
	if ($last_post_before != '')
		$conditions[] = 'u.last_post<'.$last_post_before;
	if ($registered_after != '')
		$conditions[] = 'u.registered>'.$registered_after;
	if ($registered_before != '')
		$conditions[] = 'u.registered<'.$registered_before;

	$like_command = ($db_type == 'pgsql') ? 'ILIKE' : 'LIKE';
	foreach ($form as $key => $input)
	{
		if ($input != '' && in_array($key, array('username', 'email', 'title', 'realname', 'sex', 'url', 'jabber', 'icq', 'msn', 'aim', 'yahoo', 'skype', 'magent', 'location', 'signature', 'admin_note', 'user_agent')))
			$conditions[] = 'u.'.$forum_db->escape($key).' '.$like_command.' \''.$forum_db->escape(str_replace('*', '%', $input)).'\'';
	}

	if ($posts_greater != '')
		$conditions[] = 'u.num_posts>'.$posts_greater;
	if ($posts_less != '')
		$conditions[] = 'u.num_posts<'.$posts_less;

	if ($user_group != 'all')
		$conditions[] = 'u.group_id='.intval($user_group);

	if (empty($conditions))
		message($lang_admin_users['No search terms message']);


	// Load the misc.php language file
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/misc.php';

	// Find any users matching the conditions
	$query = array(
		'SELECT'	=> 'u.id, u.username, u.email, u.title, u.num_posts, u.admin_note, g.g_id, g.g_user_title',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'		=> 'groups AS g',
				'ON'			=> 'g.g_id=u.group_id'
			)
		),
		'WHERE'		=> 'u.id>1 AND '.implode(' AND ', $conditions),
		'ORDER BY'	=> $order_by.' '.$direction
	);

	($hook = get_hook('aus_find_user_qr_find_users')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$forum_page['num_users'] = $forum_db->num_rows($result);


	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_admin_common['Forum administration'], forum_link('admin/admin.php'))
	);

	if ($forum_user['g_id'] == FORUM_ADMIN)
		$forum_page['crumbs'][] = array($lang_admin_common['Users'], forum_link('admin/users.php'));
	$forum_page['crumbs'][] = array($lang_admin_common['Searches'], forum_link('admin/users.php'));
	$forum_page['crumbs'][] = $lang_admin_users['User search results'];

	($hook = get_hook('aus_find_user_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE_SECTION', 'users');
	define('FORUM_PAGE', 'admin-uresults');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

	// Set up table headers
	$forum_page['table_header'] = array();
	$forum_page['table_header']['username'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['User information'].'</th>';
	$forum_page['table_header']['title'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['Title column'].'</th>';
	$forum_page['table_header']['posts'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['Posts'].'</th>';
	$forum_page['table_header']['actions'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_admin_users['Actions'].'</th>';
	$forum_page['table_header']['select'] = '<th class="tc'.count($forum_page['table_header']).'" scope="col">'.$lang_misc['Select'] .'</th>';

	if ($forum_page['num_users'] > 0)
		$forum_page['main_head_options']['select'] = $forum_page['main_foot_options']['select'] = '<a href="#" onclick="return Forum.toggleCheckboxes(document.getElementById(\'aus-find-user-results-form\'))">'.$lang_admin_common['Select all'].'</a>';

	($hook = get_hook('aus_find_user_output_start')) ? eval($hook) : null;

?>
	<div class="main-head">
<?php

	if (!empty($forum_page['main_head_options']))
		echo "\n\t\t".'<p class="options">'.implode(' ', $forum_page['main_head_options']).'</p>';

?>
		<h2 class="hn"><span><?php printf($lang_admin_users['Users found'], $forum_page['num_users']) ?></span></h2>
	</div>
	<form id="aus-find-user-results-form" class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/users.php') ?>?action=modify_users">
	<div class="main-content main-forum">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/users.php').'?action=modify_users') ?>" />
		</div>
		<table cellspacing="0">
			<thead>
				<tr>
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_header'])."\n" ?>
				</tr>
			</thead>
			<tbody>
<?php

	if ($forum_page['num_users'] > 0)
	{
		$forum_page['item_count'] = 0;

		while ($user_data = $forum_db->fetch_assoc($result))
		{
			++$forum_page['item_count'];

			// This script is a special case in that we want to display "Not verified" for non-verified users
			if (($user_data['g_id'] == '' || $user_data['g_id'] == FORUM_UNVERIFIED) && $user_data['title'] != $lang_common['Banned'])
				$user_title = '<strong>'.$lang_admin_users['Not verified'].'</strong>';
			else
				$user_title = get_title($user_data);

			$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? 'odd' : 'even');
			if ($forum_page['item_count'] == 1)
				$forum_page['item_style'] .= ' row1';

			($hook = get_hook('aus_find_user_pre_row_generation')) ? eval($hook) : null;

			$forum_page['table_row'] = array();
			$forum_page['table_row']['username'] = '<td class="tc'.count($forum_page['table_row']).'"><span>'.$lang_admin_users['Username'].' <a href="'.forum_link($forum_url['user'], $user_data['id']).'">'.forum_htmlencode($user_data['username']).'</a></span> <span class="usermail">'.$lang_admin_users['E-mail'].' <a href="mailto:'.forum_htmlencode($user_data['email']).'">'.forum_htmlencode($user_data['email']).'</a></span>'.(($user_data['admin_note'] != '') ? '<span class="usernote">'.$lang_admin_users['Admin note'].' '.forum_htmlencode($user_data['admin_note']).'</span>' : '').'</td>';
			$forum_page['table_row']['title'] = '<td class="tc'.count($forum_page['table_row']).'">'.$user_title.'</td>';
			$forum_page['table_row']['posts'] = '<td class="tc'.count($forum_page['table_row']).'">'.forum_number_format($user_data['num_posts']).'</td>';
			$forum_page['table_row']['actions'] = '<td class="tc'.count($forum_page['table_row']).'"><span><a href="'.forum_link('admin/users.php').'?ip_stats='.$user_data['id'].'">'.$lang_admin_users['View IP stats'].'</a></span> <span><a href="'.forum_link($forum_url['search_user_posts'], $user_data['id']).'">'.$lang_admin_users['Show posts'].'</a></span></td>';
			$forum_page['table_row']['select'] = '<td class="tc'.count($forum_page['table_row']).'"><input type="checkbox" name="users['.$user_data['id'].']" value="1" /></td>';

			($hook = get_hook('aus_find_user_pre_row_output')) ? eval($hook) : null;

?>
				<tr class="<?php echo $forum_page['item_style'] ?>">
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php

		}
	}
	else
	{
			($hook = get_hook('aus_find_user_pre_no_results_row_generation')) ? eval($hook) : null;

			$forum_page['table_row'] = array();
			$forum_page['table_row']['username'] = '<td class="tc'.count($forum_page['table_row']).'">'.$lang_admin_users['No match'].'</td>';
			$forum_page['table_row']['title'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
			$forum_page['table_row']['posts'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
			$forum_page['table_row']['actions'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';
			$forum_page['table_row']['select'] = '<td class="tc'.count($forum_page['table_row']).'"> - </td>';

			($hook = get_hook('aus_find_user_pre_no_results_row_output')) ? eval($hook) : null;

?>
				<tr class="odd row1">
					<?php echo implode("\n\t\t\t\t\t", $forum_page['table_row'])."\n" ?>
				</tr>
<?php

	}

?>
			</tbody>
		</table>
	</div>
<?php

	// Setup control buttons
	$forum_page['mod_options'] = array();

	if ($forum_page['num_users'] > 0)
	{
		if ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] && $forum_user['g_mod_ban_users']))
			$forum_page['mod_options']['ban'] = '<span class="submit'.((empty($forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="ban_users" value="'.$lang_admin_users['Ban'].'" /></span>';

		if ($forum_user['g_id'] == FORUM_ADMIN)
		{
			$forum_page['mod_options']['delete'] = '<span class="submit'.((empty($forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="delete_users" value="'.$lang_admin_common['Delete'].'" /></span>';
			$forum_page['mod_options']['change_group'] = '<span class="submit'.((empty($forum_page['mod_options'])) ? ' first-item' : '').'"><input type="submit" name="change_group" value="'.$lang_admin_users['Change group'].'" /></span>';
		}
	}

	($hook = get_hook('aus_find_user_pre_moderation_buttons')) ? eval($hook) : null;

	if (!empty($forum_page['mod_options']))
	{
?>
	<div class="main-options gen-content">
		<p class="options"><?php echo implode(' ', $forum_page['mod_options']) ?></p>
	</div>
<?php

	}

?>
	</form>
	<div class="main-subhead">
<?php

	if (!empty($forum_page['main_foot_options']))
		echo "\n\t\t".'<h2 class="hn">'.implode(' ', $forum_page['main_foot_options']).'</h2>';
	else
		echo "\n\t\t".'<h2 class="hn">&nbsp;</h2>';
?>
		<p class="ct-options options"><span><?php printf($lang_admin_users['Users found'], $forum_page['num_users']) ?></span></p>
	</div>
<?php

	($hook = get_hook('aus_find_user_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}


($hook = get_hook('aus_new_action')) ? eval($hook) : null;


// Setup form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link('admin/admin.php'))
);

if ($forum_user['g_id'] == FORUM_ADMIN)
	$forum_page['crumbs'][] = array($lang_admin_common['Users'], forum_link('admin/users.php'));
$forum_page['crumbs'][] = array($lang_admin_common['Searches'], forum_link('admin/users.php'));

($hook = get_hook('aus_search_form_pre_header_load')) ? eval($hook) : null;

define('FORUM_PAGE_SECTION', 'users');
define('FORUM_PAGE', 'admin-users');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

($hook = get_hook('aus_search_form_output_start')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_users['Search head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('admin/users.php') ?>?action=find_user">
			<div class="hidden">
				<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('admin/users.php').'?action=find_user') ?>" />
			</div>
			<div class="content-head">
				<h3 class="hn"><span><?php echo $lang_admin_users['User search head'] ?></span></h3>
			</div>
<?php ($hook = get_hook('aus_search_form_pre_user_details_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_users['Searches personal legend'] ?></strong></legend>
<?php ($hook = get_hook('aus_search_form_pre_username')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Username label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="username" size="25" maxlength="25" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_user_title')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Title label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[title]" size="30" maxlength="50" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_realname')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Real name label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[realname]" size="30" maxlength="40" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_sex')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">

					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span>Пол</span></label><br />
						<span class="fld-input"><select id="fld<?php echo ++$forum_page['fld_count'] ?>" name="form[sex]">
							<option value="1"><?php echo $lang_admin_users['Male'] ?></option>
							<option value="2"><?php echo $lang_admin_users['Female'] ?></option>
							<option value="" selected="selected"><?php echo $lang_admin_users['Do not show'] ?></option>
						</select></span>

					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_location')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Location label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[location]" size="30" maxlength="30" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_signature')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Signature label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[signature]" size="35" maxlength="512" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_admin_note')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Admin note label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[admin_note]" size="30" maxlength="30" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_user_agent')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['User agent label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[user_agent]" size="40" maxlength="100" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_user_details_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aus_search_form_user_details_fieldset_end')) ? eval($hook) : null; ?>
<?php $forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('aus_search_form_pre_user_contacts_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_users['Searches contact legend'] ?></strong></legend>
<?php ($hook = get_hook('aus_search_form_pre_email')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['E-mail address label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[email]" size="30" maxlength="80" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_website')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Website label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[url]" size="35" maxlength="100" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_jabber')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Jabber label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[jabber]" size="30" maxlength="80" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_icq')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['ICQ label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[icq]" size="12" maxlength="12" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_msn')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['MSN Messenger label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[msn]" size="30" maxlength="80" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_aim')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['AOL IM label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[aim]" size="20" maxlength="20" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_yahoo')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Yahoo Messenger label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[yahoo]" size="20" maxlength="20" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_skype')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Skype label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[skype]" size="20" maxlength="20" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_magent')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Magent label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="form[magent]" size="30" maxlength="80" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_user_contacts_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aus_search_form_user_contacts_fieldset_end')) ? eval($hook) : null; ?>
<?php $forum_page['item_count'] = 0; ?>
<?php ($hook = get_hook('aus_search_form_pre_user_activity_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_users['Searches activity legend'] ?></strong></legend>
<?php ($hook = get_hook('aus_search_form_pre_min_posts')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box frm-short text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['More posts label'] ?></span> <small><?php echo $lang_admin_users['Number of posts help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="posts_greater" size="5" maxlength="8" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_max_posts')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box frm-short text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Less posts label'] ?></span> <small><?php echo $lang_admin_users['Number of posts help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="posts_less" size="5" maxlength="8" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_last_post_after')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Last post after label'] ?></span> <small><?php echo $lang_admin_users['Date format help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="last_post_after" size="24" maxlength="19" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_last_post_before')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Last post before label'] ?></span><small><?php echo $lang_admin_users['Date format help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="last_post_before" size="24" maxlength="19" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_registered_after')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Registered after label'] ?></span> <small><?php echo $lang_admin_users['Date format help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="registered_after" size="24" maxlength="19" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_registered_before')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Registered before label'] ?></span> <small><?php echo $lang_admin_users['Date format help'] ?></small></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="registered_before" size="24" maxlength="19" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_user_activity_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php

($hook = get_hook('aus_search_form_user_activity_fieldset_end')) ? eval($hook) : null;

// Reset counter
$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>
			<div class="content-head">
				<h3 class="hn"><span><?php echo $lang_admin_users['User results head'] ?></span></h3>
			</div>
<?php ($hook = get_hook('aus_search_form_pre_results_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_users['User results legend'] ?></strong></legend>
<?php ($hook = get_hook('aus_search_form_pre_sort_by')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Order by label'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="order_by">
							<option value="username" selected="selected"><?php echo $lang_admin_users['Username'] ?></option>
							<option value="email"><?php echo $lang_admin_users['E-mail'] ?></option>
							<option value="num_posts"><?php echo $lang_admin_users['Posts'] ?></option>
							<option value="last_post"><?php echo $lang_admin_users['Last post'] ?></option>
							<option value="registered"><?php echo $lang_admin_users['Registered'] ?></option>
<?php ($hook = get_hook('aus_search_form_new_sort_by_option')) ? eval($hook) : null; ?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_sort_order')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['Sort order label'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="direction">
							<option value="ASC" selected="selected"><?php echo $lang_admin_users['Ascending'] ?></option>
							<option value="DESC"><?php echo $lang_admin_users['Descending'] ?></option>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_filter_group')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box select">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['User group label'] ?></span></label><br />
						<span class="fld-input"><select id="fld<?php echo $forum_page['fld_count'] ?>" name="user_group">
							<option value="all" selected="selected"><?php echo $lang_admin_users['All groups'] ?></option>
							<option value="<?php echo FORUM_UNVERIFIED ?>"><?php echo $lang_admin_users['Unverified users'] ?></option>
<?php

$query = array(
	'SELECT'	=> 'g.g_id, g.g_title',
	'FROM'		=> 'groups AS g',
	'WHERE'		=> 'g.g_id!='.FORUM_GUEST,
	'ORDER BY'	=> 'g.g_title'
);

($hook = get_hook('aus_search_form_qr_get_groups')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
while ($cur_group = $forum_db->fetch_assoc($result))
	echo "\t\t\t\t\t\t\t".'<option value="'.$cur_group['g_id'].'">'.forum_htmlencode($cur_group['g_title']).'</option>'."\n";

($hook = get_hook('aus_search_form_new_filter_group_option')) ? eval($hook) : null;

?>
						</select></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_results_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aus_search_form_results_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="find_user" value="<?php echo $lang_admin_users['Submit search'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

// Reset counter
$forum_page['group_count'] = $forum_page['item_count'] = 0;

?>

	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_admin_users['IP search head'] ?></span></h2>
	</div>
	<div class="main-content main-frm">
		<form class="frm-form" method="get" accept-charset="utf-8" action="<?php echo forum_link('admin/users.php') ?>">
<?php ($hook = get_hook('aus_search_form_pre_ip_search_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_admin_users['IP search legend'] ?></strong></legend>
<?php ($hook = get_hook('aus_search_form_pre_ip_address')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_users['IP address label'] ?></span></label><br />
						<span class="fld-input"><input type="text" id="fld<?php echo $forum_page['fld_count'] ?>" name="show_users" size="18" maxlength="15" class="inputbox" /></span>
					</div>
				</div>
<?php ($hook = get_hook('aus_search_form_pre_ip_search_fieldset_end')) ? eval($hook) : null; ?>
			</fieldset>
<?php ($hook = get_hook('aus_search_form_ip_search_fieldset_end')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" value=" <?php echo $lang_admin_users['Submit search'] ?> " /></span>
			</div>
		</form>
	</div>
<?php

($hook = get_hook('aus_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';
