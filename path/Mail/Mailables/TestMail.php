<?php

namespace Path\App\Mail\Mailables;

use Path\Core\Database\Model;
use Path\Core\Mail\Mailable;
use Path\Core\Mail\State;


class TestMail extends Mailable
{
    private $user;

    public $to = [
        "email" => "Test@wale.com",
        "name"  => "Adewale"
    ];

    /**
     * TestMail constructor.
     * @param State $state
     */
    public function __construct(State $state)
    {
    }

    public function title(State $state):String
    {
        return "this is the title";
    }

    public function template(State $state):String
    {
        return "Hello {$state->name}";
    }

}