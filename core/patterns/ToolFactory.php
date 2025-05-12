<?php

class ToolFactory
{
    public static function createTool($toolName)
    {
        $toolClass = ucfirst($toolName) . 'Tool';
        if (!file_exists(__DIR__ . '/../../tools/' . $toolClass . '.php')) {
            throw new Exception("Tool $toolName not found");
        }

        require_once __DIR__ . '/../../tools/' . $toolClass . '.php';

        if (!class_exists($toolClass)) {
            throw new Exception("Tool class $toolClass not found");
        }

        return new $toolClass();
    }

    public static function getAllTools()
    {
        $tools = [];
        $toolFiles = glob(__DIR__ . '/../../tools/*Tool.php');

        foreach ($toolFiles as $file) {
            $toolName = basename($file, 'Tool.php');
            $tools[] = self::createTool($toolName);
        }

        return $tools;
    }
}
