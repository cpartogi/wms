<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use DB;
use Auth;

use App\ProductType;
use App\Dimension;
use App\Inbound;

class ProductTypeController extends Controller
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
    
    public function index(Request $request)
    {
    	$types = ProductType::all();
    	return view('dashboard.product.index-type',['types' => $types]);
    }

    public function add(Request $request)
    {
    	return view('dashboard.product.new-type');
    }

    public function create(Request $request)
    {
    	$validator = Validator::make($request->all(),[
	        'name' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/productType/add')
                ->withErrors($validator)
                ->withInput();
	    } else {
            $check = DB::table('product_type')
                ->whereRaw('LOWER(`name`) like ?', array(strtolower($request->input('name'))))
                ->count();
            if($check == 0){
                DB::table('product_type')
                    ->insert([
                        'name' => $request->input('name'),
                        'version' => 0,
                        'active' => 1
                    ]);
            } else {
                $request->session()->flash('error', 'Product type \''.$request->input('name').'\' is exists, please use another name');
                return redirect('/productType');
            }

	    	$request->session()->flash('success', 'New product type has successfully added');
	    }

	    return redirect('/productType');
    }

    public function edit(Request $request, $id)
    {
    	$type = ProductType::find($id);
    	if($type->count() > 0){
    		return view('dashboard.product.edit-type',['type' => $type]);
    	}
    }

    public function update(Request $request, $id)
    {
    	$validator = Validator::make($request->all(),[
	        'name' => 'required',
	        'active' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/productType/add')
                        ->withErrors($validator)
                        ->withInput();
	    } else {
            $check = DB::table('product_type')
                ->whereRaw('LOWER(`name`) like ?', array(strtolower($request->input('name'))))
                ->where('id','<>',$id)
                ->count();
            if($check == 0){
                DB::table('product_type')
                    ->where('id','=',$id)
                    ->update([
                        'name' => $request->input('name'),
                        'active' => intval($request->input('active'))
                    ]);
            } else {
                $request->session()->flash('error', 'Product type \''.$request->input('name').'\' is exists, please use another name');
                return redirect('/productType/edit/'.$id);
            }

	    	$request->session()->flash('success', 'Type has successfully updated');
	    }

	    return redirect('/productType/edit/'.$id);	
    }

    public function delete(Request $request, $id)
    {
        $responseMessage = '';

        if(Inbound::where('product_type_id',$id)->count() > 0){
            $request->session()->flash('error', 'This product type is still attached on product inbound');
        } else {
            DB::table('product_type_size')
                ->where('product_type_id','=',$id)
                ->delete();

            DB::table('product_type')
                ->where('id','=',$id)
                ->delete();
            $request->session()->flash('success', 'Selected product type has successfully deleted');
        }

        return redirect('/productType');
    }

    /**
    *	Product Type Size	
    */

    public function indexSize(Request $request, $type_id)
    {
    	$sizes = DB::table('product_type_size')
                    ->select('product_type_size.*')
                    ->where('product_type_size.product_type_id','=',$type_id)
    				->get();

        $size = ProductType::find($type_id);

    	return view('dashboard.product.index-size',['sizes' => $sizes, 'type_id' => $type_id,'size' => $size]);
    }

    public function addSize(Request $request, $type_id)
    {
        $size = ProductType::find($type_id);
    	return view('dashboard.product.new-size',['type_id' => $type_id,'size' => $size]);
    }

    public function createSize(Request $request, $type_id)
    {
    	$validator = Validator::make($request->all(),[
	        'name' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/productType/size/'.$type_id.'/add')
                ->withErrors($validator)
                ->withInput();
	    } else {
	    	DB::table('product_type_size')
				->insert([
					'name' => $request->input('name'),
					'product_type_id' => $type_id,
					'active' => 1,
					'version' => 1
				]);

	    	$request->session()->flash('success', 'New product size type has successfully added');
	    }

	    return redirect('/productType/size/'.$type_id);
    }

    public function editSize(Request $request, $type_id, $id)
    {
        $size = DB::table('product_type_size')
                ->select('product_type_size.*','product_type.name as type_name')
                ->join('product_type','product_type.id','=','product_type_size.product_type_id')
                ->where('product_type_id','=',$type_id)
                ->where('product_type_size.id','=',$id);
        if($size->count() > 0){
            return view('dashboard.product.edit-size',['size' => $size->first()]);
        } else {
            return redirect('/productType/size/'.$type_id);
        }
    }

    public function updateSize(Request $request, $type_id, $id)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'active' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect('/productType/size/'.$type_id.'/add')
                ->withErrors($validator)
                ->withInput();
        } else {

            DB::table('product_type_size')
                ->where('id','=',$id)
                ->update([
                    'name' => $request->input('name'),
                    'active' => intval($request->input('active'))
                ]);

            $request->session()->flash('success', 'Type has successfully updated');
        }

        return redirect('/productType/size/'.$type_id.'/edit/'.$id);
    }

    public function deleteSize(Request $request, $type_id, $id)
    {
    	$responseMessage = '';

        $check = DB::table('inbound_detail')
                    ->where('product_type_size_id','=',$id)
                    ->count();

        if($check > 0){
            $request->session()->flash('error', 'It can\'t be deleted, there are inbound details related to this size');
            return redirect('/productType/size/'.$type_id.'/edit/'.$id);
        } else {
            DB::table('product_type_size')
            ->where('id','=',$id)
            ->where('product_type_id','=',$type_id)
            ->delete();
        }

    	return redirect('/productType/size/'.$type_id);
    }
}
