<?php

class Statuses
{
    private $present;
    private $abscent;
    private $late;
    private $excused;
    private $all;
    public function __construct($statuses){
        $this->present = $statuses[0]->id;
        $this->abscent = $statuses[1]->id;
        $this->late = $statuses[2]->id;
        $this->excused = $statuses[3]->id;
        $this->all = $this->present . ',' . $this->abscent . ',' . $this->late . ',' . $this->excused;

    }

    public function getAll(): string
    {
        return $this->all;
    }

    
    public function getExcused()
    {
        return $this->excused;
    }

    
    public function getLate()
    {
        return $this->late;
    }

    
    public function getAbscent()
    {
        return $this->abscent;
    }

    
    public function getPresent()
    {
        return $this->present;
    }
}