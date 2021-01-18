<?php

namespace Path\Core\File\Types;

class Temp implements Type
{
    public ?string $path = null;

    /**
     * @param string|null $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

}