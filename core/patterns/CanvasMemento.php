<?php

class CanvasMemento
{
    private $image;

    public function __construct($image)
    {
        $this->image = $image;
    }

    public function getImage()
    {
        return $this->image;
    }
}
