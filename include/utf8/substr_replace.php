<?php
/**
* @version $Id: substr_replace.php,v 1.1 2006/02/25 13:50:17 harryf Exp $
*/

/**
* UTF-8 aware substr_replace.
* Note: requires utf8_substr to be loaded
*/
function utf8_substr_replace($str, $repl, $start , $length = null )
{
	preg_match_all('/./us', $str, $ar);
	preg_match_all('/./us', $repl, $rar);

	if($length === null)
		$length = utf8_strlen($str);

	array_splice($ar[0], $start, $length, $rar[0]);
	return join('',$ar[0]);
}
