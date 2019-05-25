<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 4/18/2019
 * @Time 4:54 AM
 * @Project path
 */

namespace Path\Core\Mail;

use Path\Core\Error\Exceptions;
use Path\Plugins\PHPMailer\PHPMailer;

class Sender
{
    private $mailable;
    private $mail_state;
    private $mail_to = null;
    private $mail_from = null;
    private $throw_exception = true;

    private $errors;

    public function __construct($mailable)
    {
        $this->mailable = $mailable;
        $this->mail_state = new State();
    }

    /**
     * @return mixed
     */
    public function getMailable():?Mailable
    {
        return new $this->mailable;
    }

    public function bindState(Array $array){
        foreach ($array as $property => $value){
            $this->mail_state->{$property} = $value;
        }
        return $this;
    }

    private function phpMailer():PHPMailer{
        $mail = new PHPMailer();

        $mail->SMTPDebug = 0;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = config("MAILER->SMTP->host");  // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = config("MAILER->SMTP->username");        // SMTP username
        $mail->Password = config("MAILER->SMTP->password");        // SMTP password
        $mail->SMTPSecure = config("MAILER->SMTP->protocol");      // Enable TLS encryption, `ssl` also accepted
        $mail->Port = config("MAILER->SMTP->port");                // TCP port to connect to
        $mail->CharSet = config("MAILER->SMTP->charset");

        $from = $this->getFrom();
        $recipient = $this->getTo();
        //Recipients
        $mail->setFrom($from["email"], $from["name"] ?? "");
        $mail->addAddress($recipient["email"]);     // Add a recipient
        $mail->addReplyTo($from["email"], $from["name"]);
        //Content
        $mail->isHTML(true);                                  // Set email format to HTML
        return $mail;
    }

    private function getNativeMailHeader():String{

        $from = $this->getFrom();
        $headers = "";
        $headers .= "From: {$from['name']} <{$from['email']}>" . PHP_EOL;
        $headers .= "MIME-Version: 1.0" . PHP_EOL;
        $headers .= "Content-Type: text/html; charset=UTF-8".PHP_EOL;
//        $headers .= "Bcc: ".$this->BCC.PHP_EOL;
        $headers .= 'X-Mailer: PHP/' . phpversion();

        return $headers;
    }

    /**
     * @param mixed $from
     */
    public function setFrom($from)
    {
        if(is_string($from)){
            $this->mail_from = [
                "email" => $from,
                "name"  => null
            ];
        }else{
            $this->mail_from = $from;
        }
    }

    /**
     * @param mixed $to
     */
    public function setTo($to)
    {
        if(is_string($to)){
            $this->mail_from = [
                "email" => $to,
                "name"  => null
            ];
        }else{
            $this->mail_to = $to;
        }
    }

    /**
     * @return array
     * @throws Exceptions\Mailer
     */
    public function getFrom()
    {
        $temp_from = $this->mailable->from ?? $this->mail_from ?? config("MAILER->ADMIN_INFO");

        if(!$temp_from)
            throw new Exceptions\Mailer("Specify Sender Email in ".get_class($this->mailable).", with setFrom() or in your project.pconf.json");
        $to = [];
        if(is_string($temp_from)){
            $to['email'] = $temp_from;
            $to['name'] = null;
        }else if(is_array($temp_from)){
            if(!isset($temp_from['email']) OR !strlen($temp_from['email']))
                throw new Exceptions\Mailer("Specify recipient Email as \"email\" key in ".get_class($this->mailable)."->to array");
        }
        return $temp_from;
    }

    /**
     * @return array
     * @throws Exceptions\Mailer
     */
    public function getTo()
    {
        $temp_to = $this->mailable->to ?? $this->mail_to;

        if(!$temp_to)
            throw new Exceptions\Mailer("Specify recipient Email in ".get_class($this->mailable));
        $to = [];
        if(is_string($temp_to)){
            $to['email'] = $temp_to;
            $to['name'] = null;
        }else if(is_array($temp_to)){
            if(!isset($temp_to['email']))
                throw new Exceptions\Mailer("Specify recipient Email as \"email\" key in ".get_class($this->mailable)."->to array");
        }
        return $temp_to;
    }

    public function send(){
        $mailable = $this->mailable;
        $this->mailable = new $mailable($this->mail_state);
        $mailable = $this->mailable;
        if($mailable instanceof Mailable){
            $template = $mailable->template($this->mail_state);
            $title = $mailable->title($this->mail_state);
            $this->sendMail($template,$title);

        }else{
            throw new Exceptions\Mailer("Invalid Mailable Class");
        }

    }

    private function sendMail($template,$title){

        $to = $this->getTo();
        $from = $this->getFrom();
        $should_use_smtp = config("MAILER->USE_SMTP");
        if($should_use_smtp){
            $mail = $this->phpMailer();
            $mail->Subject = $title;
            $mail->Body    = $template;
            $mail->AltBody = $template;
            if(!$mail->send()){
                $this->errors = $mail->ErrorInfo;
                return false;
            }else{
                return true;
            }
        }else{
            $headers = $this->getNativeMailHeader();
            if(!@mail($to['email'], $title, $template, $headers)){
                throw new \Exception("Unable to send email",1);
            }
            return true;
        }

    }

    public function hasError(){
        return !!$this->errors;
    }
    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }
}