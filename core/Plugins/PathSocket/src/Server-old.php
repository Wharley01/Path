<?php
/**
 * @Author by Sulaiman Adewale.
 * @Date 1/13/2019
 * @Time 11:33 AM
 * @Project Path
 */

namespace Path\Plugins\PathSocket;


use Path\Http\Watcher;
import("core/Classes/Watcher");
class ServerOld
{
    protected $host = "127.0.0.1";
    protected $port = 3330;
    private $socketServer;
    public  $clients = [];
    protected $maxBufferSize = 2048;
    protected $watching;
    private $error_response;
    private $sendingContinous = false;
    private $error_code_values = [
        102 => "ENETRESET    -- Network dropped connection because of reset",
        103 => "ECONNABORTED -- Software caused connection abort",
        104 => "ECONNRESET   -- Connection reset by peer",
        108 => "ESHUTDOWN    -- Cannot send after transport endpoint shutdown -- probably more of an error on our part, if we're trying to write after the socket is closed.  Probably not a critical error, though.",
        110 => "ETIMEDOUT    -- Connection timed out",
        111 => "ECONNREFUSED -- Connection refused -- We shouldn't see this one, since we're listening... Still not a critical error.",
        112 => "EHOSTDOWN    -- Host is down -- Again, we shouldn't see this, and again, not critical because it's just one connection and we still want to listen to/for others.",
        113 => "EHOSTUNREACH -- No route to host",
        121 => "EREMOTEIO    -- Rempte I/O error -- Their hard drive just blew up.",
        125 => "ECANCELED    -- Operation canceled"
                            ];
    private $heldMessages = [];

    /**
     * Server constructor.
     */
    public function __construct()
    {
//     create socket socket
        echo "starting file";
        $socket = $this->newSocketServer();

        $this->socketServer = $socket;
        //add incoming connection to list of sockets to watch for
        $this->addNewClient($this->socketServer,"server");
        self::log("Server started on socket #{$this->socketServer}");
        $this->listenToSockets();
    }

    private function newSocketServer(){
        $socket = socket_create(
            AF_INET,
            SOCK_STREAM,
            SOL_TCP
        ) or die("unable to create socket");


        socket_set_option(
            $socket,
            SOL_SOCKET,
            SO_REUSEADDR,
            1
        ) or die("unable to set option");
        self::log("Necessary Option Set");


        socket_bind(
            $socket,
            $this->host,
            $this->port
        ) or die("unable to bind socket");
        self::log("Server bond with Websocket Host and port");

        socket_listen($socket,20);

        self::log("Listening to: {$this->host} on Port: {$this->port}");

        return $socket;
    }




    private function getClientId($socket){
        return intval($socket);
    }

    private function addNewClient($client, $type = null){
        $socket_id = $this->getClientId($client);
        if(!isset($this->clients[$socket_id])){
            $this->clients[$socket_id]['socket'] = $client;
            $this->clients[$socket_id]['type'] = $type ?? "client";
            $this->clients[$socket_id]['has_handshake'] = false;
            $this->clients[$socket_id]['is_closed'] = false;
            $this->clients[$socket_id]['watcher'] = null;
            $this->clients[$socket_id]['response'] = null;
            $this->clients[$socket_id]['headers'] = [];

        }
    }

    /**
     * @return array
     */
    public function getClients(): array
    {
        $clients = [];
        foreach($this->clients as $client_id => $client){
            $clients[$client_id] = $client['socket'];
        }
        return $clients;
    }
    function getClientDetails($client){
        $client_id = $this->getClientId($client);
        return $this->clients[$client_id];
    }
    private function removeSocket($socket){
        $socket_id = $this->getClientId($socket);
        unset($this->clients[$socket_id]);
    }
    private function respond($socket,$message){
        socket_write($socket, $message, strlen($message));
    }
    private function sendMsg($msg,$client){
        sleep(1);
//        var_dump($client);
        if ($client['has_handshake']) {
            $message = trim($this->frame($msg));
            self::log($message);
            $this->respond($client['socket'],$message);
//            $result = @socket_write($client['socket'], $message, strlen($message));
        }
        else {
            self::log("Needs to handshake first");
            // User has not yet performed their handshake.  Store for sending later.
            $holdingMessage = array('client' => $client, 'message' => $msg);
            $this->heldMessages[] = $holdingMessage;
        }
    }

    private function getHeaders($buffer):array {
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
        return $headers;
    }
    private function watchLiveController(){
        foreach ($this->clients as $client_id => $client){
            $watcher = @$client['watcher'];
            if($watcher instanceof Watcher){
                $watcher->watch();
                $watcher->getResponse();
                if(!is_null($watcher->error)){
                    $this->error_response[$client_id] = $watcher->error;
                }
                else{
                    if($watcher->has_changes){
                        self::log(json_encode($watcher->getResponse()));
//                        $this->sendMsg(json_encode($watcher->getResponse()),$client);
                    }
                }
            }
        }

    }

