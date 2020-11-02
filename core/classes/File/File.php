<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 11/23/2018
 * @Time 4:42 PM
 * @Project Path
 */

namespace Path\Core\File;



use Path\Core\Error\Exceptions\FileSystem;
use Path\Core\Http\Request;

class File
{
    private $file_name;
    private $files = null;
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
    public function __construct($file)
    {
        $this->rules["max_size"] = config("FILE->max_size");
        if ($file instanceof Request) {
            $this->files = $file->fetching;
        } else {
            $this->file_paths = $file;
        }
        return $this;
    }
    /**
     * File file.
     * @param $file
     * @return $this
     * @throws FileSystemException
     */
    public static function file($file)
    {
        return new static($file);
    }


    /**
     * @param $target_folder
     * @param bool $retain_name
     * @param callable $callback
     * @return FileSys
     * @throws FileSystem
     */
    public function moveUploadedTo($target_folder = null, callable $callback = null,$retain_name = false):?File
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
                $file_name = $retain_name ? urlencode($file_name) : substr(md5($file_name . (time() + rand(10, 50))), 0, 50) . "." . $ext;
                //            validate extension
                $this->files[$i]['saved_name']      = $file_name;
                $this->files[$i]['saved_fullname']  = $target_folder . $file_name;

                //            check if folder exists
                if (!file_exists($target_folder)) {
                    throw new FileSystem("target folder {$target_folder} does not exist");
                }
                //            check if file exist
                if (file_exists($target_folder . $file_name)) {
                    throw new FileSystem("file {$file_name} already exists in  {$target_folder}");
                }

                //            check if there is no error\
                if (!move_uploaded_file($file_tmp_name, $target_folder . $file_name)) {
                    throw new FileSystem("There was an error while uploading {$real_file_name}");
                }

                if (is_callable($callback)) {
                    $callback($this->files[$i], $this->errors[$real_file_name] ?? null);
                }
            }
        } else {
            //            this is file from server not sent from credentials
            throw new FileSystem("File not sent from client");
        }
        return $this;
    }

    public function moveFileTo($target_folder)
    {
        $this->processFile(function ($path) use ($target_folder){
            $file_name = basename($path);
            if(!move_uploaded_file($path, $target_folder.'/'.$file_name))
                throw new FileSystem('Unable to move '.$path);
        });
    }
    public function moveURLFileTo($target_folder,$retain_name = true,$ext = null)
    {
        $saved_file_name = null;

        $this->processFile(function ($path) use ($target_folder,$retain_name,&$saved_file_name,$ext){
            $file_name = pathinfo($path, PATHINFO_BASENAME);
            $ext = $ext ? $ext : pathinfo($file_name, PATHINFO_EXTENSION);

            $file_name = $retain_name ? $file_name:substr(md5($file_name . (time() + rand(10, 50))), 0, 50) . "." . $ext;
            $full_path = $target_folder.'/'.$file_name;

            $request = new Request();
            $response = $request->get($path);

            if ($response->status != 200 ){
                throw new FileSystem('Unable to download: '. $path);
            }

            if(file_put_contents($full_path, $response->content) === false)
                throw new FileSystem('Unable to save ' . $path);

            $saved_file_name = $file_name;
        });
        return $saved_file_name;
    }

    private function processFile(callable $func){
        if(!$this->file_paths)
            throw new FileSystem('File path not specified');

        $paths = is_string($this->file_paths) ? [$this->file_paths] : $this->file_paths;

        foreach ($paths as $path){
            call_user_func($func, $path);
        }
    }

    public function delete()
    {
        $this->processFile(function ($path){
            if(!@unlink($path))
                throw new FileSystem('Unable to delete '.$path);
        });
    }

    public function renameTo($new_name)
    {
        $this->processFile(function ($path) use ($new_name){
            $dir_name = dirname($path);
            if(!move_uploaded_file($path, $dir_name.'/'.$new_name))
                throw new FileSystem('Unable to rename '.$path);
        });
    }

    public function copyFileTo($target_folder)
    {
        $this->processFile(function ($path) use ($target_folder){
           $file_name =  basename($path);
           if(!copy($path, $target_folder.'/'.$file_name)){
               throw new FileSystem('Unable to copy '. $path);
           }
        });
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

    public static function MbToBytes(float $mb_size){
        return (1024 * 1024) * $mb_size;
    }

    public function exists():bool
    {
        return ($this->file_paths && file_exists($this->file_paths));
    }

    public function getContent()
    {
        if($this->file_paths && file_exists($this->file_paths)){
            return file_get_contents($this->file_paths);
        }

        return null;
    }

    public function writeLn(string $message,$flag = null)
    {
        return file_put_contents($this->file_paths, PHP_EOL.$message, $flag);
    }

    public function write(string $message, $flag = null)
    {
        return file_put_contents($this->file_paths, $message, $flag);
    }


}
