<?php
/**
 * Генерация основного блока личных сообщений.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

function pm_box($forum_page)
{
	global $forum_config, $forum_url, $forum_user, $lang_common, $lang_profile,  $pm_inbox_count, $pm_outbox_count, $base_url;

	$return = ($hook = get_hook('fn_pm_box_start')) ? eval($hook) : null;
	if ($return != null)
		return;
	
	$icons_path = $base_url.'/img/style/';

	$forum_page['set_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = forum_link($forum_url['pm'], array($forum_user['id'], 'write'));

	$forum_page['hidden_fields'] = array(
		'send_action'		=> '<input type="hidden" name="send_action" value="" />',
		'csrf_token'		=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />'
	);

	($hook = get_hook('fn_pm_box_pre_load')) ? eval($hook) : null;

	ob_start();

	if ($forum_page['type'] == 'inbox' && (!$forum_config['o_pm_inbox_size'] || !$pm_inbox_count) || $forum_page['type'] == 'outbox' && (!$forum_config['o_pm_outbox_size'] || !$pm_outbox_count))
		$size = '';
	else
	{
		if ($forum_page['type'] == 'inbox')
			$size = sprintf($lang_profile['Status box'], substr(($pm_inbox_count / ($forum_config['o_pm_inbox_size'] * 0.01)), 0, 5), $pm_inbox_count, $forum_config['o_pm_inbox_size']);
		else
			$size = sprintf($lang_profile['Status box'], substr(($pm_outbox_count / ($forum_config['o_pm_outbox_size'] * 0.01)), 0, 5), $pm_outbox_count, $forum_config['o_pm_outbox_size']);
	}

	($hook = get_hook('fn_pm_box_pre_message_box')) ? eval($hook) : null;

?>
<div class="main-subhead">
	<h2 class="hn"><span><?php echo $forum_page['heading'].$size ?></span></h2>
</div>	
	<div class="main-content main-frm">
		<div class="ct-box user-box">
			<h2 class="hn"><span><?php echo $lang_profile['Intro'] ?></span></h2>
		</div>
<?php

	if (!empty($forum_page['full_box']))
	{

?>
		<div class="ct-box error-box">
			<h2 class="warn"></h2>
			<ul class="error-list">
				<?php echo $forum_page['full_box'] ?>
			</ul>
		</div>
<?php

	}

	if (!count($forum_page['list']))
	{

?> 
		<div class="ct-box error-box">
			<h2 class="warn"><strong><?php echo $lang_profile['Not PM'] ?></strong></h2>
			<ul class="error-list">
				<?php echo $lang_profile['Empty box']."\n" ?>
			</ul>
		</div>
	</div>
<?php

	}
	else
	{

?>
		<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields']), "\n" ?>
			</div>
			<fieldset class="frm-set set<?php echo ++$forum_page['set_count'] ?>">
				<legend class="group-legend"><strong><?php echo $forum_page['heading'] ?></strong></legend>
				<div class="ct-group">
				<table cellspacing="0" class="pm-list">
					<thead>
					<tr>
						<th class="td1"><input onclick="return pm_select_all(this.checked);" type="checkbox" name="pm_delete_all" value="" /></th>
						<th class="td2"><img src="<?php echo $icons_path ?>/p_sent.png" height="16" width="16" alt="Status" title="Status"/></th>
						<th class="td3"><?php echo $forum_page['user_role'] ?></th>
						<th class="td4"><?php echo $lang_profile['Subject'] ?></th>
						<th class="td5"><?php echo $lang_profile['Edit date'] ?></th>
					</tr>
					</thead>
					<tbody>
<?php

		foreach($forum_page['list'] as $message)
		{
			if ($message['status'] == 'sent')
				$title =$lang_profile['sent'];
			if ($message['status'] == 'draft')
				$title =$lang_profile['draft'];
			if ($message['status'] == 'read')
				$title =$lang_profile['read'];
			if ($message['status'] == 'delivered')
				$title =$lang_profile['delivered'];

			($hook = get_hook('fn_pm_box_pre_message_link')) ? eval($hook) : null;

			$message_link = forum_link($forum_url['pm_'.($message['status'] == 'draft' ? 'edit' : 'view')], array($forum_user['id'], $message['id'], $forum_page['type']));
			$message_info = array (
				$message['status'] != 'sent' ? '<input type="checkbox" name="delete[]" value="'.$message['id'].'" />' : '',
				'<img src="'.$icons_path.'/'.($message['status'] == 'delivered' ? 'p_'.$forum_page['type'].'_' : 'p_').''.$message['status'].'.png" height="16" width="16" alt="'.$title.'" title="'.$title.'" />',
				$message['username'] ? '<a href="'.forum_link($forum_url['user'], $message['user_id']).'">'.forum_htmlencode($message['username']).'</a>' : $lang_profile['Empty'],
				'<span><a href="'.$message_link.'">'.forum_trim($message['subject'] ? forum_htmlencode($message['subject']) : $lang_profile['Empty']).'</a>'.($forum_user['pm_long_subject'] ? ' <a class="mess" href="'.$message_link.'">'.forum_htmlencode(preg_replace('#((?:\S*\s*){20})(?:.*)$#su', '$1', $message['body'])).'</a>' : '').'</span>',
				format_time($message['sent_at']),
			);
			echo "\t\t\t\t\t".'<tr'.($forum_page['type'] == 'inbox' && $message['status'] == 'delivered' ? ' class="pm_new"' : ''.'>')."\n";
			$col_count = 0;
			foreach ($message_info as $value)
				echo "\t\t\t\t\t\t".'<td class="td'.++$col_count.'">'.$value.'</td>'."\n";
			echo "\t\t\t\t\t".'</tr>'."\n";
		}

		($hook = get_hook('fn_pm_box_pre_frm-buttons')) ? eval($hook) : null;

?>
					</tbody>
				</table>
				</div>
			</fieldset>
			<div class="frm-buttons">
				<span class="submit"><input type="submit" name="delete_<?php echo $forum_page['type'] ?>" value="<?php echo $lang_profile['Delete selected'] ?>" onclick="return pm_confirm_delete();"/></span>
			</div>
		</form>
	</div>

	<script type="text/javascript">
	function pm_confirm_delete()
	{
		var a = document.all && !window.opera ? document.all : document.getElementsByTagName("*");
		var count = 0;
		for (var i = a.length; i--;)
		{
			if (a[i].tagName.toLowerCase() == 'input' && a[i].getAttribute("type") == "checkbox" && a[i].getAttribute("name") == "delete[]" && a[i].checked)
				count++;
		}
		if (!count)
		{
			alert("<?php echo $lang_profile['Not selected']?>");
			return false;
		}
		return confirm("<?php echo $lang_profile['Selected messages']?> " +count+ "\n<?php echo $lang_profile['Delete confirmation']?>");
	}

	function pm_select_all(all_checked)
	{
		var a = document.all && !window.opera ? document.all : document.getElementsByTagName("*");

		for (var i = a.length; i--;)
		{
			if (a[i].tagName.toLowerCase() == 'input' &&	a[i].getAttribute("type") == "checkbox" && a[i].getAttribute("name") == "delete[]")
				a[i].checked = all_checked;
		}
		return true;
	}
	</script>

<div id="brd-pagepost-end" class="main-pagepost gen-content">
	<p class="paging"><span class="pages"><?php echo $lang_common['Pages'].'</span> '.paginate($forum_page['num_pages'], $forum_page['page'], $forum_url['pm'.($forum_page['type'] ? '' : '_outbox')], $lang_common['Paging separator'], $forum_user['id']) ?></p>
	<p class="posting"><a class="newpost" href="<? echo forum_link($forum_url['pm'], array($forum_user['id'], 'write')) ?>"><span><?php echo $lang_profile['New message'] ?></span></a></p>
</div>
<?php

	}

	$result = ob_get_contents();
	ob_end_clean();

	($hook = get_hook('fn_pm_box_end')) ? eval($hook) : null;

	return $result;
}

define('FORUM_FUNCTIONS_PM_BOX', 1);
