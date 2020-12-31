<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Illuminate\Support\Collection;

use Auth;
use Validator;

use App\Package;

class ToolController extends Controller
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

	public function indexPackage(Request $request)
	{
		return view('dashboard.tool.index-package');
	}

	public function get_list() {
        $modal = Package::all();

        $data = array();
        for ($i=0; $i < count($modal); $i++) {
            $obj = new \stdClass; 
            $obj->id = $modal[$i]->id;
            $obj->name = $modal[$i]->name;
            $obj->barcode = $modal[$i]->barcode;
            $obj->price = $modal[$i]->price;
            $actionview = 
            "<button class='btn btn-primary dropdown-toggle' type='button' id='dropdownMenuButton' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                Action
            </button>
            <div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>
            <a href='/package/edit/".$modal[$i]->id."' title='Edit' class='dropdown-item'> Edit </a> 
            </div>";
            $obj->Actions = $actionview; 
            $data[] = $obj;
        }
        $modal = new Collection($data);

        return Datatables::of($modal)->rawColumns(['Actions', 'confirmed'])->make(true);
    }

    public function addPackage(Request $request)
    {
    	return view('dashboard.tool.new-package');
    }

    public function createPackage(Request $request) {
    	$validator = Validator::make($request->all(),[
	        'name' => 'required',
	        'barcode' => 'required',
	        'price' => 'required|numeric'
	    ]);

	    if ($validator->fails()) {
	    	return redirect('/package/add')
                        ->withErrors($validator)
                        ->withInput();
	    } else {
	    	$package = new Package;
	    	$package->name = $request->input('name');
	    	$package->barcode = $request->input('barcode');
	    	$package->price = $request->input('price');

	    	$package->save();
	    	$request->session()->flash('success', 'New packaging has been successfully added');
	    }

	    return redirect('/package');
    }

    public function editPackage(Request $request, $id)
    {
    	$package = Package::find($id);
        if($package->count() > 0) return view('dashboard.tool.edit-package', ['package' => $package]);
    }

    public function updatePackage(Request $request, $id) {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
	        'price' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return redirect('/package/add')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $package = Package::find($id);
            $package->name = $request->input('name');
	    	$package->price = $request->input('price');

            $package->save();
            $request->session()->flash('success', 'Packaging info has been successfully updated');
        }

        return redirect('/package/edit/'.$id);       
    }

    public function deletePackage(Request $request, $id) {
        $responseMessage = '';

        $package = Package::where('id',$id);
        $package->delete();
        $request->session()->flash('success', 'Selected packaging been has successfully deleted');

        return redirect('/package');
    }
}
