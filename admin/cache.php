<?php
/**
 * Скрипт для пересоздания кэш файлов и синхронизацией с базой данных.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


if( !defined ( 'FORUM_ROOT' ) )
	define( 'FORUM_ROOT', '../' );
require FORUM_ROOT.'include/common.php';
require FORUM_ROOT.'include/functions/admin.php';

($hook = get_hook('acs_start')) ? eval($hook) : null;

if ($forum_user['g_id'] != FORUM_ADMIN)
	message($lang_common['No permission']);

// Load the language file
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_common.php';
require FORUM_ROOT.'lang/'.$forum_user['language'].'/admin_cache.php';


$hook = get_hook('acs_pre_cache') ? eval($hook) : null;

function redirect_cache($cache)
{
	global $lang_admin_cache;

	redirect(forum_link('admin/cache.php'), $lang_admin_cache['Update '.$cache.' cache']);
}

if(isset($_POST['bans_cache']))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_bans_cache();
	redirect_cache('bans');
}

if(isset($_POST['censor_cache']))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_censors_cache();
	redirect_cache('censor');
}

if(isset($_POST['config_cache']))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_config_cache();
	redirect_cache('config');
}

if(isset($_POST['hooks_cache']))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_hooks_cache();
	redirect_cache('hooks');
}

if(isset($_POST['ranks_cache']))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_ranks_cache();
	redirect_cache('ranks');
}

if(isset($_POST['updates_cache']))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_updates_cache();
	redirect_cache('updates');
}

if(isset($_POST['quickjump_cache']))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_QUICKJUMP_LOADED'))
		require FORUM_ROOT.'include/cache/quickjump.php';

	generate_quickjump_cache();
	redirect_cache('quickjump');
}

if(isset($_POST['stat_cache']))
{
	if (!defined('FORUM_CACHE_STAT_USER_LOADED'))
		require FORUM_ROOT.'include/cache/stat_user.php';

	generate_stat_user_cache();
	redirect_cache('stat');
}

if(isset($_POST['all_cache']))
{
	if (!defined('FORUM_CACHE_FUNCTIONS_LOADED'))
		require FORUM_ROOT.'include/cache.php';

	generate_bans_cache();
	generate_censors_cache();
	generate_config_cache();
	generate_hooks_cache();
	generate_ranks_cache();
	generate_updates_cache();
	//generate_repository_cache();
	if (!defined('FORUM_CACHE_FUNCTIONS_QUICKJUMP_LOADED'))
		require FORUM_ROOT.'include/cache/quickjump.php';

	generate_quickjump_cache();
	if (!defined('FORUM_CACHE_STAT_USER_LOADED'))
		require FORUM_ROOT.'include/cache/stat_user.php';

	generate_stat_user_cache();
	redirect_cache('all');
}

$hook = get_hook('acs_cache') ? eval($hook) : null;

// Очистка от спама
if (isset($_POST['cleanup']))
{
	@set_time_limit(0);
	$ip = "'".implode("','", array_values(explode(' ', $_POST['ip_addys'])))."'";
	$forum_db->query('DELETE FROM '.$forum_db->prefix.'posts WHERE poster_ip IN('.$ip.')') or error('Could not delete posts', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('DELETE FROM '.$forum_db->prefix.'users WHERE registration_ip IN('.$ip.')') or error('Could not delete users', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_posts SELECT t.forum_id, count(*) as posts FROM '.$forum_db->prefix.'posts as p LEFT JOIN '.$forum_db->prefix.'topics as t on p.topic_id=t.id GROUP BY t.forum_id') or error('Creating posts table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'forums, '.$forum_db->prefix.'forum_posts SET num_posts=posts WHERE id=forum_id') or error('Could not update post counts', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_topics SELECT forum_id, count(*) as topics FROM '.$forum_db->prefix.'topics GROUP BY forum_id') or error('Creating topics table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'forums, '.$forum_db->prefix.'forum_topics SET num_topics=topics WHERE id=forum_id') or error('Could not update topic counts', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'topic_posts SELECT topic_id, count(*)-1 as replies FROM '.$forum_db->prefix.'posts GROUP BY topic_id') or error('Creating topics table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'topics, '.$forum_db->prefix.'topic_posts SET num_replies=replies WHERE id=topic_id') or error('Could not update topic counts', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_last SELECT p.posted AS n_last_post, p.id AS n_last_post_id, p.poster AS n_last_poster, t.forum_id FROM '.$forum_db->prefix.'posts AS p LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id ORDER BY p.posted DESC') or error('Creating last posts table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_lastb SELECT * FROM '.$forum_db->prefix.'forum_last WHERE forum_id > 0 GROUP BY forum_id') or error('Creating last posts tableb failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'forums, '.$forum_db->prefix.'forum_lastb SET last_post_id=n_last_post_id, last_post=n_last_post, last_poster=n_last_poster WHERE id=forum_id') or error('Could not update last post', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'topic_last SELECT posted AS n_last_post, id AS n_last_post_id, poster AS n_last_poster, topic_id FROM '.$forum_db->prefix.'posts ORDER BY posted DESC') or error('Creating last posts table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'topic_lastb SELECT * FROM '.$forum_db->prefix.'topic_last WHERE topic_id > 0 GROUP BY topic_id') or error('Creating last posts tableb failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'topics, '.$forum_db->prefix.'topic_lastb SET last_post_id=n_last_post_id, last_post=n_last_post, last_poster=n_last_poster WHERE id=topic_id') or error('Could not update last post', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'orph_topic SELECT t.id as o_id FROM '.$forum_db->prefix.'topics AS t LEFT JOIN '.$forum_db->prefix.'posts AS p ON p.topic_id = t.id WHERE p.id IS NULL') or error('Creating orphaned topics table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('DELETE '.$forum_db->prefix.'topics FROM '.$forum_db->prefix.'topics, '.$forum_db->prefix.'orph_topic WHERE o_id=id') or error('Could not delete topics', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'orph_posts SELECT p.id as o_id FROM '.$forum_db->prefix.'posts p LEFT JOIN '.$forum_db->prefix.'topics t ON p.topic_id=t.id WHERE t.id IS NULL') or error('Creating orphaned posts table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('DELETE '.$forum_db->prefix.'posts FROM '.$forum_db->prefix.'posts, '.$forum_db->prefix.'orph_posts WHERE o_id=id') or error('Could not delete posts', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'orph_topics SELECT t.id as o_id FROM '.$forum_db->prefix.'topics as t LEFT JOIN '.$forum_db->prefix.'forums as f ON t.forum_id=f.id WHERE f.id is NULL') or error('Creating orphaned topics table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('DELETE '.$forum_db->prefix.'topics FROM '.$forum_db->prefix.'topics, '.$forum_db->prefix.'orph_topics WHERE o_id=id') or error('Could not delete topics', __FILE__, __LINE__, $forum_db->error());

	redirect(forum_link('admin/cache.php'), $lang_admin_cache['Forums Cleaned']);
}


// Показатели сообщений/тем
if (isset($_POST['forum_post_sync']))
{	
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_posts SELECT t.forum_id, count(*) as posts FROM '.$forum_db->prefix.'posts as p LEFT JOIN '.$forum_db->prefix.'topics as t on p.topic_id=t.id GROUP BY t.forum_id') or error('Creating posts table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'forums, '.$forum_db->prefix.'forum_posts SET num_posts=posts WHERE id=forum_id') or error('Could not update post counts', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_topics SELECT forum_id, count(*) as topics FROM '.$forum_db->prefix.'topics GROUP BY forum_id') or error('Creating topics table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'forums, '.$forum_db->prefix.'forum_topics SET num_topics=topics WHERE id=forum_id') or error('Could not update topic counts', __FILE__, __LINE__, $forum_db->error());

	redirect(forum_link('admin/cache.php'), $lang_admin_cache['Forums synchronized']);
}

// Количество ответов в темах
elseif (isset($_POST['topic_post_sync']))
{
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'topic_posts SELECT topic_id, count(*)-1 as replies FROM '.$forum_db->prefix.'posts GROUP BY topic_id') or error('Creating topics table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'topics, '.$forum_db->prefix.'topic_posts SET num_replies=replies WHERE id=topic_id') or error('Could not update topic counts', __FILE__, __LINE__, $forum_db->error());

	redirect(forum_link('admin/cache.php'), $lang_admin_cache['Forums synchronized']);
}

// Количество сообщений участников
elseif (isset($_POST['user_post_sync']))
{
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'user_posts SELECT p.poster_id, count(p.id) as posts FROM '.$forum_db->prefix.'posts AS p  LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id LEFT JOIN '.$forum_db->prefix.'forums AS f ON t.forum_id=f.id WHERE f.counter=1 GROUP BY poster_id') or error('Creating posts table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'users, '.$forum_db->prefix.'user_posts SET num_posts=posts WHERE id=poster_id') or error('Could not update post counts', __FILE__, __LINE__, $forum_db->error());

	redirect(forum_link('admin/cache.php'), $lang_admin_cache['Messages synchronized']);
}

// Последние сообщения
elseif (isset($_POST['forum_last_post']))
{
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_last SELECT p.posted AS n_last_post, p.id AS n_last_post_id, p.poster AS n_last_poster, t.forum_id FROM '.$forum_db->prefix.'posts AS p LEFT JOIN '.$forum_db->prefix.'topics AS t ON p.topic_id=t.id ORDER BY p.posted DESC') or error('Creating last posts table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'forum_lastb SELECT * FROM '.$forum_db->prefix.'forum_last WHERE forum_id > 0 GROUP BY forum_id') or error('Creating last posts tableb failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'forums, '.$forum_db->prefix.'forum_lastb SET last_post_id=n_last_post_id, last_post=n_last_post, last_poster=n_last_poster WHERE id=forum_id') or error('Could not update last post', __FILE__, __LINE__, $forum_db->error());
	redirect(forum_link('admin/cache.php'), $lang_admin_cache['Messages synchronized 2']);
}

// Последние сообщения тем
elseif (isset($_POST['topic_last_post']))
{
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'topic_last SELECT posted AS n_last_post, id AS n_last_post_id, poster AS n_last_poster, topic_id FROM '.$forum_db->prefix.'posts ORDER BY posted DESC') or error('Creating last posts table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'topic_lastb SELECT * FROM '.$forum_db->prefix.'topic_last WHERE topic_id > 0 GROUP BY topic_id') or error('Creating last posts tableb failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('UPDATE '.$forum_db->prefix.'topics, '.$forum_db->prefix.'topic_lastb SET last_post_id=n_last_post_id, last_post=n_last_post, last_poster=n_last_poster WHERE id=topic_id') or error('Could not update last post', __FILE__, __LINE__, $forum_db->error());

	redirect(forum_link('admin/cache.php'), $lang_admin_cache['Messages synchronized 3']);
}

// Удалить предков
elseif (isset($_POST['delete_orphans']))
{

	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'orph_topic SELECT t.id as o_id FROM '.$forum_db->prefix.'topics AS t LEFT JOIN '.$forum_db->prefix.'posts AS p ON p.topic_id = t.id WHERE p.id IS NULL') or error('Creating orphaned topics table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('DELETE '.$forum_db->prefix.'topics FROM '.$forum_db->prefix.'topics, '.$forum_db->prefix.'orph_topic WHERE o_id=id') or error('Could not delete topics', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'orph_posts SELECT p.id as o_id FROM '.$forum_db->prefix.'posts p LEFT JOIN '.$forum_db->prefix.'topics t ON p.topic_id=t.id WHERE t.id IS NULL') or error('Creating orphaned posts table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('DELETE '.$forum_db->prefix.'posts FROM '.$forum_db->prefix.'posts, '.$forum_db->prefix.'orph_posts WHERE o_id=id') or error('Could not delete posts', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('CREATE TEMPORARY TABLE IF NOT EXISTS '.$forum_db->prefix.'orph_topics SELECT t.id as o_id FROM '.$forum_db->prefix.'topics as t LEFT JOIN '.$forum_db->prefix.'forums as f ON t.forum_id=f.id WHERE f.id is NULL') or error('Creating orphaned topics table failed', __FILE__, __LINE__, $forum_db->error());
	$forum_db->query('DELETE '.$forum_db->prefix.'topics FROM '.$forum_db->prefix.'topics, '.$forum_db->prefix.'orph_topics WHERE o_id=id') or error('Could not delete topics', __FILE__, __LINE__, $forum_db->error());

	redirect(forum_link('admin/cache.php'), $lang_admin_cache['Delete orphans']);
}

$hook = get_hook('acs_qr_db_data') ? eval($hook) : null;

// Setup breadcrumbs
$forum_page['crumbs'] = array(
	array($forum_config['o_board_title'], forum_link($forum_url['index'])),
	array($lang_admin_common['Forum administration'], forum_link('admin/admin.php')),	
	array($lang_admin_common['Management'], forum_link('admin/reports.php')),
	array($lang_admin_common['Cache'], forum_link('admin/cache.php'))
);

$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

$action = isset( $_GET['action'] ) ? $_GET['action'] : '';

define('FORUM_PAGE_SECTION', 'management');
define('FORUM_PAGE', 'admin-cache-syns');
require FORUM_ROOT.'header.php';
// START SUBST - <!-- forum_main -->
ob_start();

($hook = get_hook('acs_cache_start')) ? eval($hook) : null;

?>
<div class="main-subhead">
	<h2 class="hn"><span><?php echo $lang_admin_cache['About cache'] ?></span></h2>
</div>
	<div class="main-content frm">
	<form class="frm-form" method="post" accept-charset="utf-8" action="<?php echo forum_link('/admin/cache.php') ?>">
		<div class="hidden">
			<input type="hidden" name="csrf_token" value="<?php echo generate_form_token(forum_link('/admin/cache.php')) ?>" />
		</div>
		<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
			<legend class="group-legend"><strong><?php echo $lang_admin_cache['Cache'] ?></strong></legend>
<?php ($hook = get_hook('acs_cache_pre_ban')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate Bans Cache'] ?></span><small><?php echo $lang_admin_cache['Bans Cache'] ?></small></label><br />
					<span class="fld-input"><input type="submit" name="bans_cache" value="<?php echo $lang_admin_cache['Regenerate'] ?>" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_cache_pre_censor')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate Censor Cache'] ?></span><small><?php echo $lang_admin_cache['Censor Cache'] ?></small></label><br />
					<span class="fld-input"><input type="submit" name="censor_cache" value="<?php echo $lang_admin_cache['Regenerate'] ?>" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_cache_pre_config')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate Config Cache'] ?></span><small><?php echo $lang_admin_cache['Config Cache'] ?></small></label><br />
					<span class="fld-input"><input type="submit" name="config_cache" value="<?php echo $lang_admin_cache['Regenerate'] ?>" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_cache_pre_hooks')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate Hooks Cache'] ?></span><small><?php echo $lang_admin_cache['Hooks Cache'] ?></small></label><br />
					<span class="fld-input"><input type="submit" name="hooks_cache" value="<?php echo $lang_admin_cache['Regenerate'] ?>" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_cache_pre_ranks')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate Ranks Cache'] ?></span><small><?php echo $lang_admin_cache['Ranks Cache'] ?></small></label><br />
					<span class="fld-input"><input type="submit" name="ranks_cache" value="<?php echo $lang_admin_cache['Regenerate'] ?>" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_cache_pre_updates')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate Updates Cache'] ?></span><small><?php echo $lang_admin_cache['Updates Cache'] ?></small></label><br />
					<span class="fld-input"><input type="submit" name="updates_cache" value="<?php echo $lang_admin_cache['Regenerate'] ?>" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_cache_pre_repository')) ? eval($hook) : null; ?>
			<!--<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate Repository Cache'] ?></span><small><?php echo $lang_admin_cache['Repository Cache'] ?></small></label><br />
					<span class="fld-input"><input type="submit" name="repository_cache" value="<?php echo $lang_admin_cache['Regenerate'] ?>" /></span>	
				</div>
			</div>-->
<?php ($hook = get_hook('acs_cache_pre_quickjump')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate Quickjump Cache'] ?></span><small><?php echo $lang_admin_cache['Quickjump Cache'] ?></small></label><br />
					<span class="fld-input"><input type="submit" name="quickjump_cache" value="<?php echo $lang_admin_cache['Regenerate'] ?>" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_cache_pre_stat')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate Stat Cache'] ?></span><small><?php echo $lang_admin_cache['Stat Cache'] ?></small></label><br />
					<span class="fld-input"><input type="submit" name="stat_cache" value="<?php echo $lang_admin_cache['Regenerate'] ?>" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_cache_pre_all_cache')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Regenerate All Cache'] ?></span><small><?php echo $lang_admin_cache['All Cache'] ?></small></label><br />
					<span class="fld-input"><input type="submit" name="all_cache" value="<?php echo $lang_admin_cache['Regenerate'] ?>" /></span>	
				</div>
			</div>
		</fieldset>
<?php

($hook = get_hook('acs_sync_pre_fieldset')) ? eval($hook) : null;

// Setup the form
$forum_page['group_count'] = $forum_page['item_count'] = $forum_page['fld_count'] = 0;

?>
		<div class="main-subhead">
			<h2 class="hn"><span><?php echo $lang_admin_cache['About syns'] ?></span></h2>
		</div>
		<fieldset class="frm-group group<?php echo ++$forum_page['group_count'] ?>">
			<legend class="group-legend"><strong><?php echo $lang_admin_cache['Clean and syns'] ?></strong></legend>
<?php ($hook = get_hook('acs_sync_pre_cleanup')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Ip addys'] ?></span><small><?php echo $lang_admin_cache['Ip addys info'] ?></small></label><br />
					<span class="fld-input"><input type="text" name="ip_addys" size="50" maxlength="255" class="inputbox" /><input class="button" type="submit" name="cleanup" value="<?php echo $lang_admin_cache['Clean'] ?>" tabindex="4" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_sync_pre_forum_post_sync')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Forum post sync'] ?></span><small><?php echo $lang_admin_cache['Forum post sync info'] ?></small></label><br />
					<span class="fld-input"><input class="button" type="submit" name="forum_post_sync" value="<?php echo $lang_admin_cache['Syns'] ?>" tabindex="4" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_sync_pre_topic_post_sync')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Topic post sync'] ?></span><small><?php echo $lang_admin_cache['Topic post sync info'] ?></small></label><br />
					<span class="fld-input"><input class="button" type="submit" name="topic_post_sync" value="<?php echo $lang_admin_cache['Syns'] ?>" tabindex="4" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_sync_pre_user_post_sync')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['User post sync'] ?></span><small><?php echo $lang_admin_cache['User post sync info'] ?></small></label><br />
					<span class="fld-input"><input class="button" type="submit" name="user_post_sync" value="<?php echo $lang_admin_cache['Syns'] ?>" tabindex="4" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_sync_pre_forum_last_post')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Forum last post'] ?></span><small><?php echo $lang_admin_cache['Forum last post info'] ?></small></label><br />
					<span class="fld-input"><input class="button" type="submit" name="forum_last_post" value="<?php echo $lang_admin_cache['Syns'] ?>" tabindex="4" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_sync_pre_topic_last_post')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Topic last post'] ?></span><small><?php echo $lang_admin_cache['Topic last post info'] ?></small></label><br />
					<span class="fld-input"><input class="button" type="submit" name="topic_last_post" value="<?php echo $lang_admin_cache['Syns'] ?>" tabindex="4" /></span>	
				</div>
			</div>
<?php ($hook = get_hook('acs_sync_pre_delete_orphans')) ? eval($hook) : null; ?>
			<div class="sf-set set<?php echo ++$forum_page['item_count'] ?>">
				<div class="sf-box text">
					<label for="fld<?php echo ++$forum_page['fld_count'] ?>"><span><?php echo $lang_admin_cache['Delete orphans'] ?></span><small><?php echo $lang_admin_cache['Delete orphans info'] ?></small></label><br />
					<span class="fld-input"><input class="button" type="submit" name="delete_orphans" value="<?php echo $lang_admin_cache['Syns'] ?>" tabindex="4" /></span>	
				</div>
			</div>
		</fieldset>
	</form>
	</div>
<?php

$tpl_temp = trim(ob_get_contents());
$tpl_main = str_replace('<!-- forum_main -->', $tpl_temp, $tpl_main);
ob_end_clean();
// END SUBST - <!-- forum_main -->

require FORUM_ROOT.'footer.php';
