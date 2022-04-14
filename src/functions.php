<?php

/**
 * Dumps var
 * @param $var
 */
/**
 * Dumps var
 * @param $var
 */
function pr() {
    $args = func_get_args();
    $inCommandline = (php_sapi_name() === 'cli');
    if (!$inCommandline && extension_loaded('xdebug')) {
        if (count($args) === 1) {
            var_dump($args[0]);
        } else {
            foreach ( $args as $arg) {
                var_dump($arg);
            }
        }
        return;
    }



    if (!$inCommandline) {
        echo '<pre>' . "\n";
    }

    $argsCount = func_num_args();

    foreach ($args as $i => $var) {

        if ($argsCount > 1) {
            echo ($i + 1) . ".";
        }

        if (is_array($var)) {
            if (count($var) === 0) {
                print_r($var);
            } else {
                foreach ($var as $j => $v) {
                    if ($v instanceof \App\Core\Models\SimpleModel) {
                        $var[$j] = $v->toArray();
                    }
                }

                echo '(array)' . json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

        } else if (is_object($var)) {
            echo '(' . get_class($var).')' . json_encode($var, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        } else {
            var_dump($var);
        }

        echo "\n";
    }

    if (!$inCommandline) {
        echo '</pre>';
    }

}

function dd() {
    call_user_func_array('pr', func_get_args());
    die;
}

function __n($input) {
    return number_format($input);
}

function h($s) {
    return htmlspecialchars($s);
}

function hh($s) {
    echo htmlspecialchars($s);
}

function nn($num, $return_value = false) {
    if ((int) $num == $num) {
        $n = number_format($num);
    } else {
        $n =  number_format($num, 2);
    }

    if ($return_value) {
        return $n;
    }

    echo $n;
}


/**
 * Gets random string with given length
 * @param int $length
 * @param $characters
 * @return string
 */
function str_random ($length = 10, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
{

    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}

function str_format($str, $replace = []) {
   return preg_replace_callback('/{\w+}/', function($match) use($replace) {
        $k = trim($match[0], '{}');
        return isset($replace[$k]) ? $replace[$k] : $match[0];
    }, $str);
}

/**
 * @param $filename
 * @param array $replace
 * @return mixed
 */
function file_contents_format($filename, $replace = []) {
    return str_format(file_get_contents($filename), $replace);
}



function get_base_uri()
{
    $baseDirExp = preg_quote(str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']), '/');
    return preg_replace("/^$baseDirExp/", '', $_SERVER['REQUEST_URI']);
}



/**
 * Get a queryable instance
 * @param $config string DB Config
 * @param $newInstance bool Makes new queryable instance
 * @return \Q\Helpers\Queryable
 */
function db($newInstance = false, $config = 'default')
{
    static $queryable;
    static $pdoMap;
    if (!$newInstance) {
        if (isset($queryable)) {
            return $queryable;
        }
    }


    if (!isset($pdoMap[$config])) {
        $dbConfig = config('mysql')[$config];
        extract($dbConfig);
        $pdo = new \PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdoMap[$config] = $pdo;
    }

    $queryable =  new \Q\Helpers\Queryable($pdoMap[$config], 'stdClass');
    return $queryable;
}


function get_client_ip()
{

    if (!empty ($_SERVER['HTTP_CLIENT_IP']) ) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } else if (!empty ($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (!empty ($_SERVER['HTTP_X_FORWARDED'])) {
        return $_SERVER['HTTP_X_FORWARDED'];
    } else if ( !empty ($_SERVER['HTTP_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_FORWARDED_FOR'];
    } else if ( !empty ($_SERVER['HTTP_FORWARDED']) ) {
        return $_SERVER['HTTP_FORWARDED'];
    } else if( !empty ($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }

    return 'UNKNOWN';
}


function current_uri($queryString = true) {
    return $queryString ? $_SERVER['REQUEST_URI'] : explode('?', $_SERVER['REQUEST_URI'])[0];
}

function curl_get($url) {


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_ENCODING , "gzip");
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36");
// receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec ($ch);

    if ($server_output === false) {
        trigger_error(curl_error($ch));
    }


    curl_close ($ch);
    /// file_put_contents($filename, $server_output);
    return $server_output;
}


function curl_post($url,  $data = array())
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_POST, 1);
    # curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

// in real life you should use something like:
    curl_setopt($ch, CURLOPT_POSTFIELDS,
        http_build_query($data));

// receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec ($ch);

    curl_close ($ch);
    return $server_output;
}

/**
 * @return string
 */
function csrf_token()
{
    // Create a new CSRF token.
    if (! isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = base64_encode(openssl_random_pseudo_bytes(32));
    }

    return  $_SESSION['csrf_token'];
}


/**
 *
 */
function csrf_field()
{
    echo '<input type="hidden" value="' . csrf_token() . '" name="csrf_token">';
}

/**
 * Define env functions (for partner and API)
 * @param $key
 * @param $default
 * @return mixed
 * @throws \Exception
 */
function env($key = null, $default = null)
{
    static $result;

    if (!isset ($result)) {
        $result = parse_ini_file(ROOT_DIR . '/env.ini');
    }

    if ($key === null) {
        return $result;
    }


    return isset ($result[$key]) ? $result[$key] : $default;
}


function make_uid($len = 15, $table = null) {
    $uid = str_random($len);
    if ($table === null) {
        return $uid;
    }

    $db = db();

    while($db->query("SELECT COUNT(*) `count` FROM `$table` WHERE `uid`=?", [$uid])->first()->count > 0) {
        $uid = str_random($len);
    }

    return $uid;
}


function redis_get_instance()
{

    global $_REDIS;
    if (isset($_REDIS)) {
        return $_REDIS;
    }

    if (!class_exists('\Redis')) {
        return null;
    }

    $config = config('redis')['default'];
    try {
        $_REDIS = new \Redis();
        $_REDIS->connect($config['host'], $config['port']);
        $_REDIS->select(isset($config['db']) ? $config['db']: 0);
        return $_REDIS;
    } catch (\Exception $e) {

    }
    return null;
}

function redis_del($key)
{
    $redis = redis_get_instance();
    if ($redis) {
        $redis->del(REDIS_KEY_PREFIX . $key);
    }

}

/**
 * @param $key
 * @param Closure|null $fallback
 * @param null $ttl
 * @return bool|mixed|string
 */
function redis_get($key, \Closure $fallback = null, $ttl = null)
{

    $redis = redis_get_instance();

    if ($redis === null) {
        return $fallback();
    }

    $key = REDIS_KEY_PREFIX . $key;
    $result = $redis->get($key);

    if ($result === false) {

        if ($fallback) {
            $result = $fallback();
            $ttl = (int) $ttl;

            if ($ttl <= 0) {
                $ttl = REDIS_DEFAULT_EXPIRE;
            }

            if (!empty ($result)) {
                $redis->setex($key, $ttl, serialize($result));
            }

            return $result;
        }

        return false;
    }

    $result = unserialize($result);
    return $result;
}

/**
 * @param $key
 * @param $value
 * @param null $ttl
 */
function redis_set($key, $value, $ttl = null)
{
    $redis = redis_get_instance();
    if ($redis) {
        $key = REDIS_KEY_PREFIX . $key;

        $ttl = (int) $ttl;

        if ($ttl <= 0) {
            $ttl = REDIS_DEFAULT_EXPIRE;
        }

        $redis->setex($key, $ttl, serialize($value));
    }
}



function view($path, $vars = []) {
    return new \Q\Response\ViewResponse($path, $vars);
}

function redirect($uri) {
    return new \Q\Response\RedirectResponse($uri);
}

function json($data) {
    return new \Q\Response\JsonResponse($data);
}


/**
 * Concat all scripts to one file
 * @param $path
 * @param $output
 * @param $compileAsset
 * @return string
 */
function bundle_scripts($path, $output, $compileAsset = true) {
    #$compileAsset = true;//config('app.compile_asset');


    if (!$compileAsset) {
        $configs = json_decode(file_get_contents(resource_path($path)), true);
        $assets = $configs['files'];
        $html  = '';
        foreach ($assets as $asset) {
            $time = filemtime (public_path($asset));
            $html .= sprintf('<script src="%s"></script>', asset($asset). "?t=$time") . PHP_EOL;
        }

        return $html;
    } else {

        $outputPath = public_path($output);
        if (!file_exists($outputPath)) {
            $configs = json_decode(file_get_contents(resource_path($path)), true);
            $assets = $configs['files'];
            $scriptCode  = '';
            foreach ($assets as $asset) {
                $scriptCode .= file_get_contents(public_path($asset)) . ";\n";
            }

            $compileScript = compile_scripts($scriptCode);

            file_put_contents($outputPath, $compileScript === false ? $scriptCode : $compileScript);
        }

        $output = asset($output);
        return "<script src=\"$output\"></script>";

    }

}

/**
 * Concat all styles to one file
 * @param $path
 * @param $output
 * @param $compileAsset
 * @return string
 */
function bundle_styles($path, $output, $compileAsset = true) {

    if (!$compileAsset) {
        $configs = json_decode(file_get_contents(resource_path($path)), true);
        $assets = $configs['files'];
        $html  = '';
        foreach ($assets as $asset) {
            $time = filemtime (public_path($asset));
            $html .= sprintf('<link href="%s" rel="stylesheet" type="text/css">', asset($asset) . "?t=$time") . PHP_EOL;
        }
        return $html;
    } else {
        $outputPath = public_path($output);
        if (!file_exists($outputPath)) {
            $configs = json_decode(file_get_contents(resource_path($path)), true);
            $assets = $configs['files'];
            $assetCode  = '';
            foreach ($assets as $asset) {
                $assetCode .= file_get_contents(public_path($asset)) . "\n";
            }

            file_put_contents($outputPath, $assetCode);
        }

        return sprintf('<link href="%s" rel="stylesheet" type="text/css">', asset($output));

    }

}


/**
 * Compile scripts using closure service
 * @param $js_code
 * @return bool
 */
function compile_scripts($js_code) {
    $url = 'http://closure-compiler.appspot.com/compile';
    $res = curl_post($url, [
        'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
        'output_format' => 'json',
        'output_info' => 'compiled_code',
        'js_code' => $js_code
    ]);

    if ($res === false) {
        return false;
    }

    $resObj = json_decode($res, true);

    if (isset($resObj['serverErrors'])) {
        trigger_error($res);
        return false;
    } else if (!isset($resObj['compiledCode'])) {
        trigger_error(array_keys($resObj));
        return false;
    }


    return $resObj['compiledCode'];
}


function log_response($response, \Exception $e = null, $httpCode = 200)
{

    if (is_array($response)) {
        $entry['response'] = json_encode($response, JSON_PRETTY_PRINT);
    } else {
        $entry['response'] = (string) $response;
    }

    $entry['request_uri'] = $_SERVER['REQUEST_URI'];
    $entry['http_method'] = $_SERVER['REQUEST_METHOD'];
    $entry['request_headers'] = json_encode(getallheaders(), JSON_PRETTY_PRINT);
    $entry['event_type'] = 'Info';
    $entry['http_code'] = $httpCode;
    $entry['ip'] = get_client_ip();

    if (  $entry['http_method']== 'GET') {
        $entry['parameters'] = json_encode($_GET, JSON_PRETTY_PRINT);



    } else if ($entry['http_method']== 'POST') {
        $data = $_POST;

        $entry['parameters'] = json_encode($data, JSON_PRETTY_PRINT);
    } else if ($entry['http_method'] == 'PUT') {
        $_put = [];
        parse_str(file_get_contents("php://input"), $_put);
        $entry['parameters'] = json_encode($_put,JSON_PRETTY_PRINT);
    }


    $entry['user_agent'] = @$_SERVER['HTTP_USER_AGENT'];
    $entry['user_agent'] = @$_SERVER['HTTP_USER_AGENT'];
    $queries = \Q\Helpers\Queryable::getQueryHistory();

    $queries = array_map('utf8_encode', $queries);
    $entry['query'] = json_encode($queries, 128);


    if ($e !== null) {
        $entry['event_type'] = 'Error';
        $entry['exception'] = get_class($e);
        $entry['message'] = $e->getMessage();
        $entry['stack_trace'] = $e->getTraceAsString();
    }

    try {
        db()->table('debug_logs')->insert($entry);
    } catch (\Exception $e) {
        ob_get_clean();
        $msg = $e->getMessage();
        if (strpos($msg, 'table or view not found') !== false) {
            $debugLogSql = file_get_contents(ROOT_DIR . '/data/debug_logs.sql');
            db()->query($debugLogSql);
            die;
        }

        echo $msg . "<br>";
        echo $e->getTraceAsString();
    }


}

function route($name, $params = array()) {
    global $app;
    return url($app->decodeRoute($name, $params));
}


function __($str) {
    static $messages;
    if (!isset($messages)) {
        $locale = config('app')['locale'];
        $messages = require_once ROOT . '/locales/' .$locale . '.php';
    }

    return isset($messages[$str]) ? $messages[$str] : $str;
}

function flash_msg_error($key, $message) {

    $_SESSION['FLASH_MSG'][$key] = [
        'class' => 'alert alert-danger FLASH_MSG',
        'message' => $message
    ];
}


function flash_msg_success($key, $message) {
    $_SESSION['FLASH_MSG'][$key] = [
        'class' => 'alert alert-info FLASH_MSG',
        'message' => $message
    ];
}

function flash_msg_get($key) {
    $msg = isset($_SESSION['FLASH_MSG'][$key]) ? $_SESSION['FLASH_MSG'][$key] : null;
    if ($msg) {
        unset($_SESSION['FLASH_MSG'][$key]);
        echo "<div class='{$msg['class']}'>{$msg['message']}</div>";

    }
}

function make_session_id($len = 15, $table = null) {
    $uid = str_random($len);
    if ($table === null) {
        return $uid;
    }

    $db = db();

    while($db->query("SELECT COUNT(*) `count` FROM `$table` WHERE `sessionId`=?", [$uid])->first()->count > 0) {
        $uid = str_random($len);
    }

    return $uid;
}

function array2excel($filename, $title, array $data, array $headers) {


        if (empty($data)) {
            return false;
        }

        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->setActiveSheetIndex(0);

        $activeSheet =   $objPHPExcel->getActiveSheet();
        $activeSheet->SetCellValue("A1", $title );
        $activeSheet->getStyle("A1:K1")->getFont()->setBold(true)->setSize(16);
        $columnIndexes =  [];

        $idx = 0;
        foreach ($headers as $key => $text) {
            $colIdx = \PHPExcel_Cell::stringFromColumnIndex($idx);
            $columnIndexes[] = $colIdx;
            $activeSheet->SetCellValue($colIdx . '2' ,$text);
            $idx++;
        }



        $colLen = count($columnIndexes);
        $activeSheet->getStyle($columnIndexes[0] . "2:" . $columnIndexes[$colLen -1] . "2" )->getFont()->setBold(true);


        foreach ($data as $eIndex => $entry) {
            $rowCount = $eIndex + 3;
            $idx = 0;
            foreach ($headers as $key => $text) {
                $activeSheet->setCellValue($columnIndexes[$idx] . $rowCount , @$entry->$key);
               // $activeSheet->setCellValueExplicit($columnIndexes[$idx] . $rowCount , null, PHPExcel_Cell_DataType::TYPE_STRING);
                $idx++;
            }
        }

        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        header('Content-type: application/vnd.ms-excel');
        $filename = $filename . '-' . time() . ".xlsx";
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $objWriter->save('php://output');
        die;
    }




function xml2array($contents, $get_attributes = 1, $priority = 'tag')
{
    if (!$contents) return array();
    if (!function_exists('xml_parser_create')) {
        // print "'xml_parser_create()' function not found!";
        return array();
    }
    // Get the XML parser of PHP - PHP must have this module for the parser to work
    $parser = xml_parser_create('');
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); // http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($contents) , $xml_values);
    xml_parser_free($parser);
    if (!$xml_values) return; //Hmm...
    // Initializations
    $xml_array = array();
    $parents = array();
    $opened_tags = array();
    $arr = array();
    $current = & $xml_array; //Refference
    // Go through the tags.
    $repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
    foreach($xml_values as $data) {
        unset($attributes, $value); //Remove existing values, or there will be trouble
        // This command will extract these variables into the foreach scope
        // tag(string), type(string), level(int), attributes(array).
        extract($data); //We could use the array by itself, but this cooler.
        $result = array();
        $attributes_data = array();
        if (isset($value)) {
            if ($priority == 'tag') $result = $value;
            else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
        }
        // Set the attributes too.
        if (isset($attributes) and $get_attributes) {
            foreach($attributes as $attr => $val) {
                if ( $attr == 'ResStatus' ) {
                    $current[$attr][] = $val;
                }
                if ($priority == 'tag') $attributes_data[$attr] = $val;
                else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
            }
        }
        // See tag status and do the needed.
        //echo"<br/> Type:".$type;
        if ($type == "open") { //The starting of the tag '<tag>'
            $parent[$level - 1] = & $current;
            if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                $current[$tag] = $result;
                if ($attributes_data) $current[$tag . '_attr'] = $attributes_data;
                //print_r($current[$tag . '_attr']);
                $repeated_tag_index[$tag . '_' . $level] = 1;
                $current = & $current[$tag];
            }
            else { //There was another element with the same tag name
                if (isset($current[$tag][0])) { //If there is a 0th element it is already an array
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else { //This section will make the value an array if multiple tags with the same name appear together
                    $current[$tag] = array(
                        $current[$tag],
                        $result
                    ); //This will combine the existing item and the new item together to make an array
                    $repeated_tag_index[$tag . '_' . $level] = 2;
                    if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                        $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                        unset($current[$tag . '_attr']);
                    }
                }
                $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                $current = & $current[$tag][$last_item_index];
            }
        }
        elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'
            // See if the key is already taken.
            if (!isset($current[$tag])) { //New Key
                $current[$tag] = $result;
                $repeated_tag_index[$tag . '_' . $level] = 1;
                if ($priority == 'tag' and $attributes_data) $current[$tag . '_attr'] = $attributes_data;
            }
            else { //If taken, put all things inside a list(array)
                if (isset($current[$tag][0]) and is_array($current[$tag])) { //If it is already an array...
                    // ...push the new element into that array.
                    $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                    if ($priority == 'tag' and $get_attributes and $attributes_data) {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level]++;
                }
                else { //If it is not an array...
                    $current[$tag] = array(
                        $current[$tag],
                        $result
                    ); //...Make it an array using using the existing value and the new value
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $get_attributes) {
                        if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                        if ($attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                    }
                    $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                }
            }
        }
        elseif ($type == 'close') { //End of tag '</tag>'
            $current = & $parent[$level - 1];
        }
    }
    return ($xml_array);
}

function waybill_map($w) {
    $allStatus = WB_STATUSES;
    $w->declareCharge = number_format($w->declareCharge);
    $w->codCharge = number_format($w->codCharge);

    $w->valueShipFee = (int) $w->shipFee;
    $w->valueCodAmount = (int) $w->codAmount;
    $w->valueAmount = (int) $w->amount;

    $w->shipFee = number_format($w->shipFee);
    $w->codAmount = number_format($w->codAmount);
    $w->amount = number_format($w->amount);
    $w->deliveryFee = number_format($w->deliveryFee);
    $w->insuranceFee = number_format($w->insuranceFee);



    $w->receiver = json_decode($w->receiver);
    $w->pickerId = (int) $w->pickerId;
    $w->shipperId = (int) $w->shipperId;
    $w->shopNotes = json_decode($w->shopNotes);
    if (! $w->shopNotes) {
        $w->shopNotes = [];
    }

    $w->package = json_decode($w->package);
    if ( $w->package) {
        $w->package->value = number_format(  $w->package->value);
    } else {
        $w->package = (object)['value' => 0];
    }

    $w->createdAt = date('H:i d/m/Y ', strtotime(  $w->createdAt ));
    $w->updatedAt = date('H:i d/m/Y ', strtotime(  $w->updatedAt ));
    $w->statusText = isset($allStatus[$w->status]) ? $allStatus[$w->status] : 'Không xác định';

    return $w;
}
if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
function logfile_write($string, $filename = 'error') {
    static $handle;
    $filename = $filename . '-' . date('Y-m-d') . ".log";
    if (!isset($handle)) {
        if (!is_dir(ROOT . '/tmp')) {
            mkdir(ROOT . '/tmp', 0777);
        }
        $handle = fopen(ROOT . '/tmp/' . $filename, 'a+');
    }
    $time = "[" . date('Y-m-d H:i') . "]\n";
    fwrite($handle, $time . $string . PHP_EOL);
}

function asset_src($src) {
    return url($src . '?v=' . filemtime(ROOT . '/public/'. $src));
}

function excel_set_sheet(PHPExcel_Worksheet $activeSheet, $title, array $data, array $headers) {
    $activeSheet->SetCellValue("A1", $title );
    $activeSheet->getStyle("A1:K1")->getFont()->setBold(true)->setSize(16);
    $columnIndexes =  [];

    $idx = 0;
    foreach ($headers as $key => $text) {
        $colIdx = \PHPExcel_Cell::stringFromColumnIndex($idx);
        $columnIndexes[] = $colIdx;
        $activeSheet->SetCellValue($colIdx . '2' ,$text);
        $idx++;
    }



    $colLen = count($columnIndexes);
    $style = $activeSheet->getStyle($columnIndexes[0] . "2:" . $columnIndexes[$colLen -1] . "2" );
    $style->getFont()->setBold(true);
    $style->getAlignment()->setWrapText(true);



    foreach ($data as $eIndex => $entry) {
        $rowCount = $eIndex + 3;
        $idx = 0;
        foreach ($headers as $key => $text) {
            $value = is_array($entry) ? @$entry[$key] : @$entry->$key;
            $activeSheet->SetCellValue($columnIndexes[$idx] . $rowCount , $value);
            $idx++;
        }
    }
}

function shipal_date($str = null) {
    if (!$str) {
        echo date('H:i d/m/Y');
        return;
    }
    echo date('H:i d/m/Y', strtotime($str));
}

function excel2array($file) {
    if (!is_file($file)) {
        throw new \Exception("Invalid file");
    }

    $inputFileType = \PHPExcel_IOFactory::identify($file);
    $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
    //  $objReader->setReadFilter(new \App\Models\MyReaderFilter());
    $objPHPExcel = $objReader->load($file);

    $sheetData = $objPHPExcel->getActiveSheet()->toArray(null, true, true, true);

    $results = [];
    if (!empty($sheetData)) {


        for ($i = 1; $i <= count($sheetData); $i++) {
            $obj = [];
            foreach ($sheetData[$i] as $key => $value) {

                $obj[] = $sheetData[$i][$key];
            }

            $results[] = $obj;
        }
    }

    return $results;
}

function roundWeightValue($x, $roundValue = 0.2)
{
    $intPart = floor($x);
    $floatPart = $x - $intPart;

    if ($floatPart > 0 && $floatPart < $roundValue) {
        $floatPart = $roundValue;
    }

    return $intPart + $floatPart;
}

function t(string $input) {
    echo htmlentities(__($input));
}

function jObject($var) {
    echo addslashes(json_encode($var));
}

function parse_number($string) {
    if (!$string) {
        return 0;
    }

    $string = str_replace(',', '', $string);
    return 0 + $string;
}

function attachModelBelongsTo($entries, $table, $foreignKey, $ownerKey = 'id') {
    $attachKeyMap = [];

    foreach ($entries as $entry) {
        $attachKeyMap[$entry->$foreignKey] = true;
    }

    $attachKeys = array_keys($attachKeyMap);

    if (is_string($table)) {
        $tableName = $table;
        $alias = $table;
    } else if (is_array($table)) {
        $tableName  =array_key_first($table);
        $alias = $table[$tableName];
    }

    $foreignEntriesMap = [];

    if (!empty($attachKeys)) {
        $foreignEntries = db()->table($tableName)
            ->whereIn($ownerKey, $attachKeys)
            ->get();

        foreach ($foreignEntries as $entry) {
            $foreignEntriesMap[$entry->$ownerKey] = $entry;
        }
    }

    foreach ($entries as $entry) {
        $entry->$alias = $foreignEntriesMap[$entry->$foreignKey] ?? null;
    }
}

function feeCombine(array $fees) {
    $total = 0;
    foreach ($fees as $fee) {
        $fee = intval($fee);
        if ($fee != -1) {
            $total += $fee;
        }
    }
    return $total;
}


function feeFormat($value) {
    if (!$value) {
        return 0;
    }

    if ($value == -1) {
        return 'Thỏa thuận';
    }

    if (is_string($value)) {
        return $value;
    }

    if (is_int($value)) {
        return number_format($value);
    }

    return $value;
}

/**
 * @param \App\Models\Waybill $waybill
 * @return int|string
 */
function getShopFee($waybill) {
    if ($waybill->shipFeeType == SHIP_FEE_TYPE_SHOP_PAY) {
        return number_format(feeCombine([
            parse_number($waybill->shipFee),
            parse_number($waybill->insuranceFee),
            parse_number($waybill->deliveryFee),
        ]));
    }

    return 0;
}
