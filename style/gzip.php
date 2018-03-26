<?php
/**
 * gzip для css.
 *
 * @copyright Copyright (C) 2008-2009 Flazy.ru, based on code copyright (C) 2002-2009 PunBB.org
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


define('NO_PREV_URL', 1);
if (!defined('FORUM_ROOT'))
	define('FORUM_ROOT', '../');
require FORUM_ROOT.'include/common.php';

$style = isset($_GET['style']) ? $_GET['style'] : null;


function compress($code)
{
	global $forum_user, $style;
	// Удалим коментарии
	$code = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $code);
	// Удалим табы, пробелы, переносы.
	$code = str_replace(array("\r\n", "\r", "\n", "\t"), '', $code);
	// Костыль
	if (strpos($style, 'imgs.css') === false)
		$code = str_replace('url(', 'url('.$forum_user['style'].'/', $code);
	return $code;
}

$file = str_replace($base_url, '', $style);

if (file_exists(FORUM_ROOT.$file))
{
	if (isset($_SERVER['HTTP_IF_NONE_MATCH']))
	{
		if (get_file_etag($style) === $_SERVER['HTTP_IF_NONE_MATCH'])
		{
			header('HTTP/1.0 304 Not Modified');
			exit();
		}
	}
	$css_cache = 86400*365; // 3600=1час, 86400=1день
	$style = str_replace($base_url.'/', FORUM_ROOT, $style);

	// CSS headers
	header('Content-type: text/css; charset=utf-8');
	header('Cache-Control: public; must-revalidate; max-age='.$css_cache);
	header('Expires: '.gmdate('D, d M Y H:i:s', time() + $css_cache).' GMT');
	ob_start('compress');
	echo file_get_contents($style);
	ob_end_flush();
}
else
{
	header('HTTP/1.1 303 See Other');
	header('Location: '.$base_url);
}

?>