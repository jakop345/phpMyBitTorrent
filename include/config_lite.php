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

if (@eregi("config_lite.php",$_SERVER['PHP_SELF'])) die("You can't access this file directly");
if (@file_exists("setup/index.php")) die();

//Compressed Output Buffering always makes trouble with BT clients
@ob_start();
@ob_implicit_flush(0);

$phpver = phpversion();
if ($phpver < '4.1.0') {
        $_GET = $HTTP_GET_VARS;
        $_POST = $HTTP_POST_VARS;
        $_SERVER = $HTTP_SERVER_VARS;
}


if (!ini_get("register_globals")) {
    @import_request_variables('GPC');
}

require_once("include/configdata.php");
require_once("include/db/database.php");


$db = new sql_db($db_host, $db_user, $db_pass, $db_name, $db_persistency) or die("Class error");
if(!$db->db_connect_id) {
        die("d14:failure reason26:Cannot connect to databasee");
}

//This way we protect database authentication against hacked mods
unset($db_type,$db_host,$db_user,$db_pass,$db_persistency);


$sql = "SELECT * FROM ".$db_prefix."_config LIMIT 1;";

$configquery = $db->sql_query($sql,BEGIN_TRANSACTION);

if (!$configquery) die($sql."1phpMyBitTorrent not correctly installed! Ensure you have run setup.php or config_default.sql!!");
if (!$row = $db->sql_fetchrow($configquery)) die("2phpMyBitTorrent not correctly installed! Ensure you have run setup.php or config_default.sql!!");
$sql = "SELECT * FROM ".$db_prefix."_paypal LIMIT 1;";

$paypal = $db->sql_query($sql,BEGIN_TRANSACTION);

if (!$paypal) die("Configuration not found! Make sure you have installed phpMyBitTorrent correctly.");
if (!$row2 = $db->sql_fetchrow($paypal)) die("phpMyBitTorrent not correctly installed! Ensure you have run setup.php or config_default.sql!!");
#Config parser start
$sitename = $row["sitename"];
$siteurl = $row["siteurl"];
$admin_email = $row["admin_email"];
$language = $row["language"];
$theme = $row["theme"];
$torrent_prefix = $row["torrent_prefix"];
$announce_text = $row["announce_text"];
$announce_interval = $row["announce_interval"];
$announce_interval_min = ($row["announce_interval_min"] == 0) ? ($row["announce_interval"]-1) : $row["announce_interval_min"];
$dead_torrent_interval = $row["dead_torrent_interval"];
$time_tracker_update = $row["time_tracker_update"];
$best_limit = $row["best_limit"];
$down_limit = $row["down_limit"];
$torrent_global_privacy = ($row["torrent_global_privacy"] == "true") ? true : false;
$download_level = $row["download_level"];
$announce_level = $row["announce_level"];
$max_num_file = $row["max_num_file"];
$max_share_size = $row["max_share_size"];
$min_size_seed = $row["min_size_seed"];
$min_share_seed = $row["min_share_seed"];
$global_min_ratio = $row["global_min_ratio"];
$autoscrape = ($row["autoscrape"] == "true" ? true : false);
$min_num_seed_e = $row["min_num_seed_e"];
$min_size_seed_e = $row["min_size_seed_e"];
$minupload_size_file = $row["minupload_size_file"];
$allow_backup_tracker = ($row["allow_backup_tracker"] == "true") ? true : false;
$stealthmode = ($row["stealthmode"] == "true") ? true : false;
$free_dl = ($row["free_dl"] == "true") ? true : false;
$GIGSA= $row["GIGSA"];
$RATIOA= $row["RATIOA"];
$WAITA=$row["WAITA"];
$GIGSB= $row["GIGSB"];
$RATIOB= $row["RATIOB"];
$WAITB= $row["WAITB"];
$GIGSC= $row["GIGSC"];
$RATIOC= $row["RATIOC"];
$WAITC= $row["WAITC"];
$GIGSD= $row["GIGSD"];
$RATIOD= $row["WAITD"];
$WAITD= $row["WAITD"];
$force_passkey = ($row["force_passkey"] == "true" ? true : false);
$pivate_mode = ($row["pivate_mode"] == "true") ? true : false;
$wait_time = ($row["wait_time"] == "true") ? true : false;
$most_users_online = $row["most_on_line"];
$most_users_online_when = $row["when_most"];

$version = $row["version"];
$announce_url = $siteurl."/announce.php";
#donationblock
$paypal_email = $row2["paypal_email"];
$donatein = $row2["reseaved_donations"];
$donateasked = $row2["sitecost"];
$donatepagecontents = $row2['donatepage'];
$donations = ($row2["donation_block"]=="true") ? true : false;
$nodonate = $row2["nodonate"];

#Config Parser end
?>