<?php
/*
 * @author Anakeen
 * @package FDL
*/
/**
 * View Document
 *
 * @author Anakeen
 * @version $Id: fdl_card.php,v 1.42 2008/12/02 15:20:52 eric Exp $
 * @package FDL
 * @subpackage
 */
/**
 */

include_once ("FDL/fdl_card.php");

include_once ("FDL/popupdocdetail.php");
include_once ("FDL/popupfamdetail.php");
/**
 * View a extjs document
 * @param Action &$action current action
 * @global string $id Http var : document identifier to see
 * @global string $latest Http var : (Y|N|L|P) if Y force view latest revision, L : latest fixed revision, P : previous revision
 * @global string $state Http var : to view document in latest fixed state (only if revision > 0)
 * @global string $abstract Http var : (Y|N) if Y view only abstract attribute
 * @global string $props Http var : (Y|N) if Y view properties also
 * @global string $zonebodycard Http var : if set, view other specific representation
 * @global string $vid Http var : if set, view represention describe in view control (can be use only if doc has controlled view)
 * @global string $ulink Http var : (Y|N)if N hyperlink are disabled
 * @global string $target Http var : is set target of hyperlink can change (default _self)
 * @global string $inline Http var : (Y|N) set to Y for binary template. View in navigator
 * @global string $reload Http var : (Y|N) if Y update freedom folders in client navigator
 * @global string $dochead Http var :  (Y|N) if N don't see head of document (not title and icon)
 */
function eui_viewdoc(Action & $action)
{
    
    $ec = $action->getArgument("extconfig");
    if ($ec) {
        $ec = json_decode($ec);
        foreach ($ec as $k => $v) {
            setHttpVar("ext:$k", $v);
        }
    }
    fdl_card($action);
    $docid = $action->getArgument("id");
    
    $doc = new_Doc("", $docid);
    
    $im = Dcp\ExtUi\defaultMenu::getMenuConfig($action, $doc);
    $action->lay->set("documentMenu", json_encode($im));
    
    $style = $action->parent->getParam("STYLE");
    
    $action->parent->AddCssRef("STYLE/DEFAULT/Layout/EXT-ADAPTER-SYSTEM.css");
    if (file_exists($action->parent->rootdir . "/STYLE/$style/Layout/EXT-ADAPTER-USER.css")) {
        $action->parent->AddCssRef("STYLE/$style/Layout/EXT-ADAPTER-USER.css");
    } else {
        $action->parent->AddCssRef("STYLE/DEFAULT/Layout/EXT-ADAPTER-USER.css");
    }
}
