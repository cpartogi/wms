<?php

namespace App\Http\Services\Jubelio;

use App\JubelioUser;
use Carbon\Carbon;
use GuzzleHttp\Client;

class JAuth
{
    private $client;
    
    private $url;
    
    public function __construct()
    {
        $this->client = new Client();
        $this->url    = 'http://api.jubelio.com/login';
    }
    
    public function getJTokenByPat($pakde_auth_token)
    {
        if ($token = $this->getValidJTokenByPat($pakde_auth_token)) {
            return $token;
        } else {
            $token = $this->login($this->getClientIDByPat($pakde_auth_token));
    
            return $token;
        }
    }
    
    /**
     *  valid token must exist in db
     *  max age of jubelio token is 24 hours old, we must refresh it in minimum 12 hours
     *
     * @param $pakde_auth_token
     * @return mixed|null
     */
    private function getValidJTokenByPat($pakde_auth_token)
    {
        $ju = new JubelioUser();
        if ($jubelio_user = $ju->where('auth_token', $pakde_auth_token)->first()) {
            if (!$jubelio_user->token_claimed_at) {
                return null;
            }
            
            // we must refresh the jubelio token in minimum 12 hours
            $now        = Carbon::now()->subHours("12");
            $now_string = $now->format("Y-m-d H:i:s");
            
            if ($jubelio_user->token_claimed_at < $now_string) {
                return null;
            }
            
            return $jubelio_user->jubelio_token;
        }
        
        return null;
    }
    
    public function login($client_id)
    {
        $ju            = new JubelioUser();
        $jubelio_user  = $ju->where('client_id', $client_id)->first();
        $jubelio_token = null;
        
        if ($client_id && $jubelio_user) {
            $body['email']    = $jubelio_user->email;
            $body['password'] = $jubelio_user->password;
            
            $headers['Content-Type'] = 'application/json';
            $response                = $this->client->post($this->url, ['body' => json_encode($body)]);
            
            if ($response->getStatusCode() == 200) {
                $content = $response->getBody()->getContents();
                $data    = json_decode($content);
    
                // save new token
                $ju = new JubelioUser();
                if ($jubelio_user = $ju->where('client_id', $client_id)->first()) {
                    $jubelio_user->jubelio_token    = $data->token;
                    $jubelio_user->token_claimed_at = Carbon::now()->format("Y-m-d H:i:s");
                    $jubelio_user->save();
        
                    return $data->token;
                }
            }
        }
        
        return null;
    }
    
    public function getClientIDByPat($pakde_auth_token)
    {
        $ju = new JubelioUser();
        if ($jubelio_user = $ju->where('auth_token', $pakde_auth_token)->first()) {
            return $jubelio_user->client_id;
        }
        
        return null;
    }
}
