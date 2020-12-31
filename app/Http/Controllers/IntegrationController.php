<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Config;
use GuzzleHttp\Client;
use Log;

class IntegrationController extends Controller {

    public function __construct() {
        $this->http_client = app(Client::class);
    }

    public function index (Request $request) {
        return view('dashboard.integration.index');
    }

    public function partner (Request $request, $partner_id) {
        $client_id = Auth::user()->client_id;
        $web_secret_key = md5($client_id);
        $partners = Config::get('constants.partners');

        $url = env('OMNICHANNELSVC_BASE_URL') . $partners['jubelio']['URL_OMNICHANNEL_INTEGRATION'];
        try {
            $response = $this->http_client->request("GET", $url, ['query' => [
                'client_id' => $client_id,
                'partner_id' => $partner_id
            ]]);
        } catch (\Exception $exception) {
            Log::error("Failed to call integration API to create token in omnichannelsvc: ". $exception);
            $response = $exception->getResponse();
        }

        $is_already_login =  $response->getStatusCode() == 200 && count(json_decode($response->getBody()->getContents())->data->webhooks) > 0;

        return view('dashboard.integration.partner', [
            'omnichannelsvc_base_url' => env('OMNICHANNELSVC_BASE_URL'),
            'client_id' => $client_id,
            'partner_id' => $partner_id,
            'partner_name' => $partners['jubelio']['NAME'],
            'web_secret_key' => $web_secret_key,
            'is_already_login' => $is_already_login,
            'host' => env('PUBLIC_OMNICHANNELSVC_BASE_URL')
        ]);
    }

    public function jubelio_integration (Request $request) {
        $partners = Config::get('constants.partners');
        $url = env('OMNICHANNELSVC_BASE_URL') . $partners['jubelio']['URL_OMNICHANNEL_INTEGRATION'] . "/" .  $partners['jubelio']['ID'];
        try {
            $response = $this->http_client->request("POST", $url, ['body' => json_encode($request->json()->all())]);
        } catch (\Exception $exception) {
            Log::error("Failed to call integration API to create token in omnichannelsvc: ". $exception);
            $response = $exception->getResponse();
        }

        return response()->json(json_decode($response->getBody()->getContents()), $response->getStatusCode());
    }

    public function get_all_product (Request $request) {
        $partners = Config::get('constants.partners');
        $client_id = Auth::user()->client_id;
        $url = env('OMNICHANNELSVC_BASE_URL') . $partners['jubelio']['URL_GET_ALL_PRODUCT'] . "/" .  $partners['jubelio']['ID'] . "/" . $client_id;
        try {
            $response = $this->http_client->request("GET", $url, ['body' => json_encode($request->json()->all())]);
        } catch (\Exception $exception) {
            Log::error("Failed to get all products in omnichannelsvc: ". $exception);
            $response = $exception->getResponse();
        }

        return response()->json(json_decode($response->getBody()->getContents()), $response->getStatusCode());
    }

}