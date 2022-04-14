<?php
/**
 * @author quantm
 * @date: 13/04/2016 08:24
 */

namespace Q\Response;
use Q\Interfaces\Response;

class ViewResponse implements Response
{
    private $_content;
    private $_layoutPath;
    private static $_baseViewPath;
    private $_viewPath;
    private $_vars = [];


    /**
     * View constructor.
     * @param $path
     * @param $vars
     */
    public function __construct($path, $vars)
    {
        if (!self::$_baseViewPath) {
            self::$_baseViewPath = ROOT_DIR . '/app/Templates';
        }

        $this->_viewPath = self::$_baseViewPath . '/' . $path . '.phtml';
        $this->_vars = $vars;
    }


    /**
     * @param $baseViewPath
     */
    public static function setBaseViewPath($baseViewPath) {
        self::$_baseViewPath = $baseViewPath;
    }

    /**
     * @param $viewPath
     * @param array $vars
     * @throws \Exception
     * @return string
     */
    public function render()
    {
        echo $this->renderViewAsString();
    }

    /**
     * Render view as string
     * @return string
     */
    public function renderViewAsString() {
        ob_start();

        extract($this->_vars);

        include $this->_viewPath;

        $this->_content = ob_get_clean();

        if ($this->_layoutPath) {

            include $this->_layoutPath;
            return ob_get_clean();
        } else {
            return $this->_content;
        }
    }

    /**
     *
     */
    public function getContent()
    {

        return $this->_content;
    }

    /**
     * @param $element
     * @param array $vars
     */
    public function elementFromCustomer($element, array $vars = array())
    {
        $vars = array_merge($this->_vars, $vars);
        extract($vars);
        include APP_CUSTOMER_ROOT . '/app/Templates/' .$element . '.phtml';
    }

    /**
     * @param $element
     * @param array $vars
     */
    public function element($element, array $vars = array())
    {
        $vars = array_merge($this->_vars, $vars);
        extract($vars);
        include self::$_baseViewPath . '/' . $element . '.phtml';
    }


    public function extend($path) {
        $this->_layoutPath = self::$_baseViewPath .'/'. $path . '.phtml';
    }


    public function __toString()
    {
        // TODO: Implement __toString() method.
        return $this->renderViewAsString();
    }

}
