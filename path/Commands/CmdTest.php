<?php


namespace Path\Console;


use Path\Console;

class CmdTest extends CInterface
{


    /*
     * Command Line name
     *
     * @var String
     * */
    public $name = "your-custom-cl";
    public $description = "This is just a test CLI extension";

    public function __construct()
    {
    }

    /**
     * @param $argument
     */
    public function entry(object $argument)
    {
        var_dump($argument);
    }

}