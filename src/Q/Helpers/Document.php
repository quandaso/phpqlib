<?php
/**
 * @author quantm.tb@gmail.com
 * @date: 2/3/2017 2:49 PM
 */

namespace Q\Helpers;

use Symfony\Component\DomCrawler\Crawler;

class Document extends Crawler
{

    public static function load($html)
    {
        return new Document($html);
    }

    public static function loadUrl($link, $cache = true)
    {

        $key = md5($link);

        if (!$cache) {
            $content = curl_get($link);
        } else {
            $content = redis_get("url.$key", function() use($link) {
                return curl_get($link);
            }, 1800);
        }

        $doc = new Document();
        $doc->addHtmlContent($content, 'UTF-8');
        return $doc;
    }

}