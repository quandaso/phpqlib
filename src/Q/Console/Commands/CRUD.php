<?php
/**
 * @author H110
 * @date: 6/23/2017 09:30
 */

namespace Q\Console\Commands;


use Q\Console\Cmd;
use Q\Helpers\Inflect;
use Q\Response\ViewResponse;


class CRUD extends Cmd
{
    /**
     * @param null $table
     */
    public function main($table = null) {
        $db = db();

        $routes = [];
        if ($table === '--all') {
            $tables = $db->getAllTables();
            foreach ($tables as $_table) {
                $routes[] = $this->make($_table);
            }
        } else {
            $routes[] = $this->make($table);
        }

        $time = date('Y-m-d H:i:s');
        $routeFileContent = "<?php\n/**
 * @author CRUD generator
 * @date: $time
 * @var \\Q\\Router\\RouteCollection \$route
 */\n\n";

        $routeFileContent .= implode("\n", $routes);

        $routeName = 'route_' . uniqid() ;
        $routeFileContent .= "\n // Add \$this->loadRoute('$routeName'); to app/Application.php";
        file_put_contents(ROOT . '/routes/' . $routeName . '.php', $routeFileContent);
    }

    public function ls() {
        $tables = db()->getAllTables();
        $this->info("All tables:");
        foreach ($tables as $i => $table) {
            $j = $i + 1;
            $this->info("[$j]. $table");
        }
    }

    /**
     * Makes table CRUD
     * @param $table
     */
    private function make($table)
    {
        $namespace = $this->getArgs('namespace', 'Admin');
        $templateDir = ROOT_DIR . '/library/Q/Templates/CRUD';
        $templateViewDir = ROOT_DIR . '/app/Templates/' . $namespace;

        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
        $controllerName = ucfirst($str);
        $modelName = Inflect::singularize($controllerName);
        $controller = $controllerName . 'Controller';
        $controllerDir = ROOT_DIR . "/app/Controllers/$namespace/";



        if (!is_dir($templateViewDir . '/' . $controllerName)) {
            mkdir($templateViewDir . '/' . $controllerName, 0777, true);
        }

        if (!is_dir($controllerDir)) {
            mkdir($controllerDir, 0777, true);
        }


        ViewResponse::setBaseViewPath($templateDir);
        $routePrefix = $this->getArgs('routePrefix', '/' . $table);
        $routePrefix = str_format($routePrefix, ['table' => $table]);
        $force = $this->getArgs('force', false);

        $controllerContent = file_contents_format($templateDir . '/controller.tpl',
            ['controller' => $controller, 'name' => $controllerName, 'modelName' => $modelName, 'table' => $table, 'routePrefix' => $routePrefix, 'namespace' => $namespace]);
        $modelContent = file_contents_format($templateDir . '/model.tpl', ['name' => $modelName, 'table' => $table, 'routePrefix' => $routePrefix, 'namespace' => $namespace]);


        $db = db();
        $fields = $db->table($table)->scheme(true);

        $indexContent = view('index', ['fields' => $fields, 'modelName' => $modelName, 'table' => $table, 'routePrefix' => $routePrefix])->renderViewAsString();
        $createContent = view('create', ['fields' => $fields, 'modelName' => $modelName, 'table' => $table, 'routePrefix' => $routePrefix])->renderViewAsString();
        $editContent = view('edit', ['fields' => $fields, 'modelName' => $modelName, 'table' => $table, 'routePrefix' => $routePrefix])->renderViewAsString();


        $this->makeFile(ROOT_DIR . "/app/Controllers/$namespace/" . $controller . '.php', $controllerContent, $force);
        $this->makeFile(ROOT_DIR . '/app/Models/' . $modelName . '.php', $modelContent, $force);
        $this->makeFile($templateViewDir . '/' . $controllerName . '/index.phtml', $indexContent, $force);
        $this->makeFile($templateViewDir . '/' . $controllerName . '/create.phtml', $createContent, $force);
        $this->makeFile($templateViewDir . '/' . $controllerName . '/edit.phtml', $editContent, $force);
        // Generate rate router

        return "\$route->all('$routePrefix/{action}', '$namespace\\$controller');";

    }

    /**
     * @param $filename
     * @param $content
     * @param bool $force
     */
    private function makeFile($filename, $content, $force = false) {
        $basePath = substr($filename, strlen(ROOT_DIR) , strlen($filename));
        $filename = str_replace('\\', '/', $filename);

        echo ("Making $basePath..");
        if (!$force) {
            if (file_exists($filename)) {
                $this->error("\n$basePath exists");
                return;
            }
        }


        $ok = file_put_contents($filename, $content);
        if ($ok) {
            echo "[OK]\n";
        }
    }

    /**
    {
    "Field": "facebookId",
    "Type": "varchar(100)",
    "Null": "YES",
    "Key": "UNI",
    "Default": null,
    "Extra": ""
    }
     * @param $field
     * @param $mode
     * @return string
     */
    public static function  renderInputField($field, $mode='create') {
        $type = $field['Type'];
        $name = $field['Field'];
        $isNotNull = $field['Null'] === 'NO';
        $required = $isNotNull ? 'required' : '';
        $uname = ucfirst($name);
        $placeholder = $name;
        $value = (string)$field['Default'];
        if ($mode === 'edit') {
            $value = "<?php hh(\$entry->$name); ?>";
        }
        if ($type === 'text' || $type === 'mediumtext' || $type === 'longtext') {

            return "<textarea required id=\"f$name\" name=\"$name\" class=\"form-control\" placeholder=\"$placeholder\">$value</textarea>";
        }

        preg_match('/^(\w+)\((\d+)\)/', $type, $m);
        $inputType = 'text';
        $maxlength = 1000;
        $msgRequired = '';
        if ($isNotNull) {
            $msgRequired = "data-msg-required=\"Vui lòng nhập $name\"" ;
        }

        if ($m) {

            if ($m[1] === 'int' || $m[1] === 'tinyint' || $m[1] === 'double') {
                $inputType = 'number';
            }
            $maxlength = $m[2];
        }



        return "<input class=\"form-control\" value=\"$value\" $required $msgRequired type=\"$inputType\" id=\"f$uname\" maxlength=\"$maxlength\" name=\"$name\" placeholder=\"$placeholder\"/>";
    }
}

