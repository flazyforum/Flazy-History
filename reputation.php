<?php
/**
 * Просмотр репутации участников.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('rp_start')) ? eval($hook) : null;

// Load the reputation.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/reputation.php';

if (!$forum_config['o_rep_enabled'])
	message($lang_reputation['Disabled']);
if (!$forum_user['g_rep_enable'])
	message($lang_reputation['Group disabled']);
if (!$forum_user['rep_enable_adm'])
	message($lang_reputation['Individual disabled']);
if (!$forum_user['rep_enable'])
	message($lang_reputation['Your disabled']);

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$pid = isset($_GET['pid']) ? intval($_GET['pid']) : null;
$method = isset($_GET['method']) ? intval($_GET['method']) : null;
$section = isset($_GET['section']) ? $_GET['section'] : null;

if (isset($_POST['delete_rep_id']))
{
	($hook = get_hook('vp_form_delete_rep_id')) ? eval($hook) : null;

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['reputation'], $id));

	if ($forum_user['is_admmod'])
	{
		if ($id <  2)
			message($lang_common['Bad request']);

		// Delete reputation
		$query = array(
			'DELETE'	=> 'reputation',
			'WHERE'		=> 'id IN('.implode(',', array_values($_POST['delete_rep_id'])).')'
		);

		($hook = get_hook('rp_delete_reputation_qr_get')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		$query = array(
			'SELECT'	=> 'SUM(rp.rep_plus) AS plus, SUM(rp.rep_minus) AS minus',
			'FROM'		=> 'reputation AS rp',
			'WHERE'		=> 'rp.user_id='.$id,
			'GROUP BY'	=> 'rp.user_id'
		);

		($hook = get_hook('rp_sum_plus_minus_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			
		if (!$forum_db->num_rows($result))
		{
			$rep['plus'] = 0;
			$rep['minus'] = 0;
		}
		else 
			$rep = $forum_db->fetch_assoc($result);

		$query = array(
			'SELECT'	=> 'SUM(rp.rep_plus) AS plus, SUM(rp.rep_minus) AS minus',
			'FROM'		=> 'reputation AS rp',
			'WHERE'		=> 'rp.from_user_id='.$id,
			'GROUP BY'	=> 'rp.from_user_id'
		);

		($hook = get_hook('rp_sum_plus_minus_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);
			
		if (!$forum_db->num_rows($result))
		{
			$pos['plus'] = 0;
			$pos['minus'] = 0;
		}
		else 
			$pos = $forum_db->fetch_assoc($result);

		$query = array(
			'UPDATE'	=> 'users',
			'SET'		=> 'rep_plus='.$rep['plus'].',rep_minus='.$rep['minus'].',pos_plus='.$pos['plus'].',pos_minus='.$pos['minus'],
			'WHERE'		=> 'id='.$id
		);

		($hook = get_hook('rp_update_delete_rep_qr_get')) ? eval($hook) : null;
		$forum_db->query_build($query) or error(__FILE__, __LINE__);

		($hook = get_hook('vp_form_delete_rep_id_pre_redirect')) ? eval($hook) : null;

		redirect(forum_link($forum_url[($section == 'positive' ? 'positive' : 'reputation')], $id), $lang_reputation['Deleted redirect']);
	}
	else
		message($lang_common['No permission']);
}

if (isset($_POST['reputation']))
{
	($hook = get_hook('vp_form_reputation')) ? eval($hook) : null;

	// Check for use of incorrect URLs
	confirm_current_url(forum_link($forum_url['reputation'], $id));
	
	if ($forum_user['is_guest'])
		message($lang_common['No permission']);
	$pid = isset($_POST['pid']) ? intval($_POST['pid']) : message($lang_common['Bad request']);
	$poster = isset($_POST['poster']) ? $_POST['poster'] : message($lang_common['Bad request']);
	$method = isset($_POST['method']) ? intval($_POST['method']) : message($lang_common['Bad request']);
	if ($method != 2 && $method != 1)
		message($lang_common['Bad request']);

	$query = array(
		'SELECT'	=> 'p.poster, p.poster_id, p.posted, p.id, p.topic_id, t.subject, u.rep_enable, rp.time',
		'FROM'		=> 'posts AS p',
		'JOINS'		=> array(
			array(
				'INNER JOIN'	=> 'topics AS t',
				'ON'		=> 'p.topic_id=t.id'
			),
			array(
				'LEFT JOIN'	=> 'users AS u',
				'ON'		=> 'p.poster_id=u.id'
			),
			array(
				'LEFT JOIN'	=> 'reputation AS rp',
				'ON'		=> 'rp.from_user_id='.$forum_user['id'] .' AND rp.user_id=u.id'
			)
		),
		'WHERE'		=> 'p.id='.$pid.' AND p.poster=\''.$poster.'\'',
		'ORDER BY'	=> 'rp.time DESC',
		'LIMIT'		=> '0, 1'
	);

	($hook = get_hook('rp_reputation_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result))
		message($lang_common['Bad request']);

	$cur_rep = $forum_db->fetch_assoc($result);

	//Check last reputation point given timestamp
	if ($cur_rep['time'])
	{
		if($forum_config['o_rep_timeout'] * 60 > (time() - $cur_rep['time']))
  			message(sprintf($lang_reputation['Timeout'], $forum_config['o_rep_timeout']));
	}
	
	if ($cur_rep['rep_enable'] != 1)
		message($lang_reputation['User Disable']);
	// Prevent people from voting for themselves via URL hacking.
	if ($forum_user['id'] == $cur_rep['poster_id'])
    		message($lang_reputation['Silly user']);
	if ((($forum_user['g_rep_minus_min'] > $forum_user['num_posts']) && $method = 2) || (($forum_user['g_rep_plus_min'] > $forum_user['num_posts']) && $method = 1))
		message($lang_reputation['Small Number of post']);
	
	// Clean up message from POST
	$message = forum_linebreaks(forum_trim($_POST['req_message']));

	// Check message
	if ($message == '')
		message($lang_reputation['No message']);
	else if (utf8_strlen($message) > 400)
		message($lang_reputation['Too long message']);
	else if (!$forum_config['p_message_all_caps'] && is_all_uppercase($message) && !$forum_page['is_admmod'])
		$message = utf8_ucwords(utf8_strtolower($message));

	// Validate BBCode syntax
	if ($forum_config['p_message_bbcode'] || $forum_config['o_make_links'])
	{
		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		$message = preparse_bbcode($message, $errors);
	}
	
	if (isset($errors))
		message($errors[0]);

	$rep_column = ($method == 1) ? 'rep_plus' : 'rep_minus';
	$pos_column = ($method == 1) ? 'pos_plus' : 'pos_minus';

	//Add voice
	$query = array(
		'INSERT'	=> 'user_id, from_user_id, time, post_id, reason, topics_id, '.$rep_column,
		'INTO'		=> 'reputation',
		'VALUES'	=> $cur_rep['poster_id'].', '.$forum_user['id'].', '.mktime().', '.$cur_rep['id'].', \''.$forum_db->escape($message).'\', '.$cur_rep['topic_id'].', 1'
	);

	($hook = get_hook('rp_insert_rep_column_qr_get')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> $rep_column.'='.$rep_column.'+1',
		'WHERE'		=> 'id='.$cur_rep['poster_id']
	);

	($hook = get_hook('rp_update_rep_column_qr_get')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);

	$query = array(
		'UPDATE'	=> 'users',
		'SET'		=> $pos_column.'='.$pos_column.'+1',
		'WHERE'		=> 'id='.$forum_user['id']
	);

	($hook = get_hook('rp_update_pos_column_qr_get')) ? eval($hook) : null;
	$forum_db->query_build($query) or error(__FILE__, __LINE__);
	
	redirect(forum_link($forum_url['post'], $pid) , $lang_reputation['Redirect Message']);
}


if ($id && !$method)
{
	if ($id < 2)
		message($lang_common['Bad request']);
	
	$query = array(
		'SELECT'	=> 'u.username',
		'FROM'		=> 'users AS u',
		'WHERE'		=> 'u.id='.$id
	);

	if ($section == 'positive')
		$query['SELECT'] .= ', u.pos_plus, u.pos_minus';
	else
		$query['SELECT'] .= ', u.rep_plus, u.rep_minus';

	($hook = get_hook('rp_current_page_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result))
		message($lang_common['Bad request']);

	$user_rep = $forum_db->fetch_assoc($result);

	$forum_links['reputation'] = array(
		'<li'.($section == 'respect' ? ' class="active item1"' : ' class="normal"').'><a href="'.forum_link($forum_url['reputation'], $id).'">'.$lang_reputation['Reputation'].'</a></li>',
		'<li'.($section == 'positive' ? ' class="active item1"' : ' class="normal"').'><a href="'.forum_link($forum_url['positive'], $id).'">'.$lang_reputation['Positive'].'</a></li>'
	);

	$query = array(
		'SELECT'	=> 'COUNT(rp.id)',
		'FROM'		=> 'reputation AS rp'
	);

	if ($section == 'positive')
		$query['WHERE'] = 'rp.from_user_id='.$id;
	else
		$query['WHERE'] = 'rp.user_id='.$id;

	($hook = get_hook('rp_count_used_id_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	list($num_rows) = $forum_db->fetch_row($result);

	if ($num_rows > 0)
	{
		$forum_page['num_pages'] = ceil(($num_rows + 1) / $forum_user['disp_posts']);
		$forum_page['page'] = (!isset($_GET['p']) || !is_numeric($_GET['p']) || $_GET['p'] <= 1 || $_GET['p'] > $forum_page['num_pages']) ? 1 : $_GET['p'];
		$forum_page['start_from'] = $forum_user['disp_posts'] * ($forum_page['page'] - 1);

		if ($forum_page['page'] < $forum_page['num_pages'])
		{
			$forum_page['nav']['last'] = '<link rel="last" href="'.forum_link($forum_url['reputation'], $forum_url['page'], $forum_page['num_pages'], $id).'" title="'.$lang_common['Page'].' '.$forum_page['num_pages'].'" />';
			$forum_page['nav']['next'] = '<link rel="next" href="'.forum_link($forum_url['reputation'], $forum_url['page'], $forum_page['num_pages'] + 1, $id).'" title="'.$lang_common['Page'].' '.($forum_page['page'] + 1).'" />';
		}

		if ($forum_page['page'] > 1)
		{
			$forum_page['nav']['prev'] = '<link rel="prev" href="'.forum_link($forum_url['reputation'], $forum_url['page'] -1 , $forum_page['num_pages'], $id).'" title="'.$lang_common['Page'].' '.($forum_page['page'] - 1).'" />';
			$forum_page['nav']['first'] = '<link rel="first" href="'.forum_link($forum_url['reputation'],  $id).'" title="'.$lang_common['Page'].' 1" />';
		}

		$forum_page['page_post']['paging'] = '<p class="paging"><span class="pages">'.$lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['reputation'], $lang_common['Paging separator'], $id).'</p>';
	}

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array(sprintf($lang_reputation[($section == 'positive' ? 'Positive' : 'Reputation').' user'], forum_htmlencode($user_rep['username'])), forum_link($forum_url['reputation'] , $id))
	);


	($hook = get_hook('vp_pre_reputation')) ? eval($hook) : null;

	define('FORUM_PAGE', ($section == 'positive' ? 'positive' : 'reputation'));
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

?>
		<div class="admin-submenu gen-content">
			<ul>
				<?php echo implode("\n\t\t\t\t", $forum_links['reputation'])."\n" ?>
			</ul>
		</div>
<div class="main-subhead">
	<h2 class="hn"><span><?php printf($lang_reputation[($section == 'positive' ? 'Positive' : 'Reputation').' user head'], forum_htmlencode($user_rep['username'])); echo '  <strong>[+'. $user_rep[($section == 'positive' ? 'pos' : 'rep').'_plus'] . ' / -' . $user_rep[($section == 'positive' ? 'pos' : 'rep').'_minus'] .'] </strong>' ?></span></h2>
</div>
<?php

	if ($num_rows > 0)
	{
		$query = array(
			'SELECT'	=> 'rp.id, rp.time, rp.reason, rp.post_id, rp.rep_plus, rp.rep_minus, rp.user_id, t.subject, u1.username AS from_user_name, u1.id AS from_user_id',
			'FROM'		=> 'reputation AS rp',
			'JOINS'		=> array(
				array(
					'INNER JOIN'	=> 'topics AS t',
					'ON'		=> 't.id=rp.topics_id'
				)
			),
			'WHERE'		=> 'u0.id='.$id,
			'ORDER BY'	=> 'rp.time DESC',
			'LIMIT'		=> $forum_page['start_from'].','.$forum_user['disp_posts']
		);

		if ($section == 'positive')
		{
			$query['JOINS'][] = array(
				'INNER JOIN'	=> 'users AS u0',
				'ON'		=> 'rp.from_user_id=u0.id'
			);
			$query['JOINS'][] = array(
				'INNER JOIN'	=> 'users AS u1',
				'ON'		=> 'rp.user_id=u1.id'
			);
		}
		else
		{
			$query['JOINS'][] = array(
				'INNER JOIN'	=> 'users AS u0',
				'ON'		=> 'rp.user_id=u0.id'
			);
			$query['JOINS'][] = array(
				'INNER JOIN'	=> 'users AS u1',
				'ON'		=> 'rp.from_user_id=u1.id'
			);
		}

		($hook = get_hook ('rp_reputation_list_qr_get')) ? eval($hook) : null;
		$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);	

		if ($forum_user['is_admmod'])
		{
			$forum_page['fld_count'] = 0;
			$forum_page['form_action'] = forum_link($forum_url['reputation'], $id);
			$forum_page['form_attributes'] = array();

			$forum_page['hidden_fields'] = array(
				'form_sent'		=> '<input type="hidden" name="form_sent" value="1" />',
				'csrf_token'		=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
			);

?>
	<form method="post" action="<?php echo $forum_page['form_action'] ?>"<?php if (!empty($forum_page['form_attributes'])) echo ' '.implode(' ', $forum_page['form_attributes']) ?>>
		<div class="hidden">
			<?php echo implode("\n\t\t\t", $forum_page['hidden_fields'])."\n" ?>
		</div>
<?php

		}

?>
	<div class="main-content main-frm">
		<div class="ct-group">
			<table cellspacing="0">
			<thead>
				<th class="tc0" style="width:10%"><?php echo $lang_reputation[($section == 'positive' ? 'User' : 'From user')] ?></th>
				<th class="tc1" style="width:25%"><?php echo $lang_reputation['For topic'] ?></th>
				<th class="tc2" style="width:<?php echo ($forum_user['is_admmod'] ? '35' : '45') ?>%"><?php echo $lang_reputation['Reason'] ?></th>
				<th class="tc3" style="width:6%; text-align:center;"><?php echo $lang_reputation['Estimation'] ?></th>
				<th class="tc4" style="width:20%"><?php echo $lang_reputation['Date'] ?></th>
<?php if ($forum_user['is_admmod']): ?>
				<th class="tc3" style="width:4%"><?php echo $lang_reputation['Delete'] ?></th><?php endif; ?>
			</thead>
			<tbody>
<?php

		if (!defined('FORUM_PARSER_LOADED'))
			require FORUM_ROOT.'include/parser.php';

		$num = 0;
		while ($cur_rep = $forum_db->fetch_assoc($result))
		{
			($hook = get_hook('vp_pre_cur_rep')) ? eval($hook) : null;

			$rep_page['username'] = '<td class="tc0">'.($cur_rep['from_user_name'] ? '<a href="'.forum_link($forum_url['user'], $cur_rep['from_user_id']).'">'. forum_htmlencode($cur_rep['from_user_name']).'</a>' :  $lang_reputation['Profile deleted']).'</td>';
			$rep_page['subject'] = '<td class="tc1">'.($cur_rep['subject'] ? '<a href="'.forum_link($forum_url['post'], $cur_rep['post_id']).'">'.forum_htmlencode($cur_rep['subject']).'</a>' : $lang_reputation['Removed or deleted']).'</td>';
			$rep_page['reason'] = '<td class="tc2">'.parse_message($cur_rep['reason'], 0).'</td>';
			$rep_page['plus'] = '<td style="text-align:center;"><img src="'.$base_url.'/img/style/'.($cur_rep['rep_plus'] == 1 ? 'plus.gif" alt="+"' : 'minus.gif" alt="-"').' /></td>';
			$rep_page['time'] = '<td class="tc3">'.format_time($cur_rep['time']).'</td>';

			if ($forum_user['is_admmod'])
				$rep_page['delete_rep'] = '<td class="tc4" style="text-align:center;"><input type="checkbox" name="delete_rep_id[]" value="'.$cur_rep['id'].'" /></td>';
			$num++;
	
?>
				<tr class="<?php echo ($num % 2 == 0 ? 'even' : 'odd') ?>">
					<?php echo implode("\n\t\t\t\t\t", $rep_page)."\n"; ?>
				</tr>
<?php

		}

?>
			</tbody>
			</table>
		</div>
<?

		if ($forum_user['is_admmod'])
		{

?>
		<div class="frm-buttons">
			<span class="submit"><input type="submit" name="del_rep" value="<?php echo $lang_common['Delete']; ?>" onclick="return confirm('<?php echo $lang_reputation['Are you sure']; ?>')" /></span>
		</div>
<?php

		}

?>
	</div>
<?php

		if ($forum_user['is_admmod'])
		{

?>
	</form>
<?php

		}
	}
	else
	{

?>
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_reputation['No reputation'] ?></span></h2>
		</div>
	</div>
<?php	

	}
 
	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}
else 
{
	if (empty($pid) || empty($method) || empty($id))
		message($lang_common['Bad request']);
	if ($forum_user['is_guest'])
		message($lang_common['No permission']);
	if ($forum_user['id'] == $id)
		message($lang_reputation['Silly user']);
	if (!$method)
		message($lang_common['Bad request']);

	$query = array(
		'SELECT'	=> 'rp.time, u.username',
		'FROM'		=> 'users AS u',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'reputation AS rp',
				'ON'		=> 'rp.user_id='.$id.' AND rp.from_user_id='.$forum_user['id']
			)
		),
		'WHERE'		=> 'u.id='.$id,
		'ORDER BY'	=> 'rp.time DESC',
		'LIMIT'		=> '0, 1'
	);

	($hook = get_hook('rp_reputation_add_vote_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if (!$forum_db->num_rows($result))
		message($lang_common['Bad request']);

	$cur_rep = $forum_db->fetch_assoc($result);
	//Check last reputation point given timestamp
	if ($cur_rep['time'])
	{
		if($forum_config['o_rep_timeout'] * 60 > (time() - $cur_rep['time']))
 			message(sprintf($lang_reputation['Timeout'], $forum_config['o_rep_timeout']));
	}

	// Prevent people from voting for themselves via URL hacking.
	if ($forum_user['id'] == $id)
  		message($lang_reputation['Silly user']);

	if ((($forum_user['g_rep_minus_min'] > $forum_user['num_posts']) && ($method = 2) ) || (($forum_user['g_rep_plus_min'] >  $forum_user['num_posts']) && ($method = 1)))
		message($lang_reputation['Small Number of post']);

	$poster = forum_htmlencode($cur_rep['username']);

	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = forum_link($forum_url['reputation'], $id);
	$forum_page['form_attributes'] = array();

	$forum_page['hidden_fields'] = array(
		'form_sent'	=> '<input type="hidden" name="form_sent" value="1" />',
		'pid'		=> '<input type="hidden" name="pid" value="'.$pid.'" />',
		'poster'	=> '<input type="hidden" name="poster" value="'.$poster.'" />',
		'method'	=> '<input type="hidden" name="method" value="'.$method.'" />',
		'csrf_token'	=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
	);

	// Setup breadcrumbs
	$forum_page['crumbs'] = array(
		array($forum_config['o_board_title'], forum_link($forum_url['index'])),
		array(sprintf($lang_reputation['Reputation user'], forum_htmlencode($cur_rep['username'])), forum_link($forum_url['reputation'] , $id)),
		$method == 1 ? $lang_reputation['Plus'] : $lang_reputation['Minus']
	);

	($hook = get_hook('vp_pre_add_reputation')) ? eval($hook) : null;

	define('FORUM_PAGE', 'reputation-vote');
	require FORUM_ROOT.'header.php';

	// START SUBST - <!-- forum_main -->
	ob_start();

?>
	<div class="main-content main-frm">
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo $lang_reputation['Form header'] ?></span></h2>
		</div>

<script language="javascript">
<!--
	function Validate()
	{
		var max = 100;
		Length = document.Reput.req_message.value.length;
		if ((Length>max) && (max>0))
		{
			alert("<?php echo $lang_reputation['Max length of message'] ?> "+max+"<?php echo $lang_reputation['You already of use'] ?> "+Length+"<?php echo $lang_reputation['Of symbol'] ?>");
			return false;
		}
		else
		{
			document.Reput.go.disabled = true;
			return true;
		}
	}
// -->
</script>
		<div id="req-msg" class="req-warn ct-box error-box">
			<p><?php printf($lang_common['Required warn'], '<em>'.$lang_common['Required'].'</em>') ?></p>
		</div>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>"<?php if (!empty($forum_page['form_attributes'])) echo ' '.implode(' ', $forum_page['form_attributes']) ?>>
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
		<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
			<legend class="group-legend"><strong>Оформите свое сообщение</strong></legend>
<?php ($hook = get_hook('vp_new_rep_username')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_reputation['Form your name'] ?></span></label><br />
						<span class="fld-input"><?php echo forum_htmlencode($forum_user['username']) ?></span>
					</div>
				</div>
<?php ($hook = get_hook('vp_new_rep_poster')) ? eval($hook) : null; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_reputation['Form to name'] ?></span></label><br />
						<span class="fld-input"><?php echo forum_htmlencode($poster) ?></span>
					</div>
				</div>
<?php ($hook = get_hook('vp_new_rep_message')) ? eval($hook) : null; ?>
			<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="txt-box textarea required">
					<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_reputation['Form reason'] ?></span></label>
					<div class="txt-input"><span class="fld-input"><textarea id="fld1" class="inputbox" name="req_message"  rows="7" cols="95"></textarea></span></div>
				</div>
			</div>
		</fieldset>
<?php ($hook = get_hook('vp_new_rep_buttons')) ? eval($hook) : null; ?>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="reputation" value="<?php echo $lang_common['Submit'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	($hook = get_hook('rp_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
	ob_end_clean();
	// END SUBST - <!-- forum_main -->

	require FORUM_ROOT.'footer.php';
}
