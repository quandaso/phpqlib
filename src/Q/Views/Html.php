<?php
/**
 * @author quantm
 * @date: 14/04/2016 12:59
 */

namespace Q\Helpers;


class Html
{
    private static $singleTagElements = [
        'input' => true,
        'link' => true,
        'meta' => true
    ];

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


    /**
     * @param $name
     * @param $attributes
     * @param $content
     * @return string
     */
    public function element($name, array $attributes = array(), $content = '')
    {
        $hasAttr = !empty ($attributes);

        if (isset (self::$singleTagElements[$name])) {
            $element = "<$name%s>";
        } else {
            $element = "<$name%s>%s</$name>";
        }

        if (!$hasAttr) {

        }

        return sprintf($element, $this->getAttrs($attributes), $content);
    }

    private function getAttrs($attrs)
    {
        $attributes = [];
        foreach ($attrs as $key => $value) {
            if (isset (self::$boolAttributes[$key]) && $value) {
                $attributes[] = $key;
            } else {
                $attributes[] = $key . '="' . htmlspecialchars($value) . '"';
            }
        }

        return ' ' . implode(' ', $attributes);
    }

    public function select($name, $value, $options, array $attrs = array())
    {
        $element = $this->element('select', $attrs, '%s');
        $optionElements = '';

        foreach ($options as $k => $v) {
            $optionElements.= sprintf('<option value="%s">%s</option>', $k, $v);
        }

        return sprintf($element, $optionElements);
    }
}