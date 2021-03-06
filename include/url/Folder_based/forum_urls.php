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

// These are the simple folder based SEF URLs
$forum_url = array(
'change_email'		=>	'change/email/$1/',
'change_email_key'	=>	'change/email/$1/$2/',
'change_password'	=>	'change/password/$1/',
'change_password_key'	=>	'change/password/$1/$2/',
'delete'		=>	'delete/$1/',
'delete_avatar'		=>	'delete/avatar/$1/$2/',
'delete_user'		=>	'delete/user/$1/',
'edit'			=>	'edit/$1/',
'email'			=>	'email/$1/',
'feed_forum'		=>	'feed/$1/forum/$2/$3/',
'feed_index'		=>	'feed/$1/',
'feed_topic'		=>	'feed/$1/topic/$2/',
'forum'			=>	'forum/$1/',
'help'			=>	'help/$1/',
'index'			=>	'',
'category'		=>	'category/$1/',
'login'			=>	'login/',
'logout'		=>	'logout/$1/$2/',
'online'		=>	'online/',
'statistic'		=>	'statistic/$1/',
'mark_read'		=>	'mark/read/$1/',
'mark_forum_read'	=>	'mark/forum/$1/read/$2/',
'new_topic'		=>	'new/topic/$1/',
'new_reply'		=>	'new/reply/$1/',
'pm'			=>	'user/$1/pm/$2/',
'pm_send'		=>	'user/$1/pm/send/',
'pm_edit' 		=>	'user/$1/pm/write/$2/',
'pm_view'		=>	'user/$1/pm/$3/$2/',
'pm_post_link'		=>	'user/$1/pm/compose/$2/',
'post'			=>	'post/$1/#p$1',
'profile'		=>	'user/$1/$2/',
'print'			=>	'print/$1/',
'quote'			=>	'new/reply/$1/quote/$2/',
'register'		=>	'register/',
'report'		=>	'report/$1/',
'request_password'	=>	'request/password/',
'rules'			=>	'rules/',
'search'		=>	'search/',
'search_resultft'	=>	'search/k$1/$2/a$3/$4/$5/$6/$7/',
'search_results'	=>	'search/$1/',
'search_new'		=>	'search/new/',
'search_new_results'	=>	'search/new/$1/',
'search_recent'		=>	'search/recent/',
'search_recent_results'	=>	'search/recent/$1/',
'search_unanswered'	=>	'search/unanswered/',
'search_subscriptions'	=>	'search/subscriptions/$1/',
'search_user_posts'	=>	'search/posts/user/$1/',
'search_user_topics'	=>	'search/topics/user/$1/',
'subscribe'		=>	'subscribe/$1/$2/',
'topic'			=>	'topic/$1/',
'poll'			=>	'poll/$1/',
'topic_new_posts'	=>	'topic/$1/new/posts/',
'topic_last_post'	=>	'topic/$1/last/post/',
'unsubscribe'		=>	'unsubscribe/$1/$2/',
'user'			=>	'user/$1/',
'users'			=>	'users/',
'users_browse'		=>	'users/$4/$1/$2/$3/',
'page'			=>	'page/$1/',
'moderate_forum'	=>	'moderate/$1/',
'get_host'		=>	'get_host/$1/',
'move'			=>	'move_topics/$1/$2/',
'mod'			=>	'$1/$2/$3/$4/',
'moderate_topic'	=>	'moderate/$1/$2/',
'reputation'		=>	'reputation/$1/',
'positive'		=>	'positive/$1/',
'reputation_change'	=>	'reputation/$1/user$2/$3/',
);

?>
