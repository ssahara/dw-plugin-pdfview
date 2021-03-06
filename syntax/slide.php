<?php
/**
 * DokuWiki Plugin PdfView Slide (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Sahara Satoshi <sahara.satoshi@gmail.com>
 *
 * Implementaion of silde-pdf.js - a presentation tool for pdf files
 * @see also https://github.com/azu/slide-pdf.js
 *
 * SYNTAX:
 *         {{slide [size] > mediaID | title }}
 *
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_pdfview_slide extends DokuWiki_Syntax_Plugin {

    protected $mode;
    protected $pattern = array();
    protected $opts;

    function __construct() {
        $this->mode = substr(get_class($this), 7); // drop 'syntax_' from class name

        // syntax patterns
        $this->pattern[5] = '{{slide\b.*?>.*?}}';
    }


    function getType()  { return 'substition'; }
    function getPType() { return 'block'; }
    function getSort()  { return 305; }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->pattern[5], $mode, $this->mode);
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {

        list($params, $media) = explode('>', substr($match, 7, -2), 2);

        // handle media parameters (linkId and title)
        list($id, $title) = explode('|', $media, 2);

        // check alignment of iframe but IGNORE it!
        // 左右寄せ、またはセンタリングするには、2つ以上のスペースが必要とする
        $pad_left  = (bool)(substr($id, 0, 2) === '  ');
        $pad_right = (bool)(substr($id, -2) === '  ');
        if ($pad_left && $pad_right) {
            $data['align'] = 'center';
        } elseif (!$pad_left && $pad_right) {
            $data['align'] = 'left';
        } elseif ($pad_left && !$pad_right) {
            $data['align'] = 'right';
        }
        $id = trim($id); //remove aligning spaces

        // separate fragment from id
        list($id, $fragment) = explode('#', $id, 2);

        $data['id']       = (!empty($id)) ? $id : null;
        $data['fragment'] = (!empty($fragment)) ? $fragment : null;
        $data['title']    = (!empty($title)) ? trim($title) : null;
        $data['params']   = (!empty($params)) ? trim($params) : null;

        return array($state, $data);
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $indata) {

        if ($format != 'xhtml') return false;

        list($state, $data) = $indata;

        $viewer = DOKU_REL . 'lib/plugins/pdfview/pdfjs-slide/?file=';
        $css = array();

        // parse parameters
        $parser = $this->loadHelper('pdfview');

        $args = $parser->getArguments( $data['params'] );

        // size parameters (if specified)
        if (isset($args['size']) && (preg_match('/^(\d+)(.*)$/', $args['size'], $m) === 1)) {
            if (isset($args['portlait'])) {
                $args['width'] = $args['size'];
                $args['height'] = (ceil($m[1] * 1.414) + 90) . $m[2];
            } else { // assume landscape
                $args['width'] = $args['size'];
                $args['height'] = (ceil($m[1] / 1.414) + 90) . $m[2];
                $args['height'] = (ceil($m[1] / 1.333) + 70) . $m[2];
            }
            unset($args['size']);
        }

        // width and height parameters
        // treat as attributes if they are numeric (without unit like px, %),
        // otherwise as css properties in style attribute
        foreach (array('width','height') as $prop) {
            if (!is_numeric($args[$prop])) {
                $css[$prop] = $args[$prop];
                unset($args[$prop]);
            }
        }

        // build style attribute
        $style = 'border: 1px dotted pink;';
        foreach ($css as $prop => $value) {
            $style .= ' '.$prop.': '.$value.';';
        }


        // url of the pdf file
        // Note: access to external pdf might be restricted 
        //       by Cross-origin resource sharing (CORS).
        $url = $parser->resolveMediaUrl($data['id'], false);

        // check whether url is pdf
        if (strtolower(substr($url, -3)) !== 'pdf') {
            $url = false;
        }

        // html output
        if ($url) {
            $html = '<iframe scrolling="no" allowtransparency="true"';
            if (isset($data['fragment'])) $url.= '#'.$data['fragment'];
            //$url = rawurlencode($url);
            
            $html.= ' src="'.$viewer.$url.'"';
        } else {
            $html = '<div class="'.$this->mode.'"';
        }

        if (isset($args['width'])) {
            $html.= ' width="'.$args['width'].'"';
        }
        if (isset($args['height'])) {
            $html.= ' height="'.$args['height'].'"';
        }
        $html.= ' style="'.$style.'"';
        if (isset($data['title'])) {
            $html.= ' title="'.hsc($data['title']).'"';
        }
        $html.= '>';

        if ($url) {
            $html.= '</iframe>'. DOKU_LF;
        } else {
            // error message
            $html.= '<div class="error">';
            $html.= 'pdf file not exists: '. hsc($data['id']);
            $html.= '</div></div>'. DOKU_LF;
        }

        error_log('html='.$html);
        $renderer->doc .= $html;
        return true;
    }

}
