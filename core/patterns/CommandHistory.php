<?php

class CommandHistory
{
    private $undoStack = [];
    private $redoStack = [];
    private $maxHistory = 50;

    public function push(CommandInterface $command)
    {
        if (count($this->undoStack) >= $this->maxHistory) {
            array_shift($this->undoStack);
        }
        $this->undoStack[] = $command;
        $this->redoStack = [];
    }

    public function undo()
    {
        if (empty($this->undoStack)) {
            return null;
        }

        $command = array_pop($this->undoStack);
        $this->redoStack[] = $command;
        return $command->undo();
    }

    public function redo()
    {
        if (empty($this->redoStack)) {
            return null;
        }

        $command = array_pop($this->redoStack);
        $this->undoStack[] = $command;
        return $command->redo();
    }

    public function clear()
    {
        $this->undoStack = [];
        $this->redoStack = [];
    }

    public function canUndo()
    {
        return !empty($this->undoStack);
    }

    public function canRedo()
    {
        return !empty($this->redoStack);
    }
}
