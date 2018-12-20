<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/6/2018
 * @Time 1:59 AM
 * @Project Path
 */

namespace Path\Console;


abstract class CLInterface
{
    protected $name;
    protected $description;
    abstract protected function entry(object $argument);


}