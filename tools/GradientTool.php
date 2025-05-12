<?php

class GradientTool implements DrawingToolInterface
{
    private $startX;
    private $startY;
    private $endX;
    private $endY;
    private $isDrawing = false;
    private $startColor;
    private $endColor;
    private $gradientType;
    private $opacity;

    public function startDrawing($x, $y)
    {
        $this->isDrawing = true;
        $this->startX = $x;
        $this->startY = $y;
        $this->endX = $x;
        $this->endY = $y;
    }

    public function continueDrawing($x, $y)
    {
        if ($this->isDrawing) {
            $this->endX = $x;
            $this->endY = $y;
        }
    }

    public function endDrawing($x, $y)
    {
        if ($this->isDrawing) {
            $this->endX = $x;
            $this->endY = $y;
            $this->isDrawing = false;
        }
    }

    public function draw($image)
    {
        if (!$this->isDrawing && ($this->startX === null || $this->startY === null)) {
            return $image;
        }

        if ($this->gradientType === 'linear') {
            $this->drawLinearGradient($image);
        } else {
            $this->drawRadialGradient($image);
        }

        return $image;
    }

    private function drawLinearGradient($image)
    {
        $width = abs($this->endX - $this->startX);
        $height = abs($this->endY - $this->startY);

        $x1 = min($this->startX, $this->endX);
        $y1 = min($this->startY, $this->endY);
        $x2 = max($this->startX, $this->endX);
        $y2 = max($this->startY, $this->endY);

        for ($i = 0; $i <= $width; $i++) {
            $ratio = ($width == 0) ? 0 : $i / $width;
            $r = $this->startColor['r'] + ($this->endColor['r'] - $this->startColor['r']) * $ratio;
            $g = $this->startColor['g'] + ($this->endColor['g'] - $this->startColor['g']) * $ratio;
            $b = $this->startColor['b'] + ($this->endColor['b'] - $this->startColor['b']) * $ratio;

            $color = imagecolorallocatealpha(
                $image,
                $r,
                $g,
                $b,
                127 - (int)(($this->opacity / 100) * 127)
            );

            imageline($image, $x1 + $i, $y1, $x1 + $i, $y2, $color);
        }
    }

    private function drawRadialGradient($image)
    {
        $centerX = $this->startX;
        $centerY = $this->startY;
        $radius = (int)sqrt(pow($this->endX - $this->startX, 2) + pow($this->endY - $this->startY, 2));

        for ($r = $radius; $r >= 0; $r--) {
            $ratio = ($radius == 0) ? 0 : $r / $radius;
            $rColor = $this->startColor['r'] + ($this->endColor['r'] - $this->startColor['r']) * $ratio;
            $gColor = $this->startColor['g'] + ($this->endColor['g'] - $this->startColor['g']) * $ratio;
            $bColor = $this->startColor['b'] + ($this->endColor['b'] - $this->startColor['b']) * $ratio;

            $color = imagecolorallocatealpha(
                $image,
                $rColor,
                $gColor,
                $bColor,
                127 - (int)(($this->opacity / 100) * 127)
            );

            imagefilledellipse($image, $centerX, $centerY, $r * 2, $r * 2, $color);
        }
    }

    public function getName()
    {
        return "Gradient";
    }

    public function getIcon()
    {
        return "🌈";
    }

    public function requiresCanvasRedraw()
    {
        return true;
    }

    public function getCursor()
    {
        return "crosshair";
    }

    public function setStartColor($color)
    {
        $this->startColor = $color;
    }

    public function setEndColor($color)
    {
        $this->endColor = $color;
    }

    public function setGradientType($type)
    {
        $this->gradientType = $type;
    }

    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;
    }
}
