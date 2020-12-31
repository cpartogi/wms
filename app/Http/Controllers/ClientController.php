<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Yajra\Datatables\Datatables;

use Validator;
use DB;
use AWS;
use Hash;
use Auth;

use App\Client;
use App\Product;
use App\User;

use Carbon\Carbon;

class ClientController extends Controller
{

	public function __construct()
	{
		$this->middleware(function ($request, $next) {
            if(Auth::user()->roles == 'client')
			{
				return redirect('/');
			}

            return $next($request);
        });
	}

	public function get_list(Request $request)
	{
        $boots = Client::all();

        $data = array();
        for ($i=0; $i < count($boots); $i++) {
        	$products = \App\Product::where('client_id',$boots[$i]->id)->count();
			$details = DB::table('inbound_detail')
				->join('product','product.id','=','inbound_detail.product_id')
				->where('product.client_id',$boots[$i]->id)
				->count();

            $obj = new \stdClass; 
            $obj->image_url = (($boots[$i]->logo_url != null)?"https://s3-ap-southeast-1.amazonaws.com/static-pakde/".str_replace(" ","+",$boots[$i]->logo_url):"http://13.229.209.36:3006/assets/client-image.png");
            $obj->name = $boots[$i]->name;
            $obj->email = $boots[$i]->email;
            $obj->products = $products." / ".$details;
            $actionview = 
            '<div class="dropdown">
				<button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					Action
				</button>
				<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
					<a class="dropdown-item" href="/client/edit/'.$boots[$i]->id.'">Edit</a>';

			if(Auth::user()->roles != 'investor'){
				$actionview .= '<a class="dropdown-item" href="/product/add?id='.$boots[$i]->id.'">Register Product</a>';
			}

			$actionview .= '<a class="dropdown-item" href="/client/product/'.$boots[$i]->id.'/list">View Product</a>
					<a class="dropdown-item" href="#">View Analytics</a>
					<a class="dropdown-item" href="#">Cost Explorer</a>
					<a class="dropdown-item" href="#">Create Invoice</a>';

            if(Auth::user()->roles != 'crew' && Auth::user()->roles != 'investor'){
                $actionview .= '<a class="dropdown-item delete-btn" data-id="'.$boots[$i]->id.'">Delete</a>';
            }
            $actionview .= "</div>";

            $obj->action = $actionview; 
            $data[] = $obj;
        }
        $client_list = new Collection($data);

        return Datatables::of($client_list)->make(true);
	}
	
    public function index(Request $request)
    {
    	return view('dashboard.client.index');
    }

    public function edit(Request $request, $id)
    {
		$client = DB::table('client')
            ->join('users', 'users.id', '=', 'client.user_id')
			->leftJoin('warehouse', 'users.warehouse_id', '=', 'warehouse.id')
			->where('client.id', '=', $id)
			->select('client.*', 'users.warehouse_id')
			->get();

    	if($client->count() > 0){
    		return view('dashboard.client.edit',['client' => $client[0]]);
    	}
    }

    public function add(Request $request)
    {
    	return view('dashboard.client.new');
    }

    public function create(Request $request)
    {
    	$validator = Validator::make($request->all(),[
	        'name' => 'required',
	        'acronym' => 'required',
	        'email' => 'required|email',
	        'mobile' => 'required',
	        'pic' => 'required',
	        'address' => 'required',
	        'zip_code' => 'required',
			'password' => 'required',
			'warehouse_id' => 'required'
	    ])->setAttributeNames(['warehouse_id' => 'warehouse']);

	    if ($validator->fails()) {
	    	return redirect('/client/add')
                        ->withErrors($validator)
                        ->withInput();
		} else {

	    	// Create user for dashboard access
	    	if(User::where('email',$request->input('email'))->count() > 0){
	    		$request->session()->flash('error', 'Email address already registered');
	    		return redirect('/client/add')
                        ->withErrors($validator)
                        ->withInput();
	    	}

	    	if(Client::where('email',$request->input('email'))->count() > 0){
	    		$request->session()->flash('error', 'Email address already registered');
	    		return redirect('/client/add')
                        ->withErrors($validator)
                        ->withInput();
	    	}

	    	$user = new User;
	    	$user->name = $request->input('name');
	    	$user->email = $request->input('email');
			$user->roles = 'client';
			$user->warehouse_id = $request->input('warehouse_id');
			$user->password = Hash::make($request->input('password'));
	    	$user->save();

	    	$client = new Client;
	    	$client->version = 1;
	    	$client->is_active = 1;
	    	$client->created_at = date('Y-m-d H:i:s');
	    	$client->updated_at = date('Y-m-d H:i:s');
	    	$client->name = $request->input('name');
	    	$client->acronym = $request->input('acronym');
	    	$client->email = $request->input('email');
	    	$client->mobile = $request->input('mobile');
	    	$client->pic = $request->input('pic');
	    	$client->address = $request->input('address');
	    	$client->zip_code = $request->input('zip_code');
	    	$client->user_id = $user->id;
	    	
	    	// Pricing inbound
	    	$client->pricing_qty_less = $request->input('pricing_qty_less');
	    	$client->pricing_qty = $request->input('pricing_qty');
	    	$client->pricing_qty_more = $request->input('pricing_qty_more');
	    	$client->pricing_qty_more_value = $request->input('pricing_qty_more_value');

	    	// Pricing Small items
	    	$client->pricing_small_item_less = $request->input('pricing_small_item_less');
	    	$client->pricing_small_item = $request->input('pricing_small_item');
	    	$client->pricing_small_item_more = $request->input('pricing_small_item_more');
	    	$client->pricing_small_item_more_value = $request->input('pricing_small_item_more_value');

	    	// Pricing Medium items
	    	$client->pricing_medium_item_less = $request->input('pricing_medium_item_less');
	    	$client->pricing_medium_item = $request->input('pricing_medium_item');
	    	$client->pricing_medium_item_more = $request->input('pricing_medium_item_more');
	    	$client->pricing_medium_item_more_value = $request->input('pricing_medium_item_more_value');

	    	// Pricing Large items
	    	$client->pricing_large_item_less = $request->input('pricing_large_item_less');
	    	$client->pricing_large_item = $request->input('pricing_large_item');
	    	$client->pricing_large_item_more = $request->input('pricing_large_item_more');
	    	$client->pricing_large_item_more_value = $request->input('pricing_large_item_more_value');

	    	// Pricing Extra Large items
	    	$client->pricing_extra_large_item_less = $request->input('pricing_extra_large_item_less');
	    	$client->pricing_extra_large_item = $request->input('pricing_extra_large_item');
	    	$client->pricing_extra_large_item_more = $request->input('pricing_extra_large_item_more');
	    	$client->pricing_extra_large_item_more_value = $request->input('pricing_extra_large_item_more_value');

	    	// Pricing Outbound items
	    	$client->pricing_order_less = $request->input('pricing_order_less');
	    	$client->pricing_order = $request->input('pricing_order');
	    	$client->pricing_order_more = $request->input('pricing_order_more');
	    	$client->pricing_order_more_value = $request->input('pricing_order_more_value');

	    	// Pricing Event items
	    	$client->pricing_event_less = $request->input('pricing_event_less');
	    	$client->pricing_event = $request->input('pricing_event');
	    	$client->pricing_event_more = $request->input('pricing_event_more');
	    	$client->pricing_event_more_value = $request->input('pricing_event_more_value');

	    	if($request->file('logo_url') != null){
	    		$file = $request->file('logo_url');
	            $ext = $file->getClientOriginalExtension();
	            $size = $file->getSize();
	            $newName = date('Ymd')."_".rand(100000,1001238912).".".$ext;
	            $file->move('images/clients',$newName);

	            $img_path = public_path()."/images/clients/";

	            $s3 = AWS::createClient('s3');
                $upload = $s3->putObject(array(
                    'Bucket'     => 'static-pakde',
                    'Key'        => $newName,
                    'SourceFile' => $img_path.$newName,
                    'ACL'=>'public-read'
                ));

                unlink($img_path.$newName);

	            $client->logo_url = $newName;
	    	}

	    	$client->save();
	    	$user->client_id = $client->id;
	    	$user->save();

	    	$request->session()->flash('success', 'New profile has successfully added');
	    }

	    return redirect('/client');
    }

    public function update(Request $request, $id)
    {

    	$validator = Validator::make($request->all(),[
	        'name' => 'required',
	        'email' => 'required|email',
	        'mobile' => 'required',
	        'pic' => 'required',
	        'address' => 'required',
	        'zip_code' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/client/edit/'.$id)
                        ->withErrors($validator)
                        ->withInput();
	    } else {
			$client = Client::find($id);

	    	// Check if input email not same as now
	    	if($request->input('email') != $client->email && Client::where('email',$request->input('email'))->count() > 0){
	    		$request->session()->flash('error', 'Email address already registered');
	    		return redirect('/client/edit'.$id)
                        ->withErrors($validator)
                        ->withInput();
	    	}

	    	$client->name = $request->input('name');
	    	$client->acronym = $request->input('acronym');
	    	$client->email = $request->input('email');
	    	$client->mobile = $request->input('mobile');
	    	$client->pic = $request->input('pic');
	    	$client->address = $request->input('address');
	    	$client->zip_code = $request->input('zip_code');
	    	// Pricing inbound
	    	$client->pricing_qty_less = $request->input('pricing_qty_less');
	    	$client->pricing_qty = $request->input('pricing_qty');
	    	$client->pricing_qty_more = $request->input('pricing_qty_more');
	    	$client->pricing_qty_more_value = $request->input('pricing_qty_more_value');

	    	// Pricing Small items
	    	$client->pricing_small_item_less = $request->input('pricing_small_item_less');
	    	$client->pricing_small_item = $request->input('pricing_small_item');
	    	$client->pricing_small_item_more = $request->input('pricing_small_item_more');
	    	$client->pricing_small_item_more_value = $request->input('pricing_small_item_more_value');

	    	// Pricing Medium items
	    	$client->pricing_medium_item_less = $request->input('pricing_medium_item_less');
	    	$client->pricing_medium_item = $request->input('pricing_medium_item');
	    	$client->pricing_medium_item_more = $request->input('pricing_medium_item_more');
	    	$client->pricing_medium_item_more_value = $request->input('pricing_medium_item_more_value');

	    	// Pricing Large items
	    	$client->pricing_large_item_less = $request->input('pricing_large_item_less');
	    	$client->pricing_large_item = $request->input('pricing_large_item');
	    	$client->pricing_large_item_more = $request->input('pricing_large_item_more');
	    	$client->pricing_large_item_more_value = $request->input('pricing_large_item_more_value');

	    	// Pricing Extra Large items
	    	$client->pricing_extra_large_item_less = $request->input('pricing_extra_large_item_less');
	    	$client->pricing_extra_large_item = $request->input('pricing_extra_large_item');
	    	$client->pricing_extra_large_item_more = $request->input('pricing_extra_large_item_more');
	    	$client->pricing_extra_large_item_more_value = $request->input('pricing_extra_large_item_more_value');

	    	// Pricing Outbound items
	    	$client->pricing_order_less = $request->input('pricing_order_less');
	    	$client->pricing_order = $request->input('pricing_order');
	    	$client->pricing_order_more = $request->input('pricing_order_more');
	    	$client->pricing_order_more_value = $request->input('pricing_order_more_value');

	    	// Pricing Event items
	    	$client->pricing_event_less = $request->input('pricing_event_less');
	    	$client->pricing_event = $request->input('pricing_event');
	    	$client->pricing_event_more = $request->input('pricing_event_more');
	    	$client->pricing_event_more_value = $request->input('pricing_event_more_value');

	    	if($request->file('logo_url') != null){
	    		$file = $request->file('logo_url');
	            $ext = $file->getClientOriginalExtension();
	            $size = $file->getSize();
	            $newName = date('Ymd')."_".rand(100000,1001238912).".".$ext;
	            $file->move('images/clients',$newName);

	            $img_path = public_path()."/images/clients/";

	            $s3 = AWS::createClient('s3');
                $upload = $s3->putObject(array(
                    'Bucket'     => 'static-pakde',
                    'Key'        => $newName,
                    'SourceFile' => $img_path.$newName,
                    'ACL'=>'public-read'
                ));

                unlink($img_path.$newName);

	            $client->logo_url = $newName;
	    	}

			$client->save();

			//update the user data
			DB::table('users')
			->where('email', $request->input('email'))
			->update(['warehouse_id' => $request->input('warehouse_id'), 'updated_at' => Carbon::now()]);

	    	$request->session()->flash('success', 'Profile has successfully updated');
	    }

	    return redirect('/client/edit/'.$id);	    
    }

    public function delete(Request $request, $id)
    {
    	$responseMessage = '';

    	$user = Client::where('id',$id);
    	$user->delete();
    	$request->session()->flash('success', 'Selected client has successfully deleted');

    	return redirect('/client');
    }

    public function product(Request $request, $client_id)
    {
    	$products = DB::table('product')
                ->leftJoin('image','image.product_id','=','product.id')
    			->join('product_type','product.product_type_id','=','product_type.id')
    			->join('client','client.id','=','product.client_id')
    			->select('product.id','product_type.name as product_type','product.name','product.created_at','image.s3url as product_img','client.name as client_name')
                ->distinct()
                ->groupBy('product.id')
                ->where('product.client_id','=',$client_id)
    			->get();
    	return view('dashboard.client.product',['products' => $products,'client_id'=>$client_id]);
    }
}
