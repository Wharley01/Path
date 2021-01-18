<?php

namespace Path\Core\File\Types;

class Raw implements Type
{
    public ?string $content = null;

    /**
     * @param string|null $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

}