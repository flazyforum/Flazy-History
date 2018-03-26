<?php
/**
 * Функция генирирующая форму отправки личного сообщения.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

function pm_send_form($username = '', $subject = '', $body = '', $message_id = false, $reply_form = false, $notice = false, $preview = false)
{
	global $forum_config, $forum_url, $forum_user, $lang_common, $lang_profile, $pm_errors, $forum_js, $base_url;

	$return = ($hook = get_hook('fn_pm_send_form_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	// Setup the form
	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

	$username = forum_htmlencode($username);

	// Setup the form
	$forum_page['set_count'] = $forum_page['fld_count'] = 0;
	$forum_page['form_action'] = forum_link($forum_url['pm_send'], $forum_user['id']);

	$forum_page['hidden_fields'] = array(
		'csrf_token'		=> '<input type="hidden" name="csrf_token" value="'.generate_form_token($forum_page['form_action']).'" />',
		'send_action'		=> '<input type="hidden" name="send_action" value="" />'
	);

	if ($message_id !== false)
	{
		// Edit message
		$forum_page['hidden_fields']['message_id'] = '<input type="hidden" name="message_id" value="'.$message_id.'" />';
		$forum_page['heading'] = $lang_profile['Edit message'];
	}
	elseif ($reply_form !== false)
	{
		$forum_page['heading'] = $lang_profile['Quick reply'];
		$forum_page['hidden_fields']['pm_receiver'] = '<input type="hidden" name="pm_receiver" value="'.$username.'" />';
	}
	else
		$forum_page['heading'] = $lang_profile['New message'];

	($hook = get_hook('fn_pm_send_pre_text_options')) ? eval($hook) : null;

	// Setup help
	$forum_page['text_options'] = array();
	if ($forum_config['p_message_bbcode'])
		$forum_page['text_options']['bbcode'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'bbcode').'" title="'.sprintf($lang_common['Help page'], $lang_common['BBCode']).'">'.$lang_common['BBCode'].'</a></span>';
	if ($forum_config['p_message_img_tag'])
		$forum_page['text_options']['img'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'img').'" title="'.sprintf($lang_common['Help page'], $lang_common['Images']).'">'.$lang_common['Images'].'</a></span>';
	if ($forum_config['o_smilies'])
		$forum_page['text_options']['smilies'] = '<span'.(empty($forum_page['text_options']) ? ' class="item1"' : '').'><a class="exthelp" href="'.forum_link($forum_url['help'], 'smilies').'" title="'.sprintf($lang_common['Help page'], $lang_common['Smilies']).'">'.$lang_common['Smilies'].'</a></span>';

	ob_start();

	if ($reply_form === false)
	{
		if ($preview !== false)
			echo $preview;

?>
<div class="main-subhead">
	<h2 class="hn"><span><?php echo $forum_page['heading'] ?></span></h2>
</div>
	<div class="main-content main-frm">
<?php

		if (!empty($forum_page['text_options']))
			echo "\t\t".'<p class="ct-options options">'.sprintf($lang_common['You may use'], implode(' ', $forum_page['text_options'])).'</p>'."\n";
	}

	if (!empty($pm_errors))
	{

		$forum_page['errors'] = array();
		foreach ($pm_errors as $cur_error) 
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';
?>
		<div class="ct-box error-box">
			<h2 class="warn"><?php echo $lang_profile['Messsage send errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
<?php

	}

?>
		<form class="frm-form" name="post" method="post" accept-charset="utf-8" action="<?php echo $forum_page['form_action'] ?>">
			<div class="hidden">
				<?php echo implode("\n\t\t\t\t", $forum_page['hidden_fields'])."\n" ?>
			</div>

<?php

	if ($notice !== false)
		echo $notice;

?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<legend class="group-legend"><strong><?php echo $lang_profile['Send message'] ?></strong></legend>
				
<?php

	if ($reply_form === false)
	{

?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['To'] ?> <em><?php echo $lang_common['Required'] ?></em></span> <small><?php echo $lang_profile['Receivers username'] ?></small></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="pm_receiver" value="<?php echo $username ?>" size="80" maxlength="255" class="inputbox" /></span>
					</div>
				</div>
<?php

	}

?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text required longtext">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Subject'] ?></span></label><br />
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" name="pm_subject" value="<?php echo forum_htmlencode($subject) ?>" size="80" maxlength="255" class="inputbox" /></span>
					</div>
				</div>
				<div class="txt-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="txt-box textarea required">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_profile['Message'] ?> <em><?php echo $lang_common['Required'] ?></em></span></label>
<?php require FORUM_ROOT.'bb.php'; ?>
						<div class="txt-input"><span class="fld-input"><textarea id="fld<?php echo $forum_page['fld_count'] ?>" class="inputbox" name="req_message" rows="10" cols="65"><?php echo forum_htmlencode($body) ?></textarea></span></div>
					</div>
				</div>
			</fieldset>
			<div class="frm-buttons">
<?php

	if ($message_id !== false)
	{

?>
				<div style="float: right;"><input type="submit" name="delete" value="<?php echo $lang_profile['Delete draft'] ?>" onclick="return confirm('<?php echo $lang_profile['Confirm delete draft'] ?>');" /></div>
<?php

	}

?>
				<span class="submit"><input type="submit" name="submit" value="<?php echo $lang_profile['Send button'] ?>" /></span>
				<span class="submit"><input type="submit" name="preview" value="<?php echo $lang_profile['Preview'] ?>" /></span>
				<span class="submit"><input type="submit" name="draft" value="<?php echo $lang_profile['Save draft'] ?>" /></span>
			</div>
		</form>
	</div>
<?php

	$result = ob_get_contents();
	ob_end_clean();

	($hook = get_hook('fn_pm_send_form_end')) ? eval($hook) : null;

	return $result;
}

define('FORUM_FUNCTIONS_PM_SEND_FORM', 1);
