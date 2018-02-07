<?php

define('_PS_IMG_DIR_', dirname(__DIR__));
define('_PS_ROOT_DIR_', dirname(__DIR__));

require_once 'cwmedia.php';

class Module
{
    public function __construct()
    {
    }

    public function l($text)
    {
        return $text;
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function display($template_path, $template_name, $id_cache)
    {
        return '';
    }

    public function getCacheId($name = null)
    {
        return $name ?? $this->name;
    }
}

class HelperImageUploader
{
    public function render()
    {
        return '';
    }

    public function process()
    {
        return [];
    }
}

class ObjectModel
{
    const HAS_MANY = 1;
    const TYPE_INT = 1;
    const TYPE_STRING = 1;
}

class Tools
{
    public function getValue($value, $default)
    {
        return $_POST[$value] ?? $default;
    }
}
