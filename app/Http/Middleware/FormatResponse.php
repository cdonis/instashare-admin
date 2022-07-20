<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FormatResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $httpStatusCode = $response->getStatusCode();
        $errorMessage = '';
    
        if (isset($response->exception)) {
            $errorCode = $response->exception->getCode();
            $errorMessage = $response->exception->getMessage();
            $httpStatusCode = ($errorCode === 0) ? $httpStatusCode : $errorCode;
        }
    
        // If error, structure response to meet UMI's errors management engine
        if ($httpStatusCode < 200 || $httpStatusCode >= 300) {
            $finalResponse = array();
            $finalResponse['success'] = false;
            $finalResponse['errorCode'] = (string) $httpStatusCode;
            $finalResponse['errorMessage'] = $errorMessage;
            $finalResponse['showType'] = 9;
            if ($httpStatusCode === 422)
              $finalResponse['data'] = json_decode($response->content())->errors;

            $response = response()->json($finalResponse, $httpStatusCode);
        } 

        return $response;
    }
}
