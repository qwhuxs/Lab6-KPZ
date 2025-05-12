<?php

class RectangleTool implements DrawingToolInterface
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

        $x1 = min($this->startX, $this->endX);
        $y1 = min($this->startY, $this->endY);
        $x2 = max($this->startX, $this->endX);
        $y2 = max($this->startY, $this->endY);

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
            imagefilledrectangle($image, $x1, $y1, $x2, $y2, $fillColor);
        }

        $this->drawThickRectangle($image, $x1, $y1, $x2, $y2, $borderColor, $this->size);

        return $image;
    }

    private function drawThickRectangle($image, $x1, $y1, $x2, $y2, $color, $thickness)
    {
        for ($i = 0; $i < $thickness; $i++) {
            imagerectangle($image, $x1 + $i, $y1 + $i, $x2 - $i, $y2 - $i, $color);
        }
    }

    public function getName()
    {
        return "Rectangle";
    }

    public function getIcon()
    {
        return "□";
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
