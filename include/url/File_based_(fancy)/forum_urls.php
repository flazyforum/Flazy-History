<?php
/**
 * SEF URL-адреса с местом расположения скриптов.
 *
 * @copyright Copyright (C) 2008 PunBB, partially based on code copyright (C) 2008 FluxBB.org
 * @modified Copyright (C) 2008-2009 Flazy.ru
 * @license http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 * @package Flazy
 */


// Убедимся что никто не пытается запусть этот сценарий напрямую
if (!defined('FORUM'))
	exit;

// These are the "fancy" file based SEF URLs
$forum_url = array(
'insertion_find'	=>	'.html',
'insertion_replace'	=>	'-$1.html',
'change_email'		=>	'change-email$1.html',
'change_email_key'	=>	'change-email$1-$2.html',
'change_password'	=>	'change-password$1.html',
'change_password_key'	=>	'change-password$1-$2.html',
'delete_user'		=>	'delete-user$1.html',
'delete'		=>	'delete$1.html',
'delete_avatar'		=>	'delete-avatar$1-$2.html',
'edit'			=>	'edit$1.html',
'email'			=>	'email$1.html',
'feed_forum'		=>	'feed-$1-forum$2-$3.xml',
'feed_index'		=>	'feed-$1.xml',
'topic'			=>	'topic$1-$2.html',
'poll'			=>	'poll$1-$2.html',
'forum'			=>	'forum$1-$2.html',
'help'			=>	'help-$1.html',
'index'			=>	'',
'category'		=>	'category$1.html',
'login'			=>	'login.html',
'logout'		=>	'logout$1-$2.html',
'online'		=>	'online.html',
'statistic'		=>	'statistic-$1.html',
'mark_read'		=>	'mark-read-$1.html',
'mark_forum_read'	=>	'mark-forum$1-read-$2.html',
'new_topic'		=>	'new-topic$1.html',
'new_reply'		=>	'new-reply$1.html',
'pm'			=>	'user$1-pm-$2.html',
'pm_send'		=>	'user$1-pm-send.html',
'pm_edit' 		=>	'user$1-pm-write-$2.html',
'pm_view'		=>	'user$1-pm-$3-$2.html',
'pm_post_link'		=>	'user$1-pm-compose-$2.html',
'post'			=>	'post$1.html#p$1',
'profile'		=>	'user$1-$2.html',
'print'			=>	'print$1-$2.html',
'quote'			=>	'new-reply$1quote$2.html',
'register'		=>	'register.html',
'report'		=>	'report$1.html',
'request_password'	=>	'request-password.html',
'rules'			=>	'rules.html',
'search'		=>	'search.html',
'search_resultft'	=>	'search-k$1-$4-a$3-$5-$6-$2-$7.html',
'search_results'	=>	'search$1.html',
'search_new'		=>	'search-new.html',
'search_new_results'	=>	'search-new-$1.html',
'search_recent'		=>	'search-recent.html',
'search_recent_results'	=>	'search-recent-$1.html',
'search_unanswered'	=>	'search-unanswered.html',
'search_subscriptions'	=>	'search-subscriptions$1.html',
'search_user_posts'	=>	'search-posts-user$1.html',
'search_user_topics'	=>	'search-topics-user$1.html',
'subscribe'		=>	'subscribe$1-$2.html',
'feed_topic'		=>	'feed-$1-topic$2.xml',
'topic_new_posts'	=>	'topic$1-$2-new-posts.html',
'topic_last_post'	=>	'topic$1last-post.html',
'unsubscribe'		=>	'unsubscribe$1-$2.html',
'user'			=>	'user$1.html',
'users'			=>	'users.html',
'users_browse'		=>	'users/$4/$1$2-$3.html',
'page'			=>	'p$1',
'moderate_forum'	=>	'moderate$1.html',
'get_host'		=>	'get_host$1.html',
'move'			=>	'move_topics$1-$2.html',
'mod'			=>	'$1$2-$3-$4.html',
'moderate_topic'	=>	'moderate$1-$2.html',
'reputation'		=>	'reputation$1.html',
'positive'		=>	'positive$1.html',
'reputation_change'	=>	'reputation$1-user$2-$3.html',
);

?>
