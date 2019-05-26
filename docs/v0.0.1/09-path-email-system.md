## Path Email System

Path also made it possible to send emails with ease, either using the native mail() support or SMTP.

### The mailable Class

The first thing is creating your mail template/Mailable to let you reuse them, you can do so by running `php __path create email yourMailableName`, a code will be generated for you in `path/Mail/Mailables` folder, the file looks like this:

```php
<?php


namespace Path\App\Mail\Mailables;

use Path\Core\Mail\Mailable;
use Path\Core\Mail\State;


class TestMail extends Mailable
{

    /*
    * Change this recipient details or set dynamically
    */
    public $to = [
        "email" => "recipient@provider.com",
        "name"  => "Recipient name"
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
```

### Sending mail

Using the initially created Mailable file combined with `Path\Core\Mail\Sender` Class, you can send several emails with different states(data), the example below shows its usage:

```php
<?php

use Path\Core\Mail;
use Path\App\Mail\Mailables;

$mailer = new Mail\Sender(
                  Mailables\TestMail::class
               );//where TestMail is your mailable class

// you can bind a state to your mailable this way
$mailer->bindState([
        "name" => "Testing Testing"
    ]);//TestMail has access to name property in the 'Path\Core\Mail\State $state' method argument
$mailer->send();//sends the email,

```

#### Other available Sender methods

There are other useful methods can be used with the Mail\Sender, they are listed below:

1. `setFrom(array $from)` sets the mail's sender's details, if this is not set, it will use the Mailable's `$to` property or fallback to Admin details in the config(`path/project.pconf.json`).

2. `setTo(array $to)` sets the details(email and name) of whom to send this particular mail to, is this method is not called, it uses the Mailable's `$to`  property

2. `hasError():bool` returns true if there was an error

3. `getTo():array` returns array of details of the recipient.

4. `getFrom():array` returns an array of details of the sender details.

### Mailer Configuration

What helps Path decide which method to use in sending emails are configured in `path/project.pconf.json`, below is how it looks like:

```json
  "MAILER":{
    "USE_SMTP": false,
    "SMTP":{
      "host":"",
      "username":"",
      "password":"",
      "port": 0,
      "protocol":"",
      "charset":"UTF-8"
    },
    "ADMIN_INFO":{
      "email":"admin@__path.com",
      "name": "Path Admin"
    }
  },
```

___
If `USE_SMTP` is set to `true`, Path uses the SMTP configuration beneath it, this config also has the default admin info, this will be used when `from` email details are not specified(sort of fallback details). 
___