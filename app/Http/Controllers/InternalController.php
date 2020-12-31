<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Auth;
use Hash;

use App\ExtToken;
use App\User;
use App\Client;

class InternalController extends Controller
{
    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function create_client(Request $request)
    {
        $responseMessage = 'Unable to create new client.';
        $responseData    = '';
        $responseCode    = '01';

        if($request->has('name') && $request->has('acronym') && $request->has('email') && $request->has('mobile') && $request->has('pic') && $request->has('address') && $request->has('zip_code'))
        {
            $input = $request->all();
            
            // Create user for dashboard access
	    	if(User::where('email',$request->input('email'))->count() > 0 || Client::where('email',$request->input('email'))->count() > 0){
	    		$responseMessage = 'Email address already registered';
	    		return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
	    	}

	    	$user = new User;
	    	$user->name = $input['name'];
	    	$user->email = $input['email'];
	    	$user->roles = 'client';
	    	$user->password = Hash::make(md5(uniqid(rand(), true)));
	    	$user->save();

	    	$client = new Client;
	    	$client->version = 1;
	    	$client->is_active = 1;
	    	$client->created_at = date('Y-m-d H:i:s');
	    	$client->updated_at = date('Y-m-d H:i:s');
	    	$client->name = $input['name'];
	    	$client->acronym = $input['acronym'];
	    	$client->email = $input['email'];
	    	$client->mobile = $input['mobile'];
	    	$client->pic = $input['pic'];
	    	$client->address = $input['address'];
	    	$client->zip_code = $input['zip_code'];
	    	$client->user_id = $user->id;
	    	
	    	// Pricing inbound
	    	$client->pricing_qty_less = 3000;
	    	$client->pricing_qty = 2000;
	    	$client->pricing_qty_more = 3001;
	    	$client->pricing_qty_more_value = 2000;

	    	// Pricing Small items
	    	$client->pricing_small_item_less = 3000;
	    	$client->pricing_small_item = 50;
	    	$client->pricing_small_item_more = 3001;
	    	$client->pricing_small_item_more_value = 50;

	    	// Pricing Medium items
	    	$client->pricing_medium_item_less = 3000;
	    	$client->pricing_medium_item = 50;
	    	$client->pricing_medium_item_more = 3001;
	    	$client->pricing_medium_item_more_value = 50;

	    	// Pricing Large items
	    	$client->pricing_large_item_less = 3000;
	    	$client->pricing_large_item = 50;
	    	$client->pricing_large_item_more = 3001;
	    	$client->pricing_large_item_more_value = 50;

	    	// Pricing Extra Large items
	    	$client->pricing_extra_large_item_less = 3000;
	    	$client->pricing_extra_large_item = 50;
	    	$client->pricing_extra_large_item_more = 3001;
	    	$client->pricing_extra_large_item_more_value = 50;

	    	// Pricing Outbound items
	    	$client->pricing_order_less = 3000;
	    	$client->pricing_order = 2000;
	    	$client->pricing_order_more = 3001;
	    	$client->pricing_order_more_value = 2000;

	    	// Pricing Event items
	    	$client->pricing_event_less = 3000;
	    	$client->pricing_event = 2000;
	    	$client->pricing_event_more = 3001;
	    	$client->pricing_event_more_value = 2000;

	    	$client->save();
	    	$user->client_id = $client->id;
	    	$user->save();

	    	$responseCode = '00';
	    	$responseData = $client;
	    	$responseMessage = 'New profile has successfully added';

        } else {
            $responseMessage = 'Parameters required.';
        }

        return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function get_client(Request $request, $client_id)
    {
    	$responseMessage = 'Unable to get client by id.';
        $responseData    = '';
        $responseCode    = '01';

        $client = Client::find($client_id);
        if($client->count() == 0){
        	$responseMessage = 'Client is not found.';
        } else {
        	$responseCode = '00';
        	$responseData = $client;
        	$responseMessage = 'Client is successfully found';
        }

        return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
}
