<?php
/**
 * Подробно показывает всех кто есть на форуме.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('on_start')) ? eval($hook) : null;

// Check for use of incorrect URLs
confirm_current_url(forum_link($forum_url['online']));

if (!$forum_user['g_read_board'] || !$forum_config['o_users_online'])
	message($lang_common['No view']);

function ip($ip)
{
	$ip = ereg('([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})', $ip, $array) ? $array[1].'.'.$array[2] : '0.0';

	return $ip.'.*.*';
}

// Load the online.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/online.php';

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_online['Online List'],forum_link($forum_url['online']))
);

define('FORUM_ALLOW_INDEX', 1);
define('FORUM_PAGE', 'online');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

?>
	<div class="main-content main-frm">
		<div class="ct-group">
			<table cellspacing="0">
			<thead>
				<th class="tc0" style="width:25%" scope="col"><?php echo $lang_online['Name'] ?></th>
				<th class="tc1" style="width:50%" scope="col"><?php echo $lang_online['Last action'] ?></th>
				<th class="tc2" style="width:25%" scope="col"><?php echo $lang_online['Time'] ?></th>
			</thead>
			<tbody>
<?php

// Получим список участников
$query = array(
	'SELECT'	=> 'o.user_id, o.ident, o.logged, o.idle, o.current_page, o.current_page_id, o.current_ip ',
	'FROM'		=> 'online AS o',
	'WHERE'		=> 'o.idle=0 AND o.user_id>0',
	'ORDER BY'	=> 'o.ident'
);

($hook = get_hook('on_online_list_qr_get')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

$num_users_page = $forum_db->num_rows($result);
if ($num_users_page)
{
	$num = 0;
	while ($user_data = $forum_db->fetch_assoc($result))
	{
		if ($user_data['current_page']) 
		{
			if ($user_data['user_id'] > 1)
			{
				$ip = ($forum_user['is_admmod']) ? '<sup><a href="'.forum_link($forum_url['get_host'], $user_data['current_ip']).'">'.forum_htmlencode($user_data['current_ip']).'</a> <a href="'.forum_link('click.php').'?http://www.ripe.net/whois?form_type=simple&amp;full_query_string=&amp;searchtext='.forum_htmlencode($user_data['current_ip']).'&amp;do_search=Search" onclick="window.open(this.href); return false">Whois</a></sup>' : '';
				$list['user'] = '<td class="tc0"><a href="'.forum_link($forum_url['user'], $user_data['user_id']).'">'.forum_htmlencode($user_data['ident']).'</a> '.$ip.'</td>';
			}
			else
				$list['user'] = '<td class="tc0">'.$lang_online['Guest'].''.(($forum_user['is_admmod']) ? ' <sup><a href="'.forum_link($forum_url['get_host'], forum_htmlencode($user_data['current_ip'])).'">'.forum_htmlencode($user_data['current_ip']).'</a> <a href="'.forum_link('click.php').'?http://www.ripe.net/whois?form_type=simple&full_query_string=&searchtext='.forum_htmlencode($user_data['current_ip']).'&do_search=Search" onclick="window.open(this.href); return false">Whois</a></sup>' : '<sup> IP: '.ip(forum_htmlencode($user_data['ident'])).'</sup>').'</td>';

			($hook = get_hook('on_pre_current_page_id')) ? eval($hook) : null;

			// Если форум находится не в корне
			$pathinfo = pathinfo($_SERVER['PHP_SELF']);
			$lang_page = substr($user_data['current_page'], strlen($pathinfo['dirname']));
			$pathinfo_sec = pathinfo($user_data['current_page']);
			$cur_page = $pathinfo_sec['basename'];

			if (substr($cur_page, 0, 5) == 'admin')
				$cur_page = 'admin';

			if ($user_data['current_page_id'] > 0)
			{
				if ($cur_page == 'viewtopic' || $cur_page == 'post')
					$page_name = $forum_db->query('SELECT subject FROM '.$forum_db->prefix.'topics WHERE id=\''.$user_data['current_page_id'].'\'');
				if ($cur_page == 'postedit')
					$page_name = $forum_db->query('SELECT t.subject FROM '.$forum_db->prefix.'topics AS t INNER JOIN '.$forum_db->prefix.'posts AS p ON t.id=p.topic_id WHERE p.id=\''.$user_data['current_page_id'].'\'');
				if ($cur_page == 'viewforum')
					$page_name = $forum_db->query('SELECT forum_name FROM '.$forum_db->prefix.'forums WHERE id=\''.$user_data['current_page_id'].'\'');
				if ($cur_page == 'profile-about' && $cur_page != 'profile-pm' || $cur_page == 'reputation' || $cur_page == 'positive')
					$page_name = $forum_db->query('SELECT username FROM '.$forum_db->prefix.'users WHERE id=\''.$user_data['current_page_id'].'\'');

				($hook = get_hook('on_after_current_page_id_qr_get')) ? eval($hook) : null;

				if (!empty($page_name))
				{
					$page_name = $forum_db->result($page_name, 0);

					if ($forum_config['o_censoring'])
						$page_name = censor_words($page_name);

					if ($cur_page == 'post')
						$list['page'] = '<td class="tc1">'.$lang_online[$cur_page].': <strong><a href="'.forum_link($forum_url['topic'], array($user_data['current_page_id'], sef_friendly($page_name))).'">'.forum_htmlencode($page_name).'</a></strong></td>';
					if ($cur_page == 'postedit')
						$list['page'] = '<td class="tc1">'.$lang_online[$cur_page].': <strong><a href="'.forum_link($forum_url['post'], array($user_data['current_page_id'], sef_friendly($page_name))).'">'.forum_htmlencode($page_name).'</a></strong></td>';
					if ($cur_page != 'post' && $cur_page != 'postedit' && $page_name != '')
						$list['page'] = '<td class="tc1">'.$lang_online[$cur_page].': <strong><a href="'.forum_link($forum_url[$url_online[$cur_page]], array($user_data['current_page_id'], sef_friendly($page_name))).'">'.forum_htmlencode($page_name).'</a></strong></td>';

					($hook = get_hook('on_current_cur_page_id')) ? eval($hook) : null;
				}
				else
					$list['page'] = '<td class="tc1">'.$lang_online[$cur_page].'</td>';

				($hook = get_hook('on_pre_not_page_name')) ? eval($hook) : null;
			}
			else if ((@$lang_online[$cur_page]) == '')
				$list['page'] = '<td class="tc1">'.$lang_online['Hiding Somewhere'].'</td>';
			else
			{
				if (!empty($url_online[$cur_page]))
					$list['page'] = '<td class="tc1"><a href="'.forum_link( $forum_url[$url_online[$cur_page]]).'">'.$lang_online[$cur_page].'</a></td>';
				else
					$list['page'] = '<td class="tc1">'.$lang_online[$cur_page].'</td>';

			}

			($hook = get_hook('on_pre_list_time')) ? eval($hook) : null;

			$list['time'] = '<td class="tc2">'.format_time($user_data['logged']).'</td>';
			$num++;
		}
?>
				<tr class="<?php echo ($num % 2 == 0 ? 'even' : 'odd') ?>">
					<?php echo implode("\n\t\t\t\t\t", $list)."\n"; ?>
				</tr>
<?php

	}
}
else
{

?>
				<tr>
					<tr><td colspan="4"><? echo $lang_online['Nobody'] ?></td>
				</tr>
<?php

	($hook = get_hook('on_after_nobody')) ? eval($hook) : null;
}

?>
			</tbody>
			</table>
		</div>
	</div>
<?php

($hook = get_hook('on_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';
