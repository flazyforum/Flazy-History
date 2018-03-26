<?php
/**
 * Редактирование сообщений.
 *
 * Изменяет содержание указанного сообщения.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('ed_start')) ? eval($hook) : null;

if (!$forum_user['g_read_board'])
	message($lang_common['No view']);

// Load the post.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/post.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id < 1)
	message($lang_common['Bad request']);

// Check for use of incorrect URLs
confirm_current_url(forum_link($forum_url['edit'], $id));

// Fetch some info about the post, the topic and the forum
$query = array(
	'SELECT'	=> 'f.id AS fid, f.forum_name, f.moderators, f.redirect_url, fp.post_replies, fp.post_topics, t.id AS tid, t.subject, t.description, t.poll, t.posted, t.first_post_id, t.closed, p.poster, p.poster_id, p.message, p.hide_smilies, p.posted AS time_posted, u.email',
	'FROM'		=> 'posts AS p',
	'JOINS'		=> array(
		array(
			'INNER JOIN'	=> 'topics AS t',
			'ON'		=> 't.id=p.topic_id'
		),
		array(
			'INNER JOIN'	=> 'forums AS f',
			'ON'		=> 'f.id=t.forum_id'
		),
		array(
			'LEFT JOIN'	=> 'forum_perms AS fp',
			'ON'		=> 'fp.forum_id=f.id AND fp.group_id='.$forum_user['g_id']
		),
		array(
			'LEFT JOIN'	=> 'users AS u',
			'ON'		=> 'u.id=p.poster_id'
		)
	),
	'WHERE'		=> '(fp.read_forum IS NULL OR fp.read_forum=1) AND p.id='.$id
);

($hook = get_hook('ed_qr_get_post_info')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
if (!$forum_db->num_rows($result))
	message($lang_common['Bad request']);

$cur_post = $forum_db->fetch_assoc($result);

// Sort out who the moderators are and if we are currently a moderator (or an admin)
$mods_array = ($cur_post['moderators'] != '') ? unserialize($cur_post['moderators']) : array();
$forum_page['is_admmod'] = ($forum_user['g_id'] == FORUM_ADMIN || ($forum_user['g_moderator'] == '1' && array_key_exists($forum_user['username'], $mods_array))) ? true : false;

($hook = get_hook('ed_pre_permission_check')) ? eval($hook) : null;

// Do we have permission to edit this post?
if ((!$forum_user['g_edit_posts'] ||
	$cur_post['poster_id'] != $forum_user['id'] ||
	$cur_post['closed'] == '1') &&
	!$forum_page['is_admmod'])
	message($lang_common['No permission']);


$can_edit_subject = $id == $cur_post['first_post_id'];

($hook = get_hook('ed_post_selected')) ? eval($hook) : null;

// Start with a clean slate
$errors = array();
$poll = false;

if ($cur_post['poll'])
{
	//Get info about poll
	$query = array(
		'SELECT'	=> 'q.question, q.read_unvote_users, q.revote, q.days_count, q.votes_count',
		'FROM'		=> 'questions AS q',
		'WHERE'		=> 'q.topic_id='.$cur_post['tid']
	);

	($hook = get_hook('ed_qr_get_questions')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$cur_poll = $forum_db->fetch_assoc($result);
	$poll = true;

	//User didn't edit smth
	$query = array(
		'SELECT'	=> 'a.id, a.answer',
		'FROM'		=> 'answers AS a',
		'WHERE'		=> 'a.topic_id='.$cur_post['tid']
	);

	($hook = get_hook('ed_qr_get_answers')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	
	$answers = array();
	$num_rows = $forum_db->num_rows($result);
	if ($num_rows > 0)
	{
		while ($row = $forum_db->fetch_assoc($result))
			$answers[] = $row['answer'];
		$options_count = $num_rows;
	}
}


//User press update poll
if (isset($_POST['update_poll']))
{
	($hook = get_hook('ed_form_update_poll')) ? eval($hook) : null;

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['edit'], $id));

	unset($_POST['form_sent']);

	$subject = forum_trim($_POST['req_subject']);
	$description = forum_trim($_POST['desc']);
	$message = forum_linebreaks(forum_trim($_POST['req_message']));
	$question_poll = forum_trim($_POST['question']);
	$answers = array_values( $_POST['answer']);
	$days = intval($_POST['days']);
	$votes = intval($_POST['votes']);

	($hook = get_hook('ed_form_update_poll_post')) ? eval($hook) : null;

	$options_count = (!isset($_POST['ans_count']) || intval($_POST['ans_count']) < 2) ? 0 : intval($_POST['ans_count']);

	if ($options_count == 0)
	{
		$errors[] = $lang_post['Min count options'];
		$options_count = 2;
	}
	if ($options_count > $forum_config['p_poll_max_answers'])
	{
		$errors[] = sprintf($lang_post['Max count options'], $forum_config['p_poll_max_answers']);
		$options_count =  $forum_config['p_poll_max_answers'];
	}
}
else
	$options_count = 2;


if (isset($_POST['form_sent']))
{
	($hook = get_hook('ed_form_submitted')) ? eval($hook) : null;

	// If it is a topic it must contain a subject
	if ($can_edit_subject)
	{
		$subject = forum_trim($_POST['req_subject']);

		if ($subject == '')
			$errors[] = $lang_post['No subject'];
		else if (utf8_strlen($subject) > 70)
			$errors[] = $lang_post['Too long subject'];
		else if (!$forum_config['p_subject_all_caps'] && is_all_uppercase($subject) && !$forum_page['is_admmod'])
			$subject = utf8_ucwords(utf8_strtolower($subject));

		$description = forum_trim($_POST['desc']);

		if (utf8_strlen($description) > 100)
			$errors[] = $lang_post['Too long description'];
		else if (!$forum_config['p_subject_all_caps'] && is_all_uppercase($description) && !$forum_page['is_admmod'])
			$description = utf8_ucwords(utf8_strtolower($description));

		if (($forum_user['g_id'] == FORUM_ADMIN || $forum_user['username'] == $cur_post['poster'])&& $poll)
		{
			$question_poll = (isset($_POST['question'])) ? forum_trim($_POST['question']) : '';
			$answers = (isset($_POST['answer']) && !empty($_POST['answer']) && is_array($_POST['answer'])) ? array_values( $_POST['answer']) : null;
			$days = isset($_POST['days']) && !empty($_POST['days']) ? intval($_POST['days']) : 0;
			$votes = isset($_POST['votes']) && !empty($_POST['votes']) ? intval($_POST['votes']) : 0;

			if (!empty($_POST['question']) && utf8_strlen($question_poll) < 6)
				$errors[] = $lang_post['Poll question info'];
			if ($answers == null && !empty($_POST['answer']))
				message($lang_post['Bad request']);
			if ($days > 90 || $days < 0)
				$errors[] = $lang_post['Days limit'];
			if ($votes != 0 && ($votes > 1000 || $votes < 10))
				$errors[] = $lang_post['Votes count'];
			if (!empty($_POST['question']) && $days < 0 && $votes == 0)
				$errors[] = $lang_post['Input error'];
		}
	}

	// Clean up message from POST
	$message = forum_linebreaks(forum_trim($_POST['req_message']));

	if (utf8_strlen($message) > FORUM_MAX_POSTSIZE)
		$errors[] = $lang_post['Too long message'];
	else if ($forum_config['p_message_all_caps'] && is_all_uppercase($message) && !$forum_page['is_admmod'])
		$message = utf8_ucwords(utf8_strtolower($message));

	// Validate BBCode syntax
	if ($forum_config['p_message_bbcode'] || $forum_config['o_make_links'])
	{
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		$message = preparse_bbcode($message, $errors);
	}

	if ($message == '')
		$errors[] = $lang_post['No message'];

	$hide_smilies = isset($_POST['hide_smilies']) ? 1 : 0;

	($hook = get_hook('ed_end_validation')) ? eval($hook) : null;

	// Did everything go according to plan?
	if (empty($errors) && !isset($_POST['preview']))
	{
		($hook = get_hook('ed_pre_post_edited')) ? eval($hook) : null;

		if ($can_edit_subject && !$forum_user['is_guest'])
		{
			//Is there something to edit?
			if ($poll)
			{
				$reset_poll = (isset($_POST['reset_poll']) && $_POST['reset_poll'] == '1') ? true : false;
				$remove_poll = (isset($_POST['remove_poll']) && $_POST['remove_poll'] == '1') ? true : false;

				//We need to reset poll
				if ($reset_poll || $remove_poll)
				{
					//Remove voting results
					$query = array(
						'DELETE'	=> 'voting',
						'WHERE'		=> 'topic_id='.$cur_post['tid']
					);

					($hook = get_hook('ed_qr_get_delete_voting')) ? eval($hook) : null;
					$forum_db->query_build($query) or error(__FILE__, __LINE__);
					if ($remove_poll)
					{
						//Remove questions
						$query = array(
							'DELETE'	=> 'questions',
							'WHERE'		=> 'topic_id='.$cur_post['tid']
						);

						($hook = get_hook('ed_qr_get_delete_questions')) ? eval($hook) : null;
						$forum_db->query_build($query) or error(__FILE__, __LINE__);

						//Remove answers
						$query = array(
							'DELETE'	=> 'answers',
							'WHERE'		=> 'topic_id='.$cur_post['tid']
						);

						($hook = get_hook('ed_qr_get_delete_answers')) ? eval($hook) : null;
						$forum_db->query_build($query) or error(__FILE__, __LINE__);

						//Remove poll
						$query = array(
							'UPDATE'	=> 'topics',
							'SET'		=> 'poll=\'NULL\'',
							'WHERE'		=> 'id='.$cur_post['tid']
						);

						($hook = get_hook('ed_qr_get_delete_poll')) ? eval($hook) : null;
						$forum_db->query_build($query) or error(__FILE__, __LINE__);
					}
				}
				else if ($forum_user['g_id'] == FORUM_ADMIN || $forum_user['username'] == $cur_post['poster'])
				{
					$count_answers = count($answers);
					for ($ans_num = 0; $ans_num < $count_answers; $ans_num++)
					{
						 $ans = forum_trim($answers[$ans_num]);
						 if (!empty($ans))
							$answ[] = $ans;
					}

					//Determine how many new
					$query = array(
						'SELECT'	=> 'id',
						'FROM'		=> 'answers',
						'WHERE'		=> 'topic_id='.$cur_post['tid']
					);

					($hook = get_hook('ed_qr_get_answers_id')) ? eval($hook) : null;
					$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
					$ans_ids = array();
					while (list($ans_id) = $forum_db->fetch_row($result))
						$ans_ids[] = $ans_id;

					$count_answ = count($answ);
					for ($ans_num = 0; $ans_num < $count_answ; $ans_num++)
					{
						//Old answer
						if (isset($ans_ids[$ans_num]))
						{
							$query = array(
								'UPDATE'	=> 'answers',
								'SET'		=> 'answer=\''.$forum_db->escape($answ[$ans_num]).'\'',
								'WHERE'		=> 'id='.$ans_ids[$ans_num]
							);

							($hook = get_hook('ed_qr_get_update_answers')) ? eval($hook) : null;
							$forum_db->query_build($query) or error(__FILE__, __LINE__);
						}
						else
						{
							//New answer
							$query = array(
								'INSERT'	=> 'topic_id, answer',
								'INTO'		=> 'answers',
								'VALUES'	=> $cur_post['tid'].', \''.$forum_db->escape($answers[$ans_num]).'\''
							);

							($hook = get_hook('ed_qr_get_new_answers')) ? eval($hook) : null;
							$forum_db->query_build($query) or error(__FILE__, __LINE__);
						}
					}

					//Remove answers
					if (count($ans_ids) > count($answ))
					{
						$ids = implode(',', array_slice($ans_ids, count($answ)));
						$query = array(
							'DELETE'	=> 'answers',
							'WHERE'		=> 'id IN('.$ids .')'
						);

						($hook = get_hook('ed_qr_get_remove_answers')) ? eval($hook) : null;
						$forum_db->query_build($query) or error(__FILE__, __LINE__);
	
						$query = array(
							'DELETE'	=> 'voting',
							'WHERE'		=> 'answer_id IN('.$ids.')'
						);

						($hook = get_hook('ed_qr_get_remove_voting')) ? eval($hook) : null;
						$forum_db->query_build($query) or error(__FILE__, __LINE__);
					}

					//Update question if needed
					$question_form = (isset($_POST['question'])) ? forum_trim($_POST['question']) : null;
					if ($question_form == null)
						message($lang_post['Empty question']);

					$changes = array();
					if ($cur_poll['question'] != $question_form)
						$changes[] = 'question = \''.$forum_db->escape($question_form).'\'';

					$read_unvote_users_form = ($forum_config['p_poll_enable_read'] && isset($_POST['read_unvote_users']) && $_POST['read_unvote_users'] == 1) ? 1 : 0;
					$days = ($days == '') ? 0 : $days;
					$vote = ($votes == '') ? 0 : $votes;
					$revote_form = ($forum_config['p_poll_enable_revote'] && isset($_POST['revouting']) && $_POST['revouting'] == 1) ? 1 : 0;

					if ($cur_poll['read_unvote_users'] != $read_unvote_users_form)
						$changes[] = 'read_unvote_users='.$read_unvote_users_form;
					if ($days != $cur_poll['days_count'])
						$changes[] = 'days_count='.$days;
					if ($votes != $cur_poll['votes_count'])
						$changes[] = 'votes_count='.$votes;
					if ($revote_form != $cur_poll['revote'])
						$changes[] = 'revote='.$revote_form;

					if (!empty($changes))
					{
						$query = array(
							'UPDATE'	=> 'questions',
							'SET'		=> implode(',', $changes),
							'WHERE'		=> 'topic_id='.$cur_post['tid']
						);

						($hook = get_hook('ed_qr_get_update_questions')) ? eval($hook) : null;
						$forum_db->query_build($query) or error(__FILE__, __LINE__);
					}
				}
			}
			else
			{
				//Is there something to add?
				if (!empty($question_poll))
				{
					//Validate of pull_answers
					$answ = array();
					if ($answers != null)
					{
						$count_answers = count($answers);
						for ($ans_num = 0; $ans_num < $count_answers; $ans_num++)
						{
							 $ans = forum_trim($answers[$ans_num]);
							 if (!empty($ans))
								$answ[] = $ans;
						}
						if (!empty($answ))
							$answ = array_unique($answ);
					}
					if (!empty($answ) && count($answ) > 1)
					{
						//Can unvoted user read voting results?
						$read_unvote_users = ($forum_config['p_poll_enable_read'] && isset($_POST['read_unvote_users']) && $_POST['read_unvote_users'] == 1) ? 1 : 0;
						//Can user change opinion?
						$revote = ($forum_config['p_poll_enable_revote'] && isset($_POST['revouting']) && $_POST['revouting'] == 1) ? 1 : 0;

						if ($days == null)
							$days = '\'NULL\'';
						if ($votes == null)
							$votes = '\'NULL\'';

						$query = array(
							'INSERT'	=> 'topic_id, question, read_unvote_users, revote, created, days_count, votes_count',
							'INTO'		=> 'questions',
							'VALUES'	=> $cur_post['tid'].', \''.$forum_db->escape($question_poll).'\', '.$read_unvote_users.', '.$revote.', '.time().', '.$days.', '.$votes
						);

						($hook = get_hook('ed_qr_get_insert_questions')) ? eval($hook) : null;
						$forum_db->query_build($query) or error(__FILE__, __LINE__);

						//Add answers to DB
						foreach ($answ as $ans)
						{
							$query = array(
								'INSERT'	=> 'topic_id, answer',
								'INTO'		=> 'answers',
								'VALUES'	=> $cur_post['tid'].', \''.$forum_db->escape($ans).'\''
							);

							($hook = get_hook('ed_qr_get_insert_add_questions')) ? eval($hook) : null;
							$forum_db->query_build($query) or error(__FILE__, __LINE__);
						}
					}
				}
			}
		}

		if (!defined('FORUM_SEARCH_IDX_FUNCTIONS_LOADED'))
			require FORUM_ROOT.'include/search_idx.php';

		if ($can_edit_subject)
		{
			// Update the topic and any redirect topics
			$query = array(
				'UPDATE'	=> 'topics',
				'SET'		=> 'subject=\''.$forum_db->escape($subject).'\', description=\''.$forum_db->escape($description).'\'',
				'WHERE'		=> 'id='.$cur_post['tid'].' OR moved_to='.$cur_post['tid']
			);

			if (!empty($answ) && count($answ) > 1)
				$query['SET'] .= ', poll=\'1\'';

			($hook = get_hook('ed_qr_update_subject')) ? eval($hook) : null;
			$forum_db->query_build($query) or error(__FILE__, __LINE__);

			// We changed the subject, so we need to take that into account when we update the search words
			update_search_index('edit', $id, $message, $subject, $description);
		}
		else
			update_search_index('edit', $id, $message);

		// Update the post
		$query = array(
			'UPDATE'	=> 'posts',
			'SET'		=> 'message=\''.$forum_db->escape($message).'\', hide_smilies=\''.$hide_smilies.'\'',
			'WHERE'		=> 'id='.$id
		);

		if (!isset($_POST['silent']) || !$forum_page['is_admmod'])
			$query['SET'] .= ', edited='.time().', edited_by=\''.$forum_db->escape($forum_user['username']).'\'';

		if ($cur_post['time_posted'] + $forum_config['o_post_edit'] > time() && !$forum_page['is_admmod'])
			$query['SET'] = 'message=\''.$forum_db->escape($message).'\', hide_smilies=\''.$hide_smilies.'\'';

		($hook = get_hook('ed_qr_update_post')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		if (isset($_POST['mail_send']) && $cur_post['poster_id'] != $forum_user['id'] && $cur_post['poster_id'] > 1)
		{
			$mail_tpl = forum_trim(file_get_contents(FORUM_ROOT.'lang/'.$forum_user['language'].'/mail_templates/edit_post.tpl'));

			// The first row contains the subject
			$first_crlf = strpos($mail_tpl, "\n");
			$mail_subject = forum_trim(substr($mail_tpl, 8, $first_crlf-8));
			$mail_message = forum_trim(substr($mail_tpl, $first_crlf));

			$mail_subject = str_replace('<topic_subject>', $cur_post['subject'], $mail_subject);
			$mail_message = str_replace('<topic_subject>', $cur_post['subject'], $mail_message);
			$mail_message = str_replace('<edit_user>', $forum_user['username'], $mail_message);
			$mail_message = str_replace('<post_url>', forum_link($forum_url['post'], $id), $mail_message);
			$mail_message = str_replace('<board_mailer>', sprintf($lang_common['Forum mailer'], $forum_config['o_board_title']), $mail_message);

			if (!defined('FORUM_EMAIL_FUNCTIONS_LOADED'))
				require FORUM_ROOT.'include/functions/email.php';

			forum_mail($cur_post['email'], $mail_subject, $mail_message);
		}

		($hook = get_hook('ed_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link($forum_url['post'], $id), $lang_post['Edit redirect']);
	}
}

// Setup error messages
if (!empty($errors))
{
	$forum_page['errors'] = array();

	foreach ($errors as $cur_error)
		$forum_page['errors'][] = '<li><span>'.$cur_error.'</span></li>';
}

// Setup form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
$forum_page['form_action'] = forum_link($forum_url['edit'], $id);
$forum_page['form_attributes'] = array();

$forum_page['hidden_fields'] = array(
	'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
	'csrf_token'		=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
);

// Setup help
$forum_page['main_head_options'] = array();
if ($forum_config['p_message_bbcode'])
	$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
if ($forum_config['p_message_img_tag'])
	$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
if ($forum_config['o_smilies'])
	$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';


// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($cur_post['forum_name'], forum_link($forum_url['forum'], array($cur_post['fid'], sef_friendly($cur_post['forum_name'])))),
	array($cur_post['subject'], forum_link($forum_url['topic'], array($cur_post['tid'], sef_friendly($cur_post['subject'])))),
	(($id == $cur_post['first_post_id']) ? $lang_post['Edit topic'] : $lang_post['Edit reply'])
);

($hook = get_hook('ed_pre_header_load')) ? eval($hook) : null;

define('FORUM_PAGE', 'postedit');
require FORUM_ROOT.'header.php';

// START SUBST - <!-- forum_main -->
ob_start();

($hook = get_hook('ed_main_output_start')) ? eval($hook) : null;


// If preview selected and there are no errors
if (isset($_POST['preview']) && empty($forum_page['errors']))
{
	if (!defined('FORUM_PARSER_LOADED'))
		require FORUM_ROOT.'include/parser.php';

	// Generate the post heading
	$forum_page['post_ident'] = array();
	$forum_page['post_ident']['num'] = '<span class="post-num">#</span>';
	$forum_page['post_ident']['byline'] = '<span class="post-byline">'.sprintf((($id == $cur_post['first_post_id']) ? $lang_post['Topic byline'] : $lang_post['Reply byline']), '<strong>'.forum_htmlencode($cur_post['poster']).'</strong>').'</span>';
	$forum_page['post_ident']['link'] = '<span class="post-link">'.format_time(time()).'</span>';

	$forum_page['preview_message'] = parse_message($message, $hide_smilies);

	($hook = get_hook('ed_preview_pre_display')) ? eval($hook) : null;

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo $id == $cur_post['first_post_id'] ? $lang_post['Preview edited topic'] : $lang_post['Preview edited reply'] ?></span></h2>
	</div>
	<div id="post-preview" class="main-content main-frm">
		<div class="post singlepost">
			<div class="posthead">
				<h3 class="hn"><?php echo implode(' ', $forum_page['post_ident']) ?></h3>
<?php ($hook = get_hook('ed_preview_new_post_head_option')) ? eval($hook) : null; ?>
			</div>
			<div class="postbody">
				<div class="post-entry">
					<div class="entry-content">
						<?php echo $forum_page['preview_message']."\n" ?>
					</div>
<?php ($hook = get_hook('ed_preview_new_post_entry_data')) ? eval($hook) : null; ?>
				</div>
			</div>
		</div>
	</div>
<?php

}

?>
	<div class="main-subhead">
		<h2 class="hn"><span><?php echo ($id != $cur_post['first_post_id']) ? $lang_post['Compose edited reply'] : $lang_post['Compose edited topic'] ?></span></h2>
	</div>
	<div id="post-form" class="main-content main-frm">
<?php

	if (!empty($forum_page['text_options']))
		echo "\t\t".'<p class="ct-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";

// If there were any errors, show them
if (isset($forum_page['errors']))
{

?>
		<div class="ct-box error-box">
			<h2 class="warn hn"><span><?php echo $lang_post['Post errors'] ?></span></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

}

?>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p><?php printf($lang_common['Required warn'], '<em>'.$lang_common['Required'].'</em>') ?></p>
		</div>
		<form id="afocus" class="frm-form" name="post" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>"<?php if (!empty($forum_page['form_attributes'])) echo ' '.implode(' ', $forum_page['form_attributes']) ?>>
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
<?php ($hook = get_hook('ed_pre_main_fieldset')) ? eval($hook) : null; ?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_post['Edit post legend'] ?></strong></legend>
<?php ($hook = get_hook('ed_pre_subject')) ? eval($hook) : null; ?>
<?php if ($can_edit_subject): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++ $forum_page['fld_count'] ?>"><span><?php echo $lang_post['Topic subject'] ?><em><?php echo $lang_common['Required'] ?></em></span></label>
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="req_subject" size="80" maxlength="70" value="<?php echo forum_htmlencode(isset($_POST['req_subject']) ? $_POST['req_subject'] : $cur_post['subject']) ?>" class="inputbox"/></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required">
						<label for="fld<?php echo ++ $forum_page['fld_count'] ?>"><span><?php echo $lang_post['Topic description'] ?><em></em></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="desc" size="80" maxlength="100" class="inputbox" value="<?php echo forum_htmlencode(isset($_POST['req_desc']) ? $_POST['desc'] : $cur_post['description']) ?>" /></span>
					</div>
				</div>
<?php endif; ($hook = get_hook('ed_pre_message_box')) ? eval($hook) : null; ?>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++ $forum_page['fld_count'] ?>"><span><?php echo $lang_post['Write message'] ?>  <em><?php echo $lang_common['Required'] ?></em></span></label>
<?php require FORUM_ROOT.'bb.php'; ?>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" class="inputbox" name="req_message" rows="14" cols="95"><?php echo forum_htmlencode(isset($_POST['req_message']) ? $message : $cur_post['message']) ?></textarea></span></div>
					</div>
				</div>
<?php

$forum_page['checkboxes'] = array();
if ($forum_config['o_smilies'])
{
	if (isset($_POST['hide_smilies']) || $cur_post['hide_smilies'])
		$forum_page['checkboxes']['hide_smilies'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="hide_smilies" value="1" checked="checked" /></span> <label for="fld'.$forum_page['fld_count'].'">'.$lang_post['Hide smilies'].'</label></div>';
	else
		$forum_page['checkboxes']['hide_smilies'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="hide_smilies" value="1" /></span> <label for="fld'.$forum_page['fld_count'].'">'.$lang_post['Hide smilies'].'</label></div>';
}

if ($forum_page['is_admmod'])
{
	if ((isset($_POST['form_sent']) && isset($_POST['silent'])) || !isset($_POST['form_sent']))
		$forum_page['checkboxes']['silent'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="silent" value="1" checked="checked" /></span> <label for="fld'.$forum_page['fld_count'].'">'.$lang_post['Silent edit'].'</label></div>';
	else
		$forum_page['checkboxes']['silent'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="silent" value="1" /></span> <label for="fld'.$forum_page['fld_count'].'">'.$lang_post['Silent edit'].'</label></div>';

	if ($cur_post['poster_id'] != $forum_user['id'])
		$forum_page['checkboxes']['mail_send'] = '<div class="mf-item"><span class="fld-input"><input type="checkbox" id="fld'.(++$forum_page['fld_count']).'" name="mail_send" value="1" checked="checked" /></span> <label for="fld'.$forum_page['fld_count'].'">'.$lang_post['Mail send'].'</label></div>';
}

($hook = get_hook('ed_pre_checkbox_display')) ? eval($hook) : null;

if (!empty($forum_page['checkboxes']))
{

?>
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
					<legend><span><?php echo $lang_post['Post settings'] ?></span></legend>
					<div class="mf-box checkbox">
						<?php echo implode("\n\t\t\t\t\t\t", $forum_page['checkboxes'])."\n"; ?>
					</div>
<?php ($hook = get_hook('ed_pre_checkbox_fieldset_end')) ? eval($hook) : null; ?>
				</fieldset>
<?php

}

($hook = get_hook('ed_pre_main_fieldset_end')) ? eval($hook) : null;

?>
			</fieldset>
<?php

($hook = get_hook('ed_main_fieldset_end')) ? eval($hook) : null;

if ($can_edit_subject)
{
	require FORUM_ROOT.'include/functions/form_poll.php';

	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	//Is there something?
	if ($poll)
	{

		if ($forum_user['g_id'] == FORUM_ADMIN || $forum_user['username'] == $cur_post['poster'])
		{

?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<div class="sf-set set1">
					<div class="sf-box checkbox">
						<span class="fld-input"><input id="fld<?php echo ++ $forum_page['fld_count'] ?>" type="checkbox" value="1" name="reset_poll"/></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_post['Reset res'] ?></span><?php echo $lang_post['Reset res info'] ?></label>
						</div>
				</div>
				<div class="sf-set set2">
					<div class="sf-box checkbox">
						<span class="fld-input"><input id="fld<?php echo ++ $forum_page['fld_count'] ?>" type="checkbox" value="1" name="remove_poll"/></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_post['Remove'] ?></span><?php echo $lang_post['Remove info'] ?></label></div>
					</div>
			</fieldset>
<?php

		}

		if ($forum_user['g_id'] == FORUM_ADMIN || $forum_user['username'] == $cur_post['poster'])
			form_poll(isset($_POST['question']) ? $_POST['question'] : $cur_poll['question'], $answers, $options_count, (!isset($days)) ? $cur_poll['days_count'] : $days, (!isset($votes)) ? (($cur_poll['votes_count'] == 0) ? '' : $cur_poll['votes_count']) : $votes);
	}
	else
		form_poll(isset($_POST['question']) ? $_POST['question'] : '', (isset($answers) && $answers != null) ? $answers : array(), isset($options_count) ? $options_count : 2, (isset($days)) ? $days : '', (isset($votes) && $votes != null) ? $votes : '');
}
?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="submit" value="<?php echo ($id != $cur_post['first_post_id']) ? $lang_post['Submit reply'] : $lang_post['Submit topic'] ?>" /></span>
				<span class="submit"><input type="submit" name="preview" value="<?php echo ($id != $cur_post['first_post_id']) ? $lang_post['Preview reply'] : $lang_post['Preview topic'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

$forum_id = $cur_post['fid'];

($hook = get_hook('ed_end')) ? eval($hook) : null;

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';
