<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\ExtUi;

class defaultMenu
{
    
    public static function getMenuConfig(\Action $action, \Doc $doc)
    {
        
        $mainmenu = '';
        $popup = self::getMenuItems($action, $doc);
        // rewrite for api 3.0
        $im = array();
        foreach ($popup as $k => $v) {
            if ($v["visibility"] != POPUP_INVISIBLE) {
                if (!isset($v["jsfunction"])) $v["jsfunction"] = '';
                if (!isset($v["title"])) $v["title"] = '';
                if (!isset($v["color"])) $v["color"] = '';
                if (!isset($v["url"])) $v["url"] = '';
                if (!isset($v["icon"])) $v["icon"] = '';
                if (preg_match("/zone=.*:pdf/", $v["url"])) $url = $v["url"];
                else {
                    
                    $url = self::convertToExtUrl($v["url"]);
                }
                $imenu = array(
                    "url" => $url,
                    "javascript" => self::convertToExtUrl($v["jsfunction"]) ,
                    "visibility" => $v["visibility"],
                    "label" => $v["descr"],
                    "type" => "item",
                    "target" => $v["target"],
                    "description" => $v["title"],
                    "backgroundColor" => $v["color"],
                    "icon" => $v["icon"],
                    "confirm" => (($v["confirm"] && $v["confirm"] != 'false') ? array(
                        "label" => $v["tconfirm"],
                        "continue" => ___("yes", "extui") ,
                        "cancel" => ___("no", "extui")
                    ) : null)
                );
                if (!$v["submenu"]) {
                    $mainmenu = ($doc->doctype == 'C') ? ___("Family", "extui") : $v["submenu"] = $doc->fromtitle;
                    $v["submenu"] = $mainmenu;
                }
                if ($v["submenu"]) {
                    if (empty($im[$v["submenu"]])) {
                        $im[$v["submenu"]] = array(
                            "type" => "menu",
                            "label" => _($v["submenu"]) ,
                            "items" => array()
                        );
                        if ($v["submenu"] == $doc->fromtitle) $im[$v["submenu"]]["icon"] = $doc->getIcon();
                    }
                    $im[$v["submenu"]]["items"][$k] = $imenu;
                } else $im[$k] = $imenu;
            }
        }
        $im[$mainmenu]["items"]["histo"] = array(
            "script" => array(
                "file" => "lib/ui/fdl-interface-action-common.js",
                "class" => "Fdl.InterfaceAction.Historic"
            ) ,
            "label" => ___("Historic", "extui") ,
            "visibility" => isset($im[$mainmenu]["items"]["histo"]["visibility"]) ? $im[$mainmenu]["items"]["histo"]["visibility"] : ''
        );
        $fnote = new_doc($doc->dbaccess, "SIMPLENOTE");
        if ($fnote->isAlive()) {
            if ($fnote->control("icreate") == "") {
                $im[$mainmenu]["items"]["addpostit"] = array(
                    "script" => array(
                        "file" => "lib/ui/fdl-interface-action-common.js",
                        "class" => "Fdl.InterfaceAction.SimpleNote"
                    ) ,
                    "label" => ___("Add a note", "extui") ,
                    "visibility" => $im[$mainmenu]["items"]["addpostit"]["visibility"]
                );
            } else {
                $im[$mainmenu]["items"]["addpostit"]["visibility"] = POPUP_INVISIBLE;
            }
        } else {
            if (!empty($im[$mainmenu]["items"]["addpostit"])) {
                $im[$mainmenu]["items"]["addpostit"]["javascript"] = str_replace(array(
                    "EUI_EDITDOC",
                    'EXTUI'
                ) , array(
                    "GENERIC_EDIT",
                    'GENERIC'
                ) , $im[$mainmenu]["items"]["addpostit"]["javascript"]);
            }
        }
        if ($doc->control("send") == "") {
            $im[$mainmenu]["items"]["sendmail"] = array(
                "url" => "?app=FDL&action=EDITMAIL&viewext=yes&mid=" . $doc->id,
                "label" => ___("Send document", "extui") ,
                "visibility" => POPUP_ACTIVE
            );
        }
        $im[$mainmenu]["items"]["viewprint"] = array(
            "javascript" => "window.open('?app=FDL&action=IMPCARD&view=print&id=" . $doc->id . "','_blank')",
            "label" => ___("View print version", "extui") ,
            "visibility" => POPUP_ACTIVE
        );
        $im[$mainmenu]["items"]["reload"] = array(
            "script" => array(
                "file" => "lib/ui/fdl-interface-action-common.js",
                "class" => "Fdl.InterfaceAction.Reload"
            ) ,
            "label" => ___("Reload document", "extui") ,
            "visibility" => POPUP_ACTIVE
        );
        return $im;
    }
    
    public static function convertToExtUrl($url)
    {
        if (preg_match('/(?:^|[?&])action=FDL_CARD(?:&|$)/', $url)) {
            $url = str_replace(array(
                "action=FDL_CARD",
                'app=FDL'
            ) , array(
                "action=EUI_VIEWDOC",
                'app=EXTUI'
            ) , $url);
        }
        if (preg_match('/(?:^|[?&])action=GENERIC_EDIT(?:&|$)/', $url)) {
            $url = str_replace(array(
                "action=GENERIC_EDIT",
                'app=GENERIC'
            ) , array(
                "action=EUI_EDITDOC",
                'app=EXTUI'
            ) , $url);
        }
        
        $url = preg_replace('/((?:^|[?&])app=[A-Z]+)/', '${1}&viewext=yes', $url);
        
        return $url;
    }
    
    protected static function getMenuItems(\Action $action, \Doc $doc)
    {
        $popup = [];
        if ($doc->doctype == 'C') {
            $popup = getpopupfamdetail($action, $doc->id);
        } else {
            if ($doc->specialmenu) {
                if (preg_match("/(.*):(.*)/", $doc->specialmenu, $reg)) {
                    $action->parent->setVolatileParam("getmenulink", 1);
                    
                    $dir = $reg[1];
                    $function = strtolower($reg[2]);
                    $file = $function . ".php";
                    if (include_once ("$dir/$file")) {
                        $function($action);
                        $popup = $action->getParam("menulink");;
                    } else {
                        addwarningMsg(sprintf(_("Incorrect specification of special menu : %s") , $doc->specialmenu));
                    }
                    $action->parent->setVolatileParam("getmenulink", null);
                } else {
                    addwarningMsg(sprintf(_("Incorrect specification of special menu : %s") , $doc->specialmenu));
                }
            }
        }
        if (!$popup) {
            $popup = getpopupdocdetail($action, $doc->id);
        }
        return $popup;
    }
}
