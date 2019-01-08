<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/6/2018
 * @Time 2:30 AM
 * @Project Path
 */

namespace Path\Console;
load_class([
    "CLI/Console",
]);

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