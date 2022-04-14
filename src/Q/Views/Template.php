<?php
/**
 * @author H110
 * @date: 6/23/2017 15:55
 */

namespace Q\Views;


class Template
{
    public function __construct()
    {
    }

    public function parse($content) {

        $content = preg_replace_callback('/{{.+?}}/', function ($m) {
            pr($m);
            $key = trim($m[0], '{}');

            if ($key[0] === '=') {
                $key = trim($key, '=');
                return "<?php echo htmlentities($key); ?>";
            }

            return "<?php " . $key . " ?>";

        }, $content);


        return $content;

    }
}