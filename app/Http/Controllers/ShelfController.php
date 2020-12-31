<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use DB;
use Validator;
use Hash;
use QRCode;
use Auth;

use App\Rack;
use App\Shelf;
use App\InboundLocation;

class ShelfController extends Controller
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
        $rack = Rack::find($id);
    	return view('dashboard.shelf.index', ['rack_id' => $id, 'warehouse_id' => $rack->warehouse_id]);
    }

    public function get_list($id) {
    	$model = Shelf::query()->where('rack_id', '=', $id);

        return Datatables::of($model)
        		->addColumn('id', function(Shelf $shelf){
        			return $shelf->id;
        		})
        		->addColumn('code', function(Shelf $shelf){
        			return $shelf->code;
        		})
        		->addColumn('name', function(Shelf $shelf){
        			return $shelf->name;
        		})
        		->addColumn('row', function(Shelf $shelf){
        			return $shelf->row;
        		})
        		->addColumn('col', function(Shelf $shelf){
        			return $shelf->col;
        		})
        		->addColumn('Actions', function(Shelf $shelf){
        			return "
                    <button class='btn btn-primary dropdown-toggle' type='button' id='dropdownMenuButton' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                        Action
                    </button>
                    <div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>
                    <a href='/shelf/edit/".$shelf->id."' title='Edit' class='dropdown-item'> Edit </a> 
                    <a href='/shelf/barcode/single/".$shelf->id."' title='Print QRCode' class='dropdown-item'> Print QRCode </a>
                    </div>";
        		})
		        ->rawColumns(['Actions', 'confirmed'])        		
        		->make(true);
    }

    public function add($id) {
    	$rack = Rack::find($id);
    	return view('dashboard.shelf.new', ['rack' => $rack]);    	
    }

    public function create(Request $request) {
    	$validator = Validator::make($request->all(),[
	        'name' => 'required',
            'row' => 'required',
            'col' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/shelf/add/'.$request->warehouse_id)
                        ->withErrors($validator)
                        ->withInput();
	    } else {
            $rack = Rack::find($request->rack_id);
	    	$shelf = new Shelf;
	    	$shelf->name = $request->input('name');
            $shelf->col = $request->input('col');
            $shelf->row = $request->input('row');
	    	$shelf->version = 1;
	    	$shelf->is_active = 1;
	    	$shelf->code = $rack->code.'/SHL/'.str_replace(" ","",strtoupper($request->input('name')));
	    	$shelf->rack_id = $request->rack_id;

	    	$shelf->save();
	    	$request->session()->flash('success', 'New shelf has been successfully added');
	    }

	    return redirect('/shelf/'.$request->rack_id);
    }

    public function edit($id) {
    	$shelf = Shelf::find($id);
    	return view('dashboard.shelf.edit', ['shelf' => $shelf]);
    }

    public function update(Request $request, $id) {
    	$validator = Validator::make($request->all(),[
	        'name' => 'required',
            'row' => 'required',
            'col' => 'required'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/shelf/add/'.$request->rack_id)
                        ->withErrors($validator)
                        ->withInput();
	    } else {
            $rack = Rack::find($request->rack_id);
	    	$shelf = Shelf::find($id);
	    	$shelf->name = $request->input('name');
            $shelf->col = $request->input('col');
            $shelf->row = $request->input('row');
            $shelf->version = $shelf->version + 1;
            $shelf->code = $rack->code.'/SHL/'.str_replace(" ","",strtoupper($request->input('name')));

	    	$shelf->save();
	    	$request->session()->flash('success', 'Shelf has been successfully modified');
	    }

	    return redirect('/shelf/edit/'.$id);
    }

    public function delete(Request $request, $id) {
        $responseMessage = '';

        $shelf = Shelf::find($id);
        $rack_id = $shelf->rack_id;

        if($shelf == null){
            $request->session()->flash('error', 'Selected shelf not found.');
        } else if (InboundLocation::where('shelf_id',$id)->count() > 0){
            $request->session()->flash('error', 'Selected shelf is still connected to your stocks. Please delete the stock first.');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            $shelf->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $request->session()->flash('success', 'Selected shelf been has successfully deleted.');
        }

        return redirect('/shelf/'.$rack_id);
    }

    public function bulkPrint(Request $request, $id)
    {
        $responseData = Shelf::where('rack_id',$id)->get();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8', 
            'format' => [66,25],
            'orientation' => 'P',
            'margin_left' => 0,
            'margin_right' => 1,
            'margin_top' => 4,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);

        $mpdf->WriteHTML(view('dashboard.pdf.shelf-print',[
            'shelfs' => $responseData,
        ])->render());

        return $mpdf->Output();
    }

    public function singlePrint(Request $request, $id)
    {
        $responseData = Shelf::find($id);

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8', 
            'format' => [66,25],
            'orientation' => 'P',
            'margin_left' => 0,
            'margin_right' => 1,
            'margin_top' => 4,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
        ]);

        $mpdf->WriteHTML(view('dashboard.pdf.shelf-print-single',[
            'shelf' => $responseData,
        ])->render());

        return $mpdf->Output();
    }
}