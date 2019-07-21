<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/6/2018
 * @Time 2:30 AM
 * @Project Path
 */

namespace Path\Core\CLI\DefaultCommands;

use Path\Core\CLI\CInterface;

class Version  extends CInterface
{
    /*
     * Argument parsed from CLI
     *
     * @var Array
     *
     * */

    private $argument;

    /*
     * Command Line name
     *
     * @var String
     * */
    public $name = "version";
    public $arguments = [
        //        "dev"  => [
        //            "desc" => "Show Development's Current version",
        //        ],
        //        "prod" => [
        //            "desc" => "Show Production's Current version"
        //        ],
        "version" => [
            "desc" => "Show current version"
        ]
    ];

    public $description = "This command shows the App's current version";



    public function __construct()
    { }

    /**
     * @param $argument
     * @return mixed|void
     */
    public function entry($argument)
    {
        $version = $argument['version'] ?? "development";
        $this->write(PHP_EOL.'`green`'.config("PROJECT->name")." {$version} version is " . config("PROJECT->version").'`green`');
    }
}
