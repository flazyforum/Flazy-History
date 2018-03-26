<?php
/**
 * Используется в большинстве страниц форума.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

// Рекорд одновременно прибывания на форуме
if (!defined('FORUM_FUNCTIONS_RECORD'))
	require FORUM_ROOT.'include/functions/record.php';

// START SUBST - <!-- forum_about -->
ob_start();

($hook = get_hook('ft_about_output_start')) ? eval($hook) : null;

$forum_page['copyright'] = sprintf($lang_common['Powered by'], '<a href="http://flazy.ru/">Flazy</a>'.($forum_config['o_show_version'] ? ' '.$forum_config['o_cur_version'] : ''));

($hook = get_hook('ft_about_pre_copyright')) ? eval($hook) : null;

?>
	<p id="copyright"><?php echo $forum_page['copyright']; ?></p>
<?php

($hook = get_hook('ft_about_pre_quickjump')) ? eval($hook) : null;

// Display the "Jump to" drop list
if ($forum_user['g_read_board'] && $forum_config['o_quickjump'])
{
	// Load cached quickjump
	if (file_exists(FORUM_CACHE_DIR.'cache_quickjump_'.$forum_user['g_id'].'.php'))
		include FORUM_CACHE_DIR.'cache_quickjump_'.$forum_user['g_id'].'.php';

	if (!defined('FORUM_QJ_LOADED'))
	{
		if (!defined('FORUM_CACHE_FUNCTIONS_QUICKJUMP_LOADED'))
			require FORUM_ROOT.'include/cache/quickjump.php';

		generate_quickjump_cache($forum_user['g_id']);
		require FORUM_CACHE_DIR.'cache_quickjump_'.$forum_user['g_id'].'.php';
	}
}

$tpl_temp = forum_trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_about -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_about -->

($hook = get_hook('ft_about_end')) ? eval($hook) : null;


// START SUBST - <!-- forum_google_analytics -->
if (!empty($forum_config['o_google_analytics']))
{
	if ($forum_config['o_google_analytics_type'] == 'old')
		$tpl_temp = '
<script src="http://www.google-analytics.com/urchin.js" type="text/javascript"></script>
<script type="text/javascript">_uacct = "{ID}";urchinTracker();</script>';
	else if ($forum_config['o_google_analytics_type'] == 'new')
		$tpl_temp = '
<script type="text/javascript">var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");document.write(unescape("%3Cscript src=\'" + gaJsHost + "google-analytics.com/ga.js\' type=\'text/javascript\'%3E%3C/script%3E"));</script>
<script type="text/javascript">var pageTracker = _gat._getTracker("{ID}");pageTracker._trackPageview();</script>';

	$tpl_temp = str_replace('{ID}', $forum_config['o_google_analytics'], $tpl_temp);
	$tpl_main = str_replace('<!-- forum_google_analytics -->', $tpl_temp, $tpl_main);

}
// END SUBST - <!-- forum_google_analytics -->

($hook = get_hook('ft_google_analytics_end')) ? eval($hook) : null;

// START SUBST - <!-- forum_debug -->
if (defined('FORUM_DEBUG') || defined('FORUM_SHOW_QUERIES'))
{
	ob_start();

	($hook = get_hook('ft_debug_output_start')) ? eval($hook) : null;

	// Display debug info (if enabled/defined)
	if (defined('FORUM_DEBUG'))
	{
		// Calculate script generation time
		list($usec, $sec) = explode(' ', microtime());
		$time_diff = forum_number_format(((float)$usec + (float)$sec) - $forum_start, 3);
		echo '<p id="querytime">[ '.sprintf($lang_common['Querytime'], $time_diff, forum_number_format($forum_db->get_num_queries())).' ]</p>'."\n";
	}

	if (defined('FORUM_SHOW_QUERIES'))
	{
		if (!defined('FORUM_FUNCTIONS_GET_SAVED_QUERIES'))
			require FORUM_ROOT.'include/functions/get_saved_queries.php';

		echo get_saved_queries();
	}

	($hook = get_hook('ft_debug_end')) ? eval($hook) : null;

	$tpl_temp = forum_trim(ob_get_contents());
	$tpl_main = str_replace('<!-- forum_debug -->', $tpl_temp, $tpl_main);
	ob_end_clean();
}
// END SUBST - <!-- forum_debug -->

($hook = get_hook('ft_forum_debug_end')) ? eval($hook) : null;

if (isset($forum_js))
	$tpl_main = str_replace('<!-- forum_js -->', $forum_js->headerOut(), $tpl_main);

// START SUBST - <!-- forum_html_bottom -->
if ($forum_config['o_html_bottom'] && !defined('FORUM_DISABLE_HTML'))
	$tpl_main = str_replace('<!-- forum_html_bottom -->', $forum_config['o_html_bottom_message'], $tpl_main);
// END SUBST - <!-- forum_html_bottom -->

// Last call!
($hook = get_hook('ft_end')) ? eval($hook) : null;

// End the transaction
$forum_db->end_transaction();

// Close the db connection (and free up any result data)
$forum_db->close();

// Spit out the page
exit($tpl_main);
