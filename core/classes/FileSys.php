<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 11/23/2018
 * @Time 4:42 PM
 * @Project Path
 */

namespace Path;



class File
{
    private $file_name;
    private $files;
    private $errors;
    private $rules = [
        "retain_name"       => false,
        "accepted_exts"     => [],
        "accepted_types"    => [],
        "max_size"          => (5 * 1024 * 1024)
        ];//accepted_exts,accepted_types,max_size(bytes),retain_name
    private $write_folder;

    /**
     * File constructor.
     * @param $file
     * @throws FileSystemException
     */
    public function __construct($file)
    {
        $this->rules["max_size"] = config("FILE->max_zie");
        if(!@$_FILES[$file])
            throw new FileSystemException("");
        if(is_string($file)){
            $this->files = is_array($_FILES[$file]['name']) ? $this->restructure($_FILES[$file]) : [$_FILES[$file]];
        }elseif(is_array($file)){
            $this->files = is_array($file['name']) ? $this->restructure($file) : [$file];
        }

        return $this;
    }

    /**
     * @param array $rules
     * @return File
     */
    public function setRules(array $rules = []):File
    {
        $this->rules = array_merge($this->rules,$rules);
        return $this;
    }

    /**
     * @param $attr
     */
    public function get($attr){

    }
    private function restructure($file){
        $file_ary = array();
        $file_count = count($file['name']);
        $file_key = array_keys($file);

        for($i=0;$i<$file_count;$i++)
        {
            foreach($file_key as $val)
            {
                $file_ary[$i][$val] = $file[$val][$i];
            }
        }
        return $file_ary;
    }
    /**
     * @param $target_folder
     * @param callable $callback
     * @return bool
     */
    public function moveTo($target_folder,callable $callback):bool
    {
        $this->write_folder = $target_folder;
        return true;
    }

    /**
     * @return mixed
     */
    public function getErrors():array
    {
        return $this->errors;
    }

}