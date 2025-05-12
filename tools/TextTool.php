<?php

class TextTool implements DrawingToolInterface
{
    private $x;
    private $y;
    private $isDrawing = false;
    private $color;
    private $text;
    private $fontSize;
    private $fontFamily;
    private $opacity;

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
        }
    }

    public function draw($image)
    {
        if (!$this->isDrawing || empty($this->text)) {
            return $image;
        }

        $color = imagecolorallocatealpha(
            $image,
            $this->color['r'],
            $this->color['g'],
            $this->color['b'],
            127 - (int)(($this->opacity / 100) * 127)
        );

        $fontPath = __DIR__ . '/../../assets/fonts/' . $this->fontFamily . '.ttf';
        if (!file_exists($fontPath)) {
            $fontPath = 5;
        }

        if (is_int($fontPath)) {
            imagestring($image, $fontPath, $this->x, $this->y, $this->text, $color);
        } else {
            imagettftext($image, $this->fontSize, 0, $this->x, $this->y + $this->fontSize, $color, $fontPath, $this->text);
        }

        return $image;
    }

    public function getName()
    {
        return "Text";
    }

    public function getIcon()
    {
        return "T";
    }

    public function requiresCanvasRedraw()
    {
        return false;
    }

    public function getCursor()
    {
        return "text";
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function setFontSize($fontSize)
    {
        $this->fontSize = $fontSize;
    }

    public function setFontFamily($fontFamily)
    {
        $this->fontFamily = $fontFamily;
    }

    public function setOpacity($opacity)
    {
        $this->opacity = $opacity;
    }

    public function getText()
    {
        return $this->text;
    }
}
