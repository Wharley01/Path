<?php


namespace Path\Core\File;


use Path\Core\File\Types\Type;

interface Writer
{
    public function write(string $path,Type $content,?string $file_name = null): bool;
}