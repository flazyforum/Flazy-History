<?php
/**
 * Функция отображения аватара.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

// Действия разметки и отображения аватара участника
function generate_avatar_markup($user_id)
{
	global $forum_config, $base_url;

	$filetypes = array('jpg', 'gif', 'png');

	$return = ($hook = get_hook('fn_generate_avatar_markup_start')) ? eval($hook) : null;
	if ($return != null)
		return $return;

	foreach ($filetypes as $cur_type)
	{
		$path = $forum_config['o_avatars_dir'].'/'.$user_id.'.'.$cur_type;
		// Аватар по умолчанию no.Расширение
		$path_def = $forum_config['o_avatars_dir'].'/no.'.$cur_type;

		if (file_exists(FORUM_ROOT.$path) && $img_size = @getimagesize(FORUM_ROOT.$path))
		{
			$avatar_markup = '<img src="'.$base_url.'/'.$path.'?m='.filemtime(FORUM_ROOT.$path).'" '.$img_size[3].' alt="" />';
			break;
		}
		else if (file_exists(FORUM_ROOT.$path_def))
			$avatar_markup = '<img src="'.$base_url.'/'.$forum_config['o_avatars_dir'].'/no.'.$cur_type.'" alt="" />';
	}

	($hook = get_hook('fn_generate_avatar_markup_end')) ? eval($hook) : null;

	return $avatar_markup;
}

define('FORUM_FUNCTIONS_GENERATE_AVATAR', 1);
