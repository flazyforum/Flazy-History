<?php
/**
 * Скрипт страниц ошибок.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL версии 2 или выше
 * @package Flazy
 */


/************************************************************

Для работы скрипта обязательно наличие в .htaccess ErrorDocument

ErrorDocument 400 /error.php?id=400
ErrorDocument 401 /error.php?id=401
ErrorDocument 403 /error.php?id=403
ErrorDocument 404 /error.php?id=404
ErrorDocument 500 /error.php?id=500

************************************************************/


if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', './');
require FORUM_ROOT.'include/common.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (($id < 400) || ($id == 402) || (($id > 404) and ($id < 500)) || ($id > 500))
	$id = 404;

// Load the index.php language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/error.php';

$id = forum_htmlencode($id);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru" dir="ltr">
<head>
<meta http-equiv="refresh" content="10; url=<?php echo $base_url ?>" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $lang_error['title '.$id].$lang_common['Title separator'].$forum_config['o_board_title'] ?></title>
<link rel="shortcut icon" type="image/x-icon" href="<?php echo $base_url ?>'/favicon.ico" />
</head>
<body style="font-family:Arial, Verdana, Helvetica; background: #fff;" onLoad="countdown()">
<div style="border-bottom: 1px solid #CFCFCF">
<div style="float:right"><h1><? echo $id ?></h1></div>
<a href="<?php echo $base_url ?>/index.php" /><img src="<?php echo $base_url ?>/img/style/error.png" border="0" alt="" /></a>
</div>
<div style="text-align:center">
	<h2><?php echo $lang_error['desc '.$id] ?></h2>
	<h3><?php echo $lang_error['kod '.$id] ?></h3>
	<p><?php echo $lang_error['board '.$id] ?></p>
	<p><?php echo sprintf($lang_error['Search'], forum_link($forum_url['search']), forum_link($forum_url['index'])) ?></p>
</div>
<script type="text/javascript">
var count = new Number();
var count = 10;
function countdown()
{
	if((count-1) >= 0)
	{
		count = count-1;
		document.getElementById("count").innerHTML=''+count;
		setTimeout('countdown()',1000);
	}
}
</script>

<br />
<hr />
	<div style="text-align:center"><?php echo sprintf($lang_error['Redirect'], '<font c size="6"><div id="count"></div></font>') ?></div>
<hr />

</body>
</html>