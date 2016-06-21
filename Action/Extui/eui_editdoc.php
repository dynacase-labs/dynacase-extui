<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/
/**
 * View Document
 *
 * @author Anakeen
 * @version $Id: fdl_card.php,v 1.42 2008/12/02 15:20:52 eric Exp $
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
 * @subpackage
 */
/**
 */

include_once ("GENERIC/generic_edit.php");

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
function eui_editdoc(Action & $action)
{
    
    $rzone = GetHttpVars("rzone"); // special zone when finish edition
    $ezone = GetHttpVars("ezone"); // special zone when finish edition
    $rvid = GetHttpVars("rvid"); // special zone when finish edition
    $rtarget = GetHttpVars("rtarget", "_self"); // special zone when finish edition return target
    $classid = GetHttpVars("classid"); // special zone when finish edition
    $vid = GetHttpVars("vid"); // special controlled view
    $ec = getHttpVars("extconfig");
    if ($ec) {
        $ec = json_decode($ec);
        foreach ($ec as $k => $v) setHttpVar("ext:$k", $v);
    }
    
    $docid = GetHttpVars("id");
    $dbaccess = $action->dbaccess;
    
    if (($docid === 0) || ($docid === "") || ($docid === "0")) {
        if ($classid == "") $action->exitError(sprintf(_("Creation aborded : no family specified")));
        if (!is_numeric($classid)) $classid = getFamIdFromName($dbaccess, $classid);
        if ($classid == "") $action->exitError(sprintf(_("Creation aborded : unknow family %s") , GetHttpVars("classid", getDefFam($action))));
        if ($classid > 0) {
            $cdoc = new_Doc($dbaccess, $classid);
            if ($cdoc->control('create') != "") $action->exitError(sprintf(_("no privilege to create this kind (%s) of document") , $cdoc->gettitle()));
            if ($cdoc->control('icreate') != "") $action->exitError(sprintf(_("no privilege to create interactivaly this kind (%s) of document") , $cdoc->gettitle()));
        }
        
        $doc = createDoc($dbaccess, $classid);
        if (!$doc) $action->exitError(sprintf(_("no privilege to create this kind (%d) of document") , $classid));
    } else {
        $doc = new_Doc($dbaccess, $docid, true); // always latest revision
        $rev = getLatestRevisionNumber($dbaccess, $doc->initid, $doc->fromid);
        if ($doc->revision != $rev) $action->ExitError(sprintf("document %d : multiple alive revision (%d <> %d)", $doc->initid, $doc->revision, $rev));
        $docid = $doc->id;
        setHttpVar("id", $doc->id);
        $err = $doc->lock(true); // autolock
        if ($err != "") $action->ExitError($err);
        if ($err == "") $action->AddActionDone("LOCKDOC", $doc->id);
        
        $classid = $doc->fromid;
        if (!$doc->isAlive()) $action->ExitError(_("document not referenced"));
    }
    
    $im = array();
    if ($doc) {
        // rewrite for api 3.0
        $im["save"] = array(
            "url" => '',
            "javascript" => "submitEdit()",
            "icon" => $doc->getIcon() ,
            "visibility" => POPUP_ACTIVE,
            "label" => ($doc->id > 0) ? _("Save") : _("Create") ,
            "target" => "_self",
            "description" => '',
            "backgroundColor" => ''
        );
        $im["cancel"] = array(
            "url" => ($doc->id > 0) ? '?app=FDL&action=UNLOCKFILE&auto=Y&viewext=yes&id=' . $doc->id : '?app=FREEDOM&action=FREEDOM_LOGO',
            "javascript" => "",
            "visibility" => POPUP_ACTIVE,
            "label" => _("Cancel") ,
            "target" => "_self",
            "description" => '',
            "backgroundColor" => '',
            "icon" => ''
        );
        if (true || GetHttpVars("viewconstraint") == "Y") {
            if ($action->user->id == 1) {
                $im["saveforce"] = array(
                    "url" => '',
                    "javascript" => "submitEdit(null,true)",
                    "visibility" => POPUP_INVISIBLE,
                    "description" => _("override constraints") ,
                    "label" => ($doc->id > 0) ? _("Save !") : _("Create !") ,
                    "target" => "_self",
                    "backgroundColor" => '',
                    "icon" => ''
                );
            }
        }
    }
    $action->lay->set("documentMenu", json_encode($im));
    $action->lay->set("rtarget", htmlspecialchars($rtarget, ENT_QUOTES));
    $action->lay->set("title",htmlspecialchars( ($docid) ? $doc->getTitle() : $doc->getTitle($doc->fromid), ENT_QUOTES));
    $action->lay->set("vid", htmlspecialchars($vid, ENT_QUOTES));
    $action->lay->set("rvid", htmlspecialchars($rvid, ENT_QUOTES));
    $action->lay->set("rzone", htmlspecialchars($rzone, ENT_QUOTES));
    $action->lay->set("ezone", htmlspecialchars($ezone, ENT_QUOTES));
    $action->lay->set("id", $doc->id);
    $action->lay->set("classid", htmlspecialchars($classid, ENT_QUOTES));
    if ($docid) $action->lay->set("STITLE", addJsSlashes($doc->getHTMLTitle()));
    else $action->lay->set("STITLE", addJsSlashes(sprintf(_("Creation %s") , $doc->getHTMLTitle($doc->fromid))));
    $style = $action->parent->getParam("STYLE");
    
    $action->parent->AddCssRef("STYLE/DEFAULT/Layout/EXT-ADAPTER-SYSTEM.css");
    if (file_exists($action->parent->rootdir . "/STYLE/$style/Layout/EXT-ADAPTER-USER.css")) {
        $action->parent->AddCssRef("STYLE/$style/Layout/EXT-ADAPTER-USER.css");
    } else {
        $action->parent->AddCssRef("STYLE/DEFAULT/Layout/EXT-ADAPTER-USER.css");
    }
}
