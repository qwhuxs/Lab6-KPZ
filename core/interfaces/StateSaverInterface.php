<?php

interface StateSaverInterface
{
    public function saveState($image);
    public function loadState();
    public function clearStates();
    public function getStatesCount();
}
