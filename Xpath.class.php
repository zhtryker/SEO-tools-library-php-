<?php

/**
 * Class made for getting important SEO markup in a page
 * image tags
 * image tag alt attributes
 * image tag src value
 * title tag texts
 * canonical value
 * meta description
 * TwitterCards title value
 * TwitterCards Description value
 * OpenGraph title value
 * OpenGraph Description value
 * CSS files
 * JS files
 * anchor tags
 * anchor tag rel attribute value
 * 
 *
 * @author mark.dilla
 * 
 * Use: $param = new Xpath(URL);
 */
class Xpath {

    private $URL;
    private $home;
    private $DOM;
    
    public function __construct($URL, $proxy = NULL) {
        $this->URL = $URL;
        $parsed = parse_url($URL);
        
        $this->home = $parsed["scheme"] . "://" . $parsed["host"];
        $this->DOM = $this->domObject($proxy);
    }

    public function curlPage($proxy = NULL) {
        $curl = curl_init($this->URL);

        if ($proxy != null) {
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
        }
        //custom user agent and header setting in curl
//        $agent= 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0.1';
//        $curlHeaders = array (
//                'Accept: text/html',
//                'Accept-Encoding: gzip, deflate',
//                'Accept-Language: en-US,en;q=0.5',
//                'User-Agent: Mozilla/5.0 (Windows NT 6.3; WOW64; rv:35.0) Gecko/20100101 Firefox/35.0.1',
//                'Connection: Keep-Alive',
//                'Pragma: no-cache',
//                'Referer: http://example.com/',
//                'Host: blog.corp.ringcentral.com', //Host domain
//                'Cache-Control: no-cache'
//        );        
        //curl_setopt($curl, CURLOPT_HTTPHEADER, $curlHeaders);
        
        curl_setopt($curl, CURLOPT_HEADER, TRUE);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        
        //curl_setopt($curl, CURLOPT_ENCODING , "gzip");
        
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        
        //curl_setopt($curl, CURLOPT_USERAGENT, $agent);        
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($curl, CURLOPT_TCP_KEEPALIVE, 1);
//        curl_setopt($curl, CURLOPT_TCP_KEEPIDLE, 2);
//        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT ,550000000);
//        curl_setopt($curl, CURLOPT_TIMEOUT, 5500000000);
        
        $page = curl_exec($curl);
        if (curl_errno($curl)) { // check for execution errors
            echo 'Curl error: ' . curl_error($curl);
            exit;
        }
//        echo "<pre/>";
//        print_r(curl_getinfo($curl));
        curl_close($curl);
        return $page;
    }
    
    public function getImageSrc(){
        $list = array();
        $tags = $this->getMarkupTag("img");
        foreach ($tags as $tag) {
            if (substr($tag->getAttribute('src'), 0, 4) === 'http') {
                $list[] = $tag->getAttribute('src');
            } elseif (substr($tag->getAttribute('src'), 0, 2) === '//') {
                $list[] = $tag->getAttribute('src');
            } else {
                $list[] = $this->home . $tag->getAttribute('src');
            }
        }
        return $list;
    }
    
    public function getImageAlt(){
        $altList = array();
        $tags = $this->getMarkupTag("img");
        foreach ($tags as $tag){
            $tag->getAttribute('alt') == "" ? $altList[] = 'none' : $altList[] = $tag->getAttribute('alt');
        }
        return $altList;
    }
    
    public function getVideoId(){
        $videoIDList = array();
        $tags = $this->getMarkupTag("a");
        foreach ($tags as $tag){
            $tag->getAttribute('videolistid') == "" ? $videoIDList[] = 'none' : $videoIDList[] = $tag->getAttribute('videolistid');
        }
        return $videoIDList;
    }
    
    //return the list of href attribute values in anchor tags
    public function getAnchorHref(){
        $hrefList = array();
        $tags = $this->getMarkupTag("a");
        foreach ($tags as $tag){
            $tag->getAttribute('href') == "" ? $hrefList[] = 'none' : $hrefList[] = $tag->getAttribute('href');
        }
        return $hrefList;
    }
    
    function DOMinnerHTML(DOMNode $element) {
        $innerHTML = "";
        $children = $element->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }
        return $innerHTML;
    }
    
    //return the list of anchor tags
    public function getAnchor(){
        $anchorList = array();
        $tags = $this->getMarkupTag("a");
        foreach ($tags as $tag){
            $anchorList[] = htmlentities(str_replace("Ã‚", "", $this->DOMinnerHTML($tag)));
        }
        return $anchorList;
    }
    
    // return the list of external Javascript file in the page
    public function getJSFile(){
        $jsList = array();
        $tags = $this->getMarkupTag("script");
        foreach ($tags as $tag){
            if($tag->getAttribute('src') !== ""){
                if (substr($tag->getAttribute('src'), 0, 4) === 'http') {
                    $jsList[] = $tag->getAttribute('src');
                } elseif (substr($tag->getAttribute('src'), 0, 2) === '//') {
                    $jsList[] = $tag->getAttribute('src');
                } else {
                    $jsList[] = $this->home . $tag->getAttribute('src');
                }
            }
        }
        return $jsList;
    }

    //return the list of external CSS in the page
    public function getCSSfile(){
        $cssList = array();
        $tags = $this->getMarkupTag("link");
        foreach ($tags as $tag) {
            if (strtolower($tag->getAttribute('rel')) == "stylesheet") {
                if (substr($tag->getAttribute('href'), 0, 4) === 'http') {
                    $cssList[] = $tag->getAttribute('href');
                } elseif (substr($tag->getAttribute('href'), 0, 2) === '//') {
                    $cssList[] = $tag->getAttribute('href');
                } else {
                    $cssList[] = $this->home . $tag->getAttribute('href');
                }
            }
        }
        return $cssList;
    }
    
    public function getHTMLTitle(){
        $title = $this->getMarkupTag('title');
        foreach ($title as $t){
            return $t->nodeValue;
        }
    }
    
    public function getMetaDesc() {
        $ta = $this->xpathQuery('/html/head/meta[@name="description"]/@content');
        foreach ($ta as $node) {
            return $node->value;
        }
    }
    
    public function getOGDesc() {
        $ta = $this->xpathQuery('/html/head/meta[@property="og:description"]/@content');
        foreach ($ta as $node) {
            return $node->value;
        }
    }
    
    public function getOGTitle() {
        $ta = $this->xpathQuery('/html/head/meta[@property="og:title"]/@content');
        foreach ($ta as $node) {
            return $node->value;
        }
    }
    
    public function getTwitterDesc() {
        $ta = $this->xpathQuery('/html/head/meta[@name="twitter:description"]/@content');
        foreach ($ta as $node) {
            return $node->value;
        }
    }
    
    public function getTwitterTitle() {
        $ta = $this->xpathQuery('/html/head/meta[@name="twitter:title"]/@content');
        foreach ($ta as $node) {
            return $node->value;
        }
    }
    
    public function getCanonical() {
        $ta = $this->xpathQuery('/html/head/link[@rel="canonical"]/@href');
        foreach ($ta as $node) {
            return $node->value;
        }
    }

    public function getFollow(){
        $relList = array();
        $tags = $this->getMarkupTag("a");
        foreach ($tags as $tag){
            $tag->getAttribute('rel') == "" ? $relList[] = 'follow' : $relList[] = $tag->getAttribute('rel');
        }
        return $relList;
    }

    private function getMarkupTag($tag){
        $DOM = $this->DOM;
        return $DOM->getElementsByTagName($tag);
    }
    
    public function xpathQuery($path) {
        $xpath = new DOMXPath($this->DOM);
	$query = $path;
	return $entries = $xpath->query($query);
    }
    
    private function domObject($proxy = NULL){
        $page = $this->curlPage($proxy);
        $DOM = new DOMDocument;
        libxml_use_internal_errors(true);
        if (!$DOM->loadHTML($page)) {
            $errors = "";
            foreach (libxml_get_errors() as $error) {
                $errors.=$error->message . "<br/>";
            }
            libxml_clear_errors();
            print "libxml errors:<br>$errors";
            return;
        }
        return $DOM;
    }
    
    //TODO: method to validate tags
}
