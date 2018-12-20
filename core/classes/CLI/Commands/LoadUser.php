<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 12/6/2018
 * @Time 3:42 AM
 * @Project Path
 */

namespace Path\Console;
load_class("Database/Models/User");

use Path\Model;

class LoadUser extends CLInterface
{
    public $name = "user";
    public $description = "This loads user from database accepts user ID as Argument ";
    public $arguments = [
        "delete" => [
            "desc"   => "This deletes user"
        ],
        "edit" => [
            "desc"   => "This edits user"
        ],
        "list" => [
            "desc"   => "This edits user"
        ]
    ];
    public function __construct()
    {
    }

    public function entry(object $argument)
    {
        $user = (new Model\User)
            ->identify($argument->list)
            ->all();
        var_dump($user);
        return $this;
    }

}