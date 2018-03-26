<?php
/**
 * Просмотр сообщений в тем.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('vt_start')) ? eval($hook) : null;

if (!$forum_user['g_read_board'])
	message($lang_common['No view']);

// Load the viewtopic.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/topic.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/country.php';


$action = isset($_GET['action']) ? $_GET['action'] : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
if ($id < 1 && $pid < 1)
	message($lang_common['Bad request']);


// If a post ID is specified we determine topic ID and page number so we can redirect to the correct message
if ($pid)
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['post'], $pid));

	$query = array(
		'SELECT'	=> 'p.topic_id, p.posted',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.id='.$pid
	);

	($hook = get_hook('vt_qr_get_post_info')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if (!$forum_db->num_rows($result))
		message($lang_common['Bad request']);

	list($id, $posted) = $forum_db->fetch_row($result);

	// Determine on what page the post is located (depending on $forum_user['disp_posts'])
	$query = array(
		'SELECT'	=> 'COUNT(p.id)',
		'FROM'		=> 'posts AS p',
		'WHERE'		=> 'p.topic_id='.$id.' AND p.posted<'.$posted
	);

	($hook = get_hook('vt_qr_get_post_page')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$num_posts = $forum_db->result($result) + 1;

	$_GET['p'] = ceil($num_posts / $forum_user['disp_posts']);
}

// If action=new, we redirect to the first new post (if any)
else if ($action == 'new')
{
	if (!$forum_user['is_guest'])
	{
		// We need to check if this topic has been viewed recently by the user
		$tracked_topics = get_tracked_topics();
		$last_viewed = isset($tracked_topics['topics'][$id]) ? $tracked_topics['topics'][$id] : $forum_user['last_visit'];

		($hook = get_hook('vt_find_new_post')) ? eval($hook) : null;

		$query = array(
			'SELECT'	=> 'MIN(p.id)',
			'FROM'		=> 'posts AS p',
			'WHERE'		=> 'p.topic_id='.$id.' AND p.posted>'.$last_viewed
		);

		($hook = get_hook('vt_qr_get_first_new_post')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		$first_new_post_id = $forum_db->result($result);

		if ($first_new_post_id)
		{
			header('Location: '.str_replace('&amp;', '&', forum_link($forum_url['post'], $first_new_post_id)));
			exit;
		}
	}

	header('Location: '.str_replace('&amp;', '&', forum_link($forum_url['topic_last_post'], $id)));
	exit;
}

// If action=last, we redirect to the last post
else if ($action == 'last')
{
	$query = array(
		'SELECT'	=> 't.last_post_id',
		'FROM'		=> 'topics AS t',
		'WHERE'		=> 't.id='.$id
	);

	($hook = get_hook('vt_qr_get_last_post')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	$last_post_id = $forum_db->result($result);

	if ($last_post_id)
	{
		header('Location: '.str_replace('&amp;', '&', forum_link($forum_url['post'], $last_post_id)));
		exit;
	}
}


// Fetch some info about the topic
$query = array(
	'SELECT'	=> 'f.cat_id, c.cat_name, t.subject, t.description, t.poll, t.first_post_id, t.closed, t.num_replies, t.sticky, f.id AS forum_id, f.forum_name, f.moderators, fp.post_replies',
	'FROM'		=> 'topics AS t',
	'JOINS'		=> array(
		array(
			'INNER JOIN'		=> 'forums AS f',
			'ON'			=> 'f.id=t.forum_id'
		),
		array(
			'INNER JOIN'		=> 'categories AS c',
			'ON'			=> 'c.id=f.cat_id'
		),
		array(
			'LEFT JOIN'		=> 'forum_perms AS fp',
			'ON'			=> '(fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id'].')'
		)
	),
	'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND t.id='.$id.' AND t.moved_to IS NULL'
);

if (!$forum_user['is_guest'] && $forum_config['o_subscriptions'])
{
	$query['SELECT'] .= ', s.user_id AS is_subscribed';
	$query['JOINS'][] = array(
		'LEFT JOIN'	=> 'subscriptions AS s',
		'ON'		=> '(t.id=s.topic_id AND s.user_id='.$forum_user['id'].')'
	);
}

($hook = get_hook('vt_qr_get_topic_info')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
if (!$forum_db->num_rows($result))
	message($lang_common['Bad request']);

$cur_topic = $forum_db->fetch_assoc($result);

($hook = get_hook('vt_modify_topic_info')) ? eval($hook) : null;


if (!$forum_user['is_guest'] && $cur_topic['poll'])
{
	//Get info about poll
	$query = array(
		'SELECT'	=> 'q.question, q.read_unvote_users, q.revote, q.created, q.days_count, q.votes_count',
		'FROM'		=> 'questions AS q',
		'WHERE'		=> 'q.topic_id='.$id
	);

	($hook = get_hook('vt_qr_get_question')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$cur_poll = $forum_db->fetch_assoc($result);

	if ($forum_config['o_censoring'])
		$cur_poll['question'] = censor_words($cur_poll['question']);

	//Check up for condition of end poll
	if ($cur_poll['days_count'] != 0 && time() > $cur_poll['created'] + $cur_poll['days_count'] * 86400)
		$end_voting = true;
	else if ($cur_poll['votes_count'] != 0)
	{
		//Get count of votes
		$query = array(
			'SELECT'	=> 'COUNT(v.id)',
			'FROM'		=> 'voting AS v',
			'WHERE'		=> 'v.topic_id='.$id
		);

		($hook = get_hook('vt_qr_get_voting')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		if ($forum_db->num_rows($result) > 0)
			list($vote_count) = $forum_db->fetch_row($result);

		if (isset($vote_count) && $vote_count >= $cur_poll['votes_count'])
			$end_voting = true;
	}

	//Does user want to vote?
	if (isset($_POST['vote']))
	{
		if (isset($end_voting))
			message($lang_topic['End of vote']);
		if ($forum_user['num_posts'] <= $forum_config['p_poll_min_posts'])
			message($lang_topic['Poll min posts']);

		$answer_id = isset($_POST['answer']) ? intval($_POST['answer']) : 0;
		if ($answer_id < 1)
			message($lang_common['Bad request']);

		// Отвечал ли уже
		$query = array(
			'SELECT'	=> '1',
			'FROM'		=> 'answers',
			'WHERE'		=> 'topic_id='.$id.' AND id='.$answer_id
		);

		($hook = get_hook('vt_qr_get_answers_with_this_id')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		if ($forum_db->num_rows($result) < 1)
			message($lang_common['Bad request']);

		//Have user voted?
		$query = array(
			'SELECT'	=> 'v.answer_id',
			'FROM'		=> 'voting AS v',
			'WHERE'		=> 'v.topic_id='.$id.' AND v.user_id='.$forum_user['id']
		);

		($hook = get_hook('vt_qr_get_answers')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
		if (!$cur_poll['revote'] && $forum_db->num_rows($result) > 0)
			message($lang_topic['User vote error']);

		$now = time();

		//If user have voted we update table, if not - insert new record
		if ($cur_poll['revote'] && $forum_db->num_rows($result) > 0)
		{
			list($old_answer_id) = $forum_db->fetch_row($result);

			//Do we needed to update DB?
			if ($old_answer_id != $answer_id)
			{
				$query = array(
					'UPDATE'	=> 'voting',
					'SET'		=> 'answer_id='.$answer_id.', voted='.$now,
					'WHERE'		=> 'topic_id='.$id.' AND user_id='.$forum_user['id']
				);

				($hook = get_hook('vt_qr_get_update_voting')) ? eval($hook) : null;
				$forum_db->query_build($query) or error(__FILE__, __LINE__);

				//Replace old answer id with new for correct output
				$old_answer_id = $answer_id;
			}
		}
		else
		{
			//Add new record
			$query_pun_poll = array(
				'INSERT'	=> 'topic_id, user_id, answer_id, voted',
				'INTO'		=> 'voting',
				'VALUES'	=> $id.', '.$forum_user['id'].', '.$answer_id.', '.$now	
			);

			($hook = get_hook('vt_qr_get_insert_voting')) ? eval($hook) : null;
			$forum_db->query_build($query_pun_poll) or error(__FILE__, __LINE__);

			//Manually change votes count for correct results showing
			if (isset($vote_count))
				$vote_count++;
		}
		$is_voted_user = true;
	}
	else	
	{
		//Determine user have voted or not
		$query = array(
			'SELECT'	=> '1',
			'FROM'		=> 'voting',
			'WHERE'		=> 'user_id='.$forum_user['id'].' AND topic_id='.$id
		);

		($hook = get_hook('vt_qr_get_voted_or_not')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		$is_voted_user = ($forum_db->num_rows($result) > 0) ? true : false;
	}
}
else if ($forum_user['is_guest'] && $cur_topic['poll'])
{
	$query = array(
		'SELECT'	=> 'q.question, q.read_unvote_users',
		'FROM'		=> 'questions AS q',
		'WHERE'		=> 'q.topic_id='.$id
	);

	($hook = get_hook('vt_qr_get_question_guest')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$cur_poll = $forum_db->fetch_assoc($result);

	if ($forum_config['o_censoring'])
		$cur_poll['question']= censor_words($cur_poll['question']);

	$end_voting = true;
}

// Determine the post offset (based on $_GET['p'])
$forum_page['num_pages'] = ceil(($cur_topic['num_replies'] + 1) / $forum_user['disp_posts']);
$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
$forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);
$forum_page['finish_at'] = min(($forum_page['start_from'] + $forum_user['disp_posts']), ($cur_topic['num_replies'] + 1));
$forum_page['items_info'] =  generate_items_info($lang_topic['Posts'], ($forum_page['start_from'] + 1), ($cur_topic['num_replies'] + 1));

// Check for use of incorrect URLs
if (!$pid)
	confirm_current_url($forum_page['page'] == 1 ? forum_link($forum_url[($action == 'print' ? 'print' : 'topic')], array($id, sef_friendly($cur_topic['subject']))) : forum_sublink($forum_url['topic'], $forum_url['page'], $forum_page['page'], array($id, sef_friendly($cur_topic['subject']))));

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_topic['moderators'] != '') ? unserialize($cur_topic['moderators']) : array();
$forum_page['is_admmod'] = ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] && array_key_exists($forum_user['username'], $mods_array))) ? true : false;

// Can we or can we not post replies?
if (!$cur_topic['closed'] || $forum_page['is_admmod'])
	$forum_user['may_post'] = (($cur_topic['post_replies'] == '' && $forum_user['g_post_replies']) || $cur_topic['post_replies'] == '1' || $forum_page['is_admmod']) ? true : false;
else
	$forum_user['may_post'] = false;

// Add/update this topic in our list of tracked topics
if (!$forum_user['is_guest'])
{
	$tracked_topics = get_tracked_topics();
	$tracked_topics['topics'][$id] = time();
	set_tracked_topics($tracked_topics);
}

($hook = get_hook('vt_modify_page_details')) ? eval($hook) : null;

// Navigation links for header and page numbering for title/meta description
if ($forum_page['page'] < $forum_page['num_pages'])
{
	$forum_page['nav']['last'] = '<link rel="last" href="'.forum_sublink($forum_url['topic'], $forum_url['page'], $forum_page['num_pages'], array($id, sef_friendly($cur_topic['subject']))).'" title="'.$lang_common['Page'].' '.$forum_page['num_pages'].'" />';
	$forum_page['nav']['next'] = '<link rel="next" href="'.forum_sublink($forum_url['topic'], $forum_url['page'], ($forum_page['page'] + 1), array($id, sef_friendly($cur_topic['subject']))).'" title="'.$lang_common['Page'].' '.($forum_page['page'] + 1).'" />';
}
if ($forum_page['page'] > 1)
{
	$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_sublink($forum_url['topic'], $forum_url['page'], ($forum_page['page'] - 1), array($id, sef_friendly($cur_topic['subject']))).'" title="'.$lang_common['Page'].' '.($forum_page['page'] - 1).'" />';
	$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link($forum_url['topic'], array($id, sef_friendly($cur_topic['subject']))).'" title="'.$lang_common['Page'].' 1" />';
}

if ($forum_config['o_censoring'])
{
	$cur_topic['subject'] = censor_words($cur_topic['subject']);
	$cur_topic['description'] = censor_words($cur_topic['description']);
}

// Generate paging and posting links
$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['topic'], $lang_common['Paging separator'], array($id, sef_friendly($cur_topic['subject']))).'</p>';

if ($forum_user['may_post'])
	$forum_page['page_post']['posting'] = '<p class="posting"><a class="newpost" href="'.forum_link($forum_url['new_reply'], $id).'"><span>'.$lang_topic['Post reply'].'</span></a></p>';
else if ($forum_user['is_guest'])
	$forum_page['page_post']['posting'] = '<p class="posting">'.sprintf($lang_topic['Login to post'], '<a href="'.forum_link($forum_url['login']).'">'.$lang_common['login'].'</a>', '<a href="'.forum_link($forum_url['register']).'">'.$lang_common['register'].'</a>').'</p>';
else if ($cur_topic['closed'])
	$forum_page['page_post']['posting'] = '<p class="posting">'.$lang_topic['Topic closed info'].'</p>';
else
	$forum_page['page_post']['posting'] = '<p class="posting">'.$lang_topic['No permission'].'</p>';

// Setup main options
$forum_page['main_options_head'] = $lang_topic['Topic options'];
$forum_page['main_options'] = array();
$forum_page['main_options']['rss'] = '<span class="feed'.(empty($forum_page['main_options']) ? ' item1' : '').'"><a class="feed-rss" href="'.forum_link($forum_url['feed_topic'], array('rss', $id)).'">'.$lang_topic['RSS topic feed'].'</a></span>';
$forum_page['main_options']['atom'] = '<span class="feed'.(empty($forum_page['main_options']) ? ' item1' : '').'"><a class="feed-atom" href="'.forum_link($forum_url['feed_topic'], array('atom' ,$id)).'">'.$lang_topic['ATOM topic feed'].'</a></span>';


if (!$forum_user['is_guest'] && $forum_config['o_subscriptions'])
{
	if ($cur_topic['is_subscribed'])
		$forum_page['main_options']['unsubscribe'] = '<span><a class="unsub-option" href="'.forum_link($forum_url['unsubscribe'], array($id, generate_form_token('unsubscribe'.$id.$forum_user['id']))).'"><em>'.$lang_topic['Unsubscribe'].'</em></a></span>';
	else
		$forum_page['main_options']['subscribe'] = '<span><a class="sub-option" href="'.forum_link($forum_url['subscribe'], array($id, generate_form_token('subscribe'.$id.$forum_user['id']))).'" title="'.$lang_topic['Subscribe info'].'">'.$lang_topic['Subscribe'].'</a></span>';
}

if ($forum_page['is_admmod'])
{
	$forum_page['main_options']['move'] = '<span><a class="mod-option-move" href="'.forum_link($forum_url['move'], array($cur_topic['forum_id'], $id)).'">'.$lang_topic['Move'].'</a></span>';
	$forum_page['main_options']['delete'] = '<span><a class="mod-option-delete" href="'.forum_link($forum_url['delete'], $cur_topic['first_post_id']).'">'.$lang_topic['Delete topic'].'</a></span>';
	$forum_page['main_options']['closed'] = (($cur_topic['closed']) ? '<span><a class="mod-option-open" href="'.forum_link($forum_url['mod'], array('open', $cur_topic['forum_id'], $id, generate_form_token('open'.$id))).'">'.$lang_topic['Open'].'</a></span>' : '<span><a class="mod-option-closed" href="'.forum_link($forum_url['mod'], array('close', $cur_topic['forum_id'], $id, generate_form_token('close'.$id))).'">'.$lang_topic['Close'].'</a></span>');
	$forum_page['main_options']['sticky'] = (($cur_topic['sticky']) ? '<span><a class="mod-option-unstick" href="'.forum_link($forum_url['mod'], array('unstick', $cur_topic['forum_id'], $id, generate_form_token('unstick'.$id))).'">'.$lang_topic['Unstick'].'</a></span>' : '<span><a class="mod-option-sticky" href="'.forum_link($forum_url['mod'], array('stick', $cur_topic['forum_id'], $id, generate_form_token('stick'.$id))).'">'.$lang_topic['Stick'].'</a></span>');

	if ($cur_topic['num_replies'] != 0)
		$forum_page['main_options']['moderate_topic'] = '<span><a class="mod-option-moderate" href="'.forum_sublink($forum_url['moderate_topic'], $forum_url['page'], $forum_page['page'], array($cur_topic['forum_id'], $id)).'">'.$lang_topic['Moderate topic'].'</a></span>';
}

// Setup breadcrumbs
if (empty($cur_topic['description']))
	$subj = $cur_topic['subject'];
else
	$subj = $cur_topic['description'];

$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($cur_topic['cat_name'], forum_link($forum_url['category'], $cur_topic['cat_id'])),
	array($cur_topic['forum_name'], forum_link($forum_url['forum'], array($cur_topic['forum_id'], sef_friendly($cur_topic['forum_name'])))),
	array($cur_topic['subject'], forum_link($forum_url['topic'], array($id, sef_friendly($cur_topic['subject']))))
);

// Setup main heading
$forum_page['main_head'] = (($cur_topic['closed'] == '1') ? $lang_topic['Topic closed'].' ' : '').'<a class="permalink" href="'.forum_link($forum_url['topic'], array($id, sef_friendly($cur_topic['subject']))).'" rel="bookmark" title="'.$lang_topic['Permalink topic'].'">'.forum_htmlencode($subj).'</a>';

if ($forum_page['num_pages'] > 1)
	$forum_page['main_head_pages'] = sprintf($lang_common['Page info'], $forum_page['page'], $forum_page['num_pages']);

($hook = get_hook('vt_pre_header_load')) ? eval($hook) : null;

// Allow indexing if this is a permalink
if (!$pid)
	define('FORUM_ALLOW_INDEX', 1);

if ($forum_user['may_post'])
	$forum_js->addFile($base_url.'/include/js/quote.js');
$forum_js->addFile($base_url.'/include/js/jquery.js');
$forum_js->addFile($base_url.'/include/js/jquery.tooltip.js');
$forum_js->addCode('$(document).ready( function() {
	$(\'.spoiler-head\').toggle(
		function() {
		$(this).children().text(\''.$lang_common['Hide spoiler'].'\');
			$(this).next().show("slow");
		},
		function() {
			$(this).children().text(\''.$lang_common['Show spoiler'].'\');
			$(this).next().hide("slow");
		}
	);
	$(\'.hide-head\').toggle(
		function() {
		$(this).children().text(\''.$lang_common['Hidden text'].'\');
			$(this).next().show("slow");
		},
		function() {
			$(this).children().text(\''.$lang_common['Hidden show text'].'\');
			$(this).next().hide("slow");
		}
	);
	$(\'.p .postimg img, .popup\').tooltip({ track: true, delay: 0, showURL: false, showBody: " - ", fade: 250 });
	$(\'#block\').click($.tooltip.block);
	});');

if ($action == 'print')
	define('FORUM_PRINT', 1);
define('FORUM_PAGE', 'viewtopic');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

//Is there something to show?
if (isset($cur_poll['read_unvote_users']))
{
	// START SUBST - <!-- forum_main_rpe_pagepost -->
	ob_start();

	($hook = get_hook('vt_topic_pre_view_poll')) ? eval($hook) : null;

?>
<div class="main-head topic-poll">
	<h1 class="hn"><span><?php echo $lang_topic['Header note'] ?></span></h1>
</div>
	<div class="main-content main-frm view-poll">
<?php

	//Showing of vote-form if users can revote or user don't vote
	if ((!isset($end_voting) && (($is_voted_user && $cur_poll['revote']) || !$is_voted_user)) || $forum_user['is_guest'])
	{
		$query = array(
			'SELECT'	=> 'a.id, a.answer',
			'FROM'		=> 'answers AS a',
			'WHERE'		=> 'a.topic_id='.$id,
			'GROUP BY'	=> 'a.id'
		);

		($hook = get_hook('vt_qr_get_select_answers_form')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

		if ($forum_db->num_rows($result) > 1)
		{
			if (!$forum_user['is_guest'] && $forum_user['num_posts'] >= $forum_config['p_poll_min_posts'])
			{

?>
	<form class="frm-form" action="<?php echo forum_link($forum_url['topic'], array($id, sef_friendly($cur_topic['subject']))) ?>" accept-charset="utf-8" method="post">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link($forum_url['topic'], array($id, sef_friendly($cur_topic['subject'])))) ?>" />
		</div>
<?php

			}

?>
		<div class="ct-box info-box">
			<p><?php echo forum_htmlencode($cur_poll['question']) ?></p>
		</div>
		<fieldset class="frm-group group1">
			<fieldset class="mf-set set1">
				<legend><span><?php echo $lang_topic['Options'] ?></span></legend>
				<div class="mf-box">
<?php

			($hook = get_hook('vt_topic_pre_topic_list_answer')) ? eval($hook) : null;

			if (!$forum_user['is_guest'] && $forum_user['num_posts'] >= $forum_config['p_poll_min_posts'])
			{
				$num = 0;
				while ($answer = $forum_db->fetch_assoc($result))
				{
					($hook = get_hook('vt_topic_list_answer_start')) ? eval($hook) : null;

					if ($forum_config['o_censoring'])
						$answer['answer'] = censor_words($answer['answer']);

					$vote['input'] = '<span class="fld-input"><input id="fld'.++$num.'" type="radio"'.((isset($old_answer_id) && $old_answer_id == $answer['id']) ? ' checked="checked"' : '').' value="'.$answer['id'].'" name="answer"/></span>';
					$vote['answer'] = '<label for="fld'.$num.'">'.forum_htmlencode($answer['answer']).'</label>';

?>
					<div class="mf-item">
						<?php echo implode('', $vote)."\n" ?>
					</div>
<?php

				}
			}
			else
			{
?>
					<ul class="info-list">
<?php

				while ($answer = $forum_db->fetch_assoc($result))
				{
					($hook = get_hook('vt_topic_list_answer_guest_start')) ? eval($hook) : null;

					if ($forum_config['o_censoring'])
						$answer['answer'] = censor_words($answer['answer']);

					$vote['answer'] = '<li><span class="label">'.forum_htmlencode($answer['answer']).'</span></li>';
					echo implode('', $vote)."\n";
				}

?>
					</ul>
<?php

			}

?>
				</div>
			</fieldset>
		</fieldset>
<?php

			($hook = get_hook('vt_topic_pre_poll_buttons')) ? eval($hook) : null;

			if (!$forum_user['is_guest'] && $forum_user['num_posts'] >= $forum_config['p_poll_min_posts'])
			{

?>
		<div class="frm-buttons">
			<span class="submit"><input type="submit" value="<?php echo $lang_topic['But note'] ?>" name="vote" /></span>
		</div>
	</form>
<?php
			}
			else if ($forum_user['is_guest'])
			{
?>
		<div class="ct-box info-box">
			<p><?php echo $lang_topic['Poll guest'] ?></p>
		</div>
<?php

			}
			else if ($forum_user['num_posts'] <= $forum_config['p_poll_min_posts'])
			{
?>
		<div class="ct-box info-box">
			<p><?php echo $lang_topic['Poll min posts'] ?></p>
		</div>
<?php

			}
		}
	}

	($hook = get_hook('vt_topic_pre_poll_voting')) ? eval($hook) : null;

	if (!$forum_user['is_guest'])
	{
		if ((isset($end_voting) || $is_voted_user || (!$is_voted_user && $cur_poll['read_unvote_users'])))
		{
			//If we don't get count of votes
			if (!isset($vote_count))
			{
				$query = array(
					'SELECT'	=> 'COUNT(v.id)',
					'FROM'		=> 'voting AS v',
					'WHERE'		=> 'v.topic_id='.$id
				);

				($hook = get_hook('vt_qr_get_count_vote')) ? eval($hook) : null;
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
				if ($forum_db->num_rows($result) > 0)
					list($vote_count) = $forum_db->fetch_row($result);
			}

			if ($vote_count > 0)
			{

?>
		<div class="ct-box info-box">
			<p><?php echo sprintf($lang_topic['Results'], forum_htmlencode($cur_poll['question'])) ?></p>
		</div>
			<div class="ct-group">
				<table cellspacing="0">
				<thead>
					<th class="tc0" scope="col">&nbsp;</th>
					<th class="tc1" scope="col">&nbsp;</th>
					<th class="tc2" scope="col">&nbsp;</th>
				</thead>
				<tbody>
<?php

				$query = array(
					'SELECT'	=> 'a.answer, COUNT(v.id)',
					'FROM'		=> 'answers AS a',
					'JOINS'		=> array(
						array(
							'LEFT JOIN'	=> 'voting AS v',
							'ON'		=> 'a.id=v.answer_id'
						)
					),
					'WHERE'		=> 'a.topic_id='.$id,
					'GROUP BY'	=> 'a.id',
					'ORDER BY'	=> 'a.id'
				);

				($hook = get_hook('vt_qr_get_select_answers')) ? eval($hook) : null;
				$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

				$num = 0;
				while (list($answer, $vote) = $forum_db->fetch_row($result))
				{
					($hook = get_hook('vt_topic_list_answer_voting_start')) ? eval($hook) : null;

					if ($forum_config['o_censoring'])
						$answer = censor_words($answer);

					$vote_answers['answers'] = '<td class="tc0">'.forum_htmlencode($answer).'</td>';
					$vote_answers['count'] = '<td class="tc1"><h1 class="count-poll" style="width: '.forum_number_format((float)$vote/$vote_count * 100, 2).'%;"/></td>';
					$vote_answers['percent'] = '<td class="tc2">'.forum_number_format((float)$vote/$vote_count * 100, 2).'% — '.forum_number_format($vote).'</td>';
					$num++;

?>
					<tr class="<?php echo ($num % 2 == 0 ? 'even' : 'odd') ?>">
						<?php echo implode("\n\t\t\t\t\t\t", $vote_answers)."\n" ?>
					</tr>
<?php

				}

?>

				</tbody>
				</table>
			</div>
		<div class="ct-box info-box">
			<p><?php echo sprintf($lang_topic['All votes'], forum_number_format($vote_count)) ?></p>
		</div>
<?php

			}
			else
			{

?>
		<div class="ct-box info-box">
			<p><?php echo $lang_topic['No votes'] ?></p>
		</div>
<?php

			}
		}
		else
		{

?>
		<div class="ct-box info-box">
			<p><?php echo $lang_topic['Dis read vote'] ?></p>
		</div>
<?php
	
		}
	}

	($hook = get_hook('vt_topic_pre_poll_admmod')) ? eval($hook) : null;

	if ($forum_user['is_admmod'])
	{

?>
		<div class="ct-box info-box">
			<p><a href="<?php echo forum_link($forum_url['poll'], array($id, sef_friendly($cur_poll['question']))) ?>"><?php echo $lang_topic['Edit vote'] ?></a></p>
		</div>
<?php

	}

?>
	</div>
<?

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main_rpe_pagepost -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main_rpe_pagepost -->
}


($hook = get_hook('vt_main_output_start')) ? eval($hook) : null;

?>
	<div class="main-pagehead">
		<h2 class="hn"><span><?php echo $forum_page['items_info'] ?></span></h2>
	</div>
	<div id="forum<?php echo $cur_topic['forum_id'] ?>" class="main-content main-topic">
<?php

if (!defined('FORUM_PARSER_LOADED'))
	require FORUM_ROOT.'include/parser.php';

$forum_page['item_count'] = 0; // Keep track of post numbers

// Retrieve the posts (and their respective poster/online status)
$query = array(
	'SELECT'	=> 'u.email, u.title, u.sex, u.country, u.signature, u.email_setting, u.num_posts, u.admin_note, u.user_agent, u.rep_plus, u.rep_minus, u.rep_enable, p.id, p.poster AS username, p.poster_id, p.poster_ip, p.poster_email, p.message, p.hide_smilies, p.posted, p.edited, p.edited_by, g.g_id, g.g_user_title, o.user_id AS is_online',
	'FROM'		=> 'posts AS p',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'users AS u',
			'ON'		=> 'u.id=p.poster_id'
		),
		array(
			'INNER JOIN'	=> 'groups AS g',
			'ON'		=> 'g.g_id=u.group_id'
		),
		array(
			'LEFT JOIN'	=> 'online AS o',
			'ON'		=> '(o.user_id=u.id AND o.user_id!=1 AND o.idle=0)'
		),
	),
	'WHERE'		=> 'p.topic_id='.$id,
	'ORDER BY'	=> 'p.id',
	'LIMIT'		=> $forum_page['start_from'].','.$forum_user['disp_posts']
);

if ($forum_config['o_show_user_info'])
	$query['SELECT'] .= ', u.location, u.registered, u.timezone, u.url, u.jabber, u.icq, u.msn, u.yahoo, u.magent, u.vkontakte, u.classmates, u.mirtesen, u.moikrug';
if ($forum_config['o_report_enabled'])
	$query['SELECT'] .= ', p.reported';

($hook = get_hook('vt_qr_get_posts')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

$user_data_cache = array();
while ($cur_post = $forum_db->fetch_assoc($result))
{
	($hook = get_hook('vt_post_loop_start')) ? eval($hook) : null;

	++$forum_page['item_count'];

	$forum_page['post_ident'] = array();
	$forum_page['author_ident'] = array();
	$forum_page['author_info'] = array();
	$forum_page['post_options'] = array();
	$forum_page['post_contacts'] = array();
	$forum_page['post_identity'] = array();
	$forum_page['post_actions'] = array();
	$forum_page['message'] = array();

	// Generate the post heading
	$forum_page['post_ident']['num'] = '<span class="post-num">'.forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span>';

	if ($cur_post['poster_id'] > 1)
		$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == $cur_topic['first_post_id']) ? $lang_topic['Topic byline'] : $lang_topic['Reply byline']), (($forum_user['g_view_users']) ? '<a title="'.sprintf($lang_topic['Go to profile'], forum_htmlencode($cur_post['username'])).'" href="'.forum_link($forum_url['user'], $cur_post['poster_id']).'">'.forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.forum_htmlencode($cur_post['username']).'</strong>')).'</span>';
	else
		$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($cur_post['id'] == $cur_topic['first_post_id']) ? $lang_topic['Topic byline'] : $lang_topic['Reply byline']), '<strong>'.forum_htmlencode($cur_post['username']).'</strong>').'</span>';

	$forum_page['post_ident']['link'] = '<span class="post-link"><a class="permalink" rel="bookmark" title="'.$lang_topic['Permalink post'].'" href="'.forum_link($forum_url['post'], $cur_post['id']).'">'.format_time($cur_post['posted']).$lang_common['Title separator'].flazy_format_time($cur_post['posted']).'</a></span>';

	($hook = get_hook('vt_row_pre_post_ident_merge')) ? eval($hook) : null;

	if (isset($user_data_cache[$cur_post['poster_id']]['author_ident']))
		$forum_page['author_ident'] = $user_data_cache[$cur_post['poster_id']]['author_ident'];
	else
	{
		// Generate author identification
		if ($cur_post['poster_id'] > 1)
		{
			if ($forum_config['o_avatars'] && $forum_user['show_avatars'])
			{
				if (!defined('FORUM_FUNCTIONS_GENERATE_AVATAR'))
					require FORUM_ROOT.'include/functions/generate_avatar_markup.php';

				$forum_page['avatar_markup'] = generate_avatar_markup($cur_post['poster_id']);
		
				if (!empty($forum_page['avatar_markup']))
					$forum_page['author_ident']['avatar'] = '<li class="useravatar">'.$forum_page['avatar_markup'].'</li>';
			}

			if ($cur_post['sex'] == '0')
				$sex_pic = '';
			else if ($cur_post['sex'] == '1')
				$sex_pic = ' <img class="popup" src="'.$base_url.'/img/style/male.png" width="16" height="16" alt="" title="'.$lang_topic['Sex'].' - '.$lang_topic['Male'].'"/>';
			else if ($cur_post['sex'] == '2')
				$sex_pic = ' <img class="popup" src="'.$base_url.'/img/style/female.png" width="16" height="16" alt="" title="'.$lang_topic['Sex'].' - '.$lang_topic['Female'].'"/>';

			$country = ($cur_post['country'] != '') ? ' <img class="popup" src="'.$base_url.'/img/flags/'.$cur_post['country'].'" title="'.$lang_topic['Country'].' - '.$lang_country[$cur_post['country']].'" alt=""/>' : '';

			$forum_page['author_ident']['picture'] = '<li class="picture">'.$sex_pic.' '.$country.($forum_config['o_show_ua_info'] ? ' '.useragent_info($cur_post['user_agent']) : '').'</li>';

			$forum_page['author_ident']['username'] = '<li class="username">'.(($forum_user['g_view_users']) ? '<a title="'.sprintf($lang_topic['Go to profile'], forum_htmlencode($cur_post['username'])).'" href="'.forum_link($forum_url['user'], $cur_post['poster_id']).'">'.forum_htmlencode($cur_post['username']).'</a>' : '<strong>'.forum_htmlencode($cur_post['username']).'</strong>').'</li>';
			$forum_page['author_ident']['usertitle'] = '<li class="usertitle"><span>'.get_title($cur_post).'</span></li>';

			if ($cur_post['is_online'] == $cur_post['poster_id'])
				$forum_page['author_ident']['status'] = '<li class="userstatus"><span>'.$lang_topic['Online'].'</span></li>';
			else
				$forum_page['author_ident']['status'] = '<li class="userstatus"><span>'.$lang_topic['Offline'].'</span></li>';
		}
		else
		{
			$forum_page['author_ident']['username'] = '<li class="username"><strong>'.forum_htmlencode($cur_post['username']).'</strong></li>';
			$forum_page['author_ident']['usertitle'] = '<li class="usertitle"><span>'.get_title($cur_post).'</span></li>';
		}
	}

	if (isset($user_data_cache[$cur_post['poster_id']]['author_info']))
		$forum_page['author_info'] = $user_data_cache[$cur_post['poster_id']]['author_info'];
	else
	{
		// Generate author information
		if ($cur_post['poster_id'] > 1)
		{
			if ($forum_config['o_show_user_info'])
			{
				if ($cur_post['location'] != '')
				{
					if ($forum_config['o_censoring'])
						$cur_post['location'] = censor_words($cur_post['location']);

					$forum_page['author_info']['from'] = '<li><span>'.$lang_topic['From'].' <strong> '.forum_htmlencode($cur_post['location']).'</strong></span></li>';
				}

				$forum_page['author_info']['registered'] = '<li><span>'.$lang_topic['Registered'].' <strong> '.format_time($cur_post['registered'], 1).'</strong></span></li>';

				// Разница во времени
				$time_dif = $forum_user['timezone'] - $cur_post['timezone'];
				if ($time_dif != 0 && $forum_user['id'] > 1)
					$forum_page['author_info']['timezone'] = '<li><span><strong>'.$lang_topic['Timezone'].' </strong>'.$time_dif.' '.$lang_topic['From yours'].'</span></li>';
			}

			if ($forum_config['o_show_post_count'] || $forum_user['is_admmod'])
				$forum_page['author_info']['posts'] = '<li><span>'.$lang_topic['Posts info'].' <strong><a href="'.forum_link($forum_url['search_user_posts'], $cur_post['poster_id']).'">'.forum_number_format($cur_post['num_posts']).'</a></strong></span></li>';

			if ($forum_config['o_rep_enabled'] && $forum_user['rep_enable'] && $forum_user['rep_enable_adm'] && $forum_user['g_rep_enable'] && $cur_post['rep_enable'] && $cur_post['poster_id'] != 1)
			{
				if (!$forum_user['is_guest'] && $forum_user['username'] != $cur_post['username'])
					$vote = '<a href="'.forum_link($forum_url['reputation_change'], array($cur_post['id'], $cur_post['poster_id'], 1)).'"><img src="'.$base_url.'/img/style/plus.gif" alt="+" /></a>  <strong>'.forum_number_format($cur_post['rep_plus'] - $cur_post['rep_minus']).'</strong>  <a href="'.forum_link($forum_url['reputation_change'], array($cur_post['id'], $cur_post['poster_id'], 2)).'"><img src="'.$base_url.'/img/style/minus.gif" alt="-" /></a>';
				else
					$vote = '<strong>'.forum_number_format($cur_post['rep_plus'] - $cur_post['rep_minus']).'</strong>';	

				$forum_page['author_info']['reputation'] = '<li><span><a href="'.forum_link($forum_url['reputation'], $cur_post['poster_id']).'">'.$lang_topic['Reputation'].'</a>: '.$vote.'</span></li>';
			}

			if ($forum_user['is_admmod'])
			{
				if ($cur_post['admin_note'] != '')
					$forum_page['author_info']['note'] = '<li><span>'.$lang_topic['Note'].' <strong> '.forum_htmlencode($cur_post['admin_note']).'</strong></span></li>';
			}
		}
	}

	// Создание инфорции об IP для модераторов/администраторов
	if ($forum_user['is_admmod'])
		$forum_page['author_info']['ip'] = '<li><span>'.$lang_topic['IP'].' <a href="'.forum_link($forum_url['get_host'], forum_htmlencode($cur_post['id'])).'">'.forum_htmlencode($cur_post['poster_ip']).'</a> <a href="'.forum_link('click.php').'?http://www.ripe.net/whois?form_type=simple&amp;full_query_string=&amp;searchtext='.forum_htmlencode($cur_post['poster_ip']).'&amp;do_search=Search" onclick="window.open(this.href); return false">Whois</a></span></li>';

	// Создать контактную информацию об авторе
	if ($forum_config['o_show_user_info'])
	{
		if (isset($user_data_cache[$cur_post['poster_id']]['post_contacts']))
			$forum_page['post_contacts'] = $user_data_cache[$cur_post['poster_id']]['post_contacts'];
		else
		{
			if ($cur_post['poster_id'] > 1)
			{
				if ($cur_post['url'] != '')
					$forum_page['post_contacts']['url'] = '<span class="user-url'.(empty($forum_page['post_contacts']) ? ' item1' : '').'"><a class="external" href="'.forum_link('click.php').'?'.forum_htmlencode(($forum_config['o_censoring']) ? censor_words($cur_post['url']) : $cur_post['url']).'" onclick="window.open(this.href); return false" rel="nofollow">'.sprintf($lang_topic['Visit website'], '<span>'.sprintf($lang_topic['User possessive'], forum_htmlencode($cur_post['username'])).'</span>').'</a></span>';
				if ((($cur_post['email_setting'] == '0' && !$forum_user['is_guest']) || $forum_user['is_admmod']) && $forum_user['g_send_email'])
					$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' item1' : '').'"><a href="mailto:'.forum_htmlencode($cur_post['email']).'">'.$lang_topic['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></a></span>';
				else if ($cur_post['email_setting'] == '1' && !$forum_user['is_guest'] && $forum_user['g_send_email'])
					$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' item1' : '').'"><a href="'.forum_link($forum_url['email'], $cur_post['poster_id']).'">'.$lang_topic['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></a></span>';
				if (!$forum_user['is_guest'] && $forum_user['id'] != $cur_post['poster_id'])
					$forum_page['post_contacts']['pm'] = '<span class="user-pm'.(empty($forum_page['post_contacts']) ? ' item1' : '').'"><a href="'.forum_link($forum_url['pm_post_link'], array($forum_user['id'], $cur_post['poster_id'])).'" title="'.$lang_topic['Send PM'].'">'.$lang_topic['PM'].'</a></span>';
			}
			else
			{
				if ($cur_post['poster_email'] != '' && !$forum_user['is_guest'] && $forum_user['g_send_email'])
					$forum_page['post_contacts']['email'] = '<span class="user-email'.(empty($forum_page['post_contacts']) ? ' item1' : '').'"><a href="mailto:'.forum_htmlencode($cur_post['poster_email']).'">'.$lang_topic['E-mail'].'<span>&#160;'.forum_htmlencode($cur_post['username']).'</span></a></span>';
			}
		}

		($hook = get_hook('vt_row_pre_post_contacts_merge')) ? eval($hook) : null;

		if (!empty($forum_page['post_contacts']))
			$forum_page['post_options']['contacts'] = '<p class="post-contacts">'.implode(' ', $forum_page['post_contacts']).'</p>';

		if (isset($user_data_cache[$cur_post['poster_id']]['post_identity']))
			$forum_page['post_identity'] = $user_data_cache[$cur_post['poster_id']]['post_identity'];
		else
		{
			if ($cur_post['poster_id'] > 1 && !$forum_user['is_guest'])
			{
				if ($cur_post['jabber'] != '')
					$forum_page['post_identity']['jabber'] = '<a href="xmpp:'.forum_htmlencode($cur_post['jabber']).'"><img src="'.$base_url.'/img/style/p_jabber.png" /></a>';
				if ($cur_post['icq'] != '')
					$forum_page['post_identity']['icq'] = '<a href="'.forum_link('click.php').'?http://icq.com/people/about_me.php?uin='.forum_htmlencode($cur_post['icq']).'" onclick="window.open(this.href); return false" rel="nofollow"><img src="'.$base_url.'/img/style/p_icq.png" /></a>';
				if ($cur_post['msn'] != '')
					$forum_page['post_identity']['msn'] = '<a href="'.forum_link('click.php').'?http://members.msn.com/'.forum_htmlencode($cur_post['msn']).'" onclick="window.open(this.href); return false" rel="nofollow"><img src="'.$base_url.'/img/style/p_msn.png" /></a>';
				if ($cur_post['yahoo'] != '')
					$forum_page['post_identity']['yahoo'] = '<a href="'.forum_link('click.php').'?http://edit.yahoo.com/config/send_webmesg?.target='.forum_htmlencode($cur_post['yahoo']).'" onclick="window.open(this.href); return false" rel="nofollow"><img src="'.$base_url.'/img/style/p_yahoo.png" /></a>';
				if ($cur_post['magent'] != '')
					$forum_page['post_identity']['magent'] = '<a href="'.forum_link('click.php').'?http://mail.ru/agent?message&amp;to='.forum_htmlencode($cur_post['magent']).'" onclick="window.open(this.href); return false" rel="nofollow"><img src="'.$base_url.'/img/style/p_magent.png" /></a>';
				if ($cur_post['vkontakte'] != '')
					$forum_page['post_identity']['vkontakte'] = '<a href="'.forum_link('click.php').'?http://vkontakte.ru/id'.forum_htmlencode($cur_post['vkontakte']).'" onclick="window.open(this.href); return false" rel="nofollow"><img src="'.$base_url.'/img/style/p_vk.png" /></a>';
				if ($cur_post['classmates'] != '')
					$forum_page['post_identity']['classmates'] = '<a href="'.forum_link('click.php').'?'.forum_htmlencode($cur_post['classmates']).'" onclick="window.open(this.href); return false" rel="nofollow"><img src="'.$base_url.'/img/style/p_odkl.png" /></a>';
				if ($cur_post['mirtesen'] != '')
					$forum_page['post_identity']['mirtesen'] = '<a href="'.forum_link('click.php').'?http://mirtesen.ru/people/'.forum_htmlencode($cur_post['mirtesen']).'" onclick="window.open(this.href); return false" rel="nofollow"><img src="'.$base_url.'/img/style/p_mirtesen.png" /></a>';
				if ($cur_post['moikrug'] != '')
					$forum_page['post_identity']['moikrug'] = '<a href="'.forum_link('click.php').'?http://'.forum_htmlencode($cur_post['moikrug']).'.moikrug.ru/" onclick="window.open(this.href); return false" rel="nofollow"><img src="'.$base_url.'/img/style/p_moikrug.png" /></a>';
			}
		}

		($hook = get_hook('vt_row_pre_post_identity_merge')) ? eval($hook) : null;

		if (!empty($forum_page['post_identity']))
			$forum_page['post_options']['identity'] = '<p class="post-identity">'.implode(' ', $forum_page['post_identity']).'</p>';
	}

	// Generate the post options links
	if (!$forum_user['is_guest'])
	{
		if ($forum_config['o_report_enabled'])
			$forum_page['post_actions']['report'] = '<span class="report-post'.(empty($forum_page['post_actions']) ? ' item1' : '').'"><a href="'.forum_link($forum_url['report'], $cur_post['id']).'">'.$lang_topic['Report'].'<span> '.$lang_topic['Post'].' '.forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a>'.(($cur_post['reported']) ? '<span class="warn"><strong>'.$lang_topic['Already reported'].'</strong></span>' : '' ).'</span>';

		if (!$forum_page['is_admmod'])
		{
			if (!$cur_topic['closed'])
			{
				if ($cur_post['poster_id'] == $forum_user['id'])
				{
					if (($forum_page['start_from'] + $forum_page['item_count']) == 1 && $forum_user['g_delete_topics'])
						$forum_page['post_actions']['delete'] = '<span class="delete-topic'.(empty($forum_page['post_actions']) ? ' item1' : '').'"><a href="'.forum_link($forum_url['delete'], $cur_topic['first_post_id']).'">'.$lang_topic['Delete topic'].'</a></span>';
					if (($forum_page['start_from'] + $forum_page['item_count']) > 1 && $forum_user['g_delete_posts'])
						$forum_page['post_actions']['delete'] = '<span class="delete-post'.(empty($forum_page['post_actions']) ? ' item1' : '').'"><a href="'.forum_link($forum_url['delete'], $cur_post['id']).'">'.$lang_topic['Delete'].'<span> '.$lang_topic['Post'].' '.forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
					if ($forum_user['g_edit_posts'])
						$forum_page['post_actions']['edit'] = '<span class="edit-post'.(empty($forum_page['post_actions']) ? ' item1' : '').'"><a href="'.forum_link($forum_url['edit'], $cur_post['id']).'">'.$lang_topic['Edit'].'<span> '.$lang_topic['Post'].' '.forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
				}

				if (($cur_topic['post_replies'] == '' && $forum_user['g_post_replies']) || $cur_topic['post_replies'] == '1')
					$forum_page['post_actions']['reply'] = '<span class="reply-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="javascript:Reply('.$id. ','.$cur_post['id'].')">'.$lang_topic['Reply'].'<span>&#160;'.$lang_topic['Post'].' '.($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
					$forum_page['post_actions']['quote'] = '<span class="quote-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="'.forum_link($forum_url['quote'], array($id, $cur_post['id'])).'" onClick="QuickQuote('.$id. ','.$cur_post['id'].'); return false;">'.$lang_topic['Quote'].'<span>&#160;'.$lang_topic['Post'].' '.($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
			}
		}
		else
		{
			if (($forum_page['start_from'] + $forum_page['item_count']) == 1)
				$forum_page['post_actions']['delete'] = '<span class="delete-topic'.(empty($forum_page['post_actions']) ? ' item1' : '').'"><a href="'.forum_link($forum_url['delete'], $cur_topic['first_post_id']).'">'.$lang_topic['Delete topic'].'</a></span>';
			else
				$forum_page['post_actions']['delete'] = '<span class="delete-post'.(empty($forum_page['post_actions']) ? ' item1' : '').'"><a href="'.forum_link($forum_url['delete'], $cur_post['id']).'">'.$lang_topic['Delete'].'<span> '.$lang_topic['Post'].' '.forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';

			$forum_page['post_actions']['edit'] = '<span class="edit-post'.(empty($forum_page['post_actions']) ? ' item1' : '').'"><a href="'.forum_link($forum_url['edit'], $cur_post['id']).'">'.$lang_topic['Edit'].'<span> '.$lang_topic['Post'].' '.forum_number_format($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
			$forum_page['post_actions']['reply'] = '<span class="reply-post first-item"><a href="javascript:Reply('.$id. ','.$cur_post['id'].')">'.$lang_topic['Reply'].'<span>&#160;'.$lang_topic['Post'].' '.($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
			$forum_page['post_actions']['quote'] = '<span class="quote-post first-item"><a href="'.forum_link($forum_url['quote'], array($id, $cur_post['id'])).'" onClick="QuickQuote('.$id. ','.$cur_post['id'].'); return false;">'.$lang_topic['Quote'].'<span>&#160;'.$lang_topic['Post'].' '.($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
		}
	}
	else
	{
		if (!$cur_topic['closed'])
		{
			if (($cur_topic['post_replies'] == '' && $forum_user['g_post_replies']) || $cur_topic['post_replies'] == '1')
				$forum_page['post_actions']['reply'] = '<span class=""quote-post'.(empty($forum_page['post_actions']) ? ' first-item' : '').'"><a href="javascript:Reply('.$id. ','.$cur_post['id'].')">'.$lang_topic['Quote'].'<span>&#160;'.$lang_topic['Post'].' '.($forum_page['start_from'] + $forum_page['item_count']).'</span></a></span>';
		}
	}

	($hook = get_hook('vt_row_pre_post_actions_merge')) ? eval($hook) : null;


	if (!empty($forum_page['post_actions']))
		$forum_page['post_options']['actions'] = '<p class="post-actions">'.implode(' ', $forum_page['post_actions']).'</p>';

	// Give the post some class
	$forum_page['item_status'] = array(
		'post',
		($forum_page['item_count'] % 2 == 0) ? 'even' : 'odd'
	);

	if ($forum_page['item_count'] == 1)
		$forum_page['item_status']['firstpost'] = 'firstpost';

	if (($forum_page['start_from'] + $forum_page['item_count']) == $forum_page['finish_at'])
		$forum_page['item_status']['lastpost'] = 'lastpost';

	if ($cur_post['id'] == $cur_topic['first_post_id'])
		$forum_page['item_status']['topicpost'] = 'topicpost';
	else
		$forum_page['item_status']['replypost'] = 'replypost';


	// Generate the post title
	if ($cur_post['id'] == $cur_topic['first_post_id'])
		$forum_page['item_subject'] = sprintf($lang_topic['Topic title'], $cur_topic['subject']);
	else
		$forum_page['item_subject'] = sprintf($lang_topic['Reply title'], $cur_topic['subject']);

	$forum_page['item_subject'] = forum_htmlencode($forum_page['item_subject']);

	// Perform the main parsing of the message (BBCode, smilies, censor words etc)
	$forum_page['message']['message'] = parse_message($cur_post['message'], $cur_post['hide_smilies']);

	if ($cur_post['edited'] != '')
		$forum_page['message']['edited'] = '<p class="lastedit"><em>'.sprintf($lang_topic['Last edited'], forum_htmlencode($cur_post['edited_by']), format_time($cur_post['edited'])).'</em></p>';

	// Do signature parsing/caching
	if ($cur_post['signature'] != '' && $forum_user['show_sig'] && $forum_config['o_signatures'])
	{
		if (!isset($signature_cache[$cur_post['poster_id']]))
			$signature_cache[$cur_post['poster_id']] = parse_signature($cur_post['signature']);

		$forum_page['message']['signature'] = '<div class="sig-content"><span class="sig-line"><!-- --></span>'.$signature_cache[$cur_post['poster_id']].'</div>';
	}

	($hook = get_hook('vt_row_pre_display')) ? eval($hook) : null;

	// Do user data caching for the post
	if ($cur_post['poster_id'] > 1 && !isset($user_data_cache[$cur_post['poster_id']]))
	{
		$user_data_cache[$cur_post['poster_id']] = array(
			'author_ident'	=> $forum_page['author_ident'],
			'author_info'	=> $forum_page['author_info'],
			'post_contacts'	=> $forum_page['post_contacts'],
			'post_identity' => $forum_page['post_identity']
		);

		($hook = get_hook('vt_row_add_user_data_cache')) ? eval($hook) : null;
	}

?>
		<div id="p<?php echo $cur_post['id'] ?>" class="<?php echo implode(' ', $forum_page['item_status']) ?>">
			<div class="posthead">
				<h3 class="hn post-ident"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
			</div>
			<div class="postbody<?php echo ($cur_post['is_online'] == $cur_post['poster_id']) ? ' online' : '' ?>">
				<div class="post-author">
					<ul class="author-ident">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['author_ident'])."\n" ?>
					</ul>
					<ul class="author-info">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['author_info'])."\n" ?>
					</ul>
				</div>
				<div class="post-entry">
					<h4 class="entry-title hn"><?php echo $forum_page['item_subject'] ?></h4>
					<div class="entry-content">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['message'])."\n" ?>
					</div>
<?php ($hook = get_hook('vt_row_new_post_entry_data')) ? eval($hook) : null; ?>
				</div>
			</div>
<?php if (!empty($forum_page['post_options'])): ?>
			<div class="postfoot">
				<div class="post-options">
					<?php echo implode("\n\t\t\t\t\t", $forum_page['post_options'])."\n" ?>
				</div>
			</div>
<?php endif; ?>
		</div>
		<form action="post.php" method="post" id="qq">
			<div class="hidden">
				<input type="hidden" value="" id="post_msg" name="post_msg"/>
				<input type="hidden" value="<?php echo forum_link($forum_url['quote'], array($id, $cur_post['id'])) ?>" id="quote_url" name="quote_url" />
			</div>
		</form>
<?php

	if ($forum_page['item_count'] == $forum_config['o_topicbox'] && !defined('FORUM_DISABLE_HTML'))
	{
?>
		<div class="post">
			<div class="post-entry">
				<div class="entry-content">
					<?php echo $forum_config['o_topicbox_message'] ?>
				</div>
			</div>
		</div>

<?php

	}
}

?>
	</div>
<?php

if ($forum_config['o_online_ft'])
{
	if (!defined('FORUM_FUNCTIONS_ONLINE_FT'))
		require FORUM_ROOT.'include/functions/online_user.php';

	$forum_page['online'] = '<p class="user-on"><span class="pages">'.$lang_topic['Users on'].'</span> '.online_user($id, 'viewtopic').'</p>';
}
	// START SUBST - <!-- forum_main_online -->
	ob_start();

?>
<div id="brd-online-box" class="main-pagepost gen-content">
	<?php if (!empty($forum_page['online'])): echo $forum_page['online']."\n"; endif ?>
	<p class="posting"><a href="<?php echo forum_link($forum_url['print'], array($id, sef_friendly($cur_topic['subject']))) ?>"><span><?php echo $lang_topic['Print'] ?></span></a></p>
</div>
<?php

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main_online -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main_online -->

($hook = get_hook('vt_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->


// Display quick post if enabled
if ($forum_config['o_quickpost'] && 
	!$forum_user['is_guest'] &&
	($cur_topic['post_replies'] == '1' || ($cur_topic['post_replies'] == '' && $forum_user['g_post_replies'])) &&
	(!$cur_topic['closed'] || $forum_page['is_admmod']))
{

// START SUBST - <!-- forum_qpost -->
ob_start();

($hook = get_hook('vt_qpost_output_start')) ? eval($hook) : null;

// Setup form
$forum_page['form_action'] = forum_link($forum_url['new_reply'], $id);
$forum_page['form_attributes'] = array();

$forum_page['hidden_fields'] = array(
	'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
	'form_user'		=> '<input type="hidden" name="form_user" value="'.((!$forum_user['is_guest']) ? forum_htmlencode($forum_user['username']) : 'Гость').'" />',
	'csrf_token'		=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
);

if ($forum_config['o_merge_timeout'])
	$forum_page['hidden_fields']['merge'] = '<input type="hidden" name="merge" value="1" />';

if ($forum_config['o_subscriptions'] && ($forum_user['auto_notify'] == '1' || $cur_topic['is_subscribed']))
	$forum_page['hidden_fields']['subscribe'] = '<input type="hidden" name="subscribe" value="1" />';

// Setup help
$forum_page['main_head_options'] = array();
if ($forum_config['p_message_bbcode'])
	$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
if ($forum_config['p_message_img_tag'])
	$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
if ($forum_config['o_smilies'])
	$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';

($hook = get_hook('vt_quickpost_pre_display')) ? eval($hook) : null;

?>
<div class="main-subhead">
	<h2 class="hn"><span><?php echo $lang_topic['Quick post'] ?></span></h2>
</div>
<div id="brd-qpost" class="main-content main-frm">
<?php if (!empty($forum_page['text_options'])) echo "\t".'<p class="content-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n" ?>
	<div id="req-msg" class="req-warn ct-box error-box">
		<p class="important"><?php echo $lang_topic['Required warn'] ?></p>
	</div>
	<form class="frm-form" name="post" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>"<?php if (!empty($forum_page['form_attributes'])) echo ' '.implode(' ', $forum_page['form_attributes']) ?>>
		<div class="hidden">
			<?php echo implode("\n\t\t\t", $forum_page['hidden_fields'])."\n" ?>
		</div>
<?php ($hook = get_hook('vt_quickpost_pre_fieldset')) ? eval($hook) : null; ?>
		<fieldset class="frm-group group1">
			<legend class="group-legend"><strong><?php echo $lang_common['Write message legend'] ?></strong></legend>
<?php ($hook = get_hook('vt_quickpost_pre_message_box')) ? eval($hook) : null; ?>
			<div class="txt-set set1">
				<div class="txt-box textarea required">
					<label for="fld1"><span><?php echo $lang_common['Write message'] ?></span></label>
<?php require FORUM_ROOT.'bb.php'; ?>
					<div class="txt-input"><span class="fld-input"><textarea id="fld1" class="inputbox" name="req_message"  rows="7" cols="95"></textarea></span></div>
				</div>
			</div>
<?php ($hook = get_hook('vt_quickpost_pre_fieldset_end')) ? eval($hook) : null; ?>
		</fieldset>
<?php ($hook = get_hook('vt_quickpost_fieldset_end')) ? eval($hook) : null; ?>
		<div class="frm-buttons">
			<span class="submit"><input type="submit" name="submit" value="<?php echo $lang_common['Submit'] ?>" /></span>
			<span class="submit"><input type="submit" name="preview" value="<?php echo $lang_common['Preview'] ?>" /></span>
		</div>
	</form>
</div>
<?php

($hook = get_hook('vt_quickpost_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_qpost -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_qpost -->

}

// Increment "num_views" for topic
if ($forum_config['o_topic_views'])
{
	$query = array(
		'UPDATE'	=> 'topics',
		'SET'		=> 'num_views=num_views+1',
		'WHERE'		=> 'id='.$id,
	);

	($hook = get_hook('vt_qr_increment_num_views')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
}

$forum_id = $cur_topic['forum_id'];

require FORUM_ROOT.'footer.php';
