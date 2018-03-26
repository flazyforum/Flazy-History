<?php
/**
 * Редактирование личного сообщения.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

function pm_edit_message()
{
	global $forum_db, $forum_user, $lang_profile;

	$return = ($hook = get_hook('fn_pm_edit_message_start')) ? eval($hook) : null;
	if ($return != null)
		return;

	$message_id = isset($_GET['message_id']) ? $_GET['message_id'] : '';
	$errors = array();

	// Verify input data
	$query = array(
		'SELECT'	=> 'm.id, m.sender_id, m.status, u.username, m.subject, m.body',
		'FROM'		=> 'pm AS m',
		'JOINS'		=> array(
			array(
				'LEFT JOIN'	=> 'users AS u',
				'ON'		=> 'u.id=m.receiver_id'
			),
		),
		'WHERE'		=> 'm.id='.$forum_db->escape($message_id).' AND m.sender_id='.$forum_user['id'].' AND m.deleted_by_sender=0'
	);

	($hook = get_hook('fn_pm_edit_message_qr_get')) ? eval($hook) : null;
	$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

	if ($forum_db->num_rows($result) != 0)
	{
		$row = $forum_db->fetch_assoc($result);
		if ($row['status'] == 'sent')
		{
			// Change status to 'draft'
			$query = array(
				'UPDATE'	=> 'pm',
				'SET'		=> 'status=\'draft\', lastedited_at='.time(),
				'WHERE'		=> 'id='.$forum_db->escape($message_id).' AND (status=\'draft\' OR status=\'sent\')'
			);

			($hook = get_hook('fn_pm_edit_update_status_qr_get')) ? eval($hook) : null;
			$result = $forum_db->query_build($query) or error(__FILE__, __LINE__);

			// An error occured
			if ($forum_db->affected_rows() == 0)
				$errors[] = $lang_profile['Delivered message'];
		}
		elseif ($row['status'] != 'draft')
			$errors[] = $lang_profile['Delivered message'];
	}
	else
		$errors[] = $lang_profile['Non-existent message'];

	// An error occured. Go displaying error message
	if (count($errors))
	{
		$forum_page['errors'] = array(); 
		foreach ($errors as $cur_error) 
			$forum_page['errors'][] = '<li class="warn"><span>'.$cur_error.'</span></li>';

		ob_start();

?>
	<div class="main-content main-frm">
		<div class="ct-box error-box">
			<h2 class="warn"><?php echo $lang_profile['Messsage edit errors'] ?></h2>
			<ul class="error-list">
				<?php echo implode("\n\t\t\t\t", $forum_page['errors'])."\n" ?>
			</ul>
		</div>
	</div>
<?php

		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	$notice = $row['status'] == 'sent' ? "\t\t\t".'<div class="ct-box info-box">'."\n\t\t\t\t".'<p class="warn">'.$lang_profile['Sent -> draft'].'</p>'."\n\t\t\t".'</div>'."\n" : false;
	$preview = $row['status'] == 'draft' ? pm_preview($row['username'], $row['subject'], $row['body'], $errors) : false;

	($hook = get_hook('fn_pm_edit_message_end')) ? eval($hook) : null;

	if (!defined('FORUM_FUNCTIONS_PM_SEND_FORM'))
		require FORUM_ROOT.'include/functions/pm/send_form.php';

	return pm_send_form($row['username'], $row['subject'], $row['body'], $row['id'], false, $notice, $preview);
}

define('FORUM_FUNCTIONS_PM_EDIT_MESSAGE', 1);
