<?php
/**
 * Helper Component of DokuWiki PdfView plugin
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class helper_plugin_pdfview extends DokuWiki_Plugin {

    /**
     * Resolve media URLs
     * Create Media Link from DokuWiki media id considering $conf['userewrite'] value.
     * @see function ml() in inc/common.php
     *
     * @param string $linkId    mediaId
     * @param bool    $abs        Create an absolute URL
     * @return string|false      URL of the media file
     */
    function resolveMediaUrl($linkId, $abs=false) {
        global $ACT, $ID, $conf;

        if (empty($linkId)) return false;

        // external URLs including protocol-less URLs always direct without rewriting
        if (preg_match('#^(https?:)?//#', $linkId) === 1) {
            return $linkId;
        } else if ($linkId[0] == '/') { // same host outside DokuWiki
            return $linkId;
        }

        // internal media
        $id = cleanID($linkId);
        resolve_mediaid(getNS($ID), $id, $exists);

        if (!$exists) return false;

        // check MIME setting of DokuWiki - mime.conf/mime.local.conf
        // Embedding will fail if the media file is to be force_download.
        list($ext, $mtype, $force_download) = mimetype($id);

        $id = idfilter($id);
        if (!$force_download) {
            switch ($conf['userewrite']){
                case 0: // No URL rewriting
                    $mediapath = 'lib/exe/fetch.php?media='.$id;
                    break;
                case 1: // serverside rewriting eg. .htaccess file
                    $mediapath = '_media/'.$id;
                    break;
                case 2: // DokuWiki rewiteing
                    $mediapath = 'lib/exe/fetch.php/'.$id;
                    break;
            }
        } else {
            // try alternative url to avoid download dialog.
            //
            // !!! EXPERIMENTAL : WEB SITE SPECIFIC FEATURE !!!
            // we assume "DOKU_URL/_media" directory 
            // which physically mapped or linked to 
            // your DW_DATA_PATH/media directory.
            // WebServer solution includes htpd.conf, IIS virtual directory.
            // Symbolic link or Junction are Filesystem solution.
            // Example:
            // if linux: ln -s DW_DATA_PATH/media _media
            // if iis6(Win2003S): linkd.exe _media DW_DATA_PATH/media
            // if iis7(Win2008S): mklink.exe /d _media DW_DATA_PATH/media
            //

            $altMediaBaseDir = $this->getConf('alternative_mediadir');
            if (empty($altMediaBaseDir)) $altMediaBaseDir ='_media/';

            if ($id[0] == ':') $linkId = substr($id, 1);
            $mediapath = $altMediaBaseDir . str_replace(':','/',$id);
            if ($ACT=='preview') {
                msg($this->getPluginName().': alternative url ('.$mediapath.') will be used for '.$id, 2);
            }
        }

        if ($abs) {
            return DOKU_URL . $mediapath;
        } else {
            return DOKU_REL . $mediapath;
        }
    }


    /* ---------------------------------------------------------
     * get each named/non-named arguments as array variable
     *
     * Named arguments is to be given as key="value" (quoted).
     * Non-named arguments is assumed as boolean.
     *
     * @param string $args   arguments
     * @return array     parsed arguments in $arg['key']=value
     * ---------------------------------------------------------
     */
    function getArguments($args='') {
        $arg = array();
        if (empty($args)) return $arg;

        // get named arguments (key="value"), ex: width="100"
        // value must be quoted in argument string.
        $val = "([\"'`])(?:[^\\\\\"'`]|\\\\.)*\g{-1}";
        $pattern = "/\b(\w+)=($val) ?/";
        preg_match_all($pattern, $args, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $arg[$match[1]] = substr($match[2], 1, -1); // drop quates from value string
            $args = str_replace($match[0], '', $args); // remove parsed substring
        }

        // get named numeric value argument, ex width=100
        // numeric value may not be quoted in argument string.
        $val = '\d+';
        $pattern = "/\b(\w+)=($val) ?/";
        preg_match_all($pattern, $args, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $arg[$match[1]] = (int)$match[2];
            $args = str_replace($match[0], '', $args); // remove parsed substring
        }

        // get non-named arguments
        $tokens = preg_split('/\s+/', $args);
        foreach ($tokens as $token) {

            // get size parameters specified as non-named arguments
            // assume as single size or eles width and height pair
            //  ex: 85% |  256x256 | 800,600px | 85%,300px
            $pattern = '/(\d+(\%|em|pt|px)?)(?:[,xX]?(\d+(\%|em|pt|px)?))?$/';
            if (preg_match($pattern, $token, $matches)) {
                //error_log('helper matches: '.count($matches).' '.var_export($matches, 1));
                if ((count($matches) > 4) && empty($matches[2])) {
                    $matches[2] = $matches[4];
                    $matches[1] = $matches[1].$matches[4];
                } elseif (count($matches) > 3) {
                    $arg['width']  = $matches[1];
                    $arg['height'] = $matches[3];
                } else {
                    $arg['size'] = $matches[1];
                }
            }

            // get flags, ex: showdate, noshowfooter
            if (preg_match('/^(?:!|not?)(.+)/',$token, $matches)) {
                // denyed/negative prefixed token
                $arg[$matches[1]] = false;
            } elseif (preg_match('/^[A-Za-z]/',$token)) {
                $arg[$token] = true;
            }
        }
        return $arg;
    }

}

