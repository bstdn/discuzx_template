<?php

function strexists($string, $find) {
    return !(strpos($string, $find) === FALSE);
}

function checktplrefresh($maintpl, $subtpl, $timecompare, $templateid, $cachefile, $tpldir, $file) {
    static $tplrefresh, $timestamp;
    if($tplrefresh === null) {
        $tplrefresh = 1;
        $timestamp = time();
    }
    if(empty($timecompare) || $tplrefresh == 1 || ($tplrefresh > 1 && !($timestamp % $tplrefresh))) {
        if(empty($timecompare) || @filemtime(DISCUZ_ROOT.$subtpl) > $timecompare) {
            require_once DISCUZ_ROOT.'./class_template.php';
            $template = new template();
            $template->parse_template($maintpl, $templateid, $tpldir, $file, $cachefile);
            return TRUE;
        }
    }
    return FALSE;
}

function template($file, $templateid = 0, $tpldir = '', $gettplfile = 0) {
    $tpldir = $tpldir ? $tpldir : (defined('TPLDIR') ? TPLDIR : '');
    $tpldir = './'.$tpldir;
    $templateid = $templateid ? $templateid : (defined('TEMPLATEID') ? TEMPLATEID : '');
    $tplfile = ($tpldir ? $tpldir.'/' : './template/').$file.'.htm';
    $filebak = $file;
    $file == 'common/header' && defined('CURMODULE') && CURMODULE && $file = 'common/header_'.$_G['basescript'].'_'.CURMODULE;
    $cachefile = './data/template/'.(defined('STYLEID') ? STYLEID.'_' : '_').$templateid.'_'.str_replace('/', '_', $file).'.tpl.php';
    if($templateid != 1 && !file_exists(DISCUZ_ROOT.$tplfile)) {
        $tplfile = './template/default/'.$filebak.'.htm';
    }

    if($gettplfile) {
        return $tplfile;
    }
    checktplrefresh($tplfile, $tplfile, @filemtime(DISCUZ_ROOT.$cachefile), $templateid, $cachefile, $tpldir, $file);
    return DISCUZ_ROOT.$cachefile;
}
