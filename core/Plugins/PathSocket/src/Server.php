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
    "core/Plugins/PathSocket/src/Client",
    "core/Classes/Watcher",
    "core/Classes/Http/Response"
);
class Server
{
    protected $host;
    protected $port;
    private   $server = null;
    protected $max_clients = null;
    protected $clients = [];
    protected $watching = [];//array of ids of clients being watched
    protected $pending_msg = [];
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
            $this->watchAllControllers();
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
//                    return;
                    if($bytes === false){
//                        there was an error getting client request payload
//                        $this->logLastError($socket);
                        continue;
                    }else{
//                        generate header for this socket based on buffer gotten
                        //                    check if client hasn't done handshake,
                        if(!$this->hasDoneHandShake($socket_id)){
//                            check if client is done sending header,
                            if($this->isDoneBuffering($buffer)){
                                $this->generateHeaderForClient($socket_id,trim($buffer));

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
                            if($buffer){
                                $this->processInput($socket_id,$buffer,$bytes);
                            }else{
                                $this->removeClient($socket_id);
                            }
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

    private function getPayloadoffset($headers){
        $offset = 2;
        if ($headers['hasmask']) {
            $offset += 4;
        }
        if ($headers['length'] > 65535) {
            $offset += 8;
        } elseif ($headers['length'] > 125) {
            $offset += 2;
        }
        return $offset;
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
//        get message, since no action taken
        $this->readStream($client_id,$input,$size);

    }

    private function readStream($client_id,$packet,$size){
        $client = $this->clients[$client_id];
//        check if user has a buffer on pending
        if($client->partialBuffer){
//            concat it with the new packet of buffer
            $packet = $client->partialBuffer . $packet;
            $size = strlen($packet);
        }
        $frame_position = 0;
        $frame_id = 1;
        $full_packet = $packet;
        while($frame_position < $size){
            $headers = $this->getPayloadHeader($packet);
            $buffer_start_offset = $this->getPayloadoffset($headers);
            $frame_size = $headers["length"]+$buffer_start_offset;
//            split frame from packet and process
            $frame = substr($full_packet,$frame_position,$frame_size);
//            unmask the current frame

            $frame_position+=$frame_size;
            $packet = substr($full_packet,$frame_position);
            $frame_id++;

            $message = $this->processBuffer($client_id,$frame);
            if(!is_null($message)){
                if ((preg_match('//u', $message)) || ($headers['opcode']==2)) {
//                    convert message to json
                    $this->processClientMessage($client_id,$message);
                }
            }
        }

    }

    private function processClientMessage($client_id,$message){
        $client = &$this->clients[$client_id];
        if(!$data = json_decode($message,true)){
            $this->clientMessageWatcher($client_id,$message);
        }else{
            if($data['type'] == "navigate"){
//                navigate instead
                $this->navigateWatcher($client_id,$data['params'],$data['data']);
            }elseif ($data['type'] == "message"){
                $this->clientMessageWatcher($client_id,$data['data']);
            }
        }
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
                $this->checkClientWatcher($client_id);
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

    private function watchAllControllers(){
        foreach ($this->watching as $client_id => $is_watching){
            $client = @$this->clients[$client_id];
            $watcher = $client->watching;
            if($watcher instanceof Watcher){
                $watcher->watch();
                if($watcher->changesOccurred() AND is_null($watcher->error)){
//                       check if returned data from watcher is an instance of Response
//                        $watcher->getResponse();
                    $this->sendMsg($client_id,json_encode($watcher->getResponse()));
                }

            }
        }
    }

    private function initiateLiveWatcher($client_id){
        $client = $this->clients[$client_id];
        if(!$client->watching instanceof Watcher){
            $url = $client->headers['get'];
            $key = $client->headers['sec-websocket-key'];
            $session_id = $client->cookies["PHPSESSID"];
            $this->logText("Session ID: ".$session_id);

            $client->watching = new Watcher(
                $url,
                $session_id
            );
            $client->watching->socket_key = $key;
//            $client->watching->session_id = "session-id";
            $this->watching[$client_id] = true;
        }
    }
    private function fetchCookies($rawCookies){
        $cookies = explode(";",$rawCookies);
        $res = [];
        foreach ($cookies as $cookie){
            preg_match("/([^=]+)=([^=]+)/i",$cookie,$matches);
            if($matches[1] && $matches[2]){
                $key = $matches[1];
                $val = $matches[2];
                $res[$key] = $val;
            }
        }
        return $res;
    }

    protected function checkClientWatcher($client_id){
        $client = &$this->clients[$client_id];

        if($client->watching){
            $watching = &$client->watching;
            if($watching instanceof Watcher) {
                $watching->watch();
                if(!$watching->error){
                    if($watching->changesOccurred()){
                        $this->sendMsg($client_id,json_encode($watching->getResponse("check-watcher")));
                    }
                }
            }

        }
    }

    private function clientMessageWatcher($client_id, $message){
        if(isset($this->clients[$client_id])){
            $client = &$this->clients[$client_id];
            $watching = &$client->watching;
            if($watching instanceof Watcher) {
                $watching->sendMessage($message);
                if(!$watching->error){
                    if($watching->changesOccurred()){
//                        send response to client
                        $response = json_encode($watching->getResponse("sending-message"));
                        $this->sendMsg($client_id,$response);
                    }
                }
            }

        }
    }

    private function navigateWatcher($client_id, $params, $message = null){
        if(isset($this->clients[$client_id])){
            $client = &$this->clients[$client_id];
            $watching = &$client->watching;
            if($watching instanceof Watcher) {
                $watching->navigate($params,$message);
                if(!$watching->error){
                    if($watching->changesOccurred()){
//                        send response to client
                        $response = json_encode($watching->getResponse("sending-message"));
                        $this->sendMsg($client_id,$response);
                    }
                }
            }

        }
    }



    private function processBuffer($client_id, $buffer):?string {

        $payload_headers = $this->getPayloadHeader($buffer);
//        take action using the header
        $payload = $this->decodePayload($payload_headers,$this->getPayloadContent($buffer,$payload_headers));
        $payload_size = strlen($payload);
//        check if total size of the buffer is bigger than the one already sent from browser
        if($payload_headers['length'] > $payload_size){
            $this->clients[$client_id]->partialBuffer = $buffer;
            return null;
        }
        if ($payload_headers['fin']) {
            return $payload;
        }
        return null;
    }

    private function decodePayload($headers, $payload){
        $effectiveMask = "";
        if ($headers['hasmask']) {
            $mask = $headers['mask'];
        }
        else {
            return $payload;
        }
        while (strlen($effectiveMask) < strlen($payload)) {
            $effectiveMask .= $mask;
        }
        while (strlen($effectiveMask) > strlen($payload)) {
            $effectiveMask = substr($effectiveMask,0,-1);
        }
        return $effectiveMask ^ $payload;
    }

    private function encodePayload(
        $client_id,
        $message,
        $message_type = "text",
        $continues = false
    ){
        $client = &$this->clients[$client_id];
        switch ($message_type) {
            case 'continuous':
                $b1 = 0;
                break;
            case 'text':
                $b1 = ($client->sendingContinues) ? 0 : 1;
                break;
            case 'binary':
                $b1 = ($client->sendingContinues) ? 0 : 2;
                break;
            case 'close':
                $b1 = 8;
                break;
            case 'ping':
                $b1 = 9;
                break;
            case 'pong':
                $b1 = 10;
                break;
        }

        if($continues){
            $client->sendingContinues = true;
        }else{
            $b1 += 128;
            $client->sendingContinues = false;
        }
        $size = strlen($message);
        $size_field = "";
        if ($size < 126) {
            $b2 = $size;
        }elseif($size < 65536){
            $b2 = 126;
            $hex_size= dechex($size);
            if (strlen($hex_size)%2 == 1) {
                $hex_size = '0' . $hex_size;
            }

            $n = strlen($hex_size) - 2;
            for ($i = $n; $i >= 0; $i=$i-2) {
                $size_field = chr(hexdec(substr($hex_size, $i, 2))) . $size_field;
            }
            while (strlen($size_field) < 2) {
                $size_field = chr(0) . $size_field;
            }
        }else{
            $b2 = 127;
            $hex_size = dechex($size);
            if (strlen($hex_size)%2 == 1) {
                $hex_size = '0' . $hex_size;
            }
            $n = strlen($hex_size) - 2;
            for ($i = $n; $i >= 0; $i=$i-2) {
                $size_field = chr(hexdec(substr($hex_size, $i, 2))) . $size_field;
            }
            while (strlen($size_field) < 8) {
                $size_field = chr(0) . $size_field;
            }
        }

        return chr($b1) . chr($b2) . $size_field . $message;

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
        $socket = &$this->clients[$client_id];
        $bytes = @socket_recv($socket->socket,$buffer,$this->buffer_size,0);
        return $bytes;
    }

    private function generateHeaderForClient(
        $client_id,
        $buffer
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
        $client = &$this->clients[$client_id];
//        if(isset($headers['cookie'])){
//            if(preg_match("/PHPSESSID=(.+)/", $headers['cookie'], $matches)){
//                $headers["PHPSESSID"] = $matches[1];
//            }else{
//                $headers["PHPSESSID"] = null;
//            }
//        }else{
//            $headers["PHPSESSID"] = null;
//        }

        $client->headers = $headers;
        $client->cookies = $this->fetchCookies($headers['cookie']);

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
        $client = &$this->clients[$client_id];
        $client_key = $client->headers['sec-websocket-key'];

        $key = $this->genResponseSocketKey($client_key);
        $headers = "HTTP/1.1 101 Switching Protocols\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n";
        $headers .= "Sec-WebSocket-Accept: $key\r\n\r\n";

        if($this->writeResponse($client->socket,$headers))
            $client->has_done_handshake = true;
        else
            return false;

        return true;
    }

    private function sendMsg($client_id,$text){
        $client = &$this->clients[$client_id];
        if(!$this->hasDoneHandShake($client_id)){
            $this->pending_msg[$client_id] = $text;
            return;
        }else{
            $text = $this->encodePayload($client_id,$text);
        }
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
        if($this->clients[$client_id]->watching){
            $this->clients[$client_id]->watching->clearCache();
            $this->logText("...Cleared Caches");
        }

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
//        ob_flush();
    }

}
