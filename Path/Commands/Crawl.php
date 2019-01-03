<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/19/2018
 * @Time 5:09 AM
 * @Project Path
 */

namespace Path\Console;


class Crawl extends CInterface
{
    public $name = "web-spider";
    public $description = "This command launches the Spider";
    public $arguments = [
      "start" => [
          "desc" => "Starts the spider"
      ],
      "stop" => [
            "desc" => "Stops the spider"
        ],

    ];

    public function __construct()
    {
    }

    public function entry(object $argument)
    {
        // TODO: Implement tasks() method.
        var_dump($argument);
    }
}