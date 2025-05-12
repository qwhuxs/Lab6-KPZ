<?php

class CanvasState
{
    private $image;
    private $width;
    private $height;
    private $backgroundColor;

    public function __construct($width, $height, $backgroundColor = null)
    {
        $this->width = $width;
        $this->height = $height;
        $this->backgroundColor = $backgroundColor ?: imagecolorallocate($this->createNewImage(), 255, 255, 255);
        $this->image = $this->createNewImage();
    }

    private function createNewImage()
    {
        $image = imagecreatetruecolor($this->width, $this->height);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);
        return $image;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function getWidth()
    {
        return $this->width;
    }

    public function getHeight()
    {
        return $this->height;
    }

    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    public function resize($newWidth, $newHeight)
    {
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 0, 0, 0, 127);
        imagefill($newImage, 0, 0, $transparent);
        imagecopyresampled($newImage, $this->image, 0, 0, 0, 0, $newWidth, $newHeight, $this->width, $this->height);
        $this->image = $newImage;
        $this->width = $newWidth;
        $this->height = $newHeight;
    }

    public function clear()
    {
        $this->image = $this->createNewImage();
    }
}
