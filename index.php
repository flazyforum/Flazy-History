<?php
/**
 * Показывает список категорий/форумов, что участники могут видеть, вместе с некоторыми статистическими данными.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('in_start')) ? eval($hook) : null;

$cid = isset($_GET['cid']) ? intval($_GET['cid']) : 0;

// Check for use of incorrect URLs
confirm_current_url($cid ? forum_link($forum_url['category'], $cid) : forum_link($forum_url['index']));

if (!$forum_user['g_read_board'])
	message($lang_common['No view']);

// Load the index.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/index.php';

// Get list of forums and topics with new posts since last visit
if (!$forum_user['is_guest'])
{
	$query = array(
		'SELECT'	=> 't.forum_id, t.id, t.last_post',
		'FROM'		=> 'topics AS t',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'forums AS f',
				'ON'			=> 'f.id=t.forum_id'
			),
			array(
				'LEFT JOIN'		=> 'forum_perms AS fp',
				'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
			)
		),
		'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.last_post>'.$forum_user['last_visit'].' AND t.moved_to IS NULL'
	);

	($hook = get_hook('in_qr_get_new_topics')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$new_topics = array();
	while ($cur_topic = $forum_db->fetch_assoc($result))
		$new_topics[$cur_topic['forum_id']][$cur_topic['id']] = $cur_topic['last_post'];

	$tracked_topics = get_tracked_topics();
}

// Setup main heading
$forum_page['main_head'] = forum_htmlencode($forum_config['o_board_title']);

// Setup main options
$forum_page['main_options_head'] = $lang_index['Board options'];
$forum_page['main_options'] = array();
$forum_page['main_options']['rss'] = '<span class="feed'.(empty($forum_page['main_options']) ? ' item1' : '').'"><a class="feed-rss" href="'.forum_link($forum_url['feed_index'], 'rss').'">'.$lang_index['RSS active feed'].'</a></span>';
$forum_page['main_options']['atom'] = '<span class="feed'.(empty($forum_page['main_options']) ? ' item1' : '').'"><a class="feed-atom" href="'.forum_link($forum_url['feed_index'], 'atom').'">'.$lang_index['ATOM active feed'].'</a></span>';

if (!$forum_user['is_guest'])
	$forum_page['main_options']['markread'] = '<span'.(empty($forum_page['main_options']) ? ' class="item1"' : '').'><a class="mark-all-read" href="'.forum_link($forum_url['mark_read'], generate_form_token('markread'.$forum_user['id'])).'">'.$lang_common['Mark all as read'].'</a></span>';

($hook = get_hook('in_pre_header_load')) ? eval($hook) : null;

$forum_js->addFile(array($js['jquery'], $js['tooltip'], $js['cookies']));
$forum_js->addCode('$(document).ready(function() {
		$(\'a.toggle\').show();
		$(\'.info-lastpost a, #brd-today a\').tooltip({ track: true, delay: 0, showURL: false, showBody: " - ", fade: 250 });
		$(\'#block\').click($.tooltip.block);
	});');

define('FORUM_ALLOW_INDEX', 1);
define('FORUM_PAGE', 'index');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

($hook = get_hook('in_main_output_start')) ? eval($hook) : null;

// Print the categories and forums
$query = array(
	'SELECT'	=> 'c.id AS cid, c.cat_name, f.id AS fid, f.forum_name, f.forum_desc, f.redirect_url, f.moderators, f.num_topics, f.num_posts, f.last_post, f.last_post_id, f.last_poster, f.sort_by, t.subject, t.description, t.last_poster_id',
	'FROM'		=> 'categories AS c',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'forums AS f',
			'ON'			=> 'c.id=f.cat_id'
		),
		array(
			'LEFT JOIN'		=> 'forum_perms AS fp',
			'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
		),
		array(
			'LEFT JOIN'		=> 'topics AS t',
			'ON'			=> 'f.last_post_id=t.last_post_id'
		)
	),
	'WHERE'		=> ($cid ? 'c.id='.$cid . ' AND ' : '').'fp.read_forum IS NULL OR fp.read_forum=1',
	'ORDER BY'	=> 'c.disp_position, c.id, f.disp_position'
);

($hook = get_hook('in_qr_get_cats_and_forums')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

$forum_page['cur_category'] = $forum_page['item_count'] = 0;

while ($cur_forum = $forum_db->fetch_assoc($result))
{
	($hook = get_hook('in_forum_loop_start')) ? eval($hook) : null;

	++$forum_page['item_count'];

	if ($cur_forum['cid'] != $forum_page['cur_category']) // A new category since last iteration?
	{
		if ($forum_page['cur_category'] != 0)
			echo "\t".'</div>'."\n";

		$forum_page['item_count'] = 1;

		$forum_page['item_header'] = array();
		$forum_page['item_header']['subject']['title'] = '<strong class="subject-title">'.$lang_index['Forums'].'</strong>';
		$forum_page['item_header']['info']['topics'] = '<strong class="info-topics">'.$lang_index['topics'].'</strong>';
		$forum_page['item_header']['info']['post'] = '<strong class="info-posts">'.$lang_index['posts'].'</strong>';
		$forum_page['item_header']['info']['lastpost'] = '<strong class="info-lastpost">'.$lang_index['last post'].'</strong>';

		$forum_page['toggle'][] = 'if($.cookie(\'#category'.$cur_forum['cid'].'\')) {$(\'#category'.$cur_forum['cid'].'\').hide(); $(\'#toggle'.$cur_forum['cid'].'\').toggleClass(\'hide\'); }'."\n";

		($hook = get_hook('in_forum_pre_cat_head')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<a id="toggle<? echo $cur_forum['cid'] ?>" href="#" onclick="$('#category<? echo $cur_forum['cid'] ?>').toggle(500); $('#toggle<? echo $cur_forum['cid'] ?>').toggleClass('hide'); if($.cookie('#category<?php echo $cur_forum['cid'] ?>')) $.cookie('#category<? echo $cur_forum['cid'] ?>', null); else $.cookie('#category<? echo $cur_forum['cid'] ?>', 'hide', {expires: 365}); return false;" class="toggle hide">&nbsp;</a>
		<h2 class="hn"><span id="cat<? echo $cur_forum['cid'] ?>"><?php echo forum_htmlencode($cur_forum['cat_name']) ?></span></h2>
		<p class="item-summary"><span><?php printf($lang_index['Category subtitle'], implode(' ', $forum_page['item_header']['subject']), implode(', ', $forum_page['item_header']['info'])) ?></span></p>
	</div>
	<div id="category<?php echo $cur_forum['cid'] ?>" class="main-content main-category">
<?php

		$forum_page['cur_category'] = $cur_forum['cid'];
	}

	// Reset arrays and globals for each forum
	$forum_page['item_status'] = $forum_page['item_subject'] = $forum_page['item_body'] = $forum_page['item_title'] = array();

	// Is this a redirect forum?
	if ($cur_forum['redirect_url'] != '')
	{
		$forum_page['item_body']['subject']['title'] = '<h3 class="hn"><a class="external" href="'.forum_link('click.php').'?'.forum_htmlencode($cur_forum['redirect_url']).'" title="'.sprintf($lang_index['Link to'], forum_htmlencode($cur_forum['redirect_url'])).'"><span>'.forum_htmlencode($cur_forum['forum_name']).'</span></a></h3>';
		$forum_page['item_status']['redirect'] = 'redirect';

		if ($cur_forum['forum_desc'] != '')
			$forum_page['item_subject']['desc'] = $cur_forum['forum_desc'];

		$forum_page['item_subject']['redirect'] = '<span>'.$lang_index['External forum'].'</span>';

		($hook = get_hook('in_redirect_row_pre_item_subject_merge')) ? eval($hook) : null;

		if (!empty($forum_page['item_subject']))
			$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', $forum_page['item_subject']).'</p>';

		// Forum topic and post count
		$forum_page['item_body']['info']['topics'] = '<li class="info-topics"><span class="label">'.$lang_index['No topic info'].'</span></li>';
		$forum_page['item_body']['info']['posts'] = '<li class="info-posts"><span class="label">'.$lang_index['No post info'].'</span></li>';
		$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_index['No lastpost info'].'</span></li>';

		($hook = get_hook('in_redirect_row_pre_display')) ? eval($hook) : null;
	}
	else
	{
		// Setup the title and link to the forum
		$forum_page['item_title']['title'] = '<a href="'.forum_link($forum_url['forum'], array($cur_forum['fid'], sef_friendly($cur_forum['forum_name']))).'"><span>'.forum_htmlencode($cur_forum['forum_name']).'</span></a>';

		// Are there new posts since our last visit?
		if (!$forum_user['is_guest'] && $cur_forum['last_post'] > $forum_user['last_visit'] && (empty($tracked_topics['forums'][$cur_forum['fid']]) || $cur_forum['last_post'] > $tracked_topics['forums'][$cur_forum['fid']]))
		{
			// There are new posts in this forum, but have we read all of them already?
			foreach ($new_topics[$cur_forum['fid']] as $check_topic_id => $check_last_post)
			{
				if ((empty($tracked_topics['topics'][$check_topic_id]) || $tracked_topics['topics'][$check_topic_id] < $check_last_post) && (empty($tracked_topics['forums'][$cur_forum['fid']]) || $tracked_topics['forums'][$cur_forum['fid']] < $check_last_post))
				{
					$forum_page['item_status']['new'] = 'new';

					if ($forum_user['g_search'])
						$forum_page['item_title']['status'] = '<small>'.sprintf($lang_index['Forum has new'], '<a href="'.forum_link($forum_url['search_new_results'], $cur_forum['fid']).'" title="'.$lang_index['New posts title'].'">'.$lang_index['Forum new posts'].'</a>').'</small>';

					break;
				}
			}
		}
		($hook = get_hook('in_normal_row_pre_item_title_merge')) ? eval($hook) : null;

		$forum_page['item_body']['subject']['title'] = '<h3 class="hn">'.implode(' ', $forum_page['item_title']).'<a href="'.forum_link($forum_url['feed_forum'], array('rss', $cur_forum['fid'], $cur_forum['sort_by'] == '1' ? 'posted' : 'last_post')).'"><span class="subject-right feed-img"></span></a></h3>';

		// Setup the forum description and mod list
		if ($cur_forum['forum_desc'] != '')
			$forum_page['item_subject']['desc'] = $cur_forum['forum_desc'];

		if ($cur_forum['moderators'] != '')
		{
			$forum_page['mods_array'] = unserialize($cur_forum['moderators']);
			$forum_page['item_mods'] = array();

			foreach ($forum_page['mods_array'] as $mod_username => $mod_id)
				$forum_page['item_mods'][] = ($forum_user['g_view_users'] == '1') ? '<a href="'.forum_link($forum_url['user'], $mod_id).'">'.forum_htmlencode($mod_username).'</a>' : forum_htmlencode($mod_username);

			($hook = get_hook('in_row_modify_modlist')) ? eval($hook) : null;

			$forum_page['item_subject']['modlist'] = '<span class="modlist">('.sprintf($lang_index['Moderated by'], implode(', ', $forum_page['item_mods'])).')</span>';
		}

		($hook = get_hook('in_normal_row_pre_item_subject_merge')) ? eval($hook) : null;

		if (!empty($forum_page['item_subject']))
			$forum_page['item_body']['subject']['desc'] = '<p>'.implode(' ', $forum_page['item_subject']).'</p>';


		// Установка тем форума, счетчика сообщений и последнего сообщения
		$forum_page['item_body']['info']['topics'] = '<li class="info-topics"><strong class="'.item_size($cur_forum['num_topics']).'">'.forum_number_format($cur_forum['num_topics']).'</strong> <span class="label">'.(($cur_forum['num_topics'] == 1) ? $lang_index['topic'] : $lang_index['topics']).'</span></li>';
		$forum_page['item_body']['info']['posts'] = '<li class="info-posts"><strong class="'.item_size($cur_forum['num_posts']).'">'.forum_number_format($cur_forum['num_posts']).'</strong> <span class="label">'.(($cur_forum['num_posts'] == 1) ? $lang_index['post'] : $lang_index['posts']).'</span></li>';

		if ($cur_forum['last_post'] != '')
		{
			if ($forum_config['o_censoring'])
			{
				$cur_forum['subject'] = censor_words($cur_forum['subject']);
				$cur_forum['description'] = censor_words($cur_forum['description']);
			}
			$desc['subject'] = forum_htmlencode(forum_htmlencode($cur_forum['subject']));
			$desc['description'] = ($cur_forum['description'] != '') ?' - '.forum_htmlencode(forum_htmlencode($cur_forum['description'])) : '';

			$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><span class="label">'.$lang_index['Last post'].'</span> <strong><a href="'.forum_link($forum_url['post'], $cur_forum['last_post_id']).'" title="'.implode('', $desc).'">'.(utf8_strlen($cur_forum['subject']) > 20 ? forum_htmlencode(forum_trim(utf8_substr($cur_forum['subject'], 0, 23))).$lang_common['Spacer'] : forum_htmlencode($cur_forum['subject'])).'</a></strong><cite>'.format_time($cur_forum['last_post']).$lang_common['Title separator'].($cur_forum['last_poster_id'] > 1 ? '<a href="'.forum_link($forum_url['user'], $cur_forum['last_poster_id']).'">'. forum_htmlencode($cur_forum['last_poster']).'</a>': forum_htmlencode($cur_forum['last_poster'])).'</cite></li>';
		}
		else
			$forum_page['item_body']['info']['lastpost'] = '<li class="info-lastpost"><strong>'.$lang_index['Forum is empty'].'</strong>'.($forum_user['id'] > 1 ? '<span><a href="'.forum_link($forum_url['new_topic'], $cur_forum['fid']).'">'.$lang_index['First post nag'].'</a></span>' : '' ).'</li>';

		($hook = get_hook('in_normal_row_pre_display')) ? eval($hook) : null;
	}

	// Generate classes for this forum depending on its status
	$forum_page['item_style'] = (($forum_page['item_count'] % 2 != 0) ? ' odd' : ' even').(($forum_page['item_count'] == 1) ? ' main-first-item' : '').((!empty($forum_page['item_status'])) ? ' '.implode(' ', $forum_page['item_status']) : '');

	($hook = get_hook('in_row_pre_display')) ? eval($hook) : null;

?>
		<div id="forum<?php echo $cur_forum['fid'] ?>" class="main-item<?php echo $forum_page['item_style'] ?>">
			<span class="icon <?php echo implode(' ', $forum_page['item_status']) ?>"><!-- --></span>
			<div class="item-subject">
				<?php echo implode("\n\t\t\t\t", $forum_page['item_body']['subject'])."\n" ?>
			</div>
			<ul class="item-info">
				<?php echo implode("\n\t\t\t\t", $forum_page['item_body']['info'])."\n" ?>
			</ul>
		</div>
<?php

}
$forum_js->addCode('$(document).ready(function() {
		'.implode("\t\t", $forum_page['toggle']).'
	});');
// Did we output any categories and forums?
if ($forum_page['cur_category'] > 0)
	echo  "\t".'</div>'."\n";
else
{

?>
		<div class="main-content main-message">
			<p><?php echo $lang_index['Empty board'] ?></p>
		</div>
<?php

}

($hook = get_hook('in_end')) ? eval($hook) : null;

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->


// START SUBST - <!-- forum_info -->
ob_start();

($hook = get_hook('in_info_output_start')) ? eval($hook) : null;

// Load cached
if (file_exists(FORUM_CACHE_DIR.'cache_stat_user.php'))
	include FORUM_CACHE_DIR.'cache_stat_user.php';
else
{
	if (!defined('FORUM_CACHE_STAT_USER_LOADED'))
		require FORUM_ROOT.'include/cache/stat_user.php';

	generate_stat_user_cache();
	require FORUM_CACHE_DIR.'cache_stat_user.php';
}

$stats_list['no_of_users'] = '<li class="st-users"><span>'.sprintf($lang_index['No of users'], '<strong>'.forum_number_format($forum_stat_user['total_users']).'</strong>').'</span></li>';
$stats_list['newest_user'] = '<li class="st-users"><span>'.sprintf($lang_index['Newest user'], '<strong>'.($forum_user['g_view_users'] == '1' ? '<a href="'.forum_link($forum_url['user'], $forum_stat_user['id']).'">'.forum_htmlencode($forum_stat_user['username']).'</a>' : forum_htmlencode($stats['last_user']['username'])).'</strong>').'</span></li>';

$query = array(
	'SELECT'	=> 'SUM(f.num_topics), SUM(f.num_posts)',
	'FROM'		=> 'forums AS f'
);

($hook = get_hook('in_stats_qr_get_post_stats')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
list($stats['total_topics'], $stats['total_posts']) = $forum_db->fetch_row($result);

$query = array(
	'SELECT'	=> 'p.posted',
	'FROM'		=> 'posts AS p',
	'WHERE'		=> 'p.posted>='.mktime(0, 0, 0, date('m')-1, date('d'), date('y'))
);

($hook = get_hook('in_stats_qr_get_time_post_stats')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

$posts_week = $posts_day = 0;
$all_posts = array();

while ($posts = $forum_db->fetch_assoc($result))
{
	if ($posts['posted'] >= (time()-86400))
	{
		$all_posts[] = $posts['posted'];
		++$posts_day;
	}
		++$posts_week;
}

$stats_list['no_of_posts'] = '<li class="st-activity"><span>'.sprintf($lang_index['No of posts'], '<strong>'.forum_number_format($stats['total_posts']).'</strong>', '<strong>'.forum_number_format($posts_week).'</strong>', $lang_index['Online list separator'], '<strong>'.forum_number_format($posts_day).'</strong>').'</span></li>';
$stats_list['no_of_topics'] = '<li class="st-activity"><span>'.sprintf($lang_index['No of topics'], '<strong>'.forum_number_format($stats['total_topics']).'</strong>').'</span></li>';

($hook = get_hook('in_stats_pre_info_output')) ? eval($hook) : null;

?>
<div id="brd-stats" class="gen-content">
	<h2 class="hn"><span><?php echo $lang_index['Statistics'] ?></span></h2>
	<ul>
		<?php echo implode("\n\t\t", $stats_list)."\n" ?>
	</ul>
</div>
<?php

($hook = get_hook('in_stats_end')) ? eval($hook) : null;

if ($forum_config['o_users_online'])
{
	// Fetch users online info and generate strings for output
	$query = array(
		'SELECT'	=> 'o.user_id, o.ident',
		'FROM'		=> 'online AS o',
		'WHERE'		=> 'o.idle=0',
		'ORDER BY'	=> 'o.ident'
	);

	($hook = get_hook('in_users_online_qr_get_online_info')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$forum_page['num_guests'] = $forum_page['num_users'] = 0;
	$users = array();

	while ($forum_user_online = $forum_db->fetch_assoc($result))
	{
		($hook = get_hook('in_users_online_add_online_user_loop')) ? eval($hook) : null;

		if ($forum_user_online['user_id'] > 1)
		{
			$users[] = ($forum_user['g_view_users']) ? '<a href="'.forum_link($forum_url['user'], $forum_user_online['user_id']).'">'.forum_htmlencode($forum_user_online['ident']).'</a>' : forum_htmlencode($forum_user_online['ident']);
			++$forum_page['num_users'];
		}
		else
			++$forum_page['num_guests'];
	}

	$forum_page['online_info'] = array(
		'guests'	=> '<strong>'.forum_number_format($forum_page['num_guests']).'</strong> '.declination($forum_page['num_guests'], array($lang_common['Guests none'], $lang_common['Guests single'], $lang_common['Guests plural'])),
		'users'		=> '<strong>'.forum_number_format($forum_page['num_users']).'</strong> '.declination($forum_page['num_users'], array($lang_index['Users none'], $lang_index['Users single'], $lang_index['Users plural']))
	);

	($hook = get_hook('in_users_online_pre_online_info_output')) ? eval($hook) : null;

?>
<div id="brd-online" class="gen-content index-stat">
	<h3 class="hn"><span><?php printf($lang_index['Currently online'], forum_link($forum_url['online']), implode($lang_index['Online list separator'], $forum_page['online_info'])) ?></span></h3>
<?php if (!empty($users)): ?>	<p><?php echo implode($lang_index['Online list separator'], $users) ?></p>
<?php endif; ($hook = get_hook('in_new_online_data')) ? eval($hook) : null; ?>
</div>
<?php
}

($hook = get_hook('in_users_online_end')) ? eval($hook) : null;

// Сегодня были
if ($forum_config['o_online_today'])
{
	$query = array(
		'SELECT'	=> 'u.id, u.last_visit, u.username',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.last_visit>'.strtotime(gmdate('M d y')).' AND u.id>1 AND group_id!='.FORUM_UNVERIFIED,
		'ORDER BY'	=> 'u.username ASC'
	);

	($hook = get_hook('in_fl_online_today_qr_get')) ? eval($hook) : null;	
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$count_users = 0;
	$users = array();
	
	while ($forum_online_today = $forum_db->fetch_assoc($result))
	{
		$users[] = ($forum_user['g_view_users']) ? '<a href="'.forum_link($forum_url['user'], $forum_online_today['id']).'" title="'.$lang_index['last visit'].' - '.format_time($forum_online_today['last_visit']).' ('.flazy_format_time($forum_online_today['last_visit']).')'.'">'.forum_htmlencode($forum_online_today['username']).'</a>' : forum_htmlencode($forum_online_today['username']);
		++$count_users;
	}

	$forum_page['online_today'] = '<strong>'.forum_number_format($count_users ).'</strong> '.declination($count_users , array($lang_index['Users none'], $lang_index['Users single'], $lang_index['Users plural']));

	($hook = get_hook('in_fl_pre_online_today_list')) ? eval($hook) : null;

?>
<div id="brd-today" class="gen-content index-stat">
	<h3 class="hn"><span><?php printf($lang_index['Online today'], forum_link($forum_url['statistic'], 'onlinetoday'), $forum_page['online_today']) ?></span></h3>
<?php if ($count_users > 0): ?>
	<p><?php echo implode($lang_index['Online list separator'], $users) ?></p>
<?php endif; ($hook = get_hook('in_new_today_data')) ? eval($hook) : null; ?>
</div>
<?php

}

($hook = get_hook('in_online_today_end')) ? eval($hook) : null;

//Рекорд онлайна
if ($forum_config['o_record'])
{
	$forum_page['most_online'] = array(
		'guests'	=> '<strong>'.forum_number_format($forum_config['c_max_guests']).'</strong> '.declination($forum_config['c_max_guests'], array($lang_common['Guests none'], $lang_common['Guests single'], $lang_common['Guests plural'])),
		'reg_users'	=> '<strong>'.forum_number_format($forum_config['c_max_users']).'</strong> '.declination($forum_config['c_max_users'], array($lang_index['Users none'], $lang_index['Users single'], $lang_index['Users plural'])),
		'users'		=> '<strong>'.forum_number_format($forum_config['c_max_total_users']).'</strong> '.declination($forum_config['c_max_total_users'], array($lang_index['All users none'], $lang_index['All users single'], $lang_index['All users plural']))
	);

	($hook = get_hook('in_fl_pre_most_online_list')) ? eval($hook) : null;

?>
<div id="brd-mostonline" class="gen-content index-stat">
	<h3 class="hn"><span><?php printf($lang_index['Most online'], implode($lang_index['Online list separator'], $forum_page['most_online'])) ?></span></h3>
<?php ($hook = get_hook('in_fl_pre_most_online_list')) ? eval($hook) : null; ?>
</div>
<?php

}

($hook = get_hook('in_fl_statistic_end')) ? eval($hook) : null;

//Ссылки на статистику
if ($forum_config['o_statistic'])
{
	$forum_page['stat'] = array(
		'top_author'	=> '<a href="'.forum_link($forum_url['statistic'], 'topauthor').'">'.$lang_index['Top author'].'</a>',
		'top_replies'	=> '<a href="'.forum_link($forum_url['statistic'], 'topreplies').'">'.$lang_index['Top replies'].'</a>',
		'top_views'		=> '<a href="'.forum_link($forum_url['statistic'], 'topviews').'">'.$lang_index['Top views'].'</a>',
		'bans'			=> '<a href="'.forum_link($forum_url['statistic'], 'bans').'">'.$lang_index['Bans'].'</a>',
	);

	($hook = get_hook('in_fl_pre_stat_list')) ? eval($hook) : null;

?>
<div id="brd-statlist" class="gen-content index-stat">
	<p><?php echo implode($lang_index['Online stats separator'], $forum_page['stat']) ?></p>
<?php ($hook = get_hook('in_fl_pre_stat_list')) ? eval($hook) : null; ?>
</div>
<?php

}

($hook = get_hook('in_info_end')) ? eval($hook) : null;

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_info -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_info -->


require FORUM_ROOT.'footer.php';
