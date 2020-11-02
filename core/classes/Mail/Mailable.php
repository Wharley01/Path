<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 4/18/2019
 * @Time 5:02 AM
 * @Project path
 */

namespace Path\Core\Mail;


 abstract class Mailable
{
    protected $template = "";
    protected $title = "";
    public function title($state):?String{
        return $this->title;
    }

    public function template($state):?String{
        return $this->template;
    }

     public function footer($states):string {
         return "";
     }


     public function header($states):string {
         return "";
     }


 }
