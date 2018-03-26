<?php
/**
 * Поля для создания и редактирования опроса.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


function form_poll($question, $poll_answers, $options_count, $days, $votes)
{
	global $forum_user, $lang_post, $forum_config, $read_unvote_users, $revote;

	$return = ($hook = get_hook('fn_form_poll_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Poll question'] ?></span><small><?php echo $lang_post['Poll question info'] ?></small></label>
						<span class="fld-input"><input type="text" id="quest" class="inputbox" name="question" size="80" maxlength="150" value="<?php echo forum_htmlencode(forum_trim($question)); ?>" /></span>
					</div>
				</div>
<?php

	//Validate of pull_answers
	if ($poll_answers != null)
	{
		$count_answers = count($poll_answers);
		for ($ans_num = 0; $ans_num < $count_answers; $ans_num++)
			$poll_answers[$ans_num] = forum_trim($poll_answers[$ans_num]);
		$poll_answers = array_unique($poll_answers);
	}

	for ($opt_num = 0; $opt_num < $options_count; $opt_num++)
	{

?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Voting answer'] ?></span>
						</label>
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" class="inputbox" name="answer[]" size="80" maxlength="70" value="<?php echo ($poll_answers != null && isset($poll_answers[$opt_num]) ? forum_htmlencode(forum_trim($poll_answers[$opt_num])) : '') ?>" /></span>
					</div>
				</div>
<?php

	}

?>
			</fieldset>
			<fieldset class="frm-group frm-hdgroup group<?php echo ++$forum_page['group_count'] ?>">
				<fieldset class="mf-set set<?php echo ++$forum_page['item_count'] ?> mf-head">
					<legend><span><?php echo $lang_post['Summary count'] ?></span></legend>
					<div class="mf-box">
						<div class="mf-field mf-field1">
							<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span class="fld-label"><?php echo $lang_post['Count'] ?></span></label>
							<span class="fld-input"><input id="fld<?php echo ++$forum_page['fld_count'] ?>" type="text" class="inputbox" name="ans_count" size="5" maxlength="5" value="<?php echo $options_count ?>" /></span>
						</div>
						<div class="mf-field">
							<span class="submit"><input type="submit" name="update_poll" value="<?php echo $lang_post['Button note'] ?>" /></span>
						</div>
					</div>
				</fieldset>
			</fieldset>
<?php

	$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

?>
			<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
	<?php if ($forum_config['p_poll_enable_read']): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="first_option" value="1" name="read_unvote_users" <?php echo isset($_POST['read_unvote_users']) || $read_unvote_users ? 'checked' : '' ?> /></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_post['Show poll'] ?></span><?php echo $lang_post['Show poll info'] ?></label>
					</div>
				</div>
	<?php endif;
	if ($forum_config['p_poll_enable_revote']): ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box checkbox">
						<span class="fld-input"><input type="checkbox" id="second_option" value="1" name="revouting"<?php echo isset($_POST['revouting']) || $revote ? ' checked' : '' ?>/></span>
						<label for="fld<?php echo $forum_page['fld_count'] ?>"><span><?php echo $lang_post['Allow revote'] ?></span><?php echo $lang_post['Allow revote info'] ?></label>
					</div>
				</div>
	<?php endif; ?>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Allow days'] ?></span><small><?php echo $lang_post['Allow days info']; ?></small></label>
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" class="inputbox" name="days" size="5" maxlength="5" value="<?php echo isset($days) ? $days : ''; ?>" /></span>
					</div>
				</div>
				<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
					<div class="sf-box text">
						<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_post['Maximum votes'] ?></span><small><?php echo $lang_post['Maximum votes info'] ?></small></label>
						<span class="fld-input"><input id="fld<?php echo $forum_page['fld_count'] ?>" type="text" class="inputbox" name="votes" size="5" maxlength="5" value="<?php echo ($votes == null) ? '' : $votes; ?>" /></span>
					</div>
				</div>
			</fieldset>
<?php

}
