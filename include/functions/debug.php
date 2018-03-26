<?php
/**
 * Создать новое сообщение.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	die;

// DEBUG FUNCTIONS BELOW

// Extract part of a template file
function extract_part($whole, $start, $end)
{
   $start_pos = stripos($whole, $start) + strlen($start);

   $end_pos = stripos($whole, $end, $start_pos + 1);

   return substr($whole, $start_pos, $end_pos - $start_pos);
}

// Dump contents of variable(s)
function dump()
{
	echo '<pre>';

	$num_args = func_num_args();

	for ($i = 0; $i < $num_args; ++$i)
	{
		print_r(func_get_arg($i));
		echo "\n\n";
	}

	echo '</pre>';
	die;
}

define('FORUM_FUNCTIONS_DEBUG', 1);
