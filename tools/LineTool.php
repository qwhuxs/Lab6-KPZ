<?php

class LineTool implements DrawingToolInterface
{
    private $startX;
    private $startY;
    private $endX;
    private $endY;
    private $isDrawing = false;
    private $color;
    private $size;
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

        $color = imagecolorallocatealpha(
            $image,
            $this->color['r'],
            $this->color['g'],
            $this->color['b'],
            127 - (int)(($this->opacity / 100) * 127)
        );

        $this->drawThickLine($image, $this->startX, $this->startY, $this->endX, $this->endY, $color, $this->size);

        return $image;
    }

    private function drawThickLine($image, $x1, $y1, $x2, $y2, $color, $thickness)
    {
        if ($thickness == 1) {
            imageline($image, $x1, $y1, $x2, $y2, $color);
            return;
        }

        $angle = atan2($y1 - $y2, $x1 - $x2);
        $dist_x = $thickness * sin($angle);
        $dist_y = $thickness * cos($angle);

        $vertices = [
            $x1 + $dist_x,
            $y1 + $dist_y,
            $x2 + $dist_x,
            $y2 + $dist_y,
            $x2 - $dist_x,
            $y2 - $dist_y,
            $x1 - $dist_x,
            $y1 - $dist_y
        ];

        imagefilledpolygon($image, $vertices, 4, $color);
    }

    public function getName()
    {
        return "Line";
    }

    public function getIcon()
    {
        return "─";
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

    public function setSize($size)
    {
        $this->size = $size;
    }

    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;
    }
}
