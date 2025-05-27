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
        $x1 = min($this->startX, $this->endX);
        $y1 = min($this->startY, $this->endY);
        $y2 = max($this->startY, $this->endY);

        for ($i = 0; $i <= $width; $i++) {
            $ratio = ($width == 0) ? 0 : $i / $width;
            $interpolatedColor = ColorInterpolator::interpolate($this->startColor, $this->endColor, $ratio);
            $color = ColorInterpolator::allocateColor($image, $interpolatedColor, $this->opacity);
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
            $interpolatedColor = ColorInterpolator::interpolate($this->startColor, $this->endColor, $ratio);
            $color = ColorInterpolator::allocateColor($image, $interpolatedColor, $this->opacity);
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
