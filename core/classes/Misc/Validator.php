<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 5/14/2019
 * @Time 4:48 AM
 * @Project path
 */

namespace Path\Core\Misc;


use Closure;
use Path\Core\Database\Model;
use Path\Core\Error\Exceptions;

class Validator
{
    public $model;
    private  $errors = [];
    private $values;
    public  $rules = [];
    private $keys = [];
    private $files = [];
    private $regex_rule = "^regex\s*\:(.*)";
    private $max_value_rule = "^max\s*\:(.*)";
    private $min_value_rule = "^min\s*\:(.*)";
    private $equals_rule = "^equals\s*\:\s*@(.*)";
    private $php_filter_validate = "^FILTER_VALIDATE_";

    public function __construct($values)
    {
        $this->values = (array)$values;
        return $this;
    }

    /**
     * @param mixed $model
     * @return $this
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    private function fileRestructure($file)
    {
        $file_ary = [];
        $file_count = count($file['name']);
        $file_key = array_keys($file);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_key as $val) {
                $file_ary[] = [
                    $val => $file[$val][$i]
                ];
            }
        }
        return $file_ary;
    }

    private function getFiles($key){
        $file = $_FILES[$key] ?? null;

        if(!$file)
            return null;


        $files = is_array($file['name']) ? $this->fileRestructure($file) : [$file];

        return $files;

    }

    public function key($name)
    {
        $this->keys[] = $name;
        $this->rules[$name] = [
            'is_file' => false,
            'rules' => []
        ];
        return $this;
    }
    public function file($name)
    {
        $this->keys[] = $name;
        $this->rules[$name] = [
            'is_file' => true,
            'rules' => []
        ];
        return $this;
    }

    public function rules(...$rules)
    {
        $key = $this->keys[count($this->keys) - 1];
        $this->rules[$key]['rules'] = array_merge($this->rules[$key]['rules'], $rules);
        return $this;
    }

    public function validate(?Model $model = null)
    {


        foreach ($this->rules as $key => $rules) {
            $_rules = $rules['rules'];
            $_is_file = $rules['is_file'];
            if(!$_is_file){
                foreach ($_rules as $rule) {
                    if (is_string($rule)) {
                        $this->validateKey(
                            $key,
                            $rule,
                            null,
                            $rule['cust_key'] ?? null
                        );
                    } else {
                        $rule_value = $rule['rule'];
                        $error_message = $rule['error_msg'];
                        $this->validateKey(
                            $key,
                            $rule_value,
                            $error_message,
                            $rule['cust_key'] ?? null,
                            $rule['value'] ?? null
                        );
                    }
                }
            }else{
                $this->validateFiles($key,$_rules);
            }
        }
        return $this;
    }


    private function validateKey($key, $rule, $error_message, $cust_key = null,$rule_value = null)
    {
        $value = &$this->values[$key];
        $value = "$value";

        if (is_int($rule) && strlen($value) > 0) {
            if (!filter_var($value, $rule)) {
                $this->addError($key, $error_message ?? "{$key}'s value does not pass FILTER rule");
            }
            return;
        }elseif ($rule == 'required') {
            if (strlen($value) < 1) {
                $this->addError($key, $error_message ?? "{$key} is required");
            }
            return;
        }elseif ($rule == 'exists' && strlen($value) > 0) {
            if ($this->model instanceof Model) {
                $count = $this->model->where([$cust_key ?? $key => $value])
                    ->count();
                if ($count < 1) {
                    $this->addError($key, $error_message ?? "{$key} does not exist");
                }
            }
            return;
        }elseif ($rule == 'unique' && strlen($value) > 0) {
            if ($this->model instanceof Model) {
                $count = $this->model->where([$key => $value])
                    ->count();
                if ($count > 0) {
                    $this->addError($key, $error_message ?? "{$key} must be unique");
                }
            }
            return;
        }elseif ($rule == 'max' && strlen($value) > 0) {
            $max_value = $rule_value;
            if (!$max_value || !is_numeric($max_value)) {
                throw new Exceptions\Validator("max value for \"max length\" expects Integer");
            }
            if (strlen($value) > (int)$max_value) {
                $this->addError($key, $error_message ?? "{$key}'s value length must be greater than {$max_value} ");
            }
            return;
        }elseif ($rule == 'min' && strlen($value) > 0) {
            $min_value = $rule_value;
            if (!$min_value || !is_numeric($min_value)) {
                throw new Exceptions\Validator("min value for \"min length\" expects Integer got String");
            }
            if (is_numeric($value)) {
                if ((int)$value < ($min_value)) {
                    $this->addError($key, $error_message ?? "{$key}'s value must not be less than {$min_value} ");
                }
            } else {
                if (strlen($value) < (int)$min_value) {
                    $this->addError($key, $error_message ?? "{$key}'s value length must not be less than {$min_value} ");
                }
            }
            return;
        }elseif (preg_match("/$this->php_filter_validate/i", $rule) && strlen($value) > 0) {
            if (!filter_var($value, constant($rule))) {
                $this->addError($key, $error_message ?? "{$key}'s value does not pass {$rule} ");
            }
            return;
        }elseif ($rule == 'regex' && strlen($value) > 0) {
            $regex = $rule_value;
            if (!preg_match("/{$regex}/", $value)) {
                $this->addError($key, $error_message ?? "{$key}'s value does not match regex: {$regex} ");
            }
            return;
        }elseif ($rule == 'equals' && strlen($value) > 0) {
            if(!in_array($rule_value,$this->keys)){
                if ($value != $rule_value) {
                    $this->addError($key, $error_message ?? "{$key} does not match $rule_value ");
                }
            }else{
                if ($value != $this->values[$rule_value]) {
                    $this->addError($key, $error_message ?? "{$key} does not match $rule_value ");
                }
            }

            return;
        }elseif($rule == 'custom'){
            $call = call_user_func($rule_value,$value);
            if(!$call){
                $this->addError($key, $error_message ?? "CUSTOM validator returns an error");
            }elseif (is_string($call)){
                $this->addError($key, $call);
            }
        }
    }

    private function validateFiles($key, $rules){
        $files = $this->getFiles($key);

        foreach ($rules as $rule){
            $rule_value = $rule['rule'];
            $error_message = $rule['error_msg'];
            if($rule_value === 'required' && !$files){
                $this->addError($key, $error_message ?? "File {$key} required");
                return;
            }
        }
//        Loop through the files
        foreach ($files as $file){
            $this->validateFile($key,$file,$rules);
        }

    }

    private function validateFile($key,$file, $rules){

        $real_file_name  = $file["name"] ?? null;
        $file_name       = $file["name"] ?? null;
        $file_type       = $file["type"] ?? null;
        $file_size       = $file["size"] ?? null;
        $file_tmp_name   = $file["tmp_name"] ?? null;
        $ext = pathinfo($file_name, PATHINFO_EXTENSION) ?? null;


        foreach ($rules as $rule){
            $rule_name = $rule['rule'];
            $error_msg = $rule['error_msg'] ?? null;
            $rule_value = $rule['value'] ?? null;

            if ($rule_name == 'required' && !$file){
                $this->addError($key, $error_msg ?? "File \"{$key}\" not sent ");
                return;
            }elseif($rule_name == 'custom'){
                $call = call_user_func($rule_value,$file);
                if(!$call){
                    $this->addError($key, $error_msg ?? "CUSTOM validator returns an error");
                }
            }elseif($rule_name == 'max_file_size'){
                if ($file_size > $rule_value) {
                    $size_in_mb = $rule_value / (1024 * 1024);
                    $this->addError($key, $error_msg ?? "file size exceeds {$size_in_mb}mb");
                }
            }elseif($rule_name == 'min_file_size'){
                if ($file_size < $rule_value) {
                    $size_in_mb = $rule_value / (1024 * 1024);
                    $this->addError($key, $error_msg ?? "file size less than {$size_in_mb}mb");
                }
            }elseif($rule_name == 'allowed_file_exts'){
                if (!in_array($ext,$rule_value)) {
                    $this->addError($key, $error_msg ?? "Invalid extension \"$ext\" ");
                }
            }

            if($rule_name == 'allowed_mime_types'){
                if (!in_array($file_type,$rule_value)) {
                    $this->addError($key, $error_msg ?? "Invalid mime type \"$file_type\" ");
                }
            }

        }

        //            validate extension
    }




    private function addError($column, $msg, $identifier = null)
    {
        $this->errors[$column][] = [
            "msg"   => $msg
        ];
    }

    /**
     * @return bool
     */
    public function hasError(): bool
    {
        if (!$this->errors)
            return false;

        return count($this->errors) > 0;
    }


    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /*
     * Helper functions here
     * */

    /**
     * @param null $error_message
     * @return array
     */
    public static function UNIQUE(
        $error_message = null
    ) {
        return [
            "rule" => "unique",
            "error_msg" => $error_message
        ];
    }

    /**
     * @param null $error_message
     * @param $key
     * @return array
     */
    public static function EXISTS(
        $error_message = null,
        $key = null
    ) {
        return [
            "rule" => "exists",
            "cust_key" => $key,
            "error_msg" => $error_message
        ];
    }

    public static function REQUIRED(
        $error_message = null
    ) {
        return [
            "rule" => "required",
            "error_msg" => $error_message
        ];
    }

    /**
     * @param int $length
     * @param null $error_message
     * @return array
     */
    public static function MAX(
        int $length,
        $error_message = null
    ) {
        return [
            "rule" => "max:{$length}",
            "error_msg" => $error_message
        ];
    }

    /**
     * @param int $size
     * @param null $error_message
     * @return array
     */
    public static function MAX_FILE_SIZE(
        int $size,
        $error_message = null
    ) {
        return [
            "rule" => "max_file_size",
            "value" => $size,
            "error_msg" => $error_message
        ];
    }


    /**
     * @param int $size
     * @param null $error_message
     * @return array
     */
    public static function MIN_FILE_SIZE(
        int $size,
        $error_message = null
    ) {
        return [
            "rule" => "min_file_size",
            "value" => $size,
            "error_msg" => $error_message
        ];
    }


    /**
     * @param array $exts
     * @param null $error_message
     * @return array
     */
    public static function ALLOWED_FILE_EXTS(
        array $exts,
        $error_message = null
    ) {
        return [
            "rule" => "allowed_file_exts",
            "value" => $exts,
            "error_msg" => $error_message
        ];
    }


    /**
     * @param array $mimes
     * @param null $error_message
     * @return array
     */
    public static function ALLOWED_MIME_TYPES(
        array $mimes,
        $error_message = null
    ) {
        return [
            "rule" => "allowed_mime_types",
            "value" => $mimes,
            "error_msg" => $error_message
        ];
    }

    /**
     * @param int $length
     * @param null $error_message
     * @return array
     */
    public static function MIN(
        int $length,
        $error_message = null
    ) {
        return [
            "rule" => "min",
            "value" => $length,
            "error_msg" => $error_message
        ];
    }

    /**
     * @param int $rule
     * @param null $error_message
     * @return array
     */
    public static function FILTER(
        int $rule,
        $error_message = null
    ) {
        return [
            "rule" => $rule,
            "error_msg" => $error_message
        ];
    }

    /**
     * @param $regex_rule
     * @param null $error_message
     * @return array
     */
    public static function REGEX(
        $regex_rule,
        $error_message = null
    ) {
        return [
            "rule" => "regex",
            "value" => $regex_rule,
            "error_msg" => $error_message
        ];
    }

    /**
     * @param $key
     * @param null $error_message
     * @return array
     */
    public static function EQUALS(
        $key,
        $error_message = null
    ) {
        return [
            "rule" => "equals",
            "value" => $key,
            "error_msg" => $error_message
        ];
    }

    /**
     * @param Closure $closure
     * @param null $error_message
     * @return array
     */
    public static function CUSTOM(
        Closure $closure,
        $error_message = null
    ) {
        return [
            "rule" => "custom",
            "value" => $closure,
            "error_msg" => $error_message
        ];
    }

    public static function RULE($method,...$args){
        return self::{$method}(...$args);
    }


}
