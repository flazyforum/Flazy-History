<?php
/**
 * Показывает список смайлов.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */


// Send no-cache headers
header('Expires: Thu, 21 Jul 1977 07:30:00 GMT'); // When yours truly first set eyes on this world! :)
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache'); // For HTTP/1.0 compability
header('Content-Type: text/html; charset=utf-8');

if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

($hook = get_hook('sml_start')) ? eval($hook) : null;

if (!$forum_user['g_read_board'])
	message($lang_common['No view']);

// Load the smilie.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/smilies.php';

define('FORUM_PAGE', 'smilies');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir="<?php echo $lang_common['lang_direction']; ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf8" />
<title><?php echo $lang_common['Smilies'].$lang_common['Title separator'].$forum_config['o_board_title'] ?></title>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo $base_url ?>'/favicon.ico" />
<?php

$style_url = $base_url.'/style/gzip.php?style='.$base_url;

// Include stylesheets
require FORUM_ROOT.'style/'.$forum_user['style'].'/'.$forum_user['style'].'.php';

($hook = get_hook('sml_pre_loader')) ? eval($hook) : null;

?>
<script type="text/javascript" src="<?php echo $base_url ?>/include/js/bb.smilies.js"></script>
</head>
<body>

<div id="brd-wrap" class="brd">
<div id="brd-post" class="brd-page basic-page">

<div class="main-head">
	<h1 class="hn"><span><?php echo $lang_smilies['Smilies'] ?></span></h1>
</div>
	<div class="main-content">
		<div class="main-subhead">
			<h2 class="hn"><span><?php  echo $lang_smilies['Click smilies'] ?></span></h2>
		</div>
		<div class="txt-box" style="padding: 0.25em 0 0.25em 1em;">
<?php

($hook = get_hook('sml_pre_load_smilies')) ? eval($hook) : null;

// Все смайлики
require FORUM_ROOT.'include/smilies.php';

$smiley_groups = array();
foreach ($smilies as $smiley_text => $smiley_img)
	$smiley_groups[$smiley_img][] = $smiley_text;

foreach ($smiley_groups as $smiley_img => $smiley_texts)
{

?>
			<a href="javascript:insert_text('<?php echo $smiley_texts[0] ?>', '');"><img src="<?php echo $base_url ?>/img/smilies/<?php echo $smiley_img ?>" alt="<?php echo $smiley_texts[0] ?>"  title="<?php echo $smiley_texts[0] ?>"/></a>
<?php

}

?>
		</div>
	<p style="text-align:center"><a href="javascript:self.close();"><strong><?php  echo $lang_smilies['Closed'] ?></strong></a></p>
	</div>
</div>
</div>
<?php ($hook = get_hook('sml_end')) ? eval($hook) : null; ?>
</body>
</html>