<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BaseController extends Controller
{
    /**
    * Initial count for the list
    */
    public $initCnt = 200;
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message)
    {
    	$response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
    	$response = [
            'success' => false,
            'message' => $error,
        ];

        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
    * 
    */      
    public function fetchEndPoint($url, $method="GET", $data=[]){
        $curl = curl_init();
        switch ($method){
           case "POST":
              curl_setopt($curl, CURLOPT_POST, 1);
              if ($data)
                 curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
              break;

           case "PUT":
              curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
              if ($data)
                 curl_setopt($curl, CURLOPT_POSTFIELDS, $data);                       
              break;

           default:
                $headers = array(
                    "User-Agent: PostmanRuntime/7.29.2",
                    "Content-Type: application/json",
                );
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
              if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
        }
        // OPTIONS:
        curl_setopt($curl, CURLOPT_URL, $url);   
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        // EXECUTE:
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
            return ['success'=>false, 'content'=>$err];
        } else {            
            return ['success'=>true, 'content'=>json_decode($response)];
        }

    }    
}
