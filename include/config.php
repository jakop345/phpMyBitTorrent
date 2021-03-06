<?php
/*
*----------------------------phpMyBitTorrent V 2.0-----------------------------*
*--- The Ultimate BitTorrent Tracker and BMS (Bittorrent Management System) ---*
*--------------   Created By Antonio Anzivino (aka DJ Echelon)   --------------*
*-------------               http://www.p2pmania.it               -------------*
*------------ Based on the Bit Torrent Protocol made by Bram Cohen ------------*
*-------------              http://www.bittorrent.com             -------------*
*------------------------------------------------------------------------------*
*------------------------------------------------------------------------------*
*--   This program is free software; you can redistribute it and/or modify   --*
*--   it under the terms of the GNU General Public License as published by   --*
*--   the Free Software Foundation; either version 2 of the License, or      --*
*--   (at your option) any later version.                                    --*
*--                                                                          --*
*--   This program is distributed in the hope that it will be useful,        --*
*--   but WITHOUT ANY WARRANTY; without even the implied warranty of         --*
*--   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          --*
*--   GNU General Public License for more details.                           --*
*--                                                                          --*
*--   You should have received a copy of the GNU General Public License      --*
*--   along with this program; if not, write to the Free Software            --*
*-- Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA --*
*--                                                                          --*
*------------------------------------------------------------------------------*
*------              �2005 phpMyBitTorrent Development Team              ------*
*-----------               http://phpmybittorrent.com               -----------*
*------------------------------------------------------------------------------*
*-----------------   Sunday, September 14, 2008 9:05 PM   ---------------------*
*/

if (eregi("config.php",$_SERVER['PHP_SELF'])) die("You can't access this file directly");

if (function_exists('ob_gzhandler') && !ini_get('zlib.output_compression'))

    ob_start('ob_gzhandler');

    else

        ob_start();

ob_implicit_flush(0);
if (!file_exists("include/configdata.php")) {
        header("Location: setup/index.php");
        die();
}
if (file_exists("setup/index.php")) die("You MUST delete the setup directory before running phpMyBitTorrent");

/*
WARNING: (IIS USERS)
YOU *MUST* ADD THE APPLICATION/X-BITTORRENT MIMETYPE TO YOUR SERVER CONFIGURATION
ASSOCIATING IT TO THE .TORRENT EXTENSION OR YOUR USERS' BROWSERS WON'T ACCEPT ANY TORRENT
*/


if (!ini_get("register_globals")) {
    @import_request_variables('GPC');
}
//Overriding against fake input
//if (!isset($btuser)) $btuser = "";
if (!isset($_COOKIE["btuser"])) $btuser = "";
if (!isset($_COOKIE["bttheme"])) $bttheme = "";
if (!isset($_COOKIE["btlanguage"])) $btlanguage = "";

require_once("include/configdata.php");
require_once("include/db/database.php");
require_once'include/class.cache.php';

