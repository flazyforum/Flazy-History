<?php
/**
 * Скрипт для просмотра и редактирования результатов голосования.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('vp_start')) ? eval($hook) : null;

if (!$forum_user['is_admmod'])
	message($lang_common['No permission']);

$id = isset($_GET['id']) ? $_GET['id'] : null;
if ($id < 1)
	message($lang_common['Bad request']);

// Load the reputation.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/poll.php';

$query = array(
	'SELECT'	=> 'q.question, t.id AS topic_id, t.subject',
	'FROM'		=> 'questions AS q',
	'JOINS'		=> array(
		array(
			'INNER JOIN'		=> 'topics AS t',
			'ON'			=> 't.id=.q.topic_id'
		)
	),
	'WHERE'		=> 'q.topic_id='.$id
);

($hook = get_hook('vp_qr_get_question')) ? eval($hook) : null;
$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

if (!$forum_db->num_rows($result))
	message($lang_common['Bad request']);

$cur_topic = $forum_db->fetch_assoc($result);

if (isset($_POST['delete']))
{
	($hook = get_hook('vp_form_delete')) ? eval($hook) : null;

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['poll'], array($id, sef_friendly($cur_topic['question']))));

	// Delete reputation
	$query = array(
		'DELETE'	=> 'voting',
		'WHERE'		=> 'id IN('.implode(',', array_values($_POST['delete'])).')'
	);

	($hook = get_hook('vp_delete_voting_id_qr_get')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	($hook = get_hook('vp_form_delete_pre_redirect')) ? eval($hook) : null;

	redirect(forum_link($forum_url['poll'], array($id, sef_friendly($cur_topic['question']))), $lang_poll['Deleted redirect']);
}

if ($id)
{
	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['poll'], array($id, sef_friendly($cur_topic['question']))));

	$forum_page['form_action'] = forum_link($forum_url['poll'], array($id, sef_friendly($cur_topic['question'])));
	$forum_page['form_attributes'] = array();

	$forum_page['hidden_fields'] = array(
		'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
		'csrf_token'		=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
	);

	if ($forum_config['o_censoring'])
	{
		$cur_topic['subject'] = censor_words($cur_topic['subject']);
		$cur_topic['question'] = censor_words($cur_topic['question']);
	}

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array($cur_topic['subject'], forum_link($forum_url['topic'], array($cur_topic['topic_id'], sef_friendly($cur_topic['subject'])))),
		array(forum_htmlencode($cur_topic['question']), forum_link($forum_url['poll'], array($id, sef_friendly($cur_topic['question']))))
	);

	($hook = get_hook('vp_pre_header_load')) ? eval($hook) : null;

	define('FORUM_PAGE', 'viewpoll');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

?>
<div class="main-content main-frm">
	<form method="post" action="<?php echo $forum_page['form_action'] ?>"<?php if (!empty($forum_page['form_attributes'])) echo ' '.implode(' ', $forum_page['form_attributes']) ?>>
		<div class="hidden">
			<?php echo implode("\n\t\t\t", $forum_page['hidden_fields'])."\n" ?>
		</div>
		<div class="ct-box info-box">
			<p><?php echo forum_htmlencode($cur_topic['question']) ?></p>
		</div>
<?php

	//Get count of votes
	$query = array(
		'SELECT'	=> 'COUNT(v.id)',
		'FROM'		=> 'voting AS v',
		'WHERE'		=> 'v.topic_id='.$id
	);

	($hook = get_hook('vp_qr_get_voting')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
	if ($forum_db->num_rows($result) > 0)
		list($vote_count) = $forum_db->fetch_row($result);

	if ($vote_count > 0)
	{	
?>
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

	($hook = get_hook('vp_qr_get_select_answers')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$num = 0;
	while (list($answer, $vote) = $forum_db->fetch_row($result))
	{
		($hook = get_hook('vp_answers_loop_start')) ? eval($hook) : null;

		if ($forum_config['o_censoring'])
			$answer = censor_words($answer);

		$vote_answers['answers'] = '<td class="tc0">'.forum_htmlencode($answer).'</td>';
		$vote_answers['count'] = '<td class="tc1"><h1 class="count-poll" style="width: '.forum_number_format((float)$vote/$vote_count * 100, 2).'%;"/></td>';
		$vote_answers['percent'] = '<td class="tc2">'.forum_number_format((float)$vote/$vote_count * 100, 2).'% — '.forum_number_format($vote).'</td>';
		$num++;

?>
				<tr class="<?php echo ($num % 2 == 0 ? 'even' : 'odd') ?>">
					<?php echo implode("\n\t\t\t\t\t", $vote_answers)."\n" ?>
				</tr>
<?php

	}

?>
			</tbody>
			</table>
		</div>
		<fieldset class="frm-group group1">
<?php

	$query = array(
		'SELECT'	=> 'a.id AS answer_id ,a.answer, v.id AS voting_id, v.voted, u.id, u.username',
		'FROM'		=> 'answers AS a',
		'JOINS'		=> array(
			array(
				'INNER JOIN'		=> 'voting AS v',
				'ON'			=> 'v.answer_id=.a.id'
			),
			array(
				'INNER JOIN'		=> 'users AS u',
				'ON'			=> 'u.id=v.user_id'
			),
		),
		'WHERE'		=> 'a.topic_id='.$id,
		'ORDER BY'	=> 'a.id'
	);

	($hook = get_hook('vp_qr_get_select_answers')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	$forum_page['cur_answer'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	while ($cur_poll = $forum_db->fetch_assoc($result))
	{
		($hook = get_hook('vp_users_loop_start')) ? eval($hook) : null;

		if ($cur_poll['answer_id'] != $forum_page['cur_answer'])
		{
			if ($forum_page['cur_answer'] != 0)
				echo "\t\t\t\t".'</div>'."\n\t\t\t".'</fieldset>'."\n";

			if ($forum_config['o_censoring'])
				$cur_poll['answer'] = censor_words($cur_poll['answer']);

?>		
			<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?>">
				<legend><span><?php echo forum_htmlencode($cur_poll['answer']) ?></span></legend>
				<div class="mf-box">
<?php

			$forum_page['cur_answer'] = $cur_poll['answer_id'];
		}

			$forum_page['user'] = array();
			$forum_page['user']['input'] = '<span class="fld-input"><input type="checkbox" id="fld'.++$forum_page['fld_count'].'" name="delete[]" value="'.$cur_poll['voting_id'].'" /></span>';
			$forum_page['user']['userlink'] = '<label for="fld'.++$forum_page['fld_count'].'"><a href="'.forum_link($forum_url['user'], $cur_poll['id']).'">'.forum_htmlencode($cur_poll['username']).'</a> ('.format_time($cur_poll['voted']).$lang_common['Title separator'].flazy_format_time($cur_poll['voted']).')</label>';
?>
					<div class="mf-item">
						<?php echo implode("\n\t\t\t\t\t\t",$forum_page['user'])."\n" ?>
					</div>
<?php

	}
	if ($forum_page['cur_answer'] > 0)
		echo "\t\t\t\t".'</div>'."\n\t\t\t".'</fieldset>'."\n";

?>
		</fieldset>
		<div class="frm-buttons">
			<span class="submit"><input type="submit" name="submit" value="<?php echo $lang_poll['Delete'] ?>" onclick="return confirm('<?php echo $lang_poll['Are you sure']; ?>')"/></span>
		</div>
<?php
	}
	else
	{

?>
		<div class="ct-box info-box">
			<p><?php echo $lang_poll['No votes'] ?></p>
		</div>
<?php

	}

?>
	</form>
</div>
<?php

	($hook = get_hook('vp_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}
