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

if (eregi("bdecoder.php",$_SERVER['PHP_SELF'])) die("You can't access this file directly");

/*
WINDOWS WARNING
ICONV.DLL MUST BE IN C:\WINDOWS\SYSTEM32 OR
EXTENSION LOADING WILL FAIL
*/
//if (phpversion() < 5) {
//if (!extension_loaded("domxml") AND !defined("DOMXML_LOADED")) dl((PHP_OS=="WINNT" OR PHP_OS=="WIN32") ? "include/extensions/domxml.dll" : "include/extensions/domxml.so");
//}

function escape_hex($matches) {
        return sprintf("%02x", ord($matches[0]));
}


function BDecode($str, $type = "Torrent") {
        $xmlTorrent = domxml_new_doc("1.0");

        $Torrent = $xmlTorrent->create_element($type);

        $child = BDec($Torrent, $str);
        if (!$child) return null;
        $children = $child->child_nodes();
        foreach ($children as $value) {
                $Torrent->append_child($value);
        }

        $xmlTorrent->append_child($Torrent);
        return $xmlTorrent;
}

function BDec(&$parent, &$str) {
        if (preg_match('/^(\d+):/', $str))
                return BDec_string($parent, $str);
        elseif (preg_match('/^i(\d+)e/', $str))
                return BDec_integer($parent, $str);
        elseif ($str[0] == "l")
                return BDec_list($parent, $str);
        elseif ($str[0] == "d")
                return BDec_dictionary($parent, $str);
        else trigger_error("Error: ".htmlspecialchars(substr($str,0,50)),E_USER_WARNING);
        return null;
}

function BDec_string(&$parent, &$str) {
        if (!preg_match('/^(\d+):/', $str, $m)) return false;
        $parent->set_attribute("type","String");
        $l = $m[1];
        $pl = strlen($l) + 1;
        $v = substr($str, $pl, $l);
        $owner = $parent->owner_document();
        $ret = $owner->create_element("String");
        if (!preg_match('/^[ -~\\t\\r\\n]*$/', $v)) {
                $parent->set_attribute("encode","hex");
                $v = preg_replace_callback('/./s', "escape_hex", $v);
        }

        $child = $owner->create_text_node($v);
        $ret->append_child($child);
        $str = substr($str,$pl+$l);
        return $ret;
}

function BDec_name(&$str) {
        if (!preg_match('/^(\d+):/', $str, $m)) return false;
        $l = $m[1];
        $pl = strlen($l) + 1;
        $v = substr($str, $pl, $l);
        $str = substr($str,$pl+$l);
        return $v;
}

function BDec_integer(&$parent, &$str) {
        if (!preg_match('/^i(\d+)e/', $str, $m)) return false;
        $parent->set_attribute("type","Integer");
        $v = $m[1];
        if ($v === "-0" OR ($v[0] == "0" AND strlen($v) != 1)) return false;
        $owner = $parent->owner_document();
        $ret = $owner->create_element("Integer");
        $child = $owner->create_text_node($v);
        $ret->append_child($child);
        $str = substr($str,strlen($v)+2);
        return $ret;
}

function BDec_list(&$parent, &$str) {
        if ($str[0] != "l") return false;
        $parent->set_attribute("type","List");
        $owner = $parent->owner_document();
        $ret = $owner->create_element("List");
        $str = substr($str,1);
        do {
                if ($str[0] == "e") break;
                $child = $owner->create_element("Item");
                $get = BDec($child, $str);
                $children = $get->child_nodes();
                foreach ($children as $value) {
                        $child->append_child($value);
                }
                $ret->append_child($child);
                unset($child);
        } while (true);
        $str = substr($str,1);
        return $ret;
}

function BDec_dictionary(&$parent, &$str) {
        if ($str[0] != "d") return false;
        $parent->set_attribute("type","Dictionary");
        $owner = $parent->owner_document();
        $ret = $owner->create_element("Dictionary");
        $str = substr($str,1);
        $children = Array();
        do {
                if ($str[0] == "e") break;
                $name = BDec_name($str);
                if (eregi('[^-_:. \\wa-z0-9]', $name)) {
                        $nm = preg_replace_callback('/./s', "escape_hex", $name);
                        $child = $owner->create_element('a'.$nm);
                        $child->set_attribute("tag_encode","hex");
                } elseif (strpos($name," ")) {
                        $nm = str_replace(" ","_",$name);
                        $child = $owner->create_element($nm);

                        $child->set_attribute("original",$name);
                } else $child = $owner->create_element($name);

                $get = BDec($child, $str);
                $children = $get->child_nodes();
                foreach ($children as $value) {
                        $child->append_child($value);
                }
                $ret->append_child($child);
                unset($child);
        } while (true);
        $str = substr($str,1);
        return $ret;
}
/*
SYNTAX
<Torrent>
  <Spam type="Dictionary">
    <Eggs type="Dictionary">
      <Egg type="String">An egg</Eggs>
      <Num type="Integer">2</Num>
    </Eggs>
    <Mint type="String">Bad Breath</Mint>
  </Spam>
</Torrent
To get the Eggs dictionary query string is
Spam/Egg

There are 3 possible values for $type
Torrent
Announce
Scrape
*/
function entry_exists(&$document, $query, $root = "Torrent") {
        if (!preg_match('/^(?P<path>[a-z0-9._:-][a-z0-9._:\/ -]*){1}(?:\\((?P<type>String|Integer|List|Dictionary)\\)){1}$/i', $query, $matches)) {
                trigger_error("Invalid Query",E_USER_WARNING);
                return false;
        }
        $dict = $matches["path"];
        $type = $matches["type"];
        $calcX = xpath_new_context($document);
        $result = xpath_eval($calcX,"/".$root."/".str_replace(" ","_",$dict));

        if (!empty($result->nodeset) AND $result->nodeset[0]->has_attribute("type") AND strtolower($result->nodeset[0]->get_attribute("type")) == strtolower($type)) return true;
        return false;
}



function entry_get(&$document, $dict, $root = "Torrent") {
        if (!preg_match('/^[a-z0-9._:-][a-z0-9._:\/*\[\] -]*+$/i', $dict)) {
                trigger_error("Invalid dictionary request",E_USER_WARNING);
                return null;
        }
        $calcX = &xpath_new_context($document);
        $result = &xpath_eval($calcX,"/".$root."/".str_replace(" ","_",$dict));

        if (!empty($result->nodeset)) return $result->nodeset[0];
        else return null;
}

function entry_read(&$document, $query, $root = "Torrent") {
        if (!preg_match('/^(?P<path>[a-z0-9._:-][a-z0-9._:\/ -]*){1}(?:\\((?P<type>String|Integer|List|Dictionary)\\)){1}$/i', $query, $matches)) {
                trigger_error("Invalid Query: ".$query,E_USER_WARNING);
                return false;
        }
        $dict = $matches["path"];
        $type = $matches["type"];

        $calcX = xpath_new_context($document);
        $result = xpath_eval($calcX,"/".$root."/".str_replace(" ","_",$dict));

        if (empty($result->nodeset)) {
                trigger_error("Empty Result: ".$query,E_USER_WARNING);
                return null;
        }

        switch (strtolower($type)) {
                case "integer":
                case "string": return $result->nodeset[0]->get_content();
                case "list": return $result->nodeset[0]->child_nodes();
                case "dictionary": {
                        $ret = Array();
                        foreach ($result->nodeset[0]->child_nodes() as $node) {
                                $ret[$node->tagname] = $node;
                        }
                        return $ret;
                }
        }
}

?>