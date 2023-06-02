<?php

class Debugger {
    private $name;
    private $file;
    
    function __construct()
    {
        $this->name = './downloads/debug.log';
        $this->file = fopen($this->name, 'a');
    }
    
    function __destruct()
    {
        if ($this->file) fclose($this->file);
    }
    
    function write($text)
    {
        if ($this->file) fwrite($this->file, $text . "\n");
    }
}

?>

