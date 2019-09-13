<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 11/23/2018
 * @Time 4:42 PM
 * @Project Path
 */

namespace Path\Core\Misc;



use Path\Core\Http\Request;

class FileSys
{
    private $file_name;
    private $files;
    private $file_paths;
    private $errors         = [];
    private $rules = [
        "retain_name"       => false,
        "accepted_exts"     => [],
        "restricted_exts"   => [],
        "accepted_types"    => [],
        "restricted_types"  => [],
        "max_size" => 0
    ]; //accepted_exts,accepted_types,max_size(bytes),retain_name
    private $write_folder = "/";
    public function __construct()
    {
        $this->rules["max_size"] = config("FILE->max_size");
    }
    /**
     * File file.
     * @param $file
     * @return $this
     * @throws FileSystemException
     */
    public function file($file)
    {

        if ($file instanceof Request) {
            $file = @$_FILES[$file->fetching];
            $this->files = is_array($file['name']) ? $this->restructure($file) : [$file];
        } else {
            $this->file_paths = $file;
        }
        return $this;
    }

    /**
     * @param array $rules
     * @return FileSys
     */
    public function setRules(array $rules = []): FileSys
    {
        $this->rules = array_merge($this->rules, $rules);
        return $this;
    }

    private function restructure($file)
    {
        $file_ary = array();
        $file_count = count($file['name']);
        $file_key = array_keys($file);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_key as $val) {
                $file_ary[$i][$val] = $file[$val][$i];
            }
        }
        return $file_ary;
    }

    /**
     * @param $target_folder
     * @param callable $callback
     * @return FileSys
     */
    public function moveUploadedTo($target_folder = null, callable $callback = null)
    {
        $target_folder = $target_folder ?? $this->write_folder;
        //        var_dump($this->files);
        if (is_array($this->files)) {
            for ($i = 0; $i < count($this->files); $i++) {
                $real_file_name  = $this->files[$i]["name"];
                $file_name       = $this->files[$i]["name"];
                $file_type       = $this->files[$i]["type"];
                $file_size       = $this->files[$i]["size"];
                $file_tmp_name   = $this->files[$i]["tmp_name"];
                $ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $file_name = @$this->rules["retain_name"] ? urlencode($file_name) : substr(md5($file_name . (time() + rand(10, 50))), 0, 50) . "." . $ext;
                //            validate extension
                $this->files[$i]['saved_name']      = $file_name;
                $this->files[$i]['saved_fullname']  = $target_folder . $file_name;

                if (count($this->rules["accepted_exts"]) > 0) {
//                    var_dump($this->rules["accepted_exts"]);
                    if (!in_array($ext, $this->rules["accepted_exts"])) {
                        $this->errors[$real_file_name][] = "Invalid Extension {$ext}";
                    }
                }

                if (count($this->rules["restricted_exts"]) > 0) {
                    if (in_array($ext, $this->rules["restricted_exts"])) {
                        $this->errors[$real_file_name][] = "Invalid Extension {$ext}";
                    }
                }

                //            check for file type(MiME)
                if (@$this->rules["accepted_types"]) {
                    if (!in_array($file_type, $this->rules["accepted_types"])) {
                        $this->errors[$real_file_name][] = "Invalid File Type {$file_type}";
                    }
                }

                if (@$this->rules["restricted_types"]) {
                    if (in_array($file_type, $this->rules["restricted_types"])) {
                        $this->errors[$real_file_name][] = "Invalid File type {$file_type}";
                    }
                }
                //            check if folder exists
                if (!file_exists($target_folder)) {
                    $this->errors[$real_file_name][] = "target folder {$target_folder} does not exist";
                }
                //            check if file exist
                if (file_exists($target_folder . $file_name)) {
                    $this->errors[$real_file_name][] = "file {$file_name} already exists in  {$target_folder}";
                }
                //            check if size exceeds the threshold
                if ($file_size > $this->rules["max_size"]) {
                    $size_in_mb = $this->rules["max_size"] / (1024 * 1024);
                    $this->errors[$real_file_name][] = "file size exceeds {$size_in_mb}mb";
                }
                //            check if there is no error\
                if (!isset($this->errors[$real_file_name])) {
                    if (!move_uploaded_file($file_tmp_name, $target_folder . $file_name)) {
                        $this->errors[$real_file_name][] = "There was an error while uploading {$real_file_name}";
                        $this->files[$i]["has_error"] = true;
                    } else {
                        $this->files[$i]["has_error"] = false;
                    }
                } else {
                    $this->files[$i]["has_error"] = true;
                }
                if (is_callable($callback)) {
                    $callback($this->files[$i], $this->errors[$real_file_name]);
                } else {
                    if (count($this->files) == 1) {
                        $this->files[$i];
                    }
                }
            }
        } else {
            //            this is file from server not sent from credentials

        }


        /** @var FileSys $this */
        return $this;
    }

    public function moveFileTo($target_folder = null, callable $callback = null)
    {
        //        TODO: implement file movement to
    }

    public function delete()
    {
        //        TODO: implement delete
    }

    public function renameTo($new_name)
    {
        //        TODO: implement rename of files
    }

    public function copyFileTo($target_folder)
    {
        //        TODO: implement file copying
    }

    /**
     * @return mixed
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return mixed
     */
    public function getFiles()
    {
        return $this->files;
    }

    public function hasError(): bool
    {
        if ($this->errors) {
            foreach ($this->errors as $error) {
                if (count($error) > 0) {
                    return true;
                }
            }
            return false;
        } else {
            return false;
        }
    }
}
