<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 4/18/2019
 * @Time 5:29 AM
 * @Project path
 */

namespace Path\Core\Mail;



class State
{
    private array $state = [];

    public function bind(array $state_array){
        $this->state = array_merge($this->state, $state_array);
        foreach ($this->state as $key=>$value){
            $this->{$key} = $value;
        }
    }

    public function allState(){
        return $this->state;
    }
}