<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 5/14/2019
 * @Time 4:48 AM
 * @Project path
 */

namespace Path\Core\Misc;


use Path\Core\Database\Model;
use Path\Core\Error\Exceptions;

class Validator
{
    public $model;
    private  $errors;
    private $values;
    public  $rules = [];
    private $keys = [];
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

    public function key($name)
    {
        $this->keys[] = $name;
        $this->rules[$name] = [];
        return $this;
    }

    public function rules(...$rules)
    {
        $key = $this->keys[count($this->keys) - 1];
        $this->rules[$key] = array_merge($this->rules[$key], $rules);
        return $this;
    }

    public function validate(?Model $model = null)
    {
        foreach ($this->rules as $key => $rules) {

            foreach ($rules as $rule) {
                if (is_string($rule)) {
                    $this->validateKey($key, $rule, null,$rule['cust_key'] ?? null);
                } else {
                    $rule_value = $rule['rule'];
                    $error_message = $rule['error_msg'];
                    $this->validateKey($key, $rule_value, $error_message, $rule['cust_key'] ?? null);
                }
            }
        }
        return $this;
    }


    private function validateKey($key, $rule, $error_message, $cust_key = null)
    {
        $value = &$this->values[$key];
        if (is_int($rule)) {
            if (!filter_var($value, $rule)) {
                $this->addError($key, $error_message ?? "{$key}'s value does not pass FILTER rule");
            }
            return;
        }
        if ($rule == 'required') {
            if (strlen($value) < 1) {
                $this->addError($key, $error_message ?? "{$key} is required");
            }
            return;
        }
        if ($rule == 'exists') {
            if ($this->model instanceof Model) {
                $count = $this->model->where([$cust_key ?? $key => $value])
                    ->count();
                if ($count < 1) {
                    $this->addError($key, $error_message ?? "{$key} does not exist");
                }
            }
            return;
        }
        if ($rule == 'unique') {
            if ($this->model instanceof Model) {
                $count = $this->model->where([$key => $value])
                    ->count();
                if ($count > 0) {
                    $this->addError($key, $error_message ?? "{$key} must be unique");
                }
            }
            return;
        }
        //            check max value
        if (@preg_match("/{$this->max_value_rule}/", $rule, $max_val_match)) {
            $max_value = @$max_val_match[1];
            if (!$max_value || !is_numeric($max_value)) {
                throw new Exceptions\Validator("max value for \"max length\" expects Integer got String");
            }
            if (strlen($value) > (int)$max_value) {
                $this->addError($key, $error_message ?? "{$key}'s value length must be greater than {$max_value} ");
            }
            return;
        }
        //          check min value
        if (preg_match("/$this->min_value_rule/i", $rule, $min_val_match)) {
            $min_value = @$min_val_match[1];
            if (!$min_value || !is_numeric($min_value)) {
                throw new Exceptions\Validator("max value for \"min length\" expects Integer got String");
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
        }
        //          validate default validator
        if (preg_match("/$this->php_filter_validate/i", $rule)) {
            if (!filter_var($value, constant($rule))) {
                $this->addError($key, $error_message ?? "{$key}'s value does not pass {$rule} ");
            }
            return;
        }

        //        match regex

        if (preg_match("/{$this->regex_rule}/i", $rule, $regex_matches)) {
            $regex = $regex_matches[1];
            if (!preg_match("/{$regex}/", $value)) {
                $this->addError($key, $error_message ?? "{$key}'s value does not match regex: {$regex} ");
            }
            return;
        }

        //        match equality

        if (preg_match("/{$this->equals_rule}/i", $rule, $regex_matches)) {
            $_key = $regex_matches[1];
            if(!in_array($_key,$this->keys)){
                if ($value != $_key) {
                    $this->addError($key, $error_message ?? "{$key} does not match $_key ");
                }
            }else{
                if ($value != $this->values[$_key]) {
                    $this->addError($key, $error_message ?? "{$key} does not match $_key ");
                }
            }

            return;
        }
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
        if (is_null($this->errors) || !$this->errors)
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
     * @param int $length
     * @param null $error_message
     * @return array
     */
    public static function MIN(
        int $length,
        $error_message = null
    ) {
        return [
            "rule" => "min:{$length}",
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
            "rule" => "regex:$regex_rule",
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
            "rule" => "equals:@{$key}",
            "error_msg" => $error_message
        ];
    }
}
