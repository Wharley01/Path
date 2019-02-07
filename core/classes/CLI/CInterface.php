<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/6/2018
 * @Time 1:59 AM
 * @Project Path
 */

namespace Path\Console;


abstract class CInterface
{
    protected $name;
    protected $description;
    protected $arguments = [
        "param" => [
            "desc" => "param description"
        ]
    ];
    abstract protected function entry(object $argument);

    public function confirm($question, $yes = ['y','yes'], $no = ['n','no']){
        $yes = !is_array($yes) ? [$yes]:$yes;
        $no = !is_array($no) ? [$no]:$no;

        $handle = fopen ("php://stdin","r");
        $this->write($question."  {$yes[0]}/$no[0]:");

        $input = strtolower(trim(fgets($handle)));
        if(!in_array($input,array_map(function($op){ return strtolower($op); },$yes)) && !in_array($input,array_map(function($op){ return strtolower($op); },$no))){
            $this->confirm($question,$yes,$no);
        }
        return in_array($input,array_map(function($op){ return strtolower($op); },$yes));
    }

    public function ask($question,$enforce = false){
        $handle = fopen ("php://stdin","r");
        echo PHP_EOL.$question.":  ";
        $input = trim(fgets($handle));
        if($enforce && strlen($input) < 1){
            $this->ask($question);
        }
        if(strlen($input) < 1){
            return null;
        }
        return $input;
    }

    public function write($text){
        echo PHP_EOL.$text;
//        ob_flush();
    }

}