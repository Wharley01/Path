<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/21/2019
 * @Time 9:45 PM
 * @Project Path
 */

namespace Path\Plugins\PathSocket;
use Path\Http\Watcher;
use Path\Plugins\PathSocket\Client;
import(
    "core/Plugins/PathSocket/src/Client"
);
class Server
{
    protected $host;
    protected $port;
    private   $server = null;
    protected $max_clients = null;
    protected $clients = [];
    protected $watching = [];//array of ids of clients being watched
    protected $buffer_size = 1024;
    protected $delay = 1;
    public function __construct()
    {
        $this->iniServer();
        $this->listenForNewClient();
    }

    private function listenForNewClient(){
        $this->logText("Started listening for new client connection");
        do{
            $reads = $this->getClients();
            $writes = null;
            $excepts = null;
//            start watching all active watchers
            $total_changes = socket_select(
                $reads,
                $writes,
                $excepts,
                $this->delay
            );

            foreach ($reads as $socket_id => $socket){
                if($socket == $this->server){
// current iteration is a new connection from a client!
                    $client = $this->acceptClient($socket_id);
                    if($client AND $client > 0){
                        $this->addNewClient($client);
                        $this->logText("Client {$client} Accepted");

                    }

                }else{
//                    current iteration is an existing socket that has an action to do

//                    get client's request payload
                    $bytes = $this->getClientRequest($socket_id,$buffer);
                    if($bytes === false){
//                        there was an error getting client request payload
                        $this->logLastError($socket);
                    }else{
//                        generate header for this socket based on buffer gotten
 //                    check if client hasn't done handshake,
                        if(!$this->hasDoneHandShake($socket_id)){
//                            check if client is done sending header,
                            if($this->isDoneBuffering($buffer)){
                                $this->generateHeaderForClient($socket_id,$buffer);
//                                initiate current client live server
                                $this->initiateLiveWatcher($socket_id);
                                //                            do handshake
                                $this->doHandShake($socket_id);
                                $this->logText("Done handshake");
                            }else{
//                                start all over again, buffering isn't done
                                $this->logText("start all over again, buffering isn't done");
                                continue;
                            }
                        }else{
//                            has done handshake, that means there a new command from user
                            $this->processInput($socket_id,$buffer,$bytes);

                                $this->logText("buffer received: {$buffer}");
                        }
                    }

                }
            }
            sleep(1);

        }while(true);
    }

    private function getPayloadContent($buffer,$headers){
        $offset = 2;
        if ($headers['hasmask']) {
            $offset += 4;
        }
        if ($headers['length'] > 65535) {
            $offset += 8;
        }
        elseif ($headers['length'] > 125) {
            $offset += 2;
        }
        return substr($buffer,$offset);
    }

    private function getPayloadHeader($buffer){
        $header = array(
            'fin'     => $buffer[0] & chr(128),
            'rsv1'    => $buffer[0] & chr(64),
            'rsv2'    => $buffer[0] & chr(32),
            'rsv3'    => $buffer[0] & chr(16),
            'opcode'  => ord($buffer[0]) & 15,
            'hasmask' => $buffer[1] & chr(128),
            'length'  => 0,
            'mask'    => ""
        );
        $header['length'] = (
            ord(
                $buffer[1]
            ) >= 128
        ) ? ord(
            $buffer[1]
            ) - 128 : ord(
                $buffer[1]
        );


        if ($header['length'] == 126) {
            if ($header['hasmask']) {
                $header['mask'] = $buffer[4] . $buffer[5] . $buffer[6] . $buffer[7];
            }
            $header['length'] = ord($buffer[2]) * 256
                + ord($buffer[3]);
        }
        elseif ($header['length'] == 127) {
            if ($header['hasmask']) {
                $header['mask'] = $buffer[10] . $buffer[11] . $buffer[12] . $buffer[13];
            }
            $header['length'] = ord($buffer[2]) * 65536 * 65536 * 65536 * 256
                + ord($buffer[3]) * 65536 * 65536 * 65536
                + ord($buffer[4]) * 65536 * 65536 * 256
                + ord($buffer[5]) * 65536 * 65536
                + ord($buffer[6]) * 65536 * 256
                + ord($buffer[7]) * 65536
                + ord($buffer[8]) * 256
                + ord($buffer[9]);
        }
        elseif ($header['hasmask']) {
            $header['mask'] = $buffer[2] . $buffer[3] . $buffer[4] . $buffer[5];
        }

        return $header;
    }

    private function getASCII($input,$size){
        $ascii = [];
        for($i = 0; $i < $size;$i ++){
            $ascii[] = /*decbin(*/ord($input[$i])/*)*/;
        }
        return $ascii;
    }

    private function processInput($client_id,$input,$size){
        $payload_header = $this->getPayloadHeader($input);
        if($this->takeAction($client_id,$payload_header)){
//            action already taken, not necessary to proceed
            return;
        }

//        continue code execution

        $this->sendMsg(
            $client_id,
            chr(129)
            . chr(strlen("hello world"))
            . "hello world");
    }

    private function takeAction(
        $client_id,
        $payload_headers
    ){
        switch($payload_headers['opcode']) {
            case 0:
            case 1:
            case 2:
                break;
            case 8:
                // todo: close the connection
               $this->removeClient($client_id);
                return true;
            case 9:
                $this->checkWatcher($client_id);
                return true;
            case 10:
                break;
            default:
                $this->removeClient($client_id);
                return true;

                break;
        }
        return false;

    }

    private function initiateLiveWatcher($client_id){
            $client = $this->clients[$client_id];
        if(!$client->watching instanceof Watcher){
            $url = $client->headers['get'];
            $client->watching = new Watcher($url);
            $this->watching[$client_id] = true;
        }
    }

    protected function checkWatcher($client_id){
            if(isset($this->clients[$client_id]->watching)){
                $watching = $this->clients[$client_id]->watching;
                if($watching instanceof Watcher) {
                    $watching->watch();
                    if(!$watching->error){
                        if($watching->has_changes){
                            $this->logText(json_encode($watching->getResponse()));
                        }
                    }
                }

            }

    }

    private function unMask($buffer):?array{
        $payload_headers = $this->getPayloadHeader($buffer);
//        take action using the header

    }

    private function genResponseSocketKey($request_socket_key){
        $key = base64_encode(pack(
            'H*',
            sha1($request_socket_key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')
        ));
        return $key;
    }

    private function isDoneBuffering(&$buffer){
        $tmp = str_replace("\r", '', $buffer);
        return (strpos($tmp, "\n\n") !== false );
    }

    private function getClientRequest($client_id, &$buffer){
        $socket = $this->clients[$client_id]->socket;
        $bytes = socket_recv($socket,$buffer,$this->buffer_size,0);
        return $bytes;
    }

    private function generateHeaderForClient(
        $client_id,
        &$buffer
    ){
        $headers = array();
        $lines = explode("\n",$buffer);
        foreach ($lines as $line) {
            if (strpos($line,":") !== false) {
                $header = explode(":",$line,2);
                $headers[strtolower(trim($header[0]))] = trim($header[1]);
            }
            elseif (stripos($line,"get ") !== false) {
                preg_match("/GET (.*) HTTP/i", $buffer, $reqResource);
                $headers['get'] = trim($reqResource[1]);
            }
        }
        $this->clients[$client_id]->headers = $headers;
    }


    /**
     * @param $socket_id
     * @return bool|resource
     */
    private function acceptClient($socket_id){
        $socket = $this->clients[$socket_id]->socket;
        $client = socket_accept($socket);
        if($client === false){
            $this->logLastError($this->clients[$socket_id]->socket);
            return false;
        }else{
            return $client;
        }
    }

    private function hasDoneHandShake($socket_id){
        if(!isset($this->clients[$socket_id]))
            return false;

        return $this->clients[$socket_id]->has_done_handshake == true;
    }

    private function doHandShake($client_id){
//        TODO: validate client request header
        $client = $this->clients[$client_id];
        $client_key = $client->headers['sec-websocket-key'];

        $key = $this->genResponseSocketKey($client_key);
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";

        if($this->writeResponse($client->socket,$headers))
           $this->clients[$client_id]->has_done_handshake = true;
        else
            return false;

        return true;
    }

    private function sendMsg($client_id,$text){
        $client = $this->clients[$client_id];
        if(!$this->writeResponse($client->socket,$text))
            $this->logLastError();
    }

    private function writeResponse($client,$response){
        $write = socket_write($client,$response,strlen($response));
        if($write === false) $this->logLastError();
        return $write;
    }

    private function iniServer(){
//        if(is_null($this->server)){
            $server = socket_create(
                AF_INET,
                SOCK_STREAM,
                SOL_TCP
            ) or die("Can't create server");

            if($server === false){
                $this->logLastError();
                exit();
            }
            $this->logText("Server Created");

            socket_set_option(
                $server,
                SOL_SOCKET,
                SO_REUSEADDR,
                1
            ) or die("unable to set option");

            if(!socket_bind(
                $server,
                $this->host,
                $this->port
            )){
                $this->logLastError($server);
                exit();
            }

            $this->logText(
                "Created server bound with host:{$this->host} and port:{$this->port} "
            );


            socket_listen(
            $server,
            20
            );

            $this->server = $server;
            $this->logText("Server successfully started!");
            $this->addNewClient($this->server,"server");
            $this->logText("Added server socket to list of clients");

//        }
    }

    private function addNewClient(
        $socket,
        $type = "client"
    ){
        $client = new Client();
        $client->socket = $socket;
        $client->type = $type;
        $client->id = $this->getSocketId($socket);
        $client->is_closed = false;
        $client->watching = null;

        $this->clients[$client->id] = $client;
    }

    private function removeClient($client_id){
            $this->logText("{$this->clients[$client_id]->socket} Removed");
           unset($this->clients[$client_id]);
           unset($this->watching[$client_id]);
    }

    /**
     * @param $socket
     * @return int
     */
    private function getSocketId($socket){
            return intval($socket);
    }

    /**
     * @param $socket
     * @return bool
     */
    private function isServer($socket){
        $socket_id = $this->getSocketId($socket);
        return $this->clients[$socket_id]->socket == $this->server;
    }

    /**
     * @return array
     */
    public function getClients(): array
    {

        return array_map(function ($client){
            return $client->socket;
        },$this->clients);
    }
    private function logLastError($socket = null){
        $error_code = $socket ? socket_last_error($socket):socket_last_error();
        debug_print_backtrace();
        echo "[{$error_code}]: ".socket_strerror($error_code);
        ob_flush();
    }

    private function logText($text){
        echo PHP_EOL."[+] ".$text.PHP_EOL;
        ob_flush();
    }

}
