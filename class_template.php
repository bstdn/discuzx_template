<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: class_template.php 21214 2011-03-18 09:25:12Z monkey $
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class template {

    var $subtemplates = array();
    var $csscurmodules = '';
    var $replacecode = array('search' => array(), 'replace' => array());
    var $language = array();
    var $file = '';
    function parse_template($tplfile, $templateid, $tpldir, $file, $cachefile) {
        $basefile = basename(DISCUZ_ROOT.$tplfile, '.htm');
        $file == 'common/header' && defined('CURMODULE') && CURMODULE && $file = 'common/header_'.CURMODULE;
        $this->file = $file;

        if(!@$fp = fopen(DISCUZ_ROOT.$tplfile, 'r')) {
            $tpl = $tpldir.'/'.$file.'.htm';
            $tplfile = $tplfile != $tpl ? $tpl.'", "'.$tplfile : $tplfile;
            echo $tplfile.'template is not found';
        }

        $template = @fread($fp, filesize(DISCUZ_ROOT.$tplfile));
        fclose($fp);

        $var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
        $headerexists = preg_match("/{(sub)?template\s+[\w\/]+?header\}/", $template);
        $this->subtemplates = array();

        for($i = 1; $i <= 3; $i++) {
            if(strexists($template, '{subtemplate')) {
                $template = preg_replace("/[\n\r\t]*(\<\!\-\-)?\{subtemplate\s+([a-z0-9_:\/]+)\}(\-\-\>)?[\n\r\t]*/ies", "\$this->loadsubtemplate('\\2')", $template);
            }
        }

        $template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
        $template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
        $template = str_replace("{LF}", "<?=\"\\n\"?>", $template);
        $template = preg_replace("/[\n\r\t]*\{eval\s+(.+?)\s*\}[\n\r\t]*/ies", "\$this->evaltags('\\1')", $template);
        $template = preg_replace("/\{(\\\$[a-zA-Z0-9_\-\>\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);
        $template = preg_replace("/$var_regexp/es", "template::addquote('<?=\\1?>')", $template);
        $template = preg_replace("/\<\?\=\<\?\=$var_regexp\?\>\?\>/es", "\$this->addquote('<?=\\1?>')", $template);

        // $headeradd = $headerexists ? "hookscriptoutput('$basefile');" : '';
        $headeradd = '';

        if(!empty($this->subtemplates)) {
            $headeradd .= "\n0\n";
            foreach($this->subtemplates as $fname) {
                $headeradd .= "|| checktplrefresh('$tplfile', '$fname', ".time().", '$templateid', '$cachefile', '$tpldir', '$file')\n";
            }
            $headeradd .= ';';
        }

        $template = "<? if(!defined('IN_DISCUZ')) exit('Access Denied'); {$headeradd}?>\n$template";
        $template = preg_replace("/[\n\r\t]*\{template\s+([a-z0-9_:\/]+)\}[\n\r\t]*/ies", "\$this->stripvtags('<? include template(\'\\1\'); ?>')", $template);
        $template = preg_replace("/[\n\r\t]*\{template\s+(.+?)\}[\n\r\t]*/ies", "\$this->stripvtags('<? include template(\'\\1\'); ?>')", $template);
        $template = preg_replace("/[\n\r\t]*\{echo\s+(.+?)\}[\n\r\t]*/ies", "\$this->stripvtags('<? echo \\1; ?>')", $template);
        $template = preg_replace("/([\n\r\t]*)\{if\s+(.+?)\}([\n\r\t]*)/ies", "\$this->stripvtags('\\1<? if(\\2) { ?>\\3')", $template);
        $template = preg_replace("/([\n\r\t]*)\{elseif\s+(.+?)\}([\n\r\t]*)/ies", "\$this->stripvtags('\\1<? } elseif(\\2) { ?>\\3')", $template);
        $template = preg_replace("/\{else\}/i", "<? } else { ?>", $template);
        $template = preg_replace("/\{\/if\}/i", "<? } ?>", $template);
        $template = preg_replace("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\}[\n\r\t]*/ies", "\$this->stripvtags('<? if(is_array(\\1)) foreach(\\1 as \\2) { ?>')", $template);
        $template = preg_replace("/[\n\r\t]*\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}[\n\r\t]*/ies", "\$this->stripvtags('<? if(is_array(\\1)) foreach(\\1 as \\2 => \\3) { ?>')", $template);
        $template = preg_replace("/\{\/loop\}/i", "<? } ?>", $template);
        $template = preg_replace("/\{$const_regexp\}/s", "<?=\\1?>", $template);
        if(!empty($this->replacecode)) {
            $template = str_replace($this->replacecode['search'], $this->replacecode['replace'], $template);
        }
        $template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);
 
        if(!@$fp = fopen(DISCUZ_ROOT.$cachefile, 'w')) {
            echo    dirname(DISCUZ_ROOT.$cachefile).'directory is not found';
        }

        $template = preg_replace("/\"(http)?[\w\.\/:]+\?[^\"]+?&[^\"]+?\"/e", "\$this->transamp('\\0')", $template);
        $template = preg_replace("/\<script[^\>]*?src=\"(.+?)\"(.*?)\>\s*\<\/script\>/ies", "\$this->stripscriptamp('\\1', '\\2')", $template);
        $template = preg_replace("/[\n\r\t]*\{block\s+([a-zA-Z0-9_\[\]]+)\}(.+?)\{\/block\}/ies", "\$this->stripblock('\\1', '\\2')", $template);
        $template = preg_replace("/\<\?(\s{1})/is", "<?php\\1", $template);
        $template = preg_replace("/\<\?\=(.+?)\?\>/is", "<?php echo \\1;?>", $template);
        flock($fp, 2);
        fwrite($fp, $template);
        fclose($fp);
    }

    function stripphpcode($type, $code) {
        $this->phpcode[$type][] = $code;
        return '{phpcode:'.$type.'/'.(count($this->phpcode[$type]) - 1).'}';
    }

    function loadsubtemplate($file) {
        $tplfile = template($file, 0, '', 1);
        if($content = @implode('', file(DISCUZ_ROOT.$tplfile))) {
            $this->subtemplates[] = $tplfile;
            return $content;
        } else {
            return '<!-- '.$file.' -->';
        }
    }

    function transamp($str) {
		$str = str_replace('&', '&amp;', $str);
		$str = str_replace('&amp;amp;', '&amp;', $str);
        $str = str_replace('\"', '"', $str);
        return $str;
    }

    function addquote($var) {
        return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
    }

    function stripvtags($expr, $statement = '') {
        $expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
        $statement = str_replace("\\\"", "\"", $statement);
        return $expr.$statement;
    }

    function stripscriptamp($s, $extra) {
        $extra = str_replace('\\"', '"', $extra);
        $s = str_replace('&amp;', '&', $s);
        return "<script src=\"$s\" type=\"text/javascript\"$extra></script>";
    }

    function evaltags($php) {
        $php = str_replace('\"', '"', $php);
        $i = count($this->replacecode['search']);
        $this->replacecode['search'][$i] = $search = "<!--EVAL_TAG_$i-->";
        $this->replacecode['replace'][$i] = "<?php $php?>";
        return $search;
    }

    function stripblock($var, $s) {
        $s = str_replace('\\"', '"', $s);
        $s = preg_replace("/<\?=\\\$(.+?)\?>/", "{\$\\1}", $s);
        preg_match_all("/<\?=(.+?)\?>/e", $s, $constary);
        $constadd = '';
        $constary[1] = array_unique($constary[1]);
        foreach($constary[1] as $const) {
            $constadd .= '$__'.$const.' = '.$const.';';
        }

        $s = preg_replace("/<\?=(.+?)\?>/", "{\$__\\1}", $s);
        $s = str_replace('?>', "\n\$$var .= <<<EOF\n", $s);
        $s = str_replace('<?', "\nEOF;\n", $s);
        return "<?\n$constadd\$$var = <<<EOF\n".$s."\nEOF;\n?>";
    }

}
