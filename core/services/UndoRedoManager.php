<?php

class UndoRedoManager
{
    private $commandHistory;

    public function __construct()
    {
        $this->commandHistory = new CommandHistory();
    }

    public function executeCommand(CommandInterface $command)
    {
        $this->commandHistory->push($command);
        return $command->execute();
    }

    public function undo()
    {
        return $this->commandHistory->undo();
    }

    public function redo()
    {
        return $this->commandHistory->redo();
    }

    public function canUndo()
    {
        return $this->commandHistory->canUndo();
    }

    public function canRedo()
    {
        return $this->commandHistory->canRedo();
    }

    public function clearHistory()
    {
        $this->commandHistory->clear();
    }
}
