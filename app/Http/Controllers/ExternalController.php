<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use Auth;

use App\ExtToken;
use App\User;
use App\Product;

class ExternalController extends Controller
{
    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function get_login(Request $request)
    {
        $responseMessage = 'Unable to login. Please check your credentials.';
        $responseData    = '';
        $responseCode    = '01';

        if($request->has('username') && $request->has('password'))
        {
            $input = $request->all();
            if (Auth::attempt(array('email' => $input['username'], 'password' => $input['password']))) {
                $responseCode = '00';
                $responseMessage = 'Login Success';
                $user = \App\User::where('email', $input['username'])->first();
                if(!isset($user->client_key) || !isset($user->secret_key) || !isset($user->key_expired)){
                    $user->client_key = str_random(32);
                    $user->secret_key = str_random(60);
                    $user->key_expired = date('Y-m-d H:i:s',strtotime('+1 year'));
                    $user->save();
                }

                $responseData = array(
                    'client_key' => $user->client_key,
                    'client_secret' => $user->secret_key,
                    'key_expired' => $user->key_expired
                );
            }
        } else {
            $responseMessage = 'username or password is not exists.';
        }

        return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function detach(Request $request)
    {
        $responseMessage = 'Unable to process detachment.';
        $responseData    = '';
        $responseCode    = '01';

        if($request->has('client_id') && $request->has('client_secret'))
        {
            $input = $request->all();
            if (\App\User::where('client_key', $input['client_id'])->where('secret_key',$input['client_secret'])->first() != null) {
                $responseCode = '00';
                $responseMessage = 'Detach success.';
                $user = \App\User::where('client_key', $input['client_id'])->where('secret_key',$input['client_secret'])->first();
                $user->client_key = str_random(32);
                $user->secret_key = str_random(60);
                $user->save();
            }
        } else {
            $responseMessage = 'client_id or client_secret is not exists.';
        }

        return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    /**
     *
     * @return \Illuminate\Http\Response
     */
    public function get_token(Request $request)
    {
        $responseMessage = 'Get token failed. New secret key is required.';
        $responseData    = '';
        $responseCode    = '01';
        
        if ($request->has('client_id') && $request->has('client_secret')) {
        	$input = $request->all();

        	$check = User::where('client_key','=',$input['client_id'])
        		->where('secret_key','=',$input['client_secret'])
        		->first();

        	if(isset($check)){

        		if($check->roles != 'client'){
        			$responseMessage = 'You are not authorized to get 3rd party api access. Ask developer for further information.';
        			return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
        		}

        		if(time() >= strtotime($check->key_expired) && $check->auto_refresh == 1){
        			$check->secret_key = str_random(60);
        			$check->key_expired = date('Y-m-d H:i:s',strtotime('+1 year'));
        			$check->save();
        		} elseif(time() >= strtotime($check->key_expired) && $check->auto_refresh != 1){
        			$responseMessage = 'Your client_secret has expired, please get new key from Pakdé WMS.';
        			return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
        		}
            
	            $extTokenObj = ExtToken::where('user_id','=',$check->id)->first();
	            $token = str_random(60);

	            if(isset($extTokenObj)){
	            	if(time() >= strtotime($extTokenObj->expire_date)){
	            		// Date expired
	            		$extTokenObj->token = $token;
	            		$extTokenObj->expire_date = date('Y-m-d H:i:s',strtotime('+1 month'));
	            		$extTokenObj->save();
	            	}
	            }else{
		            $extTokenObj = new ExtToken;
		            $extTokenObj->token = $token;
		            $extTokenObj->user_id = $check->id;
		            $extTokenObj->platform = 'D';
		            $extTokenObj->expire_date = date('Y-m-d H:i:s',strtotime('+1 month'));
		            $extTokenObj->save();
	            }
	            
	            if (strtotime($extTokenObj->expire_date) > time()) {
	            	$responseData = User::where('id','=',$check->id)->select('name','email')->first();
	                $responseData->setAttribute('token', $extTokenObj->token);
	                $responseData->setAttribute('expiry_date', $extTokenObj->expire_date);

	                $responseCode = '00';
	                $responseMessage = 'Get token success.';
	            } else {
	                $responseData    = null;
	                $responseMessage = "Failed to generate token.";
	            }
        	}

        } else {
        	$responseMessage = 'client_id or client_secret is not exists.';
        }
        
        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    public function getProductList(Request $request)
    {
    	$responseMessage = 'Get products failed.';
        $responseData = '';
        $responseCode = '01';

        $products = Product::where('client_id','=',$request->user->client_id)
        	->leftJoin('product_type','product_type.id','=','product.product_type_id')
        	->select('product.id','product.name','product_type.name as type','price','weight','dimension','color','description')
        	->get();
        if($products->count() > 0){
        	$responseCode = '00';
        	$responseMessage = 'Products successfully loaded.';
        	$responseData = $products;
        } else {
        	$responseMessage = 'No products found.';
        }

        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    public function getProductStocks(Request $request, $id)
    {
    	$responseMessage = 'Get stocks failed.';
        $responseData = '';
        $responseCode = '01';

        $product = Product::where('client_id','=',$request->user->client_id)
        	->leftJoin('product_type','product_type.id','=','product.product_type_id')
        	->select('product.name','product_type.name as type')
        	->where('product.id','=',$id)
        	->first();
        $queries = DB::table('inbound_detail')
        	->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
        	->join('product','product.id','=','inbound_detail.product_id')
        	->select('product_type_size.name as size_name','product.color',DB::raw('IFNULL((select count(*) from inbound_detail_location left join inbound_detail as inb_d on inb_d.id = inbound_detail_location.inbound_detail_id where inb_d.product_id = '.$id.' AND inb_d.product_type_size_id = product_type_size.id AND inbound_detail_location.date_outbounded IS NULL AND inbound_detail_location.shelf_id IS NOT NULL GROUP BY inb_d.product_id, inb_d.product_type_size_id),0) as quantity'))
        	->groupBy('inbound_detail.product_id','inbound_detail.product_type_size_id')
        	->where('product.id','=',$id)
        	->where('product.client_id','=',$request->user->client_id)
        	->get();

        if(isset($product) && $queries->count() > 0){
        	$responseCode = '00';
        	$responseMessage = 'Stocks successfully loaded.';
        	$responseData = array(
        		"product" => $product,
        		"stocks" => $queries
        	);
        } else {
        	$responseMessage = 'No product found.';
        }

        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    public function getTotalStocks(Request $request)
    {
        $responseMessage = 'Get stocks failed.';
        $responseData = '';
        $responseCode = '01';

        $queries = DB::table('inbound_detail')
            ->join('product','product.id','=','inbound_detail.product_id')
            ->select('product.name','product.color',DB::raw('IFNULL((select count(*) from inbound_detail_location left join inbound_detail as inb_d on inb_d.id = inbound_detail_location.inbound_detail_id where inb_d.product_id = product.id AND inbound_detail_location.date_outbounded IS NULL AND inbound_detail_location.shelf_id IS NOT NULL GROUP BY inb_d.product_id),0) as quantity'))
            ->groupBy('inbound_detail.product_id')
            ->where('product.client_id','=',$request->user->client_id)
            ->get();

        if($queries->count() > 0){
            $responseCode = '00';
            $responseMessage = 'Stocks successfully loaded.';
            $responseData = array(
                "stocks" => $queries
            );
        } else {
            $responseMessage = 'No product found.';
        }

        return response()
            ->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
}
