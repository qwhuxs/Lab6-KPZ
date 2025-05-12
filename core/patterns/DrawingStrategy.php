<?php

class DrawingStrategy
{
    private $currentTool;
    private $isDrawing = false;
    private $startX;
    private $startY;
    private $lastX;
    private $lastY;

    public function setTool(DrawingToolInterface $tool)
    {
        $this->currentTool = $tool;
    }

    public function startDrawing($x, $y)
    {
        if ($this->currentTool) {
            $this->isDrawing = true;
            $this->startX = $x;
            $this->startY = $y;
            $this->lastX = $x;
            $this->lastY = $y;
            $this->currentTool->startDrawing($x, $y);
        }
    }

    public function continueDrawing($x, $y)
    {
        if ($this->isDrawing && $this->currentTool) {
            $this->currentTool->continueDrawing($x, $y);
            $this->lastX = $x;
            $this->lastY = $y;
        }
    }

    public function endDrawing($x, $y)
    {
        if ($this->isDrawing && $this->currentTool) {
            $this->currentTool->endDrawing($x, $y);
            $this->isDrawing = false;
            return true;
        }
        return false;
    }

    public function draw($image)
    {
        if ($this->currentTool) {
            return $this->currentTool->draw($image);
        }
        return $image;
    }

    public function getCurrentTool()
    {
        return $this->currentTool;
    }

    public function isDrawing()
    {
        return $this->isDrawing;
    }

    public function getDrawingCoordinates()
    {
        return [
            'startX' => $this->startX,
            'startY' => $this->startY,
            'lastX' => $this->lastX,
            'lastY' => $this->lastY
        ];
    }
}
