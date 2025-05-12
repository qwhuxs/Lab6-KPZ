<?php

class PencilTool implements DrawingToolInterface
{
    private $points = [];
    private $isDrawing = false;
    private $color = ['r' => 0, 'g' => 0, 'b' => 0];
    private $size = 5;
    private $opacity = 100;

    public function __construct()
    {
        $this->points = [];
        $this->isDrawing = false;
    }

    public function startDrawing($x, $y)
    {
        $this->isDrawing = true;
        $this->points = [['x' => $x, 'y' => $y]];
    }

    public function continueDrawing($x, $y)
    {
        if ($this->isDrawing) {
            $this->points[] = ['x' => $x, 'y' => $y];
        }
    }

    public function endDrawing($x, $y)
    {
        if ($this->isDrawing) {
            $this->points[] = ['x' => $x, 'y' => $y];
            $this->isDrawing = false;
            return true;
        }
        return false;
    }

    public function draw($image)
    {
        if (count($this->points) < 2) {
            return $image;
        }

        $color = imagecolorallocatealpha(
            $image,
            $this->color['r'],
            $this->color['g'],
            $this->color['b'],
            127 - (int)(($this->opacity / 100) * 127)
        );

        for ($i = 1; $i < count($this->points); $i++) {
            $x1 = $this->points[$i - 1]['x'];
            $y1 = $this->points[$i - 1]['y'];
            $x2 = $this->points[$i]['x'];
            $y2 = $this->points[$i]['y'];

            $this->drawThickLine($image, $x1, $y1, $x2, $y2, $color, $this->size);
        }

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
        return "Pencil";
    }

    public function getIcon()
    {
        return "✏️";
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
        $this->size = max(1, (int)$size);
    }

    public function setOpacity($opacity)
    {
        $this->opacity = max(0, min(100, (int)$opacity));
    }
}
