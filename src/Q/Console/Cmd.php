<?php
/**
 * @author H110
 * @date: 6/23/2017 09:25
 */

namespace Q\Console;


use Q\Helpers\ConsoleColor;

class Cmd
{
    protected $verbose = true;
    private static $isInit = false;
    protected static $args;

    /**
     * Dispatch commands
     */
    public function dispatch() {
        if (self::$isInit) {
            throw new \Exception("Could not dispatch twice");
        }

        self::$isInit = true;
        global $argv;
        self::$args = $this->parseArgv($argv);
        self::requiredAllClasses(ROOT . '/library/Q/Console/Commands');
        self::requiredAllClasses(ROOT . '/app/Console/Commands');
        $classes = self::getSubclassesOf('Q\Console\Cmd');
        $classesMap = [];
        foreach ($classes as $_class) {
            $tmp = explode("\\", $_class);
            $className = strtolower(end($tmp));
            if (isset($classesMap[$className])) {
                $this->error("$_class is conflict with {$classesMap[$className]}");
                die;
            }

            $classesMap[$className] = $_class;
        }

        $countArgv = count($argv);

        if ($countArgv === 1) {
            $this->info("Avail commands:");
            foreach ($classes as $index => $class) {
                $j = $index + 1;
                $this->info("[$j]. $class");
            }
        } else if ($countArgv >= 2) {
            $class = $argv[1];
            $method = 'main';

            if (isset($classesMap[$class])) {
                $instance = new $classesMap[$class]();
            } else {
                if (preg_match('/^\d+$/', $class) && isset($classes[$class - 1])) {
                    $instance = new $classes[$class - 1]();
                } else {
                    $this->error('Invalid class or class index ' . $class);
                    die;
                }
            }

            $params = [];
            if ($countArgv >= 3) {
                $method = $argv[2];

                for ($i = 3; $i < $countArgv; $i++) {
                    $params[] = $argv[$i];
                }
            }

            if (!method_exists($instance, $method)) {
                $this->error(get_class($instance) . '::' . $method . ' does not exist');
                die;
            }

            if ($instance->verbose) {
                echo "Execute " . get_class($instance) . '::' . $method . "\n";
            }

            try {
                call_user_func_array([$instance, $method], $params);
            } catch (\Exception $e) {
                echo $e->getMessage() . "\n";
                echo $e->getTraceAsString() . "\n";

            }


        }
    }

    /**
     * @param $dir
     * @return array
     */
    private static function requiredAllClasses($dir) {
        $files = scandir($dir);
        $classes = [];
        for($i = 2; $i < count($files); $i++) {
            $file = $dir . '/' . $files[$i];

            if (is_file($file) && preg_match('/.php$/i', $file)) {
                require_once $file;
            } else if(is_dir($file)) {
                self::requiredAllClasses($file);
            }
        }
        return $classes;
    }

    /**
     * @param $parent
     * @return array
     */
    private static function getSubclassesOf($parent) {
        $result = array();
        foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, $parent))
                $result[] = $class;
        }

        return $result;
    }

    /**
     * Ask for an input
     * @param null $msg
     * @return string
     */
    protected function ask($msg = null) {
        echo "  >" . $msg . ':';
        $handle = fopen ("php://stdin","r");
        $line = fgets($handle);
        return trim($line);
    }

    /**
     * Prints info message (in green color)
     * @param null $msg
     */
    protected function info($msg = null) {
        ConsoleColor::output($msg . "\n", 'green');
    }

    /**
     * Prints error message (in red color)
     * @param null $msg
     */
    protected function error($msg = null) {
        ConsoleColor::output($msg . "\n", 'red');
    }

    /**
     * Prints warn message (in yellow color)
     * @param null $msg
     */
    protected function warn($msg = null) {
        ConsoleColor::output($msg . "\n", 'yellow');
    }

    /**
     *
     */
    protected function parseArgv(array $args) {
        $options = [];
        foreach ($args as $arg) {
            if (substr($arg, 0, 2) === '--') {
                @list($k, $v) = explode('=', $arg);
                $k = ltrim($k, '-');
                if ($v === 'true') {
                    $v = true;
                } else if ($v === 'false') {
                    $v = false;
                }

                $options[$k] = $v;

            }
        }

        return $options;
    }

    /**
     * @param null $name
     * @param null $default
     * @return mixed
     */
    protected function getArgs($name = null, $default = null) {
        if ($name === null) {
            return self::$args;
        }

        return isset(self::$args[$name]) ? self::$args[$name] : $default;
    }
}






























