<?php

class ToolManager
{
    private $tools = [];
    private $activeTool = null;
    private $toolSettings = [];

    public function __construct()
    {
        $this->tools = ToolFactory::getAllTools();
        if (!empty($this->tools)) {
            $this->activeTool = $this->tools[0];
        }

        $this->toolSettings = [
            'color' => '#000000',
            'size' => 5,
            'fontSize' => 16,
            'fontFamily' => 'Arial',
            'fillColor' => '#ffffff',
            'gradientStart' => '#000000',
            'gradientEnd' => '#ffffff',
            'gradientType' => 'linear',
            'text' => 'Sample Text',
            'opacity' => 100
        ];
    }

    public function getTools()
    {
        return $this->tools;
    }

    public function getActiveTool()
    {
        return $this->activeTool;
    }

    public function setActiveTool($toolName)
    {
        foreach ($this->tools as $tool) {
            if ($tool->getName() === $toolName) {
                $this->activeTool = $tool;
                return true;
            }
        }
        return false;
    }

    public function getToolSettings()
    {
        return $this->toolSettings;
    }

    public function updateToolSetting($key, $value)
    {
        $this->toolSettings[$key] = $value;
    }

    public function getColor()
    {
        return $this->hexToRgb($this->toolSettings['color']);
    }

    public function getFillColor()
    {
        return $this->hexToRgb($this->toolSettings['fillColor']);
    }

    public function getGradientStart()
    {
        return $this->hexToRgb($this->toolSettings['gradientStart']);
    }

    public function getGradientEnd()
    {
        return $this->hexToRgb($this->toolSettings['gradientEnd']);
    }

    private function hexToRgb($hex)
    {
        $hex = str_replace('#', '', $hex);

        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }

        return ['r' => $r, 'g' => $g, 'b' => $b];
    }
}
