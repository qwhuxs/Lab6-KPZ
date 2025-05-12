<?php

class BezierTool implements DrawingToolInterface
{
    private $points = [];
    private $isDrawing = false;
    private $color;
    private $size;
    private $opacity;

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
        }
    }

    public function draw($image)
    {
        if (count($this->points) < 3) {
            return $image;
        }

        $color = imagecolorallocatealpha(
            $image,
            $this->color['r'],
            $this->color['g'],
            $this->color['b'],
            127 - (int)(($this->opacity / 100) * 127)
        );

        $this->drawBezierCurve($image, $this->points, $color, $this->size);

        $this->points = [];
        return $image;
    }

    private function drawBezierCurve($image, $points, $color, $thickness)
    {
        $n = count($points) - 1;
        $steps = 100;

        $prevX = $points[0]['x'];
        $prevY = $points[0]['y'];

        for ($i = 1; $i <= $steps; $i++) {
            $t = $i / $steps;

            $x = 0;
            $y = 0;

            for ($j = 0; $j <= $n; $j++) {
                $binomialCoeff = $this->binomialCoefficient($n, $j);
                $term = $binomialCoeff * pow(1 - $t, $n - $j) * pow($t, $j);

                $x += $term * $points[$j]['x'];
                $y += $term * $points[$j]['y'];
            }

            $this->drawThickLine($image, $prevX, $prevY, $x, $y, $color, $thickness);

            $prevX = $x;
            $prevY = $y;
        }
    }

    private function binomialCoefficient($n, $k)
    {
        if ($k < 0 || $k > $n) {
            return 0;
        }

        $result = 1;
        for ($i = 1; $i <= $k; $i++) {
            $result *= ($n - ($k - $i)) / $i;
        }

        return $result;
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
        return "Bezier";
    }

    public function getIcon()
    {
        return "⤴️";
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
