<?php
/**
 * Отображает панель ББ-кодов.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


($hook = get_hook('bb_fl_start')) ? eval($hook) : null;


if ($forum_config['p_enable_bb_panel'] && $forum_user['show_bb_panel'])
{
	// Load the bb.php language file
	require FORUM_ROOT.'lang/'.$forum_user['language'].'/bb.php';
	$forum_js->addFile($base_url.'/js/bb.js');
	$url_bl = $base_url.'/img/style/b_bl.gif';

	// li id => (js onclick , lang_bb)
	$bbcode = array(
		'font'		=> 	array('visibility(\'font-area\')', 'Font'),
		'size'		=> 	array('visibility(\'size-area\')', 'Size'),
		'bold'		=> 	array('bbcode(\'[b]\',\'[/b]\')', 'Bold'),
		'italic'	=> 	array('bbcode(\'[i]\',\'[/i]\')', 'Italic'),
		'underline'	=> 	array('bbcode(\'[u]\',\'[/u]\')', 'Underline'),
		'strike'	=> 	array('bbcode(\'[s]\',\'[/s]\')', 'Strike'),
		'left'		=> 	array('bbcode(\'[left]\',\'[/left]\')', 'Left'),
		'center'	=> 	array('bbcode(\'[center]\',\'[/center]\')', 'Center'),
		'right'		=> 	array('bbcode(\'[right]\',\'[/right]\')', 'Right'),
		'list'		=> 	array('bbcode(\'[list=*][*]\',\'[/*][/list]\')', 'List'),
		'link'		=> 	array('tag(\'[url]\',\'[/url]\', tag_url)', 'Link'),
		'email'		=> 	array('tag(\'[email]\',\'[/email]\', tag_email)', 'Email'),
		'image'		=> 	array('tag(\'[img]\',\'[/img]\', tag_image)', 'Image'),
		'video'		=> 	array('tag(\'[video]\',\'[/video]\', tag_video)', 'Video'),
		'hide'		=> 	array('tag_hide()', 'Hide'),
		'quote'		=> 	array('bbcode(\'[quote]\',\'[/quote]\')', 'Quote'),
		'code'		=> 	array('bbcode(\'[code]\',\'[/code]\')', 'Code'),
		'color'		=> 	array('visibility(\'color-area\')', 'Color'),
		'smile'		=> 	array('visibility(\'smilies-area\')', 'Smile'),
		'speller'	=> 	array('spellCheck()', 'Speller'),
	);

	($hook = get_hook('bb_fl_pre_bb_list')) ? eval($hook) : null;

?>
					<ul id="bbcode">
<?

	foreach ($bbcode as $bb_type => $bb_text)
	{
?>
						<li id="bt-<?php echo $bb_type ?>"><span><img onclick="<?php echo $bb_text['0'] ?>" src="<?php echo $url_bl ?>" title="<?php echo $lang_bb[$bb_text['1']] ?>" alt="" /></span></li>
<?php

	}
?>
					</ul>
					<div class="bbm" id="font-area" style="display:none" onclick="visibility('font-area')">
<?php

	$font_list = array('Arial', 'Arial Black', 'Arial Narrow', 'Book Antiqua', 'Century Gothic', 'Comic Sans Ms', 'Courier New', 'Fixedsys', 'Franklin Gothic Medium', 'Garamond', 'Georgia', 'Impact', 'Lucida Console', 'Microsoft Sans Serif', 'Palatino Linotype', 'System', 'Tahoma', 'Times New Roman', 'Trebuchet Ms', 'Verdana');

	($hook = get_hook('bb_fl_pre_bb_list_font')) ? eval($hook) : null;

	foreach ($font_list as $font)
	{

?>
						<div style="font-family:<?php echo $font ?>"><span><?php echo $font ?></span><img onclick="bbcode('[font=<?php echo $font ?>]','[/font]')" src="<?php echo $url_bl ?>" /></div>
<?php

	}

?>
					</div>
					<div class="bbm" id="size-area" style="display:none" onclick="visibility('size-area')">
<?php
 
	$size_list = array('8', '10', '12', '14', '16', '18', '20');

	($hook = get_hook('bb_fl_pre_bb_list_size')) ? eval($hook) : null;

	foreach ($size_list as $size)
	{

?>
						<div style="font-size:<?php echo $size ?>px"><span><?php echo $size ?>px</span><img onclick="bbcode('[size=<?php echo $size ?>]','[/size]')" src="<?php echo $url_bl ?>" /></div>
<?php

	}

?>
					</div>
					<div class="bbm" id="color-area" style="display:none" onclick="visibility('color-area')">
					<table cellspacing="0" cellpadding="0">
						<tr>
<?php 

	$color_list = array('black', 'silver', 'gray', 'white', 'maroon', 'red', 'purple', 'fuchsia', 'green', 'lime', 'olive', 'yellow', 'navy', 'blue', 'teal', 'aqua');

	($hook = get_hook('bb_fl_pre_bb_list_color')) ? eval($hook) : null;

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
					<div class="bbm" id="smilies-area" style="display:none" onclick="visibility('smilies-area')">
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
						<img onclick="smile('<?php echo $smiley_texts['0'] ?>')" src="<?php echo $base_url ?>/img/smilies/<?php echo $smiley_img ?>" alt="<?php echo $smiley_texts['0'] ?>" title="<?php echo $smiley_texts['0'] ?>"/>
<?php

	}

?>
						<p><a href="<?php echo forum_link('smilies.php') ?>" onclick="return smile_pop(this.href);"><span><?php echo $lang_bb['All'] ?></span></a></p>
					</div>
<?php

}

($hook = get_hook('bb_fl_end')) ? eval($hook) : null;

?>