<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/29/2018
 * @Time 12:52 PM
 * @Project macroware-vue
 */

namespace Path\Core\Misc;


use Path\Core\Database\Model;
use Path\Core\Http\Request;
use Path\Core\ValidatorException;

class OldValidator
{
    public $model;
    private $errors;
    private $values;

    private $regex_rule = "^regex\s*\:(.*)";
    private $max_value_rule = "^max\s*\:(.*)";
    private $min_value_rule = "^min\s*\:(.*)";
    private $equals_rule = "^equals\s*\:\s*@(.*)";
    private $php_filter_validate = "^FILTER_VALIDATE_";
    public function __construct(Model $model = null)
    {
        $this->model = $model;
    }

    /**
     * @param $values
     * @return $this
     */
    public function values($values)
    {
        $this->values = (array)$values;
        return $this;
    }

    private function addError($column, $msg, $identifier = null)
    {
        $this->errors[$column][] = [
            "msg"   => $msg,
            "ref"    => $identifier ?? "undefined"
        ];
    }

    private function checkErrors($column, $configs)
    {
        //       [
        //                "required",
        //                [
        //                    "rule"      => "max:200",
        //                    "error_msg" => "name must be more than 200 character"
        //                ],
        //                [
        //                    "rule"      => "regex:",
        //                    "error_msg" => "name must be more than 200 character"
        //                ],
        //                "regex:[a-z]"
        //        ];
        if (is_array($configs) && !isset($configs[1]))
            $configs = [$configs];

        if (is_string($configs))
            $configs = [$configs];
        foreach ($configs as $config) {
            if (!is_array($config)) {
                $_rule = $config;
                $_error_msg = null;
                $_id = null;
            } else {
                $_rule = trim($config['rule']);
                $_error_msg = @$config['error_msg'] ?? null;
                $_id = @$config['ref'] ?? null;
            }
            $value = trim(@$this->values[$column]);

            if ($_rule == "required" && strlen($value) < 1) {
                $this->addError($column, $_error_msg ?? "{$column} is required", $_id);
            }

            if ($_rule == "unique") {
                if ($this->model) {
                    $count = $this->model->where([$column => $value])
                        ->count();
                    if ($count > 0) {
                        $this->addError($column, $_error_msg ?? "{$column} must be unique", $_id);
                    }
                }
            }
            if ($_rule == "exists") {
                if ($this->model) {
                    $count = $this->model->where([$column => $value])
                        ->count();

                    if ($count < 1) {
                        $this->addError($column, $_error_msg ?? "{$column} does not exist", $_id);
                    }
                }
            }

            if (preg_match("/{$this->max_value_rule}/i", $_rule, $max_val_match)) {
                $max_value = @$max_val_match[1];
                if (!$max_value || !is_numeric($max_value)) {
                    throw new ValidatorException("max value for \"{$column}\" expects Integer got String");
                }
                if (strlen($value) > (int)$max_value) {
                    $this->addError($column, $_error_msg ?? "{$column}'s value length must be greater than {$max_value} ", $_id);
                }
            }

            if (preg_match("/$this->min_value_rule/i", $_rule, $min_val_match)) {
                $min_value = @$min_val_match[1];
                if (!$min_value || !is_numeric($min_value)) {
                    throw new ValidatorException("max value for \"{$column}\" expects Integer got String");
                }
                if (is_numeric($value)) {
                    if ((int)$value < ($min_value)) {
                        $this->addError($column, $_error_msg ?? "{$column}'s  must not be less than {$min_value} ", $_id);
                    }
                } else {
                    if (strlen($value) < (int)$min_value) {
                        $this->addError($column, $_error_msg ?? "{$column}'s value length must not be less than {$min_value} ", $_id);
                    }
                }
            }
            if (preg_match("/$this->php_filter_validate/i", $_rule)) {
                if (!filter_var($value, constant($_rule))) {
                    $this->addError($column, $_error_msg ?? "{$column}'s does npt pass{$_rule} ", $_id);
                }
            }
            if (preg_match("/{$this->regex_rule}/i", $_rule, $regex_matches)) {
                $regex = $regex_matches[1];
                if (!preg_match("/{$regex}/", $value)) {
                    $this->addError($column, $_error_msg ?? "{$column} does not match regex: {$regex} ", $_id);
                }
            }

            if (preg_match("/{$this->equals_rule}/i", $_rule, $regex_matches)) {
                $key = $regex_matches[1];
                if ($value != $this->values[$key]) {
                    $this->addError($column, $_error_msg ?? "{$column} does not match $key ", $_id);
                }
            }
        }
    }

    public function validate(array $rules)
    {
        foreach ($rules as $column => $config) {
            $this->checkErrors($column, $config);
        }
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
}
