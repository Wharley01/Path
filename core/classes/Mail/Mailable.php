<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 4/18/2019
 * @Time 5:02 AM
 * @Project path
 */

namespace Path\Core\Mail;


use Path\Core\Database\Model;

abstract class Mailable
{

    abstract public function title(State $state):String;

    abstract public function template(State $state):String;

}