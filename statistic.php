<?php
/**
 * Скрипт позволящий просматривать разнообразную статистику.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('st_start')) ? eval($hook) : null;

if (!$forum_user['g_read_board'] || !$forum_config['o_statistic'])
	message($lang_common['No view']);

// Load the statistic.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/statistic.php';

$section = isset($_GET['section']) ? $_GET['section'] : null;

if (!$section || $section == 'about')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'], forum_link($forum_url['statistic'], 'about'))
	);

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'about'));

	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	($hook = get_hook('st_pre_about_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

?>
<div class="main-subhead">
		<h2 class="hn"><span><?php echo $lang_stat['About stat'] ?></span></h2>
</div>
	<div class="main-content main-frm">
		<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><strong><?php echo $lang_stat['Online today'] ?></strong>
					<p><a href="<?php echo forum_link($forum_url['statistic'], 'onlinetoday') ?>"><?php echo $lang_stat['Look'] ?></a></p></span></h3>
					<ul><span><?php echo $lang_stat['Desc Online today'] ?></span></ul>
				</div>
			</div>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><strong><?php echo $lang_stat['Top author little'] ?></strong>
					<p><a href="<?php echo forum_link($forum_url['statistic'], 'topauthor') ?>"><?php echo $lang_stat['Look'] ?></a></p></span></h3>
					<ul><span><?php echo $lang_stat['Desc Top author'] ?></span></ul>
				</div>
			</div>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><strong><?php echo $lang_stat['Top replies little'] ?></strong>
					<p><a href="<?php echo forum_link($forum_url['statistic'], 'topreplies') ?>"><?php echo $lang_stat['Look'] ?></a></p></span></h3>
					<ul><span><?php echo $lang_stat['Desc Top replies'] ?></span></ul>
				</div>
			</div>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><strong><?php echo $lang_stat['Top views little'] ?></strong>
					<p><a href="<?php echo forum_link($forum_url['statistic'], 'topviews') ?>"><?php echo $lang_stat['Look'] ?></a></p></span></h3>
					<ul><span><?php echo $lang_stat['Desc Top views'] ?></span></ul>
				</div>
			</div>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h3 class="ct-legend hn"><span><strong><?php echo $lang_stat['Bans'] ?></strong>
					<p><a href="<?php echo forum_link($forum_url['statistic'], 'bans') ?>"><?php echo $lang_stat['Look'] ?></a></p></span></h3>
					<ul><span><?php echo $lang_stat['Desc Bans'] ?></span></ul>
				</div>
			</div>
		</fieldset>
	</div>
<?php

}

$forum_page['main_menu'] = array();
$forum_page['main_menu']['online_today'] = '<li'.(($section == 'onlinetoday')  ? ' class="active"' : '').'><a href="'.forum_link($forum_url['statistic'], 'onlinetoday').'"><span>'.$lang_stat['Online today'].'</span></a></li>';
$forum_page['main_menu']['top_author'] = '<li'.(($section == 'topauthor')  ? ' class="active"' : '').'><a href="'.forum_link($forum_url['statistic'], 'topauthor').'"><span>'.$lang_stat['Top author little'].'</span></a></li>';
$forum_page['main_menu']['top_replies'] = '<li'.(($section == 'topreplies') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['statistic'], 'topreplies').'"><span>'.$lang_stat['Top replies little'].'</span></a></li>';
$forum_page['main_menu']['top_views'] = '<li'.(($section == 'topviews') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['statistic'], 'topviews').'"><span>'.$lang_stat['Top views little'].'</span></a></li>';
$forum_page['main_menu']['bans'] = '<li'.(($section == 'bans') ? ' class="active"' : '').'><a href="'.forum_link($forum_url['statistic'], 'bans').'"><span>'.$lang_stat['Bans'].'</span></a></li>';

// Страница "Сегодня были"
if ($section == 'onlinetoday')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'],forum_link($forum_url['statistic'], 'about')),
		array($lang_stat['Online today'],forum_link($forum_url['statistic'], 'onlinetoday'))
	);

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'onlinetoday'));

	($hook = get_hook('st_pre_onlinetoday_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic-online-today');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();
	
?>
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['Desc Online today'] ?></span></h2>
		</div>
<?php

	$query = array(
		'SELECT'	=> 'u.id, u.last_visit, u.username',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.last_visit>'.strtotime(gmdate('M d y')).' AND id>1 AND group_id!='.FORUM_UNVERIFIED,
		'ORDER BY'	=> 'u.last_visit DESC'
	);

	($hook = get_hook('st_online_today_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result))
	{

?>
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['No one was'] ?></strong></span></h2>

		</div>
<?php

	}
	else
	{
?>
		<div class="ct-group">
			<table cellspacing="0">
			<thead>
				<th class="tc0" style="width:25%" scope="col"><?php echo $lang_common['Username'] ?></th>
				<th class="tc1" style="width:75%" scope="col"><?php echo $lang_stat['Last visit'] ?></th>
			</thead>
			<tbody>
<?php

		$num = 0;
		while ($online_today = $forum_db->fetch_assoc($result)) 
		{
			$table_page['user'] = '<td class="tc0"><a href="'.forum_link($forum_url['user'], $online_today['id']).'">'.forum_htmlencode($online_today['username']).'</a></td>';
			$table_page['last_visit'] = '<td class="tc1">'.format_time($online_today['last_visit']).$lang_common['Title separator'].flazy_format_time($online_today['last_visit']).'</td>';
			$num++;

?>
				<tr class="<?php echo ($num % 2 == 0 ? 'even' : 'odd') ?>">
					<?php echo implode("\n\t\t\t\t\t", $table_page)."\n"; ?>
				</tr>
<?php
		}

?>
			</tbody>
			</table>
		</div>
<?php
	}

?>
	</div>
<?php

}

// Страница "Самые активные пользователи" => top_author
else if ($section == 'topauthor')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'],forum_link($forum_url['statistic'], 'about')),
		array($lang_stat['Top author'],forum_link($forum_url['statistic'], 'topauthor'))
	);

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'topauthor'));

	($hook = get_hook('st_pre_topauthor_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic-top-author');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

?>
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['Desc Top author'] ?></span></h2>
		</div>
		<div class="ct-group">
			<table cellspacing="0">
			<thead>
				<th class="tc0" style="width:35%" scope="col"><?php echo $lang_common['Username'] ?></th>
				<th class="tc1" style="width:20%"><?php echo $lang_common['Registered'] ?></th>
				<th class="tc2" style="width:15%" scope="col"><?php echo $lang_common['Posts'] ?></th>
				<th class="tc3" style="width:15%" scope="col"><?php echo $lang_stat['Com forum'] ?></th>
				<th class="tc4" style="width:15%" scope="col"><?php echo $lang_stat['In day'] ?></th>
			</thead>
			<tbody>
<?php

	$query = array(
		'SELECT'	=> 'SUM(f.num_posts)',
		'FROM'		=> 'forums AS f'
	);

	($hook = get_hook('st_stats_qr_get_post_stats')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$total_posts = $forum_db->result($result);

	$query = array(
		'SELECT'	=> 'u.id, u.username, u.registered, u.num_posts',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.id>1 AND u.num_posts>0',
		'ORDER BY'	=> 'u.num_posts DESC',
		'LIMIT'		=> '20'
	);

	($hook = get_hook('st_top_author_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$num = 0;
	while ($top_author = $forum_db->fetch_assoc($result))
	{
		$pr_post = ($total_posts) ? substr($top_author['num_posts' ] / ($total_posts * 0.01), 0, 5) : '0';

		$num_posts_day = $top_author['num_posts'] > 0 ? substr($top_author['num_posts'] / (floor((time()-$top_author['registered']) / 84600) + (((time()-$top_author['registered']) % 84600) ? 1:0)), 0, 5) : 0;

		$table_page['user'] = '<td class="tc0"><a href="'.forum_link($forum_url['user'], $top_author['id']).'">'.forum_htmlencode($top_author['username']).'</a></td>';
		$table_page['registered'] = '<td class="tc1">'.format_time($top_author['registered']).'</td>';
		$table_page['num_posts'] = '<td class="tc2">'.forum_number_format($top_author['num_posts']).'</td>';
		$table_page['pr_post'] = '<td class="tc3">'.forum_number_format($pr_post).' %</td>';
		$table_page['posts_day'] = '<td class="tc4">'.forum_number_format($num_posts_day).'</td>';
		$num++;

?>
				<tr class="<?php echo ($num % 2 == 0 ? 'even' : 'odd') ?>">
					<?php echo implode("\n\t\t\t\t\t", $table_page)."\n"; ?>
				</tr>
<?php

	}

?>
			</tbody>
			</table>
		</div>
	</div>
<?php

}

// Страница "Самые комментируемые темы" => top_replies
else if ($section == 'topreplies')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'],forum_link($forum_url['statistic'], 'about')),
		array($lang_stat['Top replies'],forum_link($forum_url['statistic'], 'topreplies'))
	);

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'topreplies'));

	($hook = get_hook('st_pre_topreplies_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic-top-replies');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

?>
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['Desc Top replies'] ?></span></h2>
		</div>
		<div class="ct-group">
			<table cellspacing="0">
			<thead>
				<th class="tc0" style="width:40%" scope="col"><?php echo $lang_common['Username'] ?></th>
				<th class="tc1" style="width:20%" scope="col"><?php echo $lang_stat['Author'] ?></th>
				<th class="tc2" style="width:20%" scope="col"><?php echo $lang_stat['Replies'] ?></th>
				<th class="tc3" style="width:20%" scope="col"><?php echo $lang_stat['Views'] ?></th>
			</thead>
			<tbody>
<?php

	$query = array(
		'SELECT'	=> 't.id, t.poster, t.subject, t.num_replies, t.num_views, u.id AS user_id',
		'FROM'		=> 'topics AS t',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'users AS u',
				'ON'		=> 'u.username=t.poster'
				)
		),
		'ORDER BY'	=> 'num_replies DESC',
		'LIMIT'		=> '20'
	);

	($hook = get_hook('st_top_replies_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$num = 0;
	while ($top_replies = $forum_db->fetch_assoc($result))
	{
		if ($forum_config['o_censoring'])
			$top_replies['subject'] = censor_words($top_replies['subject']);

		$table_page['topic'] = '<td class="tc0"><a href="'.forum_link($forum_url['topic'], $top_replies['id']).'">'.forum_htmlencode($top_replies['subject']).'</a></td>';
		$table_page['user'] = '<td class="tc1"><a href="'.forum_link($forum_url['user'], $top_replies['user_id']).'">'.forum_htmlencode($top_replies['poster']).'</a></td>';
		$table_page['num_replies'] = '<td class="tc3">'.forum_number_format($top_replies['num_replies']).'</td>';
		$table_page['num_views'] = '<td class="tc4">'.forum_number_format($top_replies['num_views']).'</td>';
		$num++;

?>
				<tr class="<?php echo ($num % 2 == 0 ? 'even' : 'odd') ?>">
					<?php echo implode("\n\t\t\t\t\t", $table_page)."\n"; ?>
				</tr>
<?php

	}

?>
			</tbody>
			</table>
		</div>
	</div>
<?php

}

// Страница "Самые просматриваемые темы" => top_views
else if ($section == 'topviews')
{
	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'],forum_link($forum_url['statistic'], 'about')),
		array($lang_stat['Top views'],forum_link($forum_url['statistic'], 'topviews'))
	);

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'topviews'));

	($hook = get_hook('st_pre_topviews_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic-top-views');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();
?>
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['Desc Top views'] ?></span></h2>
		</div>
		<div class="ct-group">
			<table cellspacing="0">
			<thead>
				<th class="tc0" style="width:40%" scope="col"><?php echo $lang_common['Username'] ?></th>
				<th class="tc1" style="width:20%" scope="col"><?php echo $lang_stat['Author'] ?></th>
				<th class="tc2" style="width:20%" scope="col"><?php echo $lang_stat['Views'] ?></th>
				<th class="tc3" style="width:20%" scope="col"><?php echo $lang_stat['Replies'] ?></th>

			</thead>
			<tbody>
<?php

	$query = array(
		'SELECT'	=> 't.id, t.poster, t.subject, t.num_replies, t.num_views, u.id AS user_id',
		'FROM'		=> 'topics AS t',
		'JOINS'		=> array(
				array(
				'INNER JOIN'		=> 'users AS u',
				'ON'			=> 'u.username=t.poster'
			)
		),
		'ORDER BY'	=> 'num_views DESC',
		'LIMIT'		=> '20'
	);

	($hook = get_hook('st_top_views_qr')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$num = 0;
	while ($top_views = $forum_db->fetch_assoc($result))
	{
		if ($forum_config['o_censoring'])
			$top_views['subject'] = censor_words($top_views['subject']);

		$table_page['topic'] = '<td class="tc0"><a href="'.forum_link($forum_url['topic'], $top_views['id']).'">'.forum_htmlencode($top_views['subject']).'</a></td>';
		$table_page['user'] = '<td class="tc1"><a href="'.forum_link($forum_url['user'], $top_views['user_id']).'">'.forum_htmlencode($top_views['poster']).'</a></td>';
		$table_page['num_views'] = '<td class="tc2">'.forum_number_format($top_views['num_views']).'</td>';
		$table_page['num_replies'] = '<td class="tc3">'.forum_number_format($top_views['num_replies']).'</td>';
		$num++;

?>
				<tr class="<?php echo ($num % 2 == 0 ? 'even' : 'odd') ?>">
					<?php echo implode("\n\t\t\t\t\t", $table_page)."\n"; ?>
				</tr>
<?php

	}

?>
			</tbody>
			</table>
		</div>
	</div>
<?php

}

// Страница "Баны" => bans
else if ($section == 'bans')
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['statistic'], 'bans'));

	$query = array(
		'SELECT'	=> 'COUNT(*)',
		'FROM'		=> 'bans AS b'
	);

	($hook = get_hook('st_bans_qr_count')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$ban = $forum_db->result($result);

	if ($ban)
	{
		$forum_page['num_pages'] = ceil(($ban + 1) / $forum_user['disp_posts']);
		$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
		$forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);

		if ($forum_page['page'] < $forum_page['num_pages'])
		{
			$forum_page['nav']['last'] = '<link rel="last" href="'.forum_link($forum_url['statistic'], $forum_url['page'], $forum_page['num_pages'], 'bans').'" title="'.$lang_common['Page'].' '.$forum_page['num_pages'].'" />';
			$forum_page['nav']['next'] = '<link rel="next" href="'.forum_link($forum_url['statistic'], $forum_url['page'], $forum_page['num_pages'] + 1, 'bans').'" title="'.$lang_common['Page'].' '.($forum_page['page'] + 1).'" />';
		}

		if ($forum_page['page'] > 1)
		{
			$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_link($forum_url['statistic'], $forum_url['page'] -1 , $forum_page['num_pages'], 'bans').'" title="'.$lang_common['Page'].' '.($forum_page['page'] - 1).'" />';
			$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link($forum_url['statistic'], 'bans').'" title="'.$lang_common['Page'].' 1" />';
		}

		$page_post_ban = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['statistic'], $lang_common['Paging separator'], 'bans').'</p>';

		$query = array(
			'SELECT'	=> 'b.id, b.username, b.ip, b.email, b.message, b.expire, b.ban_creator, u0.id, u1.username AS username_creator',
			'FROM'		=> 'bans AS b',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'users AS u0',
					'ON'		=> 'b.username=u0.username'
				),
				array(
					'INNER JOIN'	=> 'users AS u1',
					'ON'		=> 'b.ban_creator=u1.id'
				)
			),
			'LIMIT'		=> $forum_page['start_from'].','.$forum_user['disp_posts']
		);

		($hook = get_hook('st_bans_qr')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	}

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($lang_stat['Stat'],forum_link($forum_url['statistic'], 'about')),
		array($lang_stat['Bans'],forum_link($forum_url['statistic'], 'bans'))
	);

	($hook = get_hook('st_pre_bans_header_load')) ? eval($hook) : null;

	define('FORUM_ALLOW_INDEX', 1);
	define('FORUM_PAGE', 'statistic-bans');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

?>
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_stat['Desc Bans'] ?></span></h2>
		</div>
<?php

		//If there are any bans in the ban list, put them in
	if ($ban)
	{

?>
		<div class="ct-group">
			<table cellspacing="0">
			<thead>
				<th class="tc0" style="width:20%" scope="col"><?php echo $lang_common['Username'] ?></th>
				<th class="tc1" style="width:45%" scope="col"><?php echo $lang_stat['Message'] ?></th>
				<th class="tc2" style="width:20%" scope="col"><?php echo $lang_stat['Expires'] ?></th>
				<th class="tc3" style="width:15%" scope="col"><?php echo $lang_stat['Ban creator'] ?></th>
			</thead>
			<tbody>
<?php

		$num = 0;	
		while ($cur_ban = $forum_db->fetch_assoc($result))
		{
			if ($forum_config['o_censoring'])
				$cur_ban['message'] = censor_words($cur_ban['message']);

			if ($cur_ban['username'] != '')
				$table_page['user'] = '<td class="tc0"><a href="'.forum_link($forum_url['user'], $cur_ban['id']).'">'.forum_htmlencode($cur_ban['username']).'</a></td>';
			else
				$table_page['user'] = '<td class="tc0">'.$lang_stat['No IP'].'</td>';

			if ($cur_ban['message'] != '')
				$table_page['message'] = '<td class="tc1">'.$cur_ban['message'].'</td>';
			else
				$table_page['message'] = '<td class="tc1">'.$lang_stat['No'].'</td>';

			$table_page['expire'] = '<td class="tc2">'.format_time($cur_ban['expire'], true).'</td>';
			$table_page['creator'] = '<td class="tc3"><a href="'.forum_link($forum_url['user'], $cur_ban['ban_creator']).'">'.forum_htmlencode($cur_ban['username_creator']).'</a></td>';
			$num++;

?>
				<tr class="<?php echo ($num % 2 == 0 ? 'even' : 'odd') ?>">
					<?php echo implode("\n\t\t\t\t\t", $table_page)."\n"; ?>
				</tr>
<?php

		}

?>
			</tbody>
			</table>
		</div>
<?

	}
	else
	//Else, say that there arn't any
	{

?>
		<div class="ct-box error-box">
			<h2 class="warn"><strong><?php echo $lang_stat['No bans'] ?></strong></h2>
			<ul class="error-list">
				<?php ($forum_config['o_rules'] ? printf($lang_stat['No bans 2'], forum_link($forum_url['rules'])) : printf($lang_stat['No bans 3'])) ?>
			</ul>
		</div>
<?php

	}
	
?>
	</div>
<?php 

	if (!empty($page_post_ban))
	{

?>
<div id="brd-pagepost-end" class="main-pagepost gen-content">
	<?php echo $page_post_ban ?>
</div>
<?php
	}
}

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';
