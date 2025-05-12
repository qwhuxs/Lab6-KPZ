<?php

class CircleTool implements DrawingToolInterface
{
    private $startX;
    private $startY;
    private $endX;
    private $endY;
    private $isDrawing = false;
    private $color;
    private $fillColor;
    private $size;
    private $opacity;
    private $isFilled;

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

        $radius = (int)sqrt(pow($this->endX - $this->startX, 2) + pow($this->endY - $this->startY, 2));

        $borderColor = imagecolorallocatealpha(
            $image,
            $this->color['r'],
            $this->color['g'],
            $this->color['b'],
            127 - (int)(($this->opacity / 100) * 127)
        );

        $fillColor = imagecolorallocatealpha(
            $image,
            $this->fillColor['r'],
            $this->fillColor['g'],
            $this->fillColor['b'],
            127 - (int)(($this->opacity / 100) * 127)
        );

        if ($this->isFilled) {
            imagefilledellipse($image, $this->startX, $this->startY, $radius * 2, $radius * 2, $fillColor);
        }

        $this->drawThickCircle($image, $this->startX, $this->startY, $radius, $borderColor, $this->size);

        return $image;
    }

    private function drawThickCircle($image, $centerX, $centerY, $radius, $color, $thickness)
    {
        for ($i = 0; $i < $thickness; $i++) {
            imageellipse($image, $centerX, $centerY, ($radius - $i) * 2, ($radius - $i) * 2, $color);
        }
    }

    public function getName()
    {
        return "Circle";
    }

    public function getIcon()
    {
        return "○";
    }

    public function requiresCanvasRedraw()
    {
        return true;
    }

    public function getCursor()
    {
        return "crosshair";
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function setFillColor($fillColor)
    {
        $this->fillColor = $fillColor;
    }

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;
    }

    public function setIsFilled($isFilled)
    {
        $this->isFilled = $isFilled;
    }
}
