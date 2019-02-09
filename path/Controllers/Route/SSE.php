<?php

/*
* This is automatically generated 
* Edit to fit your need
* Powered By: Path
*/

namespace Path\Controller\Route;


use Path\Controller;
use Path\Http\Request;
use Path\Http\Response;
use Path\Database\Models\SSEModel;
use Path\SSEWatcher;
use Path\Storage\Sessions;

import(
    "Path/Database/Models/SSEModel",
    "core/Classes/SSEWatcher"
    );

class SSE implements Controller
{
    private $session;
    private $sse_watcher;
    public function __construct(Request $request,Response $response)
    {
        $this->session = new Sessions();
        $this->sse_watcher = new SSEWatcher(
            $request->params->controller,
            $request->fetch("Methods"),
            $request->fetch("Params")
        );
    }
    public function watch(Request $request,Response $response){
//     return a response here

        switch ($request->params->action){
            case "watch":
                $this->sse_watcher->watch();
            break;
            case "reset":
                $this->sse_watcher->reset();
            break;
            case "message":
                $this->sse_watcher->sendMessage($request->fetch("message"));
            break;
            case "navigate":
                $this->sse_watcher->navigate($request->fetch("Params"),$request->fetch("message"));
                break;
        }

        if($this->sse_watcher->changesOccurred()){
            return $response->SSEStream($this->sse_watcher->getResponse())->addHeader([
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods'=> 'GET, POST',
                'Access-Control-Allow-Headers'=> 'X-Requested-With'
            ]);
        }else{
            return $response->SSEStream("")->addHeader([
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods'=> 'GET, POST',
                'Access-Control-Allow-Headers'=> 'X-Requested-With'
            ]);
        }
    }

}