<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/6/2018
 * @Time 1:59 AM
 * @Project Path
 */

namespace Path\Console;


abstract class CLInterface
{
    protected $name;
    protected $description;
    protected $arguments = [
        "param" => [
            "desc" => "param description"
        ]
    ];
    abstract protected function entry(object $argument);

    public function confirm($quest){
        $handle = fopen ("php://stdin","r");
        echo $quest."  Y/N:";
        $input = strtolower(trim(fgets($handle)));
        if($input != "y" && $input != "n"){
            echo "Enter Y/N";
            $this->confirm($quest);
        }
        return $input == "y";
    }

    public function ask($question,$enforce = false){
        $handle = fopen ("php://stdin","r");
        echo PHP_EOL.$question;
        $input = strtolower(trim(fgets($handle)));
        if($enforce && strlen($input) < 1){
            $this->ask($question);
        }
        return $input;
    }



}