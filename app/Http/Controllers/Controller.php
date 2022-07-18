<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Format success responses.
     * @author Carlos Donis <cdonisdiaz@gmail.com>
     * 
     * @param mixed     $data       Data to be included in response
     * @param string    $message    [Optional] Response message
     * @param int       $code       Response code 
     *    
     * @return \Illuminate\Http\Response
     */

    public function sendResponse(mixed $data, string $message = null, int $code)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data) $response['data'] = $data;

        return response()->json($response, $code);
    }


    /**
     * Format error responses.
     * @author Carlos Donis <cdonisdiaz@gmail.com>
     * 
     * @param mixed     $error      Exception error message
     * @param int       $code       Exception error code 
     * @param string    $errorData  [optional] Details about the error, ex. validation errors
     *    
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $code, $errorData = [])
    {
    	$response = [
            'success' => false,
            'message' => $error,
            'data'    => $errorData,
        ];

        return response()->json($response, $code);
    }
}
