<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/6/2018
 * @Time 1:59 AM
 * @Project Path
 */

namespace Path\Core\CLI;


abstract class CInterface
{
    protected $name;
    protected $description;
    protected $arguments = [
        "param" => [
            "desc" => "param description"
        ]
    ];
    static      $foreground_colors = array(
        'bold'         => '1',    'dim'          => '2',
        'black'        => '0;30', 'dark_gray'    => '1;30',
        'blue'         => '0;34', 'light_blue'   => '1;34',
        'green'        => '0;32', 'light_green'  => '1;32',
        'cyan'         => '0;36', 'light_cyan'   => '1;36',
        'red'          => '0;31', 'light_red'    => '1;31',
        'purple'       => '0;35', 'light_purple' => '1;35',
        'brown'        => '0;33', 'yellow'       => '1;33',
        'light_gray'   => '0;37', 'white'        => '1;37',
        'normal'       => '0;39',
    );

    static      $background_colors = array(
        'black'        => '40',   'red'          => '41',
        'green'        => '42',   'yellow'       => '43',
        'blue'         => '44',   'magenta'      => '45',
        'cyan'         => '46',   'light_gray'   => '47',
    );

    static         $options = array(
        'underline'    => '4',    'blink'         => '5',
        'reverse'      => '7',    'hidden'        => '8',
    );


    /**
     * @param $argument
     * @return mixed
     */
    abstract protected function entry($argument);

    public function confirm($question, $yes = ['y', 'yes'], $no = ['n', 'no'])
    {
        $yes = !is_array($yes) ? [$yes] : $yes;
        $no = !is_array($no) ? [$no] : $no;

        $handle = fopen("php://stdin", "r");
        $this->write($question . "  {$yes[0]}/$no[0]:");

        $input = trim(strtolower(fgets($handle)));
        if (!in_array($input, array_map(function ($op) {
            return strtolower($op);
        }, $yes)) && !in_array($input, array_map(function ($op) {
            return strtolower($op);
        }, $no))) {
            $this->confirm($question, $yes, $no);
        }
        return in_array($input, array_map(function ($op) {
            return strtolower($op);
        }, $yes));
    }

    public function ask($question, $enforce = false)
    {
        $handle = fopen("php://stdin", "r");
        echo PHP_EOL . $question . ":  ";
        $input = trim(fgets($handle));
        if ($enforce && strlen($input) < 1) {
            $this->ask($question);
        }
        if (strlen($input) < 1) {
            return null;
        }
        return $input;
    }

    public function write($text, $format = null)
    {
        if(is_array($text)){
            $r = [];
            foreach ($text as $txt){
                $r[] = $this->parseText($txt);
            }
            if($format !== null)
                printf($format, ...$r);
            else
                echo join(" ",$r);
        }else{
            $text = $this->parseText($text);
            if($format !== null)
                printf($format, $text);
            else
                print($text);
        }
    }

    private function parseText($text)
    {
        $regx = "((`(\\w+)`)([^`]+)(`(\\w+)`))";
        $return_value = preg_replace_callback("/$regx/i", function ($match) {
            $color = self::$foreground_colors[$match[3]];
            return "\033[{$color}m{$match[4]}\033[0m";
        }, $text);
        return $return_value;
    }
}
