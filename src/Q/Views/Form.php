<?php
/**
 * @author quantm
 * @date: 14/04/2016 20:52
 */

namespace Q\Views;


class Form
{
    private static $boolAttributes = [
        'required' => true,
        'checked' => true,
        'selected' => true,
        'async' => true,
        'autofocus' => true,
        'autoplay' => true,
        'controls' => true,
        'defer' => true,
        'disabled' => true,
        'hidden' => true,
        'ismap' => true,
        'loop' => true,
        'multiple' => true,
        'open' => true,
        'readonly' => true,
        'scope' => true
    ];

    private $data = [];
    private $attrs = [];

    /**
     * @param $data
     */
    public function withData($data)
    {
        $this->data = $data;
    }

    /**
     * @param array $attrs
     */
    public function setDefaultAttrs(array $attrs)
    {
        $this->attrs = $attrs;
    }


    /**
     * @param array $attrs
     * @return string
     */
    private function getAttrs(array $attrs)
    {
        if (!empty ($this->attrs)) {
            $attrs = array_merge($this->attrs, $attrs);
        }

        if (empty ($attrs)) {
            return '';
        }

        $attributes = [];
        foreach ($attrs as $key => $value) {
            if (isset (self::$boolAttributes[$key])) {
                if (!$value) {
                    continue;
                }

                $attributes[] = $key;
            } else {
                $attributes[] = h($key) . '="' . h($value) . '"';
            }
        }

        return implode(' ', $attributes);
    }

    /**
     * @param $name
     * @param $method
     * @param $action
     * @param array $attrs
     * @return string
     */
    public function begin($name, $action = '', $method = 'post', array $attrs = array())
    {
        $attrs = [
            'role' => 'form',
            'id' => $name,
            'name' => $name,
            'action' => $action,
            'method' => $method
        ];

        $attrs = array_merge([
            'role' => 'form',
            'id' => $name,
            'name' => $name,
            'action' => $action,
            'method' => $method
        ], $attrs );

        $element = sprintf('<form %s>', $this->getAttrs($attrs));
        $element .= '<input type="hidden" value="' . csrf_token() . '" name="csrf_token">';
        return $element;
    }

    /**
     * @param $name
     * @param $action
     * @param array $attrs
     * @return string
     */
    public function beginUploadForm($name, $action = '', array $attrs = array())
    {
        $attrs = array_merge([
            'role' => 'form',
            'id' => $name,
            'name' => $name,
            'action' => $action,
            'method' => 'POST'
        ], $attrs );

        $attrs['enctype'] = 'multipart/form-data';

        $element = sprintf('<form %s>', $this->getAttrs($attrs));
        $element .= '<input type="hidden" value="' . csrf_token() . '" name="csrf_token">';
        return $element;
    }

    /**
     * @return string
     */
    public function end()
    {
        return '</form>';
    }

    /**
     * @param $text
     * @param $for
     * @param array $attrs
     * @return string
     */
    public function label($text, $for = '', array $attrs = array())
    {
        $attrs = array_merge(['for' => $for], $attrs);
        return sprintf('<label %s>%s</label>', $this->getAttrs($attrs), $text);
    }

    /**
     * @param $name
     * @param $value
     * @param array $attrs
     * @return string
     */
    public function input($name, $value = '', array $attrs = array(), $label = null)
    {
        if (isset ($this->data[$name])) {
            $value = $this->data[$name];
        }

        $attrs = array_merge(['type' => 'text', 'id' => $name, 'name' => $name, 'value' => $value], $attrs);

        $element = sprintf('<input %s>', $this->getAttrs($attrs));

        if ($label) {
            $element = '<div class="form-group">' . $this->label($label, $name) . $element . '</div>';
        }

        return $element;

    }

    /**
     * @param $name
     * @param array $attrs
     * @param null $label
     * @return string
     */
    public function file($name, array $attrs = array(), $label = null)
    {
        $attrs = array_merge(['id' => $name, 'name' => $name], $attrs);
        $attrs['type'] = 'file';

        $element = sprintf('<input %s>', $this->getAttrs($attrs));

        if ($label) {
            $element = '<div class="form-group">' . $this->label($label, $name) . $element . '</div>';
        }

        return $element;
    }

    /**
     * @param $name
     * @param $value
     * @return string
     */
    public function hidden($name, $value = '')
    {
        if (isset ($this->data[$name])) {
            $value = $this->data[$name];
        }

        return sprintf('<input name="%s" id="%s" value="%s"  type="hidden">', $name, $name, $value);
    }

    /**
     * @param $name
     * @param $value
     * @param array $options
     * @param array $attrs
     * @return string
     */
    public function radio($name, $value = '', $options = array(), array $attrs = array(), $label = null)
    {
        if (isset ($this->data[$name])) {
            $value = $this->data[$name];
        }

        $attrs = array_merge(['name' => $name], $attrs);

        $element = '';

        foreach ($options as $k => $v) {
            $element .= sprintf('<label>%s <input %s type="radio" value="%s" %s></label>&nbsp;',
                $v,($k == $value) ? 'checked' : '', $k, $this->getAttrs($attrs));
        }

        if ($label) {
            $element = '<div class="form-group">' . $this->label($label, '') . $element . '</div>';
        }

        return $element;
    }

    /**
     * @param $name
     * @param $value
     * @param array $options
     * @param array $attrs
     * @param null $label
     * @return string
     */
    public function checkbox($name, $value = '', $options = array(), array $attrs = array(), $label = null)
    {
        if (isset ($this->data[$name])) {
            $value = $this->data[$name];
        }

        $attrs = array_merge(['name' => $name], $attrs);

        $element = '';

        foreach ($options as $k => $v) {
            $element .= sprintf('<label>%s <input %s type="checkbox" value="%s" %s></label>&nbsp;',
                $v,($k == $value) ? 'checked' : '', $k, $this->getAttrs($attrs));
        }

        if ($label) {
            $element = '<div class="form-group">' . $this->label($label, '') . $element . '</div>';
        }

        return $element;
    }

    /**
     * @param $name
     * @param string|array $value
     * @param array $options
     * @param array $attrs
     * @param string $label
     * @return string
     */
    public function select($name, $value = '', array  $options = array(), array $attrs = array(), $label = null)
    {
        if (isset ($this->data[$name])) {
            $value = $this->data[$name];
        }

        $attrs = array_merge(['type' => 'text', 'id' => $name, 'name' => $name], $attrs);

        $element = '<select %s>%s</select>';
        $optionElements = '';

        if (is_array($value)) {
            foreach ($options as $k => $v) {
                $optionElements.= sprintf('<option %s value="%s">%s</option>', in_array($k, $value) ? 'selected' : '', $k, $v);
            }
        } else {
            foreach ($options as $k => $v) {
                $optionElements.= sprintf('<option %s value="%s">%s</option>', ($value == $k) ? 'selected' : '', $k, $v);
            }
        }

        $element = sprintf($element, $this->getAttrs($attrs), $optionElements);

        if ($label) {
            $element = '<div class="form-group">' . $this->label($label, $name) . $element . '</div>';
        }

        return $element;
    }

    /**
     * @param $name
     * @param $value
     * @param array $attrs
     * @param string $label
     * @return string
     */
    public function textarea($name, $value = '', array $attrs = array(), $label = null)
    {
        if (isset ($this->data[$name])) {
            $value = $this->data[$name];
        }

        $attrs = array_merge(['type' => 'text', 'id' => $name, 'name' => $name], $attrs);

        $element = sprintf('<textarea %s>%s</textarea>', $this->getAttrs($attrs), h($value));

        if ($label) {
            $element = '<div class="form-group">' . $this->label($label, $name) . $element . '</div>';
        }

        return $element;
    }

    /**
     * @param $text
     * @param array $attrs
     * @return string
     */
    public function button($text,  array $attrs = array())
    {
        $attrs = array_merge([ 'type' => 'submit'], $attrs);

        $element = sprintf('<button %s>%s</button>', $this->getAttrs($attrs), h($text));

        return $element;
    }
}