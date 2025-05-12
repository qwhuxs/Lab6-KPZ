<?php

interface DrawingToolInterface
{
    public function startDrawing($x, $y);
    public function continueDrawing($x, $y);
    public function endDrawing($x, $y);
    public function draw($image);
    public function getName();
    public function getIcon();
    public function requiresCanvasRedraw();
    public function getCursor();
}
