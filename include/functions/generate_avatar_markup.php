<?php
/**
 * Функция отображения аватара.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

// Действия разметки и отображения аватара участника
function generate_avatar_markup($user_id, $filetypes, $user_email)
{
	global $base_url, $forum_config;

	$return = ($hook = get_hook('fn_generate_avatar_markup_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	if ($filetypes)
	{
		$path = FORUM_AVATAR_DIR.'/'.$user_id.'.'.$filetypes;
		$img_size = @getimagesize($path);
		$avatar_markup = '<img src="'.$base_url.'/'.$path.'?m='.filemtime($path).'" '.$img_size[3].' alt="" />';
	}
	else
	{
		$default = $base_url.'/'.FORUM_AVATAR_DIR.'/no.gif';
		$size = $forum_config['o_avatars_width'];
		$avatar_markup = '<img src="http://www.gravatar.com/avatar.php?gravatar_id='.md5($user_email).'&amp;d='.$default.'&amp;s='.$size.'" alt="" />';
	}

	($hook = get_hook('fn_generate_avatar_markup_end')) ? eval($hook) : null;

	return $avatar_markup;
}

define('FORUM_FUNCTIONS_GENERATE_AVATAR', 1);
