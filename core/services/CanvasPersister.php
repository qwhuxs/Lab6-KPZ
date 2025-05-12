<?php

class CanvasPersister implements StateSaverInterface
{
    private $states = [];
    private $maxStates = 10;
    private $savePath;

    public function __construct($savePath = 'save/')
    {
        $this->savePath = $savePath;
        if (!file_exists($this->savePath)) {
            mkdir($this->savePath, 0777, true);
        }
    }

    public function saveState($image)
    {
        if (count($this->states) >= $this->maxStates) {
            $oldestState = array_shift($this->states);
            if (file_exists($oldestState)) {
                unlink($oldestState);
            }
        }

        $filename = $this->savePath . 'state_' . time() . '_' . mt_rand() . '.png';
        imagepng($image, $filename);
        $this->states[] = $filename;
    }

    public function loadState()
    {
        if (empty($this->states)) {
            return null;
        }

        $filename = end($this->states);
        if (file_exists($filename)) {
            return imagecreatefrompng($filename);
        }
        return null;
    }

    public function clearStates()
    {
        foreach ($this->states as $filename) {
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
        $this->states = [];
    }

    public function getStatesCount()
    {
        return count($this->states);
    }

    public function saveImage($image, $filename)
    {
        $fullPath = $this->savePath . $filename;
        imagepng($image, $fullPath);
        return $fullPath;
    }
}
