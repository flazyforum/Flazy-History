<?php
/**
 * Функция генирирующая тело сообщения.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

function pm_next_reply($str)
{
	$return = ($hook = get_hook('fn_pm_next_reply')) ? eval($hook) : null;
	if ($return != null)
		return;

	if (substr($str, 0, 4) == 'Ответ: ')
		return 'Oтвет[2]: ' . substr($str, 4);
	$str1 = preg_replace('#^Ответ\[(\d{1,10})\]: #eu', '\'Ответ[\'.(\\1 + 1).\']: \'', $str);

	($hook = get_hook('fn_pm_next_reply_end')) ? eval($hook) : null;

	return $str == $str1 ? 'Ответ: ' . $str : $str1;
}

function pm_message($message, $type)
{
	global $forum_config, $forum_url, $forum_user, $lang_common, $lang_profile, $base_url, $forum_js;

	$return = ($hook = get_hook('fn_pm_message_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Setup the form
	$forum_page['set_count'] = $forum_page['fld_count'] = 0;

	$forum_page['form_action'] = isset($message['id']) ? forum_link($forum_url['pm_edit'], array($forum_user['id'], $message['id'])) : '';

	$forum_page['hidden_fields'] = array(
		'csrf_token'		=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
		'send_action'		=> '<input type="hidden" name="send_action" value="" />'
	);

	if ($type == 'inbox')
	{
		$forum_page['heading'] = $lang_profile['Incoming message'];
		$forum_page['user_text'] = $lang_profile['Sender'];
		$forum_page['user_content'] = $message['sender'] != '' ? '<a href="'.forum_link($forum_url['user'], $message['sender_id']).'">'.forum_htmlencode($message['sender']).'</a>' : $lang_profile['Empty'];
	}
	else
	{
		$forum_page['heading'] = $lang_profile['Outgoing message'];
		$forum_page['user_text'] = $lang_profile['Receiver'];
		$forum_page['user_content'] = $message['receiver'] != '' ? '<a href="'.forum_link($forum_url['user'], $message['receiver_id']).'">'.forum_htmlencode($message['receiver']).'</a>' : $lang_profile['Empty'];
	}
	if (!isset($message['id']))
		$forum_page['heading'] = $lang_profile['Preview message'];

	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	($hook = get_hook('fn_pm_message_pre_load')) ? eval($hook) : null;

	// some if required...
	global $smilies;
	if (!defined('FORUM_PARSER_LOADED'))
		require FORUM_ROOT.'include/parser.php';

	$forum_js->addFile($base_url.'/include/js/jquery.js');
	$forum_js->addCode('$(document).ready( function() {
		$(".spoiler-head").toggle(
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
	});');

	ob_start();

?>
	<div class="main-subhead">
		<h3 class="hn"><span><?php echo $forum_page['heading'] ?></span></h3>
	</div>
	<div class="main-content main-frm">
<?php

	if ($type == 'outbox' && $message['status'] == 'sent')
	{

?>
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_profile['Sent note']?></span></h2>
		</div>
<?php

	}

?>	
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h4 class="ct-legend hn"><span><?php echo $forum_page['user_text'] ?></span></h4>
					<h4 class="hn"><?php echo $forum_page['user_content'] ?></h4>
				</div>
			</div>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
<?php

	if ($type == 'inbox')
	{

?>
					<h4 class="ct-legend hn"><span><?php echo $lang_profile['Sent'] ?></span></h4>
					<h4 class="hn"><?php echo $message['sent_at'] ? format_time($message['sent_at'])."\n" : $lang_profile['Not sent'] ?></h4>
<?php

	}
	else
	{

?>
					<h4 class="ct-legend hn"><span><?php echo $lang_profile['Status'] ?></span></h4>
					<h4 class="hn"><?php echo $lang_profile[$message['status']], $message['status'] == 'read' ? ' '.format_time($message['read_at']) : '' ?></h4>
<?php

	}

?>
				</div>
			</div>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="ct-box data-box">
					<h4 class="ct-legend hn"><span><?php echo $lang_profile['Subject'] ?></span></h4>
					<h4 class="hn"><?php echo $message['subject'] ? forum_htmlencode($message['subject'])."\n" : $lang_profile['Empty'] ?></h4>
				</div>
			</div>
			<div class="ct-set data-set set<?php echo ++$forum_page['item_count'] ?>"></div>
			<div class="post-entry">
				<div class="entry-content">
					<p><?php echo parse_message($message['body'], false) ?></p>
				</div>
			</div>
		</fieldset>
<?php

	if (isset($message['id']))
	{

?>
			<div class="frm-buttons">
<?php

		if ($type == 'outbox' && ($message['status'] == 'draft' || $message['status'] == 'sent'))
		{

?>
				<span class="submit"><input type="submit" name="pm_edit" value="<?php echo $lang_profile['Edit message']; ?>" /></span>
<?php

		}

		if ($type != 'outbox' || $message['status'] != 'sent')
		{

?>
				<span class="submit"><input type="submit" name="delete_<?php echo $type ?>" value="<?php echo $lang_profile['Delete message'] ?>" onclick="return confirm('<?php echo $lang_profile['Delete confirmation 1'] ?>');" /></span>
<?php

		}
		else
		{

?>
				<span class="fld-help"><?php echo $lang_profile['Sent -> draft note']; ?></span>
<?php

		}

?>
			</div>
<?php

	}

?>
		</form>
	</div>
<?php

	// Setup help
	$forum_page['text_options'] = array();
	if ($forum_config['p_message_bbcode'])
		$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
	if ($forum_config['p_message_img_tag'])
		$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
	if ($forum_config['o_smilies'])
		$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';

	if (!defined('FORUM_FUNCTIONS_PM_SEND_FORM'))
		require FORUM_ROOT.'include/functions/pm/send_form.php';

	if (isset($message['id']) && $type == 'inbox')
	{

?>
	<div class="main-subhead">
		<h3 class="hn"><span>Ответить</span></h3>
	</div>
	<div class="main-content main-frm">
<?php
		if (!empty($forum_page['text_options']))
			echo "\t\t".'<p class="ct-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";

		echo pm_send_form($message['sender'], pm_next_reply($message['subject']), '[quote='.$message['sender'].']'.$message['body'].'[/quote]' , false, true);
?>
<?php

	}

	$result = ob_get_contents();
	ob_end_clean();

	($hook = get_hook('fn_pm_message')) ? eval($hook) : null;

	return $result;
}

define('FORUM_FUNCTIONS_PM_MESSAGE', 1);
