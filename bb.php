<?php
/**
 * Отображает панель ББ-кодов.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


($hook = get_hook('bb_start')) ? eval($hook) : null;


if ($forum_config['p_enable_bb_panel'] && $forum_user['show_bb_panel'])
{
        $forum_js->addFile($base_url.'/include/js/bb.js');

	$url_bl = $base_url.'/img/style/b_bl.gif';
	$bbcode = array(
		'font'		=> 	array('changeVisibility(\'font-area\')', 'Шрифт'),
		'size'		=> 	array('changeVisibility(\'size-area\')', 'Размер текста'),
		'bold'		=> 	array('bbcode(\'[b]\',\'[/b]\')', 'Жирный'),
		'italic'	=> 	array('bbcode(\'[i]\',\'[/i]\')', 'Наклонный'),
		'underline'	=> 	array('bbcode(\'[u]\',\'[/u]\')', 'Подчеркнутый'),
		'strike'	=> 	array('bbcode(\'[s]\',\'[/s]\')', 'Зачеркнутый'),
		'left'		=> 	array('bbcode(\'[left]\',\'[/left]\')', 'Выравнивание по левому краю'),
		'center'	=> 	array('bbcode(\'[center]\',\'[/center]\')', 'Выравнивание по центру'),
		'right'		=> 	array('bbcode(\'[right]\',\'[/right]\')', 'Выравнивание по правому краю'),
		'list'		=> 	array('bbcode(\'[list=*][*]\',\'[/*][/list]\')', 'Список'),
		'link'		=> 	array('tag(\'[url]\',\'[/url]\', tag_url)', 'Ссылка'),
		'email'		=> 	array('tag(\'[email]\',\'[/email]\', tag_email)', 'E-mail'),
		'image'		=> 	array('tag(\'[img]\',\'[/img]\', tag_image)', 'Изображение'),
		'video'		=> 	array('tag(\'[video]\',\'[/video]\', tag_video)', 'Видео'),
		'hide'		=> 	array('tag_hide()', 'Хайд'),
		'spo'		=> 	array('tag(\'[spoiler]\',\'[/spoiler]\', tag_spoiler)', 'Спойлер'),
		'quote'		=> 	array('bbcode(\'[quote]\',\'[/quote]\')', 'Цитата'),
		'code'		=> 	array('bbcode(\'[code]\',\'[/code]\')', 'Код'),
		'color'		=> 	array('changeVisibility(\'color-area\')', 'Цвет'),
		'smile'		=> 	array('changeVisibility(\'smilies-area\')', 'Смайлики'),
	);

	($hook = get_hook('bb_pre_bb_list')) ? eval($hook) : null;

?>
					<ul id="bbcode">
<?

	foreach ($bbcode as $bb_type => $bb_text)
	{
?>
						<li id="bt-<?php echo $bb_type ?>"><span><img onclick="<?php echo $bb_text[0] ?>" src="<?php echo $url_bl ?>" title="<?php echo $bb_text[1] ?>" alt="" /></span></li>
<?php

	}
?>
					</ul>
					<div class="bbm" id="font-area" style="display:none" onclick="changeVisibility('font-area')">
<?php

	$font_list = array(1 => 'Arial', 'Arial Black', 'Arial Narrow', 'Book Antiqua', 'Century Gothic', 'Comic Sans Ms', 'Courier New', 'Fixedsys', 'Franklin Gothic Medium', 'Garamond', 'Georgia', 'Impact', 'Lucida Console', 'Microsoft Sans Serif', 'Palatino Linotype', 'System', 'Tahoma', 'Times New Roman', 'Trebuchet Ms', 'Verdana');

	($hook = get_hook('bb_pre_bb_list_font')) ? eval($hook) : null;

	foreach ($font_list as $font)
	{

?>
						<div style="font-family:<?php echo $font ?>"><span><?php echo $font ?></span><img onclick="bbcode('[font=<?php echo $font ?>]','[/font]')" src="<?php echo $url_bl ?>" /></div>
<?php

	}

?>
					</div>
					<div class="bbm" id="size-area" style="display:none" onclick="changeVisibility('size-area')">
<?php
 
	$size_list = array(1 => '8', '10', '12', '14', '16', '18', '20');

	($hook = get_hook('bb_pre_bb_list_size')) ? eval($hook) : null;

	foreach ($size_list as $size)
	{

?>
						<div style="font-size:<?php echo $size ?>px"><span><?php echo $size ?>px</span><img onclick="bbcode('[size=<?php echo $size ?>]','[/size]')" src="<?php echo $url_bl ?>" /></div>
<?php

	}

?>
					</div>
					<div class="bbm" id="color-area" style="display:none" onclick="changeVisibility('color-area')">
					<table cellspacing="0" cellpadding="0">
						<tr>
<?php 

	$color_list = array(1 => 'black', 'silver', 'gray', 'white', 'maroon', 'red', 'purple', 'fuchsia', 'green', 'lime', 'olive', 'yellow', 'navy', 'blue', 'teal', 'aqua');

	($hook = get_hook('bb_pre_bb_list_color')) ? eval($hook) : null;

	foreach ($color_list as $color)
	{

?>
							<td style="background-color:<?php echo $color ?>"><img onclick="bbcode('[color=<?php echo $color ?>]','[/color]')" src="<?php echo $url_bl ?>" /></td>
<?php

	}

?>
						</tr>
					</table>
					</div>
					<div class="bbm" id="smilies-area" style="display:none" onclick="changeVisibility('smilies-area')">
<?php

	if (!defined('FORUM_SMILIES_LOADED'))
		require FORUM_ROOT.'include/smilies.php';

	$smiley_groups = array();
	foreach ($smilies as $smiley_text => $smiley_img)
		$smiley_groups[$smiley_img][] = $smiley_text;

	// Ограничим количество смайлов
	$smiley_groups = array_slice($smiley_groups, 0, $forum_config['p_bb_panel_smilies']);
	foreach ($smiley_groups as $smiley_img => $smiley_texts)
	{

?>
						<img onclick="smile('<?php echo $smiley_texts[0] ?>')" src="<?php echo $base_url ?>/img/smilies/<?php echo $smiley_img ?>" alt="<?php echo $smiley_texts[0] ?>" title="<?php echo $smiley_texts[0] ?>"/>
<?php

	}

?>
						<p><a href="<?php echo forum_link('smilies.php') ?>" onclick="return smile_pop(this.href);"><span><?php echo $lang_common['All'] ?></span></a></p>
					</div>
<?php

}

($hook = get_hook('bb_end')) ? eval($hook) : null;

?>