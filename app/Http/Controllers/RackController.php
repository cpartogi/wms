<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use DB;
use Validator;
use Hash;
use Auth;

use App\Rack;
use App\Warehouse;
use App\Shelf;

class RackController extends Controller
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
    
    public function index (Request $request, $id) {
    	return view('dashboard.rack.index', ['warehouse_id' => $id]);
    }

    public function get_list($id) {
    	$model = Rack::query()->where('warehouse_id', '=', $id);

        return Datatables::of($model)
        		->addColumn('id', function(Rack $rack){
        			return $rack->id;
        		})
                ->addColumn('code', function(Rack $rack){
        			return $rack->code;
        		})
        		->addColumn('name', function(Rack $rack){
        			return $rack->name;
        		})
        		->addColumn('Actions', function(Rack $rack){
        			return "
                    <button class='btn btn-primary dropdown-toggle' type='button' id='dropdownMenuButton' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                        Action
                    </button>
                    <div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>
                    <a href='/rack/edit/".$rack->id."' title='Edit' class='dropdown-item'> Edit </a> 
                    <a href='/shelf/".$rack->id."' title='View Rack' class='dropdown-item'> View Shelf </a>
                    <a href='/shelf/barcode/bulk/".$rack->id."' title='Bulk Barcode Print' class='dropdown-item'> Bulk Barcode Print </a>
                    </div>";
        		})
		        ->rawColumns(['Actions', 'confirmed'])        		
        		->make(true);
    }

    public function add($id) {
    	$warehouse = Warehouse::find($id);
    	return view('dashboard.rack.new', ['warehouse' => $warehouse]);    	
    }

    public function create(Request $request) {
    	$validator = Validator::make($request->all(),[
	        'name' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/rack/add/'.$request->input('warehouse_id'))
                        ->withErrors($validator)
                        ->withInput();
	    } else {
	    	$rack = new Rack;
	    	$rack->name = $request->input('name');
	    	$rack->version = 1;
	    	$rack->is_active = 1;
            $warehouse = Warehouse::find($request->input('warehouse_id'));
	    	$rack->code = $warehouse->acronym.'/RCK/'.strtoupper($request->input('name'));
	    	$rack->warehouse_id = $request->warehouse_id;

	    	$rack->save();
	    	$request->session()->flash('success', 'New rack has been successfully added');
	    }

	    return redirect('/rack/'.$request->warehouse_id);
    }

    public function edit($id) {
    	$rack = Rack::find($id);
    	return view('dashboard.rack.edit', ['rack' => $rack]);
    }

    public function update(Request $request, $id) {
    	$validator = Validator::make($request->all(),[
	        'name' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/rack/add/'.$request->input('warehouse_id'))
                        ->withErrors($validator)
                        ->withInput();
	    } else {
	    	$rack = Rack::find($id);
	    	$rack->name = $request->input('name');
            $rack->version = $rack->version + 1;
            $warehouse = Warehouse::find($request->input('warehouse_id'));
	    	$rack->code = $warehouse->acronym.'/RCK/'.strtoupper($request->input('name'));
	    	$rack->save();
	    	$request->session()->flash('success', 'Rack has been successfully modified');
	    }

	    return redirect('/rack/edit/'.$id);
    }

    public function delete(Request $request, $id) {
        $responseMessage = '';

        $rack = Rack::find($id);
        $warehouse_id = $rack->warehouse_id;

        if($rack == null){
            $request->session()->flash('error', 'Selected rack not found.');
        } else if (Shelf::where('rack_id',$id)->count() > 0){
            $request->session()->flash('error', 'Selected rack is still connected to shelfs. Please delete the shelf first.');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $rack->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $request->session()->flash('success', 'Selected rack been has successfully deleted');
        }

        return redirect('/rack/'.$warehouse_id);
    }
}