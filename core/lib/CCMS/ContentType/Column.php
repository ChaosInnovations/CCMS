<?php

class Column
{
    private $name = "";
    private $type = "";
    private $nullable = false;
    private $defaultValue = "";
    private $extras = "";

    public function __construct($name, $type, $nullable, $defaultValue, $extras="") {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable;
        $this->defaultValue = $defaultValue;
        $this->extras = $extras;
    }

    public function getCompiled() {

    }
}