<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Collection;
use DB;
use Validator;
use Hash;
use Excel;
use Auth;

use App\Warehouse;
use App\Rack;
use App\Shelf;

class WarehouseController extends Controller
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
    
    public function index (Request $request) {
        return view('dashboard.warehouse.index');
    }

    public function get_list() {
        $modal = DB::table('warehouse')
            ->leftJoin('users','users.id','=','warehouse.head_id')
            ->select('warehouse.*','users.name as head')
            ->get();
    	// $model = Warehouse::query();

        $data = array();
        for ($i=0; $i < count($modal); $i++) {
            $obj = new \stdClass; 
            $obj->id = $modal[$i]->id;
            $obj->code = $modal[$i]->code;
            $obj->name = $modal[$i]->name;
            $obj->address = $modal[$i]->address;
            $obj->zip_code = $modal[$i]->zip_code;
            $obj->head = (isset($modal[$i]->head))?$modal[$i]->head:"-";
            $actionview = 
            "<button class='btn btn-primary dropdown-toggle' type='button' id='dropdownMenuButton' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                Action
            </button>
            <div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>
            <a href='/warehouse/edit/".$modal[$i]->id."' title='Edit' class='dropdown-item'> Edit </a> 
            <a href='/rack/".$modal[$i]->id."' title='View Rack' class='dropdown-item'> View Racks </a>
            <a href='#' title='View Product' class='dropdown-item'> View Products </a>
            </div>";
            $obj->Actions = $actionview; 
            $data[] = $obj;
        }
        $modal = new Collection($data);

        return Datatables::of($modal)->rawColumns(['Actions', 'confirmed'])->make(true);
    }

    public function add() {
    	return view('dashboard.warehouse.new');
    }

    public function create(Request $request) {
    	$validator = Validator::make($request->all(),[
            'name' => 'required',
            'acronym' => 'required|unique:warehouse,acronym|max:3|alpha',
	        'address' => 'required',
	        'zip_code' => 'required|numeric',
            'head_id' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/warehouse/add')
                        ->withErrors($validator)
                        ->withInput();
	    } else {
	    	$warehouse = new Warehouse;
	    	$warehouse->acronym = $request->input('acronym');
	    	$warehouse->name = $request->input('name');
	    	$warehouse->address = $request->input('address');
	    	$warehouse->zip_code = $request->input('zip_code');
	    	$warehouse->version = 1;
            $warehouse->head_id = $request->input('head_id');
            $warehouse->code = strtoupper(md5(date('Ymd').$request->input('name')));
            $warehouse->is_active = 1;

	    	$warehouse->save();
	    	$request->session()->flash('success', 'New warehouse has been successfully added');
	    }

	    return redirect('/warehouse');
    }

    public function edit(Request $request, $id) {
        $warehouse = Warehouse::find($id);

        if($warehouse->count() > 0) return view('dashboard.warehouse.edit', ['warehouse' => $warehouse]);
    }

    public function update(Request $request, $id) {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'acronym' => 'required|unique:warehouse,acronym|max:3|alpha',
            'address' => 'required',
            'zip_code' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return redirect('/warehouse/add')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $warehouse = Warehouse::find($id);
            $warehouse->acronym = $request->input('acronym');
            $warehouse->name = $request->input('name');
            $warehouse->address = $request->input('address');
            $warehouse->zip_code = $request->input('zip_code');
            $warehouse->head_id = $request->input('head_id');
            $warehouse->version = $warehouse->version + 1;

            $warehouse->save();
            $request->session()->flash('success', 'Warehouse info has been successfully updated');
        }

        return redirect('/warehouse/edit/'.$id);       
    }

    public function delete(Request $request, $id) {
        $responseMessage = '';

        $warehouse = Warehouse::where('id',$id);

        if($warehouse == null){
            $request->session()->flash('error', 'Selected warehouse not found.');
        } else if (Rack::where('warehouse_id',$id)->count() > 0){
            $request->session()->flash('error', 'Selected warehouse is still connected to racks. Please delete the rack first.');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $warehouse->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $request->session()->flash('success', 'Selected warehouse been has successfully deleted');
        }

        return redirect('/warehouse');
    }


    public function bulkUpload(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'bulk-shelf' => 'required',
            'warehouse-id' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect('/warehouse/edit/'.$request->input('warehouse-id'))
                    ->withErrors($validator)
                    ->withInput();
        } else {

            if($request->hasFile('bulk-shelf')){
                $path = $request->file('bulk-shelf')->getRealPath();
                $spreadsheet = Excel::load($path)->get();
                $warehouse_id = $request->input('warehouse-id');
                $warehouse = Warehouse::find($warehouse_id);
                
                if($spreadsheet->count() > 0){
                    foreach ($spreadsheet as $key => $value) {
                        $rack = Rack::where('warehouse_id',$warehouse_id)
                            ->where('name',strtoupper($value->rack))->first();

                        if($rack == null){
                            $rack = new Rack;
                            $rack->name = strtoupper($value->rack);
                            $rack->is_active = 1;
                            $rack->order_no = 1;
                            $rack->warehouse_id = $warehouse_id;
                            $rack->version = 0;
                            $rack->code = $warehouse->acronym.'/RCK/'.strtoupper($value->rack);
                            $rack->save();
                        }

                        $shelf = Shelf::where('rack_id',$rack->id)
                            ->where('col',$value->col)
                            ->where('row',$value->row)
                            ->first();

                        if($shelf == null){
                            $shelf = new Shelf;
                            $name = $rack->name." - ".$value->row." - ".$value->col;
                            $shelf->name = $name;
                            $shelf->col = $value->col;
                            $shelf->row = $value->row;
                            $shelf->version = 1;
                            $shelf->is_active = 1;
                            $shelf->code = $rack->code.'/SHL/'.str_replace(" ","",$name);
                            $shelf->rack_id = $rack->id;

                            $shelf->save();
                        }

                        $request->session()->flash('success', 'All shelf is successfully registered.');
                    }

                    return redirect('/rack/'.$warehouse_id);
                } else {
                    $request->session()->flash('error', 'Please put at least one shelf on excel.');
                }
            } else {
                $request->session()->flash('error', 'Please upload the formatted bulk file first.');
            }
        }

        return redirect('/warehouse/edit/'.$request->input('warehouse-id'));
    }
}
