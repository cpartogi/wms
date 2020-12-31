<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Hash;
use Auth;
use DB;
use AWS;
use Image;

use App\User;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if(Auth::user()->roles == 'crew')
            {
                return redirect('/');
            }

            return $next($request);
        });
    }
    
    public function index(Request $request)
    {
        $users = null;
        if(Auth::user()->roles == 'client'){
            $users = User::where('client_id','=',Auth::user()->client_id)->where('id','<>',Auth::user()->id)->get();
        } else {
            $users = DB::table('users')
                ->leftJoin('client','client.id','=','users.client_id')
                ->select('users.*','client.name as client_name')
                ->get();
        }
    	
    	return view('dashboard.user.index',['users' => $users]);
    }

    public function edit(Request $request, $id)
    {
    	$responseMessage = '';
    	$responseCode = '';

        if(isset(Auth::user()->client_id) && User::where('client_id',Auth::user()->client_id)->where('id',$id)->count() == 0){
            $request->session()->flash('error', 'You are not allowed to edit this user');
            return redirect('user');
        }

    	$user = User::find($id);
    	if($user->count() > 0){
    		return view('dashboard.user.edit',['user' => $user]);
    	}
    }

    public function update(Request $request, $id)
    {
    	$validator = Validator::make($request->all(),[
	        'id' => 'required',
	        'name' => 'required',
	        'email' => 'required|email',
	        'phone' => 'required',
            'warehouse_id' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/user/edit/'.$id)
                        ->withErrors($validator)
                        ->withInput();
	    } else {
	    	$user = User::find($id);

            // Create user for dashboard access
            if ($user->email != $request->input('email') && User::where('email',$request->input('email'))->count() > 0) {
                $request->session()->flash('error', 'Email address already registered');
                return redirect('/user/edit/'.$id)
                        ->withErrors($validator)
                        ->withInput();
            }

            if ($user->phone != $request->input('phone') && User::where('phone',$request->input('phone'))->count() > 0) {
                $request->session()->flash('error', 'Phone number already registered');
                return redirect('/user/edit/'.$id)
                        ->withErrors($validator)
                        ->withInput();
            }

	    	$user->name = $request->input('name');
	    	$user->phone = $request->input('phone');
	    	$user->email = $request->input('email');
	    	$user->roles = $request->input('roles');
	    	$user->status = $request->input('status');
            if(Auth::user()->roles == 'client'){
                $user->client_id = Auth::user()->client_id;
            }
            $user->warehouse_id = $request->input('warehouse_id');
	    	if(strlen(trim($request->input('password'))) > 0){
	    		$user->password = Hash::make($request->input('password'));
	    	}
	    	$user->save();
	    	$request->session()->flash('success', 'User has successfully updated');
	    }

	    return redirect('/user/edit/'.$id);
    }

    public function delete(Request $request, $id)
    {
    	$responseMessage = '';

    	$count = User::all()->count();
    	if($count > 1){
    		$user = User::where('id',$id);
    		$user->delete();
    		$request->session()->flash('success', 'Selected user has successfully deleted');
    	} else {
    		$responseCode = '01';
    		$request->session()->flash('error', 'You need at least one user in the system');
    		return redirect('/user/edit/'.$id);
    	}

    	return redirect('/user');
    }

    public function add(Request $request)
    {
    	return view('dashboard.user.new');
    }

    public function create(Request $request)
    {
    	$validator = Validator::make($request->all(),[
	        'name' => 'required',
            'email' => 'required|email',
	        'phone' => 'required',
	        'roles' => 'required',
	        'password' => 'required',
            'warehouse_id' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/user/add')
                        ->withErrors($validator)
                        ->withInput();
	    } else {

            // Check if email exists
            if (User::where('email',$request->input('email'))->count() > 0) {
                $request->session()->flash('error', 'Email address already registered');
                return redirect('/user/add')
                        ->withErrors($validator)
                        ->withInput();
            }

            if (User::where('phone',$request->input('phone'))->count() > 0) {
                $request->session()->flash('error', 'Phone number already registered');
                return redirect('/user/edit/'.$id)
                        ->withErrors($validator)
                        ->withInput();
            }

	    	$user = new User;
	    	$user->name = $request->input('name');
	    	$user->phone = $request->input('phone');
	    	$user->email = $request->input('email');
            if(Auth::user()->roles == 'client'){
                $user->client_id = Auth::user()->client_id;
            }
	    	$user->roles = $request->input('roles');
            $user->warehouse_id = $request->input('warehouse_id');
	    	$user->password = Hash::make($request->input('password'));
	    	$user->status = $request->input('status');
	    	$user->save();
            
	    	$request->session()->flash('success', 'New user has successfully added');
	    }

	    return redirect('/user');
    }

    public function integration()
    {
        if(Auth::user()->roles == 'client'){
            if(!isset(Auth::user()->client_key)){
                $user = User::find(Auth::user()->id);
                $user->client_key = str_random(32);
                $user->save();
            }
            return view('dashboard.user.integration',['user' => Auth::user()]);
        } else {
            return redirect('/');
        }
    }

    public function updateIntegration(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'api_key' => 'required',
            'allowed_url' => 'required|url|active_url'
        ]);

        if ($validator->fails()) {
            return redirect('/integration')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $user = User::find(Auth::user()->id);

            $user->secret_key = $request->input('api_key');
            if(trim($request->input('api_key')) != "" && ($request->input('api_key') != $user->api_key || time() >= strtotime($user->key_expired))){
                $user->key_expired = date('Y-m-d H:i:s',strtotime('+1 year'));
            }
            $user->auto_refresh = ($request->input('auto_refresh') != null && $request->input('auto_refresh') == "on")?1:0;
            $user->allowed_url = $request->input('allowed_url');
            $user->save();
            $request->session()->flash('success', 'User has successfully updated');
        }

        return redirect('/integration');
    }

    public function ajaxGenerateKey(Request $request)
    {
        $responseData = str_random(60);
        $responseCode = "00";
        $responseMessage = "Generate api key success!";

        return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }

    public function profile(Request $request)
    {
        $responseMessage = '';
        $responseCode = '';

        $user = User::find(Auth::user()->id);

        return view('dashboard.user.profile',['user' => $user]);
    }

    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'user_id' => 'required',
            'name' => 'required',
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return redirect('/profile')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $user = User::find($request->input('user_id'));

            $user->name = $request->input('name');
            $user->email = $request->input('email');
            if(strlen(trim($request->input('password'))) > 0){
                $user->password = Hash::make($request->input('password'));
            }

            if($request->file('pictures') != null){
                $s3 = AWS::createClient('s3');
                $ori = $request->file('pictures')->getClientOriginalName();
                $ext = $request->file('pictures')->getClientOriginalExtension();
                $size = $request->file('pictures')->getSize();
                $newName = date('Ymd')."_user_".rand(100000,1001238912).".".$ext;
                $request->file('pictures')->move('images/products',$newName);
                $img_path = public_path()."/images/products/";
                $img = Image::make($img_path.$newName)->fit(80);
                $tmpName = date('Ymd')."_user_".rand(100000,1001238912)."-80.".$ext;
                $img->save($img_path.$tmpName);
                
                $upload = $s3->putObject(array(
                    'Bucket'     => 'static-pakde',
                    'Key'        => $newName,
                    'SourceFile' => $img_path.$tmpName,
                    'ACL'=>'public-read'
                ));

                unlink($img_path.$newName);
                unlink($img_path.$tmpName);

                $user->pictures = $newName;
            }

            $user->save();
            $request->session()->flash('success', 'Your profile has successfully updated');
        }

        return redirect('/profile');
    }
}