$db = new sql_db($db_host, $db_user, $db_pass, $db_name, $db_persistency) or die("Class error");
if(!$db->db_connect_id) {
        $err = $db->sql_error;
        $errmsg = $errmsg["message"];
        die("<html>\n
        <head><title>phpMyBitTorrent Error</title>\n
        </head>\n
        <body>\n
        <!-- Error: $errmsg -->\n
        <p><center>\n
        <br /><br />\n
        <b>There seems to be a problem with the database server, sorry for the inconvenience.
        <br /><br />\n
        We should be back shortly.</b></center></p>\n
        </body>\n
        </html>");
}

//This way we protect database authentication against hacked mods
unset($db_type,$db_host,$db_user,$db_pass,$db_persistency);

require_once("include/bittorrent.php");

if(!$pmbt_cache->get_sql("config")){
$sql = "SELECT * FROM ".$db_prefix."_config LIMIT 1;";
$configquery = $db->sql_query($sql,BEGIN_TRANSACTION);
if (!$configquery) die("Configuration not found! Make sure you have installed phpMyBitTorrent correctly.");
if (!$row = $db->sql_fetchrow($configquery)) die("phpMyBitTorrent not correctly installed! Ensure you have run setup.php or config_default.sql!!");
$pmbt_cache->set_sql("config", $row);
}else{
$row = $pmbt_cache->get_sql("config");
}
if(!$pmbt_cache->get_sql("userautodel")){
$sql = "SELECT * FROM ".$db_prefix."_userautodel LIMIT 1;";
$userautodel = $db->sql_query($sql,BEGIN_TRANSACTION);
if (!$userautodel) die("Configuration not found! Make sure you have installed phpMyBitTorrent correctly.");
if (!$row3 = $db->sql_fetchrow($userautodel)) die("phpMyBitTorrent not correctly installed! Ensure you have run setup.php or config_default.sql!!");
$pmbt_cache->set_sql("userautodel", $row3);
}else{
$row3 = $pmbt_cache->get_sql("userautodel");
}
if(!$pmbt_cache->get_sql("paypal")){
$sql = "SELECT * FROM ".$db_prefix."_paypal LIMIT 1;";
$paypal = $db->sql_query($sql,BEGIN_TRANSACTION);
if (!$paypal) die("Configuration not found! Make sure you have installed phpMyBitTorrent correctly.");
if (!$row2 = $db->sql_fetchrow($paypal)) die("phpMyBitTorrent not correctly installed! Ensure you have run setup.php or config_default.sql!!");
$pmbt_cache->set_sql("paypal", $row2);
}else{
$row2 = $pmbt_cache->get_sql("paypal");
}
if(!$pmbt_cache->get_sql("shout")){
$sql = "SELECT * FROM ".$db_prefix."_shout_config LIMIT 1;";
$shout = $db->sql_query($sql,BEGIN_TRANSACTION);
if (!$shout) die("Configuration not found! Make sure you have installed phpMyBitTorrent correctly.");
if (!$row4 = $db->sql_fetchrow($shout)) die("phpMyBitTorrent not correctly installed! Ensure you have run setup.php or config_default.sql!!");
$pmbt_cache->set_sql("shout", $row4);
}else{
$row4 = $pmbt_cache->get_sql("shout");
}
$shout_config = $row4;
		if(!$pmbt_cache->get_sql("avatar")){
		$avsql = 'SELECT * FROM `'.$db_prefix.'_avatar_config`';
		$avres = $db->sql_query($avsql) or btsqlerror($avsql);
		$avconfig = $db->sql_fetchrow($avres);
		$pmbt_cache->set_sql("avatar", $avconfig);
		}else{
		$avconfig = $pmbt_cache->get_sql("avatar");
		}
		$avon = ($avconfig['enable_avatars'] == 'true')? true : false;
		$avgalon = ($avconfig['enable_gallery_avatars'] == 'true')? true : false;
		$avremoteon = ($avconfig['enable_remote_avatars'] == 'true')? true : false;
		$avuploadon = ($avconfig['enable_avatar_uploading'] == 'true')? true : false;
		$avremoteupon = ($avconfig['enable_remote_avatar_uploading'] == 'true')? true : false;
		$avmaxsz = $avconfig['maximum_avatar_file_size'];
		$avstore = $avconfig['avatar_storage_path'];
		$avgal = $avconfig['avatar_gallery_path'];
		$avminht = $avconfig['minimum_avatar_dimensions_ht'];
		$avminwt = $avconfig['minimum_avatar_dimensions_wt'];
		$avmaxht = $avconfig['maximum_avatar_dimensions_ht'];
		$avmaxwt = $avconfig['maximum_avatar_dimensions_wt'];
			$sql_attach = 'SELECT config_name, config_value, is_dynamic
				FROM '.$db_prefix.'_attachments_config';
			$result = $db->sql_query($sql_attach);
			while ($row_attach = $db->sql_fetchrow($result))
			{
				$attach_config[$row_attach['config_name']] = $row_attach['config_value'];
			}
			$db->sql_freeresult($result);
//Config parser start
$emaileditecf = true;
$sitename = $row["sitename"];
$siteurl = $row["siteurl"];
$cookiedomain = $row["cookiedomain"];
$cookiepath = $row["cookiepath"];
$admin_email = $row["admin_email"];
$language = $row["language"];
$theme = $row["theme"];
$welcome_message = $row["welcome_message"];
$announce_message = $row["announce_ments"];
$announce_text = $row["announce_text"];
$allow_html = ($row["allow_html"] == "true") ? true : false;
$rewrite_engine = ($row["rewrite_engine"] == "true") ? true : false;
$torrent_prefix = $row["torrent_prefix"];
if(isset($user->user_torrent_per_page) AND  $user->user_torrent_per_page != "0" AND  $user->user_torrent_per_page != "")$torrent_per_page = $user->user_torrent_per_page;
else
$torrent_per_page = $row["torrent_per_page"];
if($torrent_per_page == "" OR $torrent_per_page == "0")$torrent_per_page = 20;
$onlysearch = ($row["onlysearch"] == "true") ? true : false;
$max_torrent_size = $row["max_torrent_size"];
$announce_interval = $row["announce_interval"];
$announce_interval_min = $row["announce_interval_min"];
$dead_torrent_interval = $row["dead_torrent_interval"];
$pivate_mode = ($row["pivate_mode"] == "true") ? true : false;
$minvotes = $row["minvotes"];
$time_tracker_update = $row["time_tracker_update"];
$best_limit = $row["best_limit"];
$down_limit = $row["down_limit"];
$torrent_complaints = ($row["torrent_complaints"] == "true") ? true : false;
$torrent_global_privacy = ($row["torrent_global_privacy"] == "true") ? true : false;
$disclaimer_check = ($row["disclaimer_check"] == "true") ? true : false;
$gfx_check = ($row["gfx_check"] == "true") ? true : false;
$upload_level = $row["upload_level"];
$download_level = $row["download_level"];
$announce_level = $row["announce_level"];
$max_num_file = $row["max_num_file"];
$max_share_size = $row["max_share_size"];
$min_size_seed = $row["min_size_seed"];
$min_share_seed = $row["min_share_seed"];
$global_min_ratio = $row["global_min_ratio"];
$autoscrape = ($row["autoscrape"] == "true") ? true : false;
$min_num_seed_e = $row["min_num_seed_e"];
$min_size_seed_e = $row["min_size_seed_e"];
$minupload_size_file = $row["minupload_size_file"];
$allow_backup_tracker = ($row["allow_backup_tracker"] == "true") ? true : false;
$stealthmode = ($row["stealthmode"] == "true") ? true : false;
$version = $row["version"];
$force_upload = ($row["upload_dead"]=="true") ? true : false;
$force_passkey = ($row["force_passkey"] == "true") ? true : false;
$search_cloud = ($row["search_cloud_block"] == "true") ? true : false;
$free_dl = ($row["free_dl"] == "true") ? true : false;
$most_users_online = $row["most_on_line"];
$most_users_online_when = $row["when_most"];
$give_sign_up_credit = $row['give_sign_up_credit'];
$conferm_email = ($row['conferm_email'] =="true") ? true : false;
$phpEx = substr(strrchr(__FILE__, '.'), 1);
#donationblock
$paypal_email = $row2["paypal_email"];
$donatein = $row2["reseaved_donations"];
$donateasked = $row2["sitecost"];
$donatepagecontents = $row2['donatepage'];
$donations = ($row2["donation_block"]=="true") ? true : false;
$nodonate = $row2["nodonate"];
#user purge
$inactwarning_time = 86400*$row3["inactwarning_time"];
$autodel_users_time = 86400*$row3["autodel_users_time"];
$autodel_users = ($row3["autodel_users"] == "true") ? true : false;
#hnr system not active yet
$runhnrsystem = false;
$TheQueryCount=0;
#Config Parser end
$announce_url = $siteurl."/announce.php";
$INVITEONLY = ($row["invites_open"]=="true") ? true : false;
$singup_open = ($row["invite_only"]=="true") ? true : false;
$invite_timeout = 86400 * 3;
$invites1 = $row["max_members"];
$autoclean_interval = $row["auto_clean"];
$addprivate = ($row["addprivate"]=="true") ? true : false;
if ($user->moderator) $onlysearch = false;
if (isset($theme_change)){
$bttheme = $theme_change;
if ($bttheme != "" AND is_dir("themes/".$bttheme) AND $bttheme != "CVS") {
setcookie("bttheme",$bttheme,$session_time,$cookiepath,$cookiedomain,0);
unset($_POST);
}
}
if(isset($language_change)){
$btlanguage = $language_change;
if ($btlanguage != "" AND is_readable("language/".$btlanguage.".php") AND $btlanguage != "CVS") {
setcookie("btlanguage",$btlanguage,(time() + 31536000),$cookiepath,$cookiedomain,0);
unset($_POST);
}
}
if (isset($btlanguage) AND is_readable("language/".$btlanguage.".php")) $language = $btlanguage;
if (isset($bttheme) AND is_readable("themes/".$bttheme."/main.php")) $theme = $bttheme;

if (file_exists("./language/".$language.".php"))
        require_once("./language/".$language.".php");
else
        require_once("./language/english.php");

if (file_exists("./themes/".$theme."/main.php")) {
        require_once("./themes/".$theme."/main.php");
} elseif (file_exists("./themes/pmbt/main.php")) {
        $theme = "tleech";
        require_once("./themes/pmbt/main.php");
} else {
        die("Cannot run without theme! Reinstall phpMyBitTorrent NOW!!");
}
#ob_clean();
?>