<?php

use Html;

class Phpx
{
    protected $view = null;
    protected $view_name = null;
    protected $file_content = null;

    public function __construct($view)
    {
        $this->view_name = $view;
        $this->view = 'view/' . $view;
    }

    protected function extractJsx($stringBlock)
    {
        $pattern = '/view (\w+)\(\$args\) \{(.+?)return \((.+?)\)\s*\}/s';
        preg_match($pattern, $stringBlock, $matches);

        $viewName = $matches[1];
        $contentBeforeReturn = $matches[2];
        $returnStatement = $matches[3];

        return array($viewName, $contentBeforeReturn, $returnStatement);
    }
    function get_inner_html($node)
    {
        $innerHTML = '';
        $children = $node->childNodes;
        foreach ($children as $child) {
            $innerHTML .= ($child->ownerDocument->saveXML($child));
        }

        return $innerHTML;
    }

    function render_attributes($dom)
    {
        $attributes = [];
        foreach ($dom->attributes as $attribute) {
            // die($attribute->value);
            $attributes[$attribute->name] = $attribute->value;
        }

        $attribute = var_export($attributes, true);

        return $attribute;
    }

    protected function jsxRenderer($html)
    {
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $result = '';
        $root = $dom->documentElement;
        $elements = $root->getElementsByTagName('*');

        $result .= 'Html\\' . $root->tagName . '(';

        // render root elements
        $result .= $this->render_attributes($root) . ',' .  ($elements->count() > 0 ? '' : '"' . $this->get_inner_html($root) . '"');
        // render children elements
        foreach ($elements as $i => $element) {
            if ($i != 0 || $elements->count() === 1) {
                $innerDOM = $element->ownerDocument->saveHTML($element);
                // echo $innerDOM;
                $result .= $this->jsxRenderer($innerDOM);
            }
        }

        $result .= '),';
        return $result;
    }

    protected function renderDom($dom)
    {
        $html = $this->extractJsx($dom);

        return substr($this->jsxRenderer($html), -1);
    }

    protected function replaceCurlyBraces($string)
    {
        $pattern = '/\"\$([\d\w]+)\"/';
        $replacement = '\$$1';
        $result = preg_replace($pattern, $replacement, $string);

        $pattern = '/\'\$([\d\w]+)\'/';
        $replacement = '\$$1';
        $result = preg_replace($pattern, $replacement, $result);

        return $result;
    }


    public function render($args = '')
    {
        $this->load_file_content();
        // $pattern = '/view(\s*)([\d\w]+)\(\$args\) \{(.+?)\}/s';
        $pattern = '/view(\s*)([\d\w]+)\(\$args\) \{([^{}]+|\{[^{}]*\})*\}/s';
        preg_match_all($pattern, $this->file_content, $matches, PREG_SET_ORDER);
        // foreach ($matches as $
        foreach ($matches as $func) {
            $parsedJsx = $this->extractJsx($func[0]);
            $jsxDOM = $parsedJsx[2];
            $html_block = $this->jsxRenderer($jsxDOM);

            // replace function title
            $func_name = $func[2];
            $this->file_content = str_replace('view ' . $func_name, 'function ' . $func_name, $this->file_content);
            // replace function content
            $this->file_content = str_replace($jsxDOM, $html_block, $this->file_content);

            // replace comma
            $re = '/\,\)\,\)/m';
            $this->file_content = preg_replace($re, ",));", $this->file_content);
            $this->file_content = $this->replaceCurlyBraces($this->file_content);
        }
        // return $html_block;
        eval($this->file_content);
    }

    // echo $this->file_content;

    public function load_file_content()
    {
        return $this->file_content = file_get_contents($this->view . '.phx');
    }
}
function view($v, $args)
{
    $comp = new Phpx($v);
    $comp->render();
    return $v($args);
}

function void($func)
{
    return $func();
}
