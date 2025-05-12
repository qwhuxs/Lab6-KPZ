<?php

class DrawingCommand implements CommandInterface
{
    private $image;
    private $previousImage;
    private $drawingStrategy;
    private $toolName;

    public function __construct($image, DrawingStrategy $drawingStrategy, $toolName)
    {
        $this->image = $image;
        $this->previousImage = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagecopy($this->previousImage, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        $this->drawingStrategy = $drawingStrategy;
        $this->toolName = $toolName;
    }

    public function execute()
    {
        $this->drawingStrategy->draw($this->image);
        return $this->image;
    }

    public function undo()
    {
        return $this->previousImage;
    }

    public function redo()
    {
        return $this->execute();
    }

    public function getToolName()
    {
        return $this->toolName;
    }
}