    private function initiateLiveControllerWatcher($url,$client_id){
        if(!$this->clients[$client_id]['watcher'] instanceof Watcher){
            $this->clients[$client_id]['watcher'] = new Watcher($url);
        }
    }
    public function listenToSockets(){
        do{

            if (empty($this->clients)) {
                $this->clients['server'] = $this->socketServer;
            }
            $write = null;
            $except = null;
            $tv_sec = 1;
            $all_clients = $this->getClients();
            $total_clients = count($all_clients);
//            $this->watchLiveController();
//            $this->sendPendingMsg();
//            var_dump($all_clients);
            socket_select($all_clients, $write, $except, $tv_sec);
//            var_dump($all_clients);
            foreach ($all_clients as $client_id => $client){
//                because server socket already listened to, we just need to accept the connection

                if($client == $this->socketServer){
                    $_client = socket_accept($client);
                    if($_client < 0){
                        self::log("unable to accept connection, client: {$client}");
                        continue;//on to the next one
                    }else{
                        self::log("Accepted connection {$client}, which is server");
                        $this->addNewClient($_client);
                    }
                }else{
//receive the connection
                    /*
                     * &$buffer holds the data buffer from client
                     *
                     * */
                    $response_num = @socket_recv($client, $buffer, $this->maxBufferSize, 0);

                    $headers = $this->getHeaders($buffer);
                    $_headers = null;
                    if(!$this->clients[$client_id]['headers']){
                        $this->clients[$client_id]['headers'] = $headers;
                        $_headers = $this->clients[$client_id]['headers'];
                    }else{
                        $_headers = $this->clients[$client_id]['headers'];

                        $this->initiateLiveControllerWatcher(@$_headers['get'],$client_id);
                    }

                    if($this->validateClientRequestCode($client,$response_num)){
//                        validate header
//                        check if buffer is done sending
                        if (!$this->clients[$client_id]['has_handshake']) {

                            $tmp = str_replace("\r", '', $buffer);
                            if (strpos($tmp, "\n\n") === false ) {
                                continue; // If the client has not finished sending the header, then wait before ` our upgrade response.
                            }

                        if($this->validateRequestHeaders($client,$_headers)){
                            $this->hasHandShake($client);
                        }

                            }else{
//                            check for input instead
                            $this->split_packet($response_num,$buffer,$this->clients[$client_id]);
//                            self::log("has already done handshake for client {$client}");
                            $this->sendMsg("testing",$this->clients[$client_id]);
                        }

                    }

                }

            }
        }while(true);
    }
    private function calcoffset($headers) {
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
    protected function extractHeaders($message) {
        $header = array(
            'fin'     => $message[0] & chr(128),
            'rsv1'    => $message[0] & chr(64),
            'rsv2'    => $message[0] & chr(32),
            'rsv3'    => $message[0] & chr(16),
            'opcode'  => ord($message[0]) & 15,
            'hasmask' => $message[1] & chr(128),
            'length'  => 0,
            'mask'    => ""
        );

        $header['length'] = (ord($message[1]) >= 128) ? ord($message[1]) - 128 : ord($message[1]);

        if ($header['length'] == 126) {
            if ($header['hasmask']) {
                $header['mask'] = $message[4] . $message[5] . $message[6] . $message[7];
            }
            $header['length'] = ord($message[2]) * 256
                + ord($message[3]);
        }
        elseif ($header['length'] == 127) {
            if ($header['hasmask']) {
                $header['mask'] = $message[10] . $message[11] . $message[12] . $message[13];
            }
            $header['length'] = ord($message[2]) * 65536 * 65536 * 65536 * 256
                + ord($message[3]) * 65536 * 65536 * 65536
                + ord($message[4]) * 65536 * 65536 * 256
                + ord($message[5]) * 65536 * 65536
                + ord($message[6]) * 65536 * 256
                + ord($message[7]) * 65536
                + ord($message[8]) * 256
                + ord($message[9]);
        }
        elseif ($header['hasmask']) {
            $header['mask'] = $message[2] . $message[3] . $message[4] . $message[5];
        }

        return $header;
    }
    protected function split_packet($length, $packet, $client) {
        //add PartialPacket and calculate the new $length
        $_client = $client['socket'];
        $client_id = $this->getClientId($_client);
        $fullpacket=$packet;
        $frame_pos=0;
        $frame_id=1;
        while($frame_pos<$length) {
            $headers = $this->extractHeaders($packet);
            $headers_size = $this->calcoffset($headers);
            $framesize=$headers['length']+$headers_size;

            //split frame from packet and process it
            $frame=substr($fullpacket,$frame_pos,$framesize);
            if (($message = $this->deframe($frame,$this->clients[$client_id],$headers)) !== FALSE) {
                if ($client['is_closed']) {
                    $this->disconnectClient($_client);
                    self::log("connection ended from split_packet");
                } else {
                    if ((preg_match('//u', $message)) || ($headers['opcode']==2)) {
//                        Data received
//                        execute anything here
                        self::log("Message received");
//                        socket_write($client['socket'],"",)
//                        $this->sendMsg("message? thank you !",$client);
                    } else {
                        self::log("not UTF-8\n");
                    }
                }
            }
            //get the new position also modify packet data
            $frame_pos+=$framesize;
            $packet=substr($fullpacket,$frame_pos);
            $frame_id++;
        }
    }
    private function deframe($message, &$client) {
        //echo $this->strtohex($message);
        $headers = $this->extractHeaders($message);
        $pongReply = false;
        $willClose = false;
        switch($headers['opcode']) {
            case 0:
            case 1:
            case 2:
                break;
            case 8:
                // todo: close the connection
                $client['is_closed'] = true;
                return "";
            case 9:
                $pongReply = true;
            case 10:
                break;
            default:
                //$this->disconnect($user); // todo: fail connection
                $willClose = true;
                break;
        }
        /* Deal by split_packet() as now deframe() do only one frame at a time.
        if ($user->handlingPartialPacket) {
          $message = $user->partialBuffer . $message;
          $user->handlingPartialPacket = false;
          return $this->deframe($message, $user);
        }
        */

        if ($this->checkRSVBits($headers,$client)) {
            return false;
        }
        if ($willClose) {
            // todo: fail the connection
            return false;
        }
        $payload = $this->extractPayload($message,$headers);
        if ($pongReply) {
            $reply = $this->frame($payload,$client,'pong');
            socket_write($client['socket'],$reply,strlen($reply));
            return false;
        }
        if ($headers['length'] > strlen($this->applyMask($headers,$payload))) {
            $client['partial_buffer'] = $message;
            return false;
        }
        $payload = $this->applyMask($headers,$payload);
        if ($headers['fin']) {
            $client['partialMessage'] = "";
            return $payload;
        }
        $client['partialMessage'] = $payload;
        return false;
    }

    protected function frame($message,  $messageType='text', $messageContinues=true) {

        switch ($messageType) {
            case 'continuous':
                $b1 = 0;
                break;
            case 'text':
                $b1 = ($this->sendingContinous) ? 0 : 1;
                break;
            case 'binary':
                $b1 = ($this->sendingContinous) ? 0 : 2;
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
        if ($messageContinues) {
            $this->sendingContinous = true;
        }
        else {
            $b1 += 128;
            $this->sendingContinous = false;
        }
        $length = strlen($message);
        $lengthField = "";
        if ($length < 126) {
            $b2 = $length;
        }
        elseif ($length < 65536) {
            $b2 = 126;
            $hexLength = dechex($length);
            //$this->stdout("Hex Length: $hexLength");
            if (strlen($hexLength)%2 == 1) {
                $hexLength = '0' . $hexLength;
            }
            $n = strlen($hexLength) - 2;
            for ($i = $n; $i >= 0; $i=$i-2) {
                $lengthField = chr(hexdec(substr($hexLength, $i, 2))) . $lengthField;
            }
            while (strlen($lengthField) < 2) {
                $lengthField = chr(0) . $lengthField;
            }
        }
        else {
            $b2 = 127;
            $hexLength = dechex($length);
            if (strlen($hexLength)%2 == 1) {
                $hexLength = '0' . $hexLength;
            }
            $n = strlen($hexLength) - 2;
            for ($i = $n; $i >= 0; $i=$i-2) {
                $lengthField = chr(hexdec(substr($hexLength, $i, 2))) . $lengthField;
            }
            while (strlen($lengthField) < 8) {
                $lengthField = chr(0) . $lengthField;
            }
        }
        return chr($b1) . chr($b2) . $lengthField . $message;
    }


    protected function extractPayload($message,$headers) {
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
        return substr($message,$offset);
    }
    protected function checkRSVBits($headers,$user) { // override this method if you are using an extension where the RSV bits are used.
        if (ord($headers['rsv1']) + ord($headers['rsv2']) + ord($headers['rsv3']) > 0) {
            //$this->disconnect($user); // todo: fail connection
            return true;
        }
        return false;
    }
    protected function applyMask($headers,$payload) {
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
    private function validateClientRequestCode($client,$request){
        $request_code = socket_last_error($client);
        if($request === false){
            if(isset($this->error_code_values[$request_code])){
                self::log($this->error_code_values[$request_code]." Requesting: $request");
                $this->disconnectClient($client);
                self::log("connection ended from validateClientRequestCode");
                return false;
            }elseif($request_code == 0){
                self::log("connection lost for client {$client}");
                self::log("Error: ".socket_strerror($request_code));
            }
        }else{
            return true;
        }
        return true;
    }
    private function disconnectClient($client){
        $client_id = $this->getClientId($client);
        unset($this->clients[$client_id]);
        self::log("Disconnected Client: {$client}");
        socket_close($client);
    }

    public function writeResponse($client,$response){
        $response .= "\n\r";
        if($rem = socket_write($client,$response,strlen($response)) === false){
            self::log("WRITE ERROR: ". socket_strerror(socket_last_error($client)) );
        }
    }

    private function validateRequestHeaders($client,$headers){
        $error_response = null;
//        var_dump($headers);
        if(!isset($headers['host'])){
            $error_response = "HTTP/1.1 400 Bad Request";
        }
        if (!isset($headers['upgrade']) || strtolower($headers['upgrade']) != 'websocket'){
            $error_response = "HTTP/1.1 400 Bad Request -- 'upgrade missing'";
        }
        if(!isset($headers['connection']) || strpos(strtolower($headers['connection']), 'upgrade') === FALSE){
            $error_response = "HTTP/1.1 400 Bad Request -- 'Connection'";
            self::log($headers['connection']);
        }
        if(!isset($headers['sec-websocket-key'])){
            $error_response = "HTTP/1.1 400 Bad Request -- 'socket-key'";
        }
        if(!isset($headers['sec-websocket-version']) || strtolower($headers['sec-websocket-version']) != 13){
            $error_response = "HTTP/1.1 426 Upgrade Required\r\nSec-WebSocketVersion: 13 -- 'socket-version'";
        }

        if($this->error_response[$this->getClientId($client)]){
            $error_response = $this->error_response[$this->getClientId($client)];
        }

        if(is_null($error_response)){
            $response_headers = "HTTP/1.1 101 Switching Protocols\r\n";
            $response_headers .= "Upgrade: websocket\r\n";
            $response_headers .= "Connection: Upgrade\r\n";
            $response_headers .= "Sec-WebSocket-Version: 13\r\n";;
            $response_headers .= "Sec-WebSocket-Accept: ".self::generateToken($headers['sec-websocket-key'])."\r\n";
            echo PHP_EOL.PHP_EOL;
            $this->writeResponse($client,$response_headers);
            return true;
        }else{
            $this->writeResponse($client,$error_response);
            return false;
        }
    }

    static public function generateToken($client_key){
        $magic_key = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
        $webSocketKeyHash = sha1($client_key . $magic_key);
        $rawToken = "";
        for ($i = 0; $i < 20; $i++) {
            $rawToken .= chr(hexdec(substr($webSocketKeyHash,$i*2, 2)));
        }
        $handshakeToken = base64_encode($rawToken);
        return $handshakeToken;
    }

    private function doHandShake($client,$headers){

    }

    private function clientIsActive($client):bool{
        return array_key_exists($this->getClientId($client),$this->clients);
    }

    private function hasHandShake($client){
        $client_id = $this->getClientId($client);
        $this->clients[$client_id]['has_handshake'] = true;
    }

    private function logError($socket,$code = 0){
        echo "error #" . $code . ": " . socket_strerror(socket_last_error($socket)) . "\n";
        exit();
    }

    public function watchClientMsg($socket,$client){
        $message = socket_read($client, 2048, PHP_NORMAL_READ);
        if($message === false){
            $this->logError($socket,1);
            socket_close($socket);
            $this->removeSocket($socket);
        }else{
            echo PHP_EOL.$message;
        }
    }

    private function sendPendingMsg(){
//        array('client' => $client, 'message' => $msg);
        foreach ($this->heldMessages as $id => $hm) {
            $found = false;
            foreach ($this->clients as $client_id => $currentClient) {
                if ($hm['client']['socket'] == $currentClient['socket']) {
                    $found = true;
                    if ($currentClient['has_handshake']) {
                        unset($this->heldMessages[$id]);
                        $this->sendMsg($hm['message'],$currentClient['socket']);
                    }
                }
            }
            if (!$found) {
                // If they're no longer in the list of connected users, drop the message.
                unset($this->heldMessages[$id]);
            }
        }
    }

    /**
     * @param $text
     */
    private static function log($text){
        echo PHP_EOL.$text;
//        flush();
        ob_flush();
    }


}