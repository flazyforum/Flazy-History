<?php
/**
* @version $Id: strcasecmp.php,v 1.1 2006/02/25 13:50:17 harryf Exp $
*/

/**
* UTF-8 aware alternative to strcasecmp
* A case insensivite string comparison
* Note: requires utf8_strtolower
* @param string
* @param string
* @return int
* @package utf8
*/
function utf8_strcasecmp($strX, $strY)
{
	$strX = utf8_strtolower($strX);
	$strY = utf8_strtolower($strY);
	return strcmp($strX, $strY);
}
