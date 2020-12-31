<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

use Closure;
use App\ApiToken;
use App\User;
use App\ExtToken;
use App\IntToken;

class CustomAuth
{
    /**
     * Handle a customer auth for each request to check ADMIN or USER instead of every Controller Function
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $roles)
    {
        //for api we need to get AFTER framework process the request
        if($roles == 'api') {
            $access_token = $request->header('Authorization');
            $token = explode(" ", $access_token);

            //array of token must 2 : BEARER + {token}
            if(count($token) != 2) {
                return response()
                ->json(['code' => '98', 'message' => 'INVALID PARAM', 'data' => ''])
                ->setStatusCode(401);
            }

            $apiTokenObj = \App\ApiToken::where('token', $token[1])->first();
            if($apiTokenObj != null){
                if($apiTokenObj->status != 'A') {
                    return response()
                        ->json(['code' => '99', 'message' => 'INVALID TOKEN', 'data' => ''])
                        ->setStatusCode(401);
                }

                //append ApiToken Object into request so controller able to use without to query again.
                $request->tokenObj = $apiTokenObj;
                $request->user = User::find($apiTokenObj->user_id);
            } else {
                return response()
                    ->json(['code' => '99', 'message' => 'TOKEN NOT EXISTS', 'data' => ''])
                    ->setStatusCode(401);
            }

            return $next($request);
        }else if ($roles == 'oauth'){
            // Api for external 3rd party / partner
            $access_token = $request->header('Authorization');
            $token = explode(" ", $access_token);

            //array of token must 2 : BEARER + {token}
            if(count($token) != 2) {
                return response()
                ->json(['code' => '97', 'message' => 'INVALID PARAM', 'data' => ''])
                ->setStatusCode(401);
            }elseif(count($token) > 2 && strtolower($token[0]) != 'bearer'){
                return response()
                ->json(['code' => '97', 'message' => 'INVALID PARAM', 'data' => ''])
                ->setStatusCode(401);
            }

            $extTokenObj = \App\ExtToken::where('token', $token[1])->first();
            if($extTokenObj != null){
                if(time() >= strtotime($extTokenObj->expire_date)) {
                    return response()
                        ->json(['code' => '98', 'message' => 'TOKEN EXPIRED', 'data' => ''])
                        ->setStatusCode(401);
                }

                $user = User::find($extTokenObj->user_id);
                if($user->roles != 'client'){
                    return response()
                        ->json(['code' => '96', 'message' => 'You are not authorized to get 3rd party api access. Ask developer for further information.', 'data' => ''])
                        ->setStatusCode(401);
                }

                //append ApiToken Object into request so controller able to use without to query again.
                $request->tokenObj = $extTokenObj;
                $request->user = $user;
            } else {
                return response()
                    ->json(['code' => '99', 'message' => 'TOKEN NOT EXISTS', 'data' => ''])
                    ->setStatusCode(401);
            }

            return $next($request);
        }else if($roles == 'internal'){
            // Api for external 3rd party / partner
            $access_token = $request->header('Authorization');
            $token = explode(" ", $access_token);

            //array of token must 2 : BEARER + {token}
            if(count($token) != 2) {
                return response()
                ->json(['code' => '97', 'message' => 'INVALID PARAM', 'data' => ''])
                ->setStatusCode(401);
            }elseif(count($token) > 2 && strtolower($token[0]) != 'bearer'){
                return response()
                ->json(['code' => '97', 'message' => 'INVALID PARAM', 'data' => ''])
                ->setStatusCode(401);
            }

            $intTokenObj = \App\IntToken::where('token', $token[1])->first();
            if($intTokenObj != null){

                // Check if refferal url is allowed_url
                // $referer = parse_url(Request::server('HTTP_REFERER'));
                // $embed = '';

                // if(array_key_exists('scheme', $referer) && array_key_exists('host', $referer)){
                //     $embed = $referer['scheme'].'://'.$referer['host'];
                // }

                // if($intTokenObj->allowed_url != $embed){
                //     return response()
                //         ->json(['code' => '98', 'message' => 'URL NOT ALLOWED', 'data' => ''])
                //         ->setStatusCode(401);
                // }

                //append ApiToken Object into request so controller able to use without to query again.
                $request->tokenObj = $intTokenObj;
            } else {
                return response()
                    ->json(['code' => '99', 'message' => 'TOKEN NOT EXISTS', 'data' => ''])
                    ->setStatusCode(401);
            }

            return $next($request);
        }else if ($roles == 'api-guest') {
            return $next($request);
        }
        //for normal request (non-API) we can process before request being process
        else {

            if (\Auth::guest()) {
                //echo 'not authenticated user';
                return redirect('/login');
            }
            else {
                
                //authorized access
                //\Auth::user()->id

            }

            // In ADMIN (/adn) and USER (/account) below is the code to redirect to secure page 
            // we try on admin page first (/adn)
            // if($roles == 'admin') {
            //     $request->setTrustedProxies( [ $request->getClientIp() ] );     //to avoid endless redirect e.g in CLOUDFARE

            //     if (!$request->secure() && env('APP_ENV') === 'production') {
            //         return redirect()->secure($request->getRequestUri());
            //     }
            // }

        }

        return $next($request);
    }

    private function areHeadersValid($request){
        $headerRequirements = [
            'Authorization' => NULL,
            //'Content-Type'  => 'application/json',
            'Cache-Control' => 'no-cache'
        ];

        foreach($headerRequirements as $header=>$value){
            if( ! $request->headers->has($header)){
                return false;
            }
        }
        return true;
    }
}