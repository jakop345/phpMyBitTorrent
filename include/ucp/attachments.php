<?php
/*
*-----------------------------phpMyBitTorrent V 2.0.5--------------------------*
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
*------              ©2005 phpMyBitTorrent Development Team              ------*
*-----------               http://phpmybittorrent.com               -----------*
*------------------------------------------------------------------------------*
*-----------------  Thursday, November 04, 2010 9:05 PM   ---------------------*
*/
/**
*
* @package phpMyBitTorrent
* @version $Id: attachments.php 1 2010-11-04 00:22:48Z joeroberts $
* @copyright (c) 2010 phpMyBitTorrent Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/
define("ATTACHMENTS_TABLE","torrent_attachments");
if(!$user->ulanguage == '' && file_exists('language/attachment/'.$user->ulanguage.'.php'))include'language/attachment/'.$user->ulanguage.'.php';
elseif (file_exists('language/attachment/'.$language.'.php'))include_once'language/attachment/'.$language.'.php';
else
include_once'language/attachment/english.php';
		$start		= request_var('start', 0);
		$sort_key	= request_var('sk', 'a');
		$sort_dir	= request_var('sd', 'a');

		$delete		= (isset($_POST['delete'])) ? true : false;
		$confirm	= (isset($_POST['confirm'])) ? true : false;
		$delete_ids	= array_keys(request_var('attachment', array(0)));

		if ($delete && sizeof($delete_ids))
		{
			// Validate $delete_ids...
			$sql = 'SELECT attach_id
				FROM ' . ATTACHMENTS_TABLE . '
				WHERE poster_id = ' . $uid . '
					AND is_orphan = 0
					AND ' . $db->sql_in_set('attach_id', $delete_ids);
			$result = $db->sql_query($sql);

			$delete_ids = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$delete_ids[] = $row['attach_id'];
			}
			$db->sql_freeresult($result);
		}

		if ($delete && sizeof($delete_ids))
		{
			$s_hidden_fields = array(
				'delete'	=> 1
			);

			foreach ($delete_ids as $attachment_id)
			{
				$s_hidden_fields['attachment'][$attachment_id] = 1;
			}
				$s_hidden_fields['op'] = "editprofile";
				$s_hidden_fields['action'] = 'overview';
				$s_hidden_fields['mode'] = 'attachments';
		
			if (confirm_box(true))
			{
				if (!function_exists('delete_attachments'))
				{
					include_once('include/function_posting.php');
				}

				delete_attachments('attach', $delete_ids);

				$message = ((sizeof($delete_ids) == 1) ? ATTACHMENT_DELETED : ATTACHMENTS_DELETED) . '<br /><br />' . sprintf(RETURN_UCP, '<a href="' . $siteurl . '/user.php?op=editprofile&action=overview&mode=attachments">', '</a>');
                                $template->assign_vars(array(
										'S_REFRESH'				=> true,
										'META' 				  	=> '<meta http-equiv="refresh" content="5;url=' . $siteurl . '/user.php?op=editprofile&amp;action=overview&amp;mode=attachments" />',
										'S_ERROR_HEADER'		=>_btaccdenied,
                                        'S_ERROR_MESS'			=> $message,
                                ));
				//trigger_error($message);
                echo $template->fetch('error.html');
				die();
			}
			else
			{
				confirm_box(false, 'bt_fm_del_attach', build_hidden_fields($s_hidden_fields));
			}
		}

		// Select box eventually
		$sort_key_text = array('a' => _btname, 'b' => _btrequestdetails_comments, 'c' => _btfiletype, 'd' => _btrequest_added, 'e' => _btdownloadedbts, 'f' => _btaddtime, 'g' => SORT_TOPIC_TITLE);
		$sort_key_sql = array('a' => 'a.real_filename', 'b' => 'a.attach_comment', 'c' => 'a.extension', 'd' => 'a.filesize', 'e' => 'a.download_count', 'f' => 'a.filetime', 'g' => 't.topic_title');

		$sort_dir_text = array('a' => _btord, 'd' => _btdesc);

		$s_sort_key = '';
		foreach ($sort_key_text as $key => $value)
		{
			$selected = ($sort_key == $key) ? ' selected="selected"' : '';
			$s_sort_key .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}

		$s_sort_dir = '';
		foreach ($sort_dir_text as $key => $value)
		{
			$selected = ($sort_dir == $key) ? ' selected="selected"' : '';
			$s_sort_dir .= '<option value="' . $key . '"' . $selected . '>' . $value . '</option>';
		}

		if (!isset($sort_key_sql[$sort_key]))
		{
			$sort_key = 'a';
		}

		$order_by = $sort_key_sql[$sort_key] . ' ' . (($sort_dir == 'a') ? 'ASC' : 'DESC');

		$sql = 'SELECT COUNT(attach_id) as num_attachments
			FROM ' . ATTACHMENTS_TABLE . '
			WHERE poster_id = ' . $uid ."
				AND is_orphan = 0";
		$result = $db->sql_query($sql);
		$num_attachments = $db->sql_fetchfield('num_attachments');
		$db->sql_freeresult($result);

		$sql = 'SELECT a.*, t.subject, p.subject as message_title
			FROM ' . ATTACHMENTS_TABLE . ' a
				LEFT JOIN `torrent_forum_topics` t ON (a.topic_id = t.id AND a.in_message = 0)
				LEFT JOIN `torrent_private_messages` p ON (a.post_msg_id = p.id AND a.in_message = 1)
			WHERE a.poster_id = ' . $uid . '
				AND a.is_orphan = 0
			ORDER BY '. $order_by;
		$result = $db->sql_query($sql) or btsqlerror($sql);

		$row_count = 0;
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_var('S_ATTACHMENT_ROWS', true);

			do
			{
				if ($row['in_message'])
				{
					$view_topic = "pm.php?op=readmsg&mode=inbox&mid=" . $row['id'];
				}
				else
				{
					$view_topic = "forums.php?action=viewtopic&topicid=" . $row['topic_id'] . "#" . $row['post_msg_id'];
				}

				$template->assign_block_vars('attachrow', array(
					'ROW_NUMBER'		=> $row_count + ($start + 1),
					'FILENAME'			=> $row['real_filename'],
					'COMMENT'			=> $row['attach_comment'],
					'EXTENSION'			=> $row['extension'],
					'SIZE'				=> mksize($row['filesize']),
					'DOWNLOAD_COUNT'	=> $row['download_count'],
					'POST_TIME'			=> format_date2($row['filetime']),
					'TOPIC_TITLE'		=> ($row['in_message']) ? $row['message_title'] : $row['subject'],

					'ATTACH_ID'			=> $row['attach_id'],
					'POST_ID'			=> $row['post_msg_id'],
					'TOPIC_ID'			=> $row['topic_id'],

					'S_IN_MESSAGE'		=> $row['in_message'],

					'U_VIEW_ATTACHMENT'	=> $siteurl . "/file.php?id=" . $row['attach_id'],
					'U_VIEW_TOPIC'		=> $view_topic)
				);

				$row_count++;
			}
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'PAGE_NUMBER'			=> on_page($num_attachments, $torrent_per_page, $start),
			'PAGINATION'			=> generate_pagination("user.php?op=editprofile&action=overview&mode=attachments&amp;sk=$sort_key&amp;sd=$sort_dir", $num_attachments, $torrent_per_page, $start),
			'TOTAL_ATTACHMENTS'		=> $num_attachments,

			'L_TITLE'				=> $user->lang['UCP_ATTACHMENTS'],

			'U_SORT_FILENAME'		=> "user.php?op=editprofile&action=overview&mode=attachments&amp;sk=a&amp;sd=" . (($sort_key == 'a' && $sort_dir == 'a') ? 'd' : 'a'),
			'U_SORT_FILE_COMMENT'	=> "user.php?op=editprofile&action=overview&mode=attachments&amp;sk=b&amp;sd=" . (($sort_key == 'b' && $sort_dir == 'a') ? 'd' : 'a'),
			'U_SORT_EXTENSION'		=> "user.php?op=editprofile&action=overview&mode=attachments&amp;sk=c&amp;sd=" . (($sort_key == 'c' && $sort_dir == 'a') ? 'd' : 'a'),
			'U_SORT_FILESIZE'		=> "user.php?op=editprofile&action=overview&mode=attachments&amp;sk=d&amp;sd=" . (($sort_key == 'd' && $sort_dir == 'a') ? 'd' : 'a'),
			'U_SORT_DOWNLOADS'		=> "user.php?op=editprofile&action=overview&mode=attachments&amp;sk=e&amp;sd=" . (($sort_key == 'e' && $sort_dir == 'a') ? 'd' : 'a'),
			'U_SORT_POST_TIME'		=> "user.php?op=editprofile&action=overview&mode=attachments&amp;sk=f&amp;sd=" . (($sort_key == 'f' && $sort_dir == 'a') ? 'd' : 'a'),
			'U_SORT_TOPIC_TITLE'	=> "user.php?op=editprofile&action=overview&mode=attachments&amp;sk=g&amp;sd=" . (($sort_key == 'f' && $sort_dir == 'a') ? 'd' : 'a'),

			'S_DISPLAY_MARK_ALL'	=> ($num_attachments) ? true : false,
			'S_DISPLAY_PAGINATION'	=> ($num_attachments) ? true : false,
			'S_UCP_ACTION'			=> 'user.php?op=editprofile&action=overview&mode=attachments',
			'S_SORT_OPTIONS' 		=> $s_sort_key,
			'S_ORDER_SELECT'		=> $s_sort_dir)
		);

?>