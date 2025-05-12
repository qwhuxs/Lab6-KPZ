<?php

class FillTool implements DrawingToolInterface
{
    private $x;
    private $y;
    private $isDrawing = false;
    private $color = ['r' => 0, 'g' => 0, 'b' => 0];
    private $opacity = 100;

    public function startDrawing($x, $y)
    {
        $this->isDrawing = true;
        $this->x = $x;
        $this->y = $y;
    }

    public function continueDrawing($x, $y) {}

    public function endDrawing($x, $y)
    {
        if ($this->isDrawing) {
            $this->isDrawing = false;
            return true;
        }
        return false;
    }

    public function draw($image)
    {
        if (!$this->isDrawing) {
            return $image;
        }

        $targetColor = imagecolorat($image, $this->x, $this->y);
        $targetRgb = imagecolorsforindex($image, $targetColor);

        $fillColor = imagecolorallocatealpha(
            $image,
            $this->color['r'],
            $this->color['g'],
            $this->color['b'],
            127 - (int)(($this->opacity / 100) * 127)
        );

        $this->floodFillScanline($image, $this->x, $this->y, $targetRgb, $fillColor);

        return $image;
    }

    private function floodFillScanline(&$image, $x, $y, $targetRgb, $fillColor)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $stack = [[$x, $y]];
        $filled = [];

        while (!empty($stack)) {
            list($x, $y) = array_pop($stack);

            $leftBound = $x;
            while ($leftBound >= 0 && $this->checkPixel($image, $leftBound, $y, $targetRgb)) {
                $leftBound--;
            }
            $leftBound++;

            $rightBound = $x;
            while ($rightBound < $width && $this->checkPixel($image, $rightBound, $y, $targetRgb)) {
                $rightBound++;
            }
            $rightBound--;

            for ($i = $leftBound; $i <= $rightBound; $i++) {
                imagesetpixel($image, $i, $y, $fillColor);

                if ($y > 0 && $this->checkPixel($image, $i, $y - 1, $targetRgb) && !isset($filled[$i][$y - 1])) {
                    $stack[] = [$i, $y - 1];
                    $filled[$i][$y - 1] = true;
                }

                if ($y < $height - 1 && $this->checkPixel($image, $i, $y + 1, $targetRgb) && !isset($filled[$i][$y + 1])) {
                    $stack[] = [$i, $y + 1];
                    $filled[$i][$y + 1] = true;
                }
            }
        }
    }

    private function checkPixel($image, $x, $y, $targetRgb)
    {
        $color = imagecolorat($image, $x, $y);
        $rgb = imagecolorsforindex($image, $color);

        return ($rgb['red'] == $targetRgb['red'] &&
            $rgb['green'] == $targetRgb['green'] &&
            $rgb['blue'] == $targetRgb['blue'] &&
            $rgb['alpha'] == $targetRgb['alpha']);
    }

    public function getName()
    {
        return "Fill";
    }

    public function getIcon()
    {
        return "🎨";
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

    public function setOpacity($opacity)
    {
        $this->opacity = max(0, min(100, (int)$opacity));
    }
}
