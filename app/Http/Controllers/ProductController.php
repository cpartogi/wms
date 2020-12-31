<?php

namespace App\Http\Controllers;

use App\Client;
use App\Color;
use App\Http\Services\Jubelio\Product as JProduct;
use App\Inbound;
use App\Inbound_detail;
use App\InboundLocation;
use App\JubelioSyncProduct;
use App\Product;
use App\ProductType;
use App\ProductTypeSize;
use App\User;
use App\Warehouse;
use Auth;
use AWS;
use DB;
use Excel;
use Log;
use Validator;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $client = Client::all();
        $warehouse = Warehouse::all();

        return view('dashboard.product.index', [
            'clients' => $client,
            'warehouses' => $warehouse
        ]);
    }
    
    public function get_list(Request $request)
    {
        $client_id = null;
        $columns = array('id', 'image_url', 'product_type', 'name', 'client', 'color', 'created_at', 'action');
        if (Auth::user()->roles == 'client') {
            if (($key = array_search('client', $columns)) !== false) {
                unset($columns[$key]);
            }
        }
        $total = null;
        
        if (Auth::user()->roles == 'client') {
            $user  = User::find(Auth::user()->id);
            $total = DB::table('product')
                ->leftJoin('image', 'image.product_id', '=', 'product.id')
                ->join('product_type', 'product.product_type_id', '=', 'product_type.id')
                ->join('client', 'client.id', '=', 'product.client_id')
                ->select('product.id', 'product_type.name as product_type', 'product.name', 'product.created_at',
                    'image.s3url as product_img', 'client.name as client_name', 'product.color')
                ->groupBy('product.id')
                ->distinct()
                ->where('product.client_id', '=', $user->client_id)
                ->get()->count();
            
            $client_id = $user->client_id;
        } else {
            $total = DB::table('product')
                ->leftJoin('image', 'image.product_id', '=', 'product.id')
                ->join('product_type', 'product.product_type_id', '=', 'product_type.id')
                ->join('client', 'client.id', '=', 'product.client_id')
                ->select('product.id', 'product_type.name as product_type', 'product.name', 'product.created_at',
                    'image.s3url as product_img', 'client.name as client_name', 'product.color')
                ->groupBy('product.id')
                ->where('product.client_id', '=', $request->input('client'))
                ->distinct()
                ->get()->count();

            $client_id = $request->input('client');
        }
        
        $totalFiltered = $total;
        
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir   = $request->input('order.0.dir');
        
        $boots = null;
        
        if (empty($request->input('search.value'))) {
            if (Auth::user()->roles == 'client') {
                $user  = User::find(Auth::user()->id);
                $boots = DB::table('product')
                    ->leftJoin('image', 'image.product_id', '=', 'product.id')
                    ->join('product_type', 'product.product_type_id', '=', 'product_type.id')
                    ->join('client', 'client.id', '=', 'product.client_id')
                    ->select('product.id', 'product_type.name as product_type', 'product.name', 'product.created_at',
                        'image.s3url as product_img', 'client.name as client_name', 'product.color', 'product.product_partner_id')
                    ->groupBy('product.id')
                    ->distinct()
                    ->where('product.client_id', '=', $client_id)
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
            } else {
                $boots = DB::table('product')
                    ->leftJoin('image', 'image.product_id', '=', 'product.id')
                    ->join('product_type', 'product.product_type_id', '=', 'product_type.id')
                    ->join('client', 'client.id', '=', 'product.client_id')
                    ->leftJoin('omnichannel_db.product_partner as pp', 'pp.id', '=', 'product.product_partner_id')
                    ->select('product.id', 'product_type.name as product_type', 'product.name', 'product.created_at',
                        'pp.external_sku', 'image.s3url as product_img', 'client.name as client_name', 'product.color', 'product.product_partner_id')
                    ->groupBy('product.id')
                    ->distinct()
                    ->where('product.client_id', '=', $client_id)
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
            }
            
        } else {
            $search = $request->input('search.value');
            
            if (Auth::user()->roles == 'client') {
                $user  = User::find(Auth::user()->id);
                $boots = DB::table('product')
                    ->leftJoin('image', 'image.product_id', '=', 'product.id')
                    ->join('product_type', 'product.product_type_id', '=', 'product_type.id')
                    ->join('client', 'client.id', '=', 'product.client_id')
                    ->select('product.id', 'product_type.name as product_type', 'product.name', 'product.created_at', 'image.s3url as product_img', 'client.name as client_name', 'product.color', 'product.product_partner_id')
                    ->groupBy('product.id')
                    ->distinct()
                    ->where('product.client_id', '=', $client_id)
                    ->where('product.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('client.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('product_type.name', 'LIKE', '%' . $search . '%')
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();

                $totalFiltered = DB::table('product')
                    ->leftJoin('image', 'image.product_id', '=', 'product.id')
                    ->join('product_type', 'product.product_type_id', '=', 'product_type.id')
                    ->join('client', 'client.id', '=', 'product.client_id')
                    ->select('product.id', 'product_type.name as product_type', 'product.name', 'product.created_at', 'image.s3url as product_img', 'client.name as client_name', 'product.color')
                    ->groupBy('product.id')
                    ->distinct()
                    ->where('product.client_id', '=', $client_id)
                    ->where('product.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('client.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('product_type.name', 'LIKE', '%' . $search . '%')
                    ->get()->count();
                
            } else {
                $boots = DB::table('product')
                    ->leftJoin('image', 'image.product_id', '=', 'product.id')
                    ->join('product_type', 'product.product_type_id', '=', 'product_type.id')
                    ->join('client', 'client.id', '=', 'product.client_id')
                    ->leftJoin('omnichannel_db.product_partner as pp', 'pp.id', '=', 'product.product_partner_id')
                    ->select('product.id', 'product_type.name as product_type', 'product.name', 'product.created_at', 'pp.external_sku', 'image.s3url as product_img', 'client.name as client_name', 'product.color', 'product.product_partner_id')
                    ->groupBy('product.id')
                    ->distinct()
                    ->where('product.client_id', '=', $client_id)
                    ->where('product.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('client.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('pp.external_sku', 'LIKE', '%' . $search . '%')
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
                
                $totalFiltered = DB::table('product')
                    ->leftJoin('image', 'image.product_id', '=', 'product.id')
                    ->join('product_type', 'product.product_type_id', '=', 'product_type.id')
                    ->join('client', 'client.id', '=', 'product.client_id')
                    ->leftJoin('omnichannel_db.product_partner as pp', 'pp.id', '=', 'product.product_partner_id')
                    ->select('product.id', 'product_type.name as product_type', 'product.name', 'product.created_at', 'pp.external_sku', 'image.s3url as product_img', 'client.name as client_name', 'product.color')
                    ->groupBy('product.id')
                    ->distinct()
                    ->where('product.client_id', '=', $client_id)
                    ->where('product.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('client.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('pp.external_sku', 'LIKE', '%' . $search . '%')
                    ->get()->count();
            }
        }

        $data = array();
        if (!empty($boots)) {
            foreach ($boots as $product) {
                $obj                 = array();
                $obj['id']           = $product->id;
                $obj['image_url']    = (($product->product_img != null) ? "https://s3-ap-southeast-1.amazonaws.com/static-pakde/" . str_replace(" ", "+", $product->product_img) : "http://13.229.209.36:3006/assets/client-image.png");
                $obj['product_type'] = $product->product_type;
                if (Auth::user()->roles != 'client') {                
                    $obj['external_sku'] = $product->external_sku;
                }
                $obj['name']         = $product->name;
                if (Auth::user()->roles != 'client') {
                    $obj['client'] = $product->client_name;
                }
                $obj['color']      = ($product->color != null) ? $product->color : '-';
                $obj['data_added'] = date('d-M-Y H:i', strtotime($product->created_at));
                $obj['source'] = (is_null($product->product_partner_id) ? "WMS" : "Jubelio");
                $actionview        = '<div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Action
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item" href="/product/edit/' . $product->id . '">Edit</a>
                                <a class="dropdown-item" href="/product/variants/' . $product->id . '">View Stocks</a>';
                
                if (Auth::user()->roles != 'crew') {
                    $actionview .= '<a class="dropdown-item delete-btn" href="#" data-id="' . $product->id . '">Delete Product</a>';
                }
                $actionview .= '<a class="dropdown-item" href="product/location/' . $product->id . '">Locations</a></div></div>';
                $obj['action'] = $actionview;
                $data[]        = $obj;
            }
        }
        
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($total),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        
        echo json_encode($json_data);
    }
    
    public function add(Request $request)
    {
        $client_id = $request->input('id');
        $raw       = Color::where('status', 1)->get();
        $colors    = array();
        
        foreach ($raw as $key => $val) {
            if (!array_key_exists($val->parent, $colors)) {
                $colors[$val->parent] = array();
            }
            array_push($colors[$val->parent], $val->name);
        }
        
        if (isset($client_id)) {
            $check = Client::where('id', $client_id)->count();
            if ($check > 0) {
                return view('dashboard.product.new', ['client_id' => $client_id, 'colors' => $colors]);
            }
        }
        
        return view('dashboard.product.new', ['client_id' => $client_id, 'colors' => $colors]);
    }
    
    public function edit(Request $request, $id)
    {
        $client_id = $request->input('id');
        
        if (isset(Auth::user()->client_id) && Product::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
            $request->session()->flash('error', 'You are not allowed to access this product');
            
            return redirect('product');
        }
        
        $product = Product::find($id);
        $images  = DB::table('image')
            ->where('product_id', '=', $id)
            ->get();
        $raw     = Color::where('status', 1)->get();
        $colors  = array();
        
        foreach ($raw as $key => $val) {
            if (!array_key_exists($val->parent, $colors)) {
                $colors[$val->parent] = array();
            }
            array_push($colors[$val->parent], $val->name);
        }
        
        if (isset($client_id) && $product->count() > 0) {
            $check = Client::where('id', $client_id)->count();
            if ($check > 0) {
                return view('dashboard.product.edit', ['client_id' => $client_id, 'product' => $product, 'images' => $images, 'colors' => $colors]);
            }
        } else if ($product->count() > 0) {
            return view('dashboard.product.edit', ['client_id' => $client_id, 'product' => $product, 'images' => $images, 'colors' => $colors]);
        }
    }
    
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id'       => 'required',
            'product_type_id' => 'required',
            'name'            => 'required',
            'qc_point'        => 'required'
        ]);
        
        if ($validator->fails()) {
            return redirect('/product/add')
                ->withErrors($validator)
                ->withInput();
        } else {
            $product                       = new Product;
            $product->version              = 1;
            $product->client_id            = $request->input('client_id');
            $product->product_type_id      = $request->input('product_type_id');
            $product->name                 = $request->input('name');
            $product->price                = $request->input('price');
            $product->product_price_sizing = $request->input('product_price_sizing');
            $product->color = $request->input('color');
            $product->weight = $request->input('weight');
            $product->dimension = json_encode(array('w' => $request->input('dimension-w'),'h' => $request->input('dimension-h'),'d' => $request->input('dimension-d')));
            $product->qc_point = $request->input('qc_point');
            $product->status = 'DRAFT';
//            $product->save();

            $namabarang = $request->input('name');

            if (Product::where('name',$request->input('name'))->where('client_id',$request->input('client_id'))->count() > 0) {
                return redirect('/product/add')
                ->withErrors('Product '.$namabarang.' from this client already registered, please use another name.')
                ->withInput();
            }
            else {
                $product->save();    
            }

            if($request->file('product_img') != null){
                $file = $request->file('product_img');
                $ori = $file->getClientOriginalName();
                $ext = $file->getClientOriginalExtension();
                $size = $file->getSize();
                $newName = date('Ymd')."_".rand(100000,1001238912).".".$ext;
                $file->move('images/products',$newName);

                $img_path = public_path()."/images/products/";

                $s3 = AWS::createClient('s3');
                $upload = $s3->putObject(array(
                    'Bucket'     => 'static-pakde',
                    'Key'        => $newName,
                    'SourceFile' => $img_path . $newName,
                    'ACL'        => 'public-read'
                ));
                
                unlink($img_path . $newName);
                
                DB::table('image')
                    ->insert([
                        'version'    => 0,
                        'label'      => $ori . "_" . date('YmdHis'),
                        'product_id' => $product->id,
                        's3url'      => $newName
                    ]);
            }
            
            $request->session()->flash('success', 'New product has successfully added');
        }
        
        return redirect('/product');
    }
    
    public function bulkUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bulk-product' => 'required'
        ]);
        
        if ($validator->fails()) {
            return redirect('/product')
                ->withErrors($validator)
                ->withInput();
        } else {
            
            if ($request->hasFile('bulk-product')) {
                $path        = $request->file('bulk-product')->getRealPath();
                $spreadsheet = Excel::load($path);
                $tempImages  = array();
                
                $i = 0;
                foreach ($spreadsheet->getActiveSheet()->getDrawingCollection() as $drawing) {
                    if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                        ob_start();
                        call_user_func(
                            $drawing->getRenderingFunction(),
                            $drawing->getImageResource()
                        );
                        $imageContents = ob_get_contents();
                        ob_end_clean();
                        switch ($drawing->getMimeType()) {
                            case \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::MIMETYPE_PNG :
                                $extension = 'png';
                                break;
                            case \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::MIMETYPE_GIF:
                                $extension = 'gif';
                                break;
                            case \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::MIMETYPE_JPEG :
                                $extension = 'jpg';
                                break;
                        }
                    } else {
                        $zipReader     = fopen($drawing->getPath(), 'r');
                        $imageContents = '';
                        while (!feof($zipReader)) {
                            $imageContents .= fread($zipReader, 1024);
                        }
                        fclose($zipReader);
                        $extension = $drawing->getExtension();
                    }
                    $img_path   = public_path() . "/images/products/";
                    $myFileName = $img_path . '00_Image_' . ++$i . '.' . $extension;
                    file_put_contents($myFileName, $imageContents);
                    $tempImages[intval(substr($drawing->getCoordinates(), 1)) - 2] = $myFileName;
                }
                
                $data = $spreadsheet->get();
                if ($data->count()) {
                    foreach ($data as $key => $value) {
                        $client = null;
                        if (Auth::user()->roles == 'client') {
                            $user   = User::find(Auth::user()->id);
                            $client = Client::find($user->client_id);
                        } else {
                            $client = Client::where('name', $value->client)->first();
                        }
                        
                        if ($client == null) {
                            if (Auth::user()->roles == 'client') {
                                $request->session()->flash('error', 'Client with email ' . $user->email . ' is not registered yet');
                            } else {
                                $request->session()->flash('error', 'Product on line ' . strval($key + 1) . ', doesn\'t has registered client ' . $value->client . '. Please double check');
                            }
                            
                            return redirect('/product');
                        }
                        
                        $type = ProductType::where('name', $value->product_type)->first();
                        
                        if ($type == null) {
                            $request->session()->flash('error', 'Product on line ' . strval($key + 1) . ', product type ' . $value->product_type . ' is not registered yet');
                            
                            return redirect('/product');
                        }
                        
                        $product = Product::where('name', $value->product_name)->where('client_id', $client->id)->count();
                        // W x H x D
                        $dimension = explode("/", $value->dimension_w_x_h_x_d_cm);
                        if ($product > 0) {
                            $request->session()->flash('error', 'Product on line ' . strval($key + 1) . ' already registered, please use another name.');
                            
                            return redirect('/product');
                        } else if ($client != null && $type != null) {
                            $product_id = DB::table('product')->insertGetId(['client_id' => $client->id, 'name' => $value->product_name, 'price' => $value->product_price, 'weight' => $value->weight, 'dimension' => json_encode(array("w" => $dimension[0], "h" => $dimension[1], "d" => $dimension[2])), 'color' => $value->color, 'product_type_id' => $type->id, 'status' => 'DRAFT', 'description' => $value->description, 'created_at' => date('Y-m-d h:i:s'), 'updated_at' => date('Y-m-d h:i:s')]);
                            
                            if (array_key_exists($key, $tempImages) && $tempImages[$key] !== null) {
                                $newName = date('Ymd') . "_" . rand(100000, 1001238912) . "." . substr(strrchr($tempImages[$key], '.'), 1);
                                
                                $s3     = AWS::createClient('s3');
                                $upload = $s3->putObject(array(
                                    'Bucket'     => 'static-pakde',
                                    'Key'        => $newName,
                                    'SourceFile' => $tempImages[$key],
                                    'ACL'        => 'public-read'
                                ));
                                
                                DB::table('image')
                                    ->insert([
                                        'version'    => 0,
                                        'label'      => "00_Image_" . $key . '.' . date('YmdHis'),
                                        'product_id' => $product_id,
                                        's3url'      => $newName
                                    ]);
                            }
                            
                            $request->session()->flash('success', 'Bulk product has succesfully uploaded.');
                            
                        } else {
                            if ($client == null) {
                                $request->session()->flash('error', 'This client name is not registered yet on product line ' . strval($key + 1));
                                
                                return redirect('/product');
                            } else if ($type == null) {
                                $request->session()->flash('error', 'Unable to find related product type on product line ' . strval($key + 1));
                                
                                return redirect('/product');
                            }
                        }
                    }
                    
                } else {
                    $request->session()->flash('error', 'Please put at least one new product on excel.');
                }
            } else {
                $request->session()->flash('error', 'Please upload the formatted bulk file first.');
            }
            
        }
        
        // Remove all image temp files
        // $files = glob(public_path()."/images/products/*");
        // foreach($files as $file){
        //   if(is_file($file))
        //     unlink($file);
        // }
        
        return redirect('/product');
    }
    
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required',
            'name'      => 'required',
            'tags'      => 'required',
            'color'     => 'required',
            'price'     => 'required'
        ]);
        
        if ($validator->fails()) {
            return redirect('/product/edit/' . $id)
                ->withErrors($validator)
                ->withInput();
        } else {
            $product                       = Product::find($id);
            $product->client_id            = $request->input('client_id');
            $product->name                 = $request->input('name');
            $product->description          = $request->input('description');
            $product->price                = $request->input('price');
            $product->product_price_sizing = $request->input('product_price_sizing');
            $product->color                = $request->input('color');
            $product->weight               = $request->input('weight');
            $product->dimension            = json_encode(array('w' => $request->input('dimension-w'), 'h' => $request->input('dimension-h'), 'd' => $request->input('dimension-d')));
            $product->tags                 = $request->input('tags');
            $product->save();
            
            if ($request->file('product_img') != null && count($request->file('product_img')) > 0) {
                $s3 = AWS::createClient('s3');
                foreach ($request->file('product_img') as $file) {
                    $ori     = $file->getClientOriginalName();
                    $ext     = $file->getClientOriginalExtension();
                    $size    = $file->getSize();
                    $newName = date('Ymd') . "_" . rand(100000, 1001238912) . "." . $ext;
                    $file->move('images/products', $newName);
                    $img_path = public_path() . "/images/products/";
                    
                    $upload = $s3->putObject(array(
                        'Bucket'     => 'static-pakde',
                        'Key'        => $newName,
                        'SourceFile' => $img_path . $newName,
                        'ACL'        => 'public-read'
                    ));
                    
                    unlink($img_path . $newName);
                    
                    DB::table('image')
                        ->insert([
                            'version'    => 0,
                            'label'      => $ori . "_" . date('YmdHis'),
                            'product_id' => $product->id,
                            's3url'      => $newName
                        ]);
                }
            }
            
            $request->session()->flash('success', 'Product has successfully updated');
        }
        
        return redirect('/product/edit/' . $id);
    }
    
    public function delete(Request $request)
    {
        $responseMessage = '';
        $products        = explode(',', $request->input('p'));
        $names           = array();
        
        foreach ($products as $id) {
            // Check the order
            $check_order = DB::table('order_detail')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                ->where('inbound_detail.product_id', '=', $id)
                ->count();
            
            if ($check_order == 0) {
                $check_inbound = Inbound::where('product_id', $id)->count();
                if ($check_inbound == 0 || $request->input('forced') != null) {
                    // Delete images
                    $images = DB::table('image')
                        ->where('product_id', '=', $id)
                        ->get();
                    $s3     = AWS::createClient('s3');
                    foreach ($images as $image) {
                        $exp = explode('/', $image->s3url);
                        $s3->deleteObject([
                            'Bucket' => 'static-pakde',
                            'Key'    => 'products/' . end($exp)
                        ]);
                    }
                    
                    DB::table('image')
                        ->where('product_id', '=', $id)
                        ->delete();
                    
                    // Delete order detail
                    $inbound_details = DB::table('inbound_detail')
                        ->where('product_id', '=', $id)
                        ->get();
                    
                    $order_deletes = array();
                    
                    foreach ($inbound_details as $ikey => $ivalue) {
                        $order_details = DB::table('order_detail')
                            ->where('inbound_detail_id', '=', $ivalue->id)
                            ->get();
                        
                        foreach ($order_details as $okey => $ovalue) {
                            if (!in_array($ovalue->orders_id, $order_deletes)) {
                                array_push($order_deletes, $ovalue->orders_id);
                            }
                        }
                        
                        DB::table('order_detail')
                            ->where('inbound_detail_id', '=', $ivalue->id)
                            ->delete();
                        
                        DB::table('orders')
                            ->whereIn('id', $order_deletes)
                            ->delete();
                        
                    }
                    
                    // Delete detail location
                    DB::table('inbound_detail_location')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                        ->where('inbound_detail.product_id', '=', $id)
                        ->delete();
                    
                    // Delete detail
                    DB::table('inbound_detail')
                        ->where('product_id', '=', $id)
                        ->delete();
                    
                    // Delete inbound
                    DB::table('inbound')
                        ->where('product_id', '=', $id)
                        ->delete();
                    
                    // Delete product location
                    DB::table('product_detail_location')
                        ->join('product_detail', 'product_detail.id', '=', 'product_detail_location.product_detail_id')
                        ->where('product_detail.product_id', '=', $id)
                        ->delete();
                    
                    // Delete product detail
                    DB::table('product_detail')
                        ->where('product_id', $id)
                        ->delete();
                    
                    // Delete product
                    $product = Product::find($id);
                    array_push($names, $product->name);
                    $product->delete();
                    $request->session()->flash('success', 'Selected products have successfully deleted');
                    
                } else {
                    if (count($names) > 0) {
                        $request->session()->flash('success', 'These products (' . implode(', ', $names) . ') have successfully deleted');
                    }
                    $request->session()->flash('error', 'This product is still related to inbound, please remove from inbound first');
                    
                    return redirect('/product/edit/' . $id);
                }
            } else {
                if (count($names) > 0) {
                    $request->session()->flash('success', 'These products (' . implode(', ', $names) . ') have successfully deleted');
                }
                $request->session()->flash('error', 'This product is still related to order, please remove from order detail first');
                
                return redirect('/product/edit/' . $id);
            }
        }
        
        return redirect('/product');
    }
    
    /**
     * Product Excel
     */
    
    public function downloadExcel(Request $request)
    {
        // Array data
        $result = [];
        $i = 0;
        $offset = 5000;
        
        $clientId = null;
        $roles = Auth::user()->roles;

        if (Auth::user()->roles == 'client')
            $clientId = Auth::user()->client_id;
        else
            $clientId = $request->input('client');

        while (true) {
            // Build query for result
            $query = DB::connection('read_replica')
                ->table('product')
                ->join('client', 'client.id', '=', 'product.client_id')
                ->leftJoin('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                ->leftJoin('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->leftJoin('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                ->leftJoin('omnichannel_db.product_partner as pp', 'pp.id', '=', 'product.product_partner_id')
                ->select(
                    'client.name as client', 'product.name as product', 'product.weight', 'product.dimension',
                    'product_type_size.name as size', 'inbound_detail.color', 'inbound_detail_location.shelf_id',
                    'inbound_detail_location.date_outbounded', 'inbound_detail_location.order_detail_id',
                    'inbound_detail_location.date_picked', 'inbound_detail_location.date_rejected',
                    'inbound_detail_location.date_adjustment', 'inbound_detail_location.date_ordered', 'pp.external_sku as external_sku'
                )
                ->where('client.id', '=', $clientId)
                ->skip($i * $offset)
                ->take($offset);

            // Check if user loggedin is client or admin
            // if (Auth::user()->roles == 'client')
            //     $query->where('product.client_id', '=', Auth::user()->client_id);

            $data = $query->get();

            foreach ($data as $stock) { 
                $index = $stock->client.'_'.$stock->product.'_'.$stock->color.'_'.$stock->size.'_'.$stock->external_sku;
            
                // Push new stock
                if (!array_key_exists($index, $result)) {
                    $dimension = json_decode($stock->dimension);
                    $result[$index] = array(
                        'Client' => $stock->client,
                        'Product' => $stock->product,
                        'External SKU' => $stock->external_sku,
                        'Size' => $stock->size,
                        'Weight (Kg)' => $stock->weight,
                        'Dimension (W/H/D in cm)' => $dimension->w.'/'.$dimension->h.'/'.$dimension->d,
                        'Total Qty' => 0,
                        'Available' => 0,
                        'Rejected Qty' => 0,
                        'Adjustment Qty' => 0,
                        'Ordered' => 0,
                        'Processing' => 0,
                        'Shipped' => 0
                    );

                    if($roles == 'client') {
                        unset($result[$index]['Adjustment Qty']); 
                    }
                }

                // Count manually
                if ($stock->shelf_id != null) {
                    if ($stock->date_outbounded == null) {
                        if ($stock->date_rejected == null) {
                            if ($stock->date_adjustment == null) {
                                $result[$index]['Total Qty']++;
                                if ($stock->date_picked == null) {
                                    if ($stock->order_detail_id == null)
                                        $result[$index]['Available']++;
                                    else
                                        $result[$index]['Ordered']++;
                                } else if ($stock->order_detail_id != null)
                                    $result[$index]['Processing']++;
                            }
                        } else
                            $result[$index]['Rejected Qty']++;
                    } else if ($stock->order_detail_id != null && $stock->date_picked != null)
                        $result[$index]['Shipped']++;
                } else if ($stock->date_rejected != null) {
                    $result[$index]['Rejected Qty']++;
                }

                // Count Adjustment Qty
                if ($roles != 'client'){
                    if ($stock->date_adjustment != null) {
                        $result[$index]['Adjustment Qty']++;
                    }
                }
            }

            // Stop if data is not full
            if (count($data) < $offset)
                break;
            
            $i++;
        }

        // Generate Excel
        Excel::create('product-stocks-' . date('Ymd-His'), function ($excel) use ($result) {
            $excel->sheet('Stocks', function ($sheet) use ($result) {
                $sheet->fromArray($result);

                // Set header background color
                $sheet->row(1, function($row) {
                    $row->setBackground('#191970');
                    $row->setFontColor('#ffffff');
                    $row->setFontWeight('bold');
                });
            });
        })->download();
    }
    
    /**
     *   Product Variance
     */
    
    public function indexVariant(Request $request, $id)
    {
        
        if (isset(Auth::user()->client_id) && Product::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
            $request->session()->flash('error', 'You are not allowed to access this product variant');
            
            return redirect('product');
        }
        
        $variant = DB::table('inbound_detail_location')
            ->select('product.id as product_id', 'product.name as product_name', 'inbound_detail.id as inbound_detail_id', 'inbound_detail.sku as inbound_detail_sku', 'inbound_detail.name', 'inbound_detail.price', 'warehouse.name as warehouse', 'product_type_size.name as size_name', 'inbound_detail.actual_qty', 'inbound_detail.color', 'inbound_detail.product_type_size_id', 'inbound_detail.sku as sku')
            ->leftJoin('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
            ->leftJoin('product', 'product.id', '=', 'inbound_detail.product_id')
            ->leftJoin('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
            ->leftJoin('rack', 'rack.id', '=', 'shelf.rack_id')
            ->leftJoin('warehouse', 'warehouse.id', '=', 'rack.warehouse_id')
            ->where('product.id', '=', $id)
            ->whereNotNull('warehouse.id')
            ->groupBy('inbound_detail.product_type_size_id', 'inbound_detail.color')
            ->get();
        
        foreach ($variant as $k => $v) {
            $jubelio_sku = "-";
            if ($synced = DB::table('jubelio_sync_product')->where('jubelio_sync_product.pakde_inbound_detail_sku', '=', $v->sku)->first()) {
                $jubelio_sku = $synced->jubelio_variant_item_code;
            }
            $variant[$k]->jubelio_sku = $jubelio_sku;
            
            $variant[$k]->total = DB::table('inbound_detail_location')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->where('inbound_detail.product_type_size_id', '=', $v->product_type_size_id)
                ->where('inbound_detail.color', '=', $v->color)
                ->where('inbound_detail.product_id', '=', $v->product_id)
                ->whereNotNull('inbound_detail_location.shelf_id')
                ->whereNull('inbound_detail_location.date_outbounded')
                ->whereNull('inbound_detail_location.date_rejected')
                ->whereNull('inbound_detail_location.date_adjustment')
                ->count();
            
            $variant[$k]->available = DB::table('inbound_detail_location')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->where('inbound_detail.product_type_size_id', '=', $v->product_type_size_id)
                ->where('inbound_detail.color', '=', $v->color)
                ->where('inbound_detail.product_id', '=', $v->product_id)
                ->whereNotNull('inbound_detail_location.shelf_id')
                ->whereNull('inbound_detail_location.date_outbounded')
                ->whereNull('inbound_detail_location.date_rejected')
                ->whereNull('inbound_detail_location.date_adjustment')
                ->whereNull('inbound_detail_location.order_detail_id')
                ->whereNull('inbound_detail_location.date_picked')
                ->count();
            
            $variant[$k]->ordered = DB::table('inbound_detail_location')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->where('inbound_detail.product_type_size_id', '=', $v->product_type_size_id)
                ->where('inbound_detail.color', '=', $v->color)
                ->where('inbound_detail.product_id', '=', $v->product_id)
                ->whereNotNull('inbound_detail_location.shelf_id')
                ->whereNull('inbound_detail_location.date_outbounded')
                ->whereNull('inbound_detail_location.date_rejected')
                ->whereNull('inbound_detail_location.date_adjustment')
                ->whereNotNull('inbound_detail_location.order_detail_id')
                ->whereNull('inbound_detail_location.date_picked')
                ->count();
            
            $variant[$k]->reserved = DB::table('inbound_detail_location')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->where('inbound_detail.product_type_size_id', '=', $v->product_type_size_id)
                ->where('inbound_detail.color', '=', $v->color)
                ->where('inbound_detail.product_id', '=', $v->product_id)
                ->whereNotNull('inbound_detail_location.shelf_id')
                ->whereNull('inbound_detail_location.date_outbounded')
                ->whereNull('inbound_detail_location.date_rejected')
                ->whereNull('inbound_detail_location.date_adjustment')
                ->whereNotNull('inbound_detail_location.order_detail_id')
                ->whereNotNull('inbound_detail_location.date_picked')
                ->count();
            
            $variant[$k]->outbound = DB::table('inbound_detail_location')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->where('inbound_detail.product_type_size_id', '=', $v->product_type_size_id)
                ->where('inbound_detail.color', '=', $v->color)
                ->where('inbound_detail.product_id', '=', $v->product_id)
                ->whereNotNull('inbound_detail_location.shelf_id')
                ->whereNotNull('inbound_detail_location.order_detail_id')
                ->whereNotNull('inbound_detail_location.date_picked')
                ->whereNotNull('inbound_detail_location.date_outbounded')
                ->count();
            
            $variant[$k]->rejected = DB::table('inbound_detail_location')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->where('inbound_detail.product_type_size_id', '=', $v->product_type_size_id)
                ->where('inbound_detail.color', '=', $v->color)
                ->where('inbound_detail.product_id', '=', $v->product_id)
                ->whereNotNull('inbound_detail_location.date_rejected')
                ->count();
            
            $variant[$k]->adjustment = DB::table('inbound_detail_location')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->where('inbound_detail.product_type_size_id', '=', $v->product_type_size_id)
                ->where('inbound_detail.color', '=', $v->color)
                ->where('inbound_detail.product_id', '=', $v->product_id)
                ->whereNotNull('inbound_detail_location.date_adjustment')
                ->count();
        }
        
        $product = Product::find($id);
        
        return view('dashboard.product.index-variant', ['variant' => $variant, 'product' => $product]);
    }
    
    public function addVariant(Request $request, $id)
    {
        $raw     = Color::where('status', 1)->get();
        $colors  = array();
        $product = DB::table('product')
            ->select('product.id', 'product.name as product_name', 'client.name as client_name', 'product_type.name as type_name', 'product.product_type_id', 'client.id as client_id')
            ->join('client', 'client.id', '=', 'product.client_id')
            ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
            ->where('product.id', '=', $id)
            ->first();
        
        foreach ($raw as $key => $val) {
            if (!array_key_exists($val->parent, $colors)) {
                $colors[$val->parent] = array();
            }
            array_push($colors[$val->parent], $val->name);
        }
        
        return view('dashboard.product.new-variant', ['product' => $product, 'colors' => $colors]);
    }
    
    public function createVariant(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'product_id'       => 'required',
            'product_type_id'  => 'required',
            'name'             => 'required',
            'color'            => 'required',
            'price'            => 'required',
            'type_size'        => 'required',
            'numeric-key'      => 'required_if:type_size,NUMERICAL',
            'numeric-value'    => 'required_if:type_size,NUMERICAL',
            'alphabetic-key'   => 'required_if:type_size,ALPHABETIC',
            'alphabetic-value' => 'required_if:type_size,ALPHABETIC'
        ]);
        
        if ($validator->fails()) {
            return redirect('/product/variants/' . $id . '/add')
                ->withErrors($validator)
                ->withInput();
        } else {
            
            $dimension_name = '';
            $qty            = ($request->input('type_size') == 'NUMERICAL') ? $request->input('numeric-value') : $request->input('alphabetic-value');
            
            if ($request->input('type_size') == 'ALPHABETIC') {
                $alpha          = explode('|', $request->input('alphabetic-key'));
                $dimension_name = $alpha[1];
            } else {
                $dimension_name = $request->input('numeric-key');
            }
            
            $check = DB::table('inbound_detail')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->where('inbound_detail.product_id', '=', $id)
                ->where('product_type_size.name', '=', $dimension_name)
                ->where('product_type_size.product_type_id', '=', $request->input('product_type_id'))
                ->where('inbound_detail.color', '=', $request->input('color'))
                ->count();
            
            if ($check == 0 && $qty > 0) {
                $detail              = new Inbound_detail;
                $detail->version     = 0;
                $detail->actual_qty  = $qty;
                $detail->stated_qty  = $qty;
                $detail->code        = generate_code();
                $detail->color       = $request->input('color');
                $detail->name        = $request->input('name');
                $detail->description = $request->input('description');
                $detail->price       = $request->input('price');
                $detail->product_id  = $request->input('product_id');
                
                // Product type size
                $product_type_size = ProductTypeSize::where('name', $dimension_name)->where('product_type_id', $request->input('product_type_id'));
                if ($product_type_size->count() == 0) {
                    $size_type                  = new ProductTypeSize;
                    $size_type->version         = 2;
                    $size_type->active          = 1;
                    $size_type->name            = $dimension_name;
                    $size_type->product_type_id = $request->input('product_type_id');
                    $size_type->save();
                    $detail->product_type_size_id = $size_type->id;
                } else {
                    $detail->product_type_size_id = $product_type_size->first()->id;
                }
                
                $detail->sku    = str_replace(" ", "", strtoupper($request->input('client_name'))) . "/" . str_replace(" ", "", strtoupper($request->input('product_type_name'))) . "/" . str_replace(" ", "", strtoupper($request->input('product_name'))) . "/" . str_replace(" ", "", strtoupper($request->input('color'))) . "/" . $dimension_name;
                $detail->status = "ACTIVE";
                $detail->tags   = $request->input('tags');
                $detail->save();
                
                for ($i = 0; $i < $qty; $i++) {
                    $location                    = new InboundLocation;
                    $location->version           = 0;
                    $location->code              = 'PDL' . time() . substr(md5(uniqid(mt_rand(), true)), 0, 11);
                    $location->date_stored       = date('Y-m-d H:i:s');
                    $location->inbound_detail_id = $detail->id;
                    $location->save();
                }
                
                $request->session()->flash('success', 'New variant of this product has successfully added');
            } else {
                $request->session()->flash('error', 'This variant already registered for this product, or check the quantity amount.');
            }
            
        }
        
        return redirect('/product/variants/' . $id . '/add');
    }
    
    public function editVariant(Request $request, $id, $detail_id)
    {
        $raw    = Color::where('status', 1)->get();
        $colors = array();
        
        $product = DB::table('inbound_detail')
            ->select('product.id', 'inbound_detail.id as variant_id', 'product.name as product_name', 'client.name as client_name', 'product_type.name as type_name', 'product.product_type_id', 'client.id as client_id', 'inbound_detail.name as variant_name', 'inbound_detail.color', 'inbound_detail.price', 'inbound_detail.tags', 'inbound_detail.description', 'product_type_size.name as size_name')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('client', 'client.id', '=', 'product.client_id')
            ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->where('inbound_detail.id', '=', $detail_id)
            ->first();
        
        foreach ($raw as $key => $val) {
            if (!array_key_exists($val->parent, $colors)) {
                $colors[$val->parent] = array();
            }
            array_push($colors[$val->parent], $val->name);
        }
        
        return view('dashboard.product.edit-variant', ['product' => $product, 'colors' => $colors]);
    }
    
    public function editJubelioSKU(Request $request, $id, $detail_id)
    {
        $jubelio_sku = "";
        
        $product = DB::table('inbound_detail')
            ->select('product.id', 'inbound_detail.id as inbound_detail_id', 'product.name as product_name', 'client.name as client_name', 'product_type.name as type_name', 'product.product_type_id', 'client.id as client_id', 'inbound_detail.name as variant_name', 'inbound_detail.sku as sku', 'inbound_detail.color', 'inbound_detail.price', 'inbound_detail.tags', 'inbound_detail.description', 'product_type_size.name as size_name')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('client', 'client.id', '=', 'product.client_id')
            ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->where('inbound_detail.id', '=', $detail_id)
            ->first();
        
        if ($synced = DB::table('jubelio_sync_product')->where('jubelio_sync_product.pakde_inbound_detail_sku', '=', $product->sku)->first()) {
            $jubelio_sku = $synced->jubelio_variant_item_code;
        }
        
        return view('dashboard.product.edit-sku-jubelio', ['product' => $product, 'jubelio_sku' => $jubelio_sku]);
    }
    
    public function processEditJubelioSKU(Request $request, $id, $detail_id)
    {
        if ($product = DB::table('inbound_detail')
            ->select('product.id as product_id', 'inbound_detail.id as inbound_detail_id', 'product.name as product_name', 'client.name as client_name', 'product_type.name as type_name', 'product.product_type_id', 'client.id as client_id', 'inbound_detail.name as variant_name', 'inbound_detail.sku as sku', 'inbound_detail.color', 'inbound_detail.price', 'inbound_detail.tags', 'inbound_detail.description', 'product_type_size.name as size_name')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('client', 'client.id', '=', 'product.client_id')
            ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->where('inbound_detail.id', '=', $detail_id)
            ->first()
        ) {
            $jSync = new JubelioSyncProduct();
            
            if ($synced = $jSync->where('pakde_inbound_detail_sku', $product->sku)->first()) {
                $synced->pakde_product_id          = $product->product_id;
                $synced->jubelio_variant_item_code = $request->input('jubelio_sku');
                $synced->save();
            } else {
                $jSync->pakde_product_id          = $product->product_id;
                $jSync->client_id                 = $product->client_id;
                $jSync->pakde_inbound_detail_sku  = $product->sku;
                $jSync->jubelio_variant_item_code = $request->input('jubelio_sku');
                $jSync->save();
            }
            
            $p    = new JProduct();
            $skus = [];
            $item = $p->getItemBySku($product->client_id, $request->input('jubelio_sku'));
            foreach ($item->product_skus as $key => $product_sku) {
                $item->product_skus[$key]->item_category_name = $item->category->category_name;
                $item->product_skus[$key]->item_group_name    = $item->item_group_name;
                $item->product_skus[$key]->weight             = $item->package_weight / 1000;
                $item->product_skus[$key]->description        = $item->description;
                $item->product_skus[$key]->notes              = $item->notes;
                $item->product_skus[$key]->item_group_id      = $item->item_group_id;
            }
            $skus = array_merge($skus, $item->product_skus);
            $skus = collect($skus);
            
            $p->synchronizeSku($skus, $product->client_id);
            
            $request->session()->flash('success', 'Your SKU has successfully updated');
        } else {
            $request->session()->flash('error', 'Product detail not found!');
        }
        
        return redirect(route('product.variants', $id));
    }
    
    public function updateVariant(Request $request, $id, $detail_id)
    {
        $validator = Validator::make($request->all(), [
            'product_id'      => 'required',
            'product_type_id' => 'required',
            'name'            => 'required',
            'color'           => 'required',
            'price'           => 'required'
        ]);
        
        if ($validator->fails()) {
            return redirect('/product/variants/' . $id . '/edit/' . $detail_id)
                ->withErrors($validator)
                ->withInput();
        } else {
            
            $check = DB::table('inbound_detail')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->where('inbound_detail.product_id', '=', $id)
                ->where('product_type_size.name', '=', $request->input('product_type_size'))
                ->where('product_type_size.product_type_id', '=', $request->input('product_type_id'))
                ->where('inbound_detail.color', '=', $request->input('color'))
                ->count();
            
            if ($check == 0 || ($check > 0 && $request->input('color') == $request->input('old_color'))) {
                
                $detail              = Inbound_detail::find($detail_id);
                $detail->name        = $request->input('name');
                $detail->color       = $request->input('color');
                $detail->description = $request->input('description');
                $detail->price       = $request->input('price');
                $detail->tags        = $request->input('tags');
                $detail->save();
                
                $request->session()->flash('success', 'This product variant has successfully updated.');
            } else {
                $request->session()->flash('error', 'This variant with the same color and size of this product already registered.');
            }
            
        }
        
        return redirect('/product/variants/' . $id . '/edit/' . $detail_id);
    }
    
    /**
     *   Product Location
     */
    
    public function indexLocation(Request $request, $id)
    {
        
        if (isset(Auth::user()->client_id) && Product::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
            $request->session()->flash('error', 'You are not allowed to access this product location');
            
            return redirect('product');
        }
        
        $detail = DB::table('product')
            ->select('product.id as product_id', 'product.name as product_name', 'client.name as client_name', 'product_type.name as type_name', 'inbound_detail.tags', 'inbound_detail.description', 'product.price', 'product.dimension')
            ->leftJoin('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
            ->leftJoin('client', 'client.id', '=', 'product.client_id')
            ->leftJoin('product_type', 'product_type.id', '=', 'product.product_type_id')
            ->where('product.id', '=', $id)
            ->first();
        
        $locations = DB::table('inbound_detail_location')
            ->select('inbound_detail_location.id', 'inbound_detail_location.code', 'shelf.name as shelf_name', 'inbound_detail_location.date_rejected', 'inbound_detail_location.date_stored', 'product_type_size.name as size_name', 'product.color', 'inbound_detail_location.order_detail_id', 'inbound_detail_location.id', 'inbound_detail_location.date_ordered', 'inbound_detail_location.date_picked', 'inbound_detail_location.date_outbounded', 'warehouse.name as warehouse')
            ->join('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
            ->leftJoin('rack', 'rack.id', '=', 'shelf.rack_id')
            ->leftJoin('warehouse', 'warehouse.id', '=', 'rack.warehouse_id')
            ->where('product.id', '=', $id)
            ->whereNull('inbound_detail_location.date_outbounded')
            ->get();
        
        return view('dashboard.product.index-location', ['product' => $detail, 'locations' => $locations]);
    }
    
    /**
     *   Product Adjustment
     */
    
    public function adjustmentList(Request $request)
    {
        $adjustment = null;
        
        if (Auth::user()->roles == 'client') {
            return redirect('/');
            /*$user = User::find(Auth::user()->id);
            $adjustment = DB::table('adjustment')
                ->join('inbound_detail_location','inbound_detail_location.id','=','adjustment.inbound_detail_location_id')
                ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                ->join('inbound','inbound.id','=','inbound_detail.inbound_id')
                ->join('client','client.id','=','inbound.client_id')
                ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
                ->join('shelf','shelf.id','=','inbound_detail_location.shelf_id')
                ->where('inbound.client_id','=',$user->client_id)
                ->select('adjustment.id','inbound_detail.name as product_name','product_type_size.name as size_name','client.name as client_name','inbound.batch_id','adjustment.status','adjustment.created_at')
                ->orderBy('adjustment.created_at')
                ->get();*/
        } else {
            $adjustment = DB::table('adjustment')
                ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                ->join('client', 'client.id', '=', 'inbound.client_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at')
                ->orderBy('adjustment.created_at')
                ->get();
        }
        
        if ($adjustment == null) {
            return redirect('/product');
        }
        
        return view('dashboard.product.index-adjustment', ['adjs' => $adjustment]);
    }
    
    public function adjust_list(Request $request)
    {
        $columns = array('id', 'product', 'client', 'size', 'batch', 'status', 'date_added', 'action');
        if (Auth::user()->roles == 'client') {
            if (($key = array_search('client', $columns)) !== false) {
                unset($columns[$key]);
            }
        }
        $total = null;
        
        if (Auth::user()->roles == 'client') {
            $user  = User::find(Auth::user()->id);
            $total = DB::table('adjustment')
                ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                ->join('client', 'client.id', '=', 'inbound.client_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                ->where('inbound.client_id', '=', $user->client_id)
                ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at')
                ->orderBy('adjustment.created_at')
                ->get()->count();
        } else {
            $total = DB::table('adjustment')
                ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                ->join('client', 'client.id', '=', 'inbound.client_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at')
                ->orderBy('adjustment.created_at')
                ->get()->count();
        }
        
        $totalFiltered = $total;
        
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir   = $request->input('order.0.dir');
        
        $boots = null;
        
        if (empty($request->input('search.value'))) {
            if (Auth::user()->roles == 'client') {
                $user  = User::find(Auth::user()->id);
                $boots = DB::table('adjustment')
                    ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('client', 'client.id', '=', 'inbound.client_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->where('inbound.client_id', '=', $user->client_id)
                    ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at')
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy('adjustment.created_at', $order, $dir)
                    ->get();
            } else {
                $boots = DB::table('adjustment')
                    ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('client', 'client.id', '=', 'inbound.client_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at')
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy('adjustment.created_at', $order, $dir)
                    ->get();
            }
            
        } else {
            $search = $request->input('search.value');
            
            if (Auth::user()->roles == 'client') {
                $user  = User::find(Auth::user()->id);
                $boots = DB::table('adjustment')
                    ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('client', 'client.id', '=', 'inbound.client_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->where('inbound.client_id', '=', $user->client_id)
                    ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at')
                    ->where('inbound_detail.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('product_type_size.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('client.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('adjustment.status', 'LIKE', '%' . $search . '%')
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy('adjustment.created_at', $order, $dir)
                    ->get();
                
                $totalFiltered = DB::table('adjustment')
                    ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('client', 'client.id', '=', 'inbound.client_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->where('inbound.client_id', '=', $user->client_id)
                    ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at')
                    ->orderBy('adjustment.created_at')
                    ->where('inbound_detail.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('product_type_size.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('client.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('adjustment.status', 'LIKE', '%' . $search . '%')
                    ->get()->count();
                
            } else {
                $boots = DB::table('adjustment')
                    ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('client', 'client.id', '=', 'inbound.client_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at')
                    ->where('inbound_detail.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('product_type_size.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('client.name', 'LIKE', '%' . $search . '%')
                    ->orWhere('adjustment.status', 'LIKE', '%' . $search . '%')
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy('adjustment.created_at', $order, $dir)
                    ->get();
                
                $totalFiltered = DB::table('adjustment')
                    ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                    ->join('client', 'client.id', '=', 'inbound.client_id')
                    ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at')
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy('adjustment.created_at', $order, $dir)
                    ->get()->count();
            }
        }
        
        $data = array();
        if (!empty($boots)) {
            foreach ($boots as $adjust) {
                $obj            = array();
                $obj['id']      = $adjust->id;
                $obj['product'] = $adjust->product_name;
                if (Auth::user()->roles != 'client') {
                    $obj['client'] = $adjust->client_name;
                }
                $obj['size']       = $adjust->size_name;
                $obj['batch']      = '#' . str_pad($adjust->batch_id, 5, '0', STR_PAD_LEFT);
                $obj['status']     = ($adjust->status == 1) ? 'Adjusted' : 'On Checking';
                $obj['data_added'] = date('d-M-Y H:i', strtotime($adjust->created_at));
                $actionview        = '<div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Action
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item" href="/product/adjustment/' . $adjust->id . '">View History</a>
                            </div>
                        </div>';
                $obj['action']     = $actionview;
                $data[]            = $obj;
            }
        }
        
        $json_data = array(
            "draw"            => intval($request->input('draw')),
            "recordsTotal"    => intval($total),
            "recordsFiltered" => intval($totalFiltered),
            "data"            => $data
        );
        
        echo json_encode($json_data);
    }
    
    public function indexAdjustment(Request $request, $id)
    {
        $adjustment = null;
        if (Auth::user()->roles == 'client') {
            $user       = User::find(Auth::user()->id);
            $adjustment = DB::table('adjustment')
                ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                ->join('client', 'client.id', '=', 'inbound.client_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                ->join('users as u1', 'u1.id', '=', 'adjustment.reporter_id')
                ->join('users as u2', 'u2.id', '=', 'adjustment.approver_id')
                ->where('inbound.client_id', '=', $user->client_id)
                ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at', 'u1.name as reporter', 'u2.name as approver')
                ->where('adjustment.id', '=', $id)
                ->first();
        } else {
            $adjustment = DB::table('adjustment')
                ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'adjustment.inbound_detail_location_id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
                ->join('client', 'client.id', '=', 'inbound.client_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                ->join('users as u1', 'u1.id', '=', 'adjustment.reporter_id')
                ->join('users as u2', 'u2.id', '=', 'adjustment.approver_id')
                ->select('adjustment.id', 'inbound_detail.name as product_name', 'product_type_size.name as size_name', 'client.name as client_name', 'inbound.batch_id', 'adjustment.status', 'adjustment.created_at', 'adjustment.updated_at', 'u1.name as reporter', 'u2.name as approver')
                ->where('adjustment.id', '=', $id)
                ->first();
        }
        
        $log = DB::table('inbound_detail_location')
            ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
            ->join('inbound', 'inbound.id', '=', 'inbound_detail.inbound_id')
            ->join('inbound_batch', 'inbound_batch.id', '=', 'inbound.batch_id')
            ->leftJoin('order_detail', 'order_detail.id', '=', 'inbound_detail_location.order_detail_id')
            ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
            ->leftJoin('rack', 'rack.id', '=', 'shelf.rack_id')
            ->leftJoin('warehouse', 'warehouse.id', '=', 'rack.warehouse_id')
            ->leftJoin('users as u1', 'u1.id', '=', 'inbound_detail_location.officer_id')
            ->leftJoin('users as u2', 'u2.id', '=', 'inbound_batch.receiver_id')
            ->select('inbound_detail_location.date_stored', 'inbound_detail_location.date_ordered', 'inbound_detail_location.date_adjustment', 'inbound_detail_location.date_picked', 'inbound_detail_location.date_outbounded', 'inbound_batch.id as batch_id', 'inbound_batch.arrival_date', 'inbound_batch.sender_name', 'inbound_batch.courier', 'u1.name as officer', 'u2.name as receiver', 'shelf.name as shelf_name', 'warehouse.name as warehouse_name')
            ->first();
        
        if ($adjustment == null) {
            return redirect('/product/adjustment');
        }
        
        return view('dashboard.product.edit-adjustment', ['detail' => $adjustment, 'log' => $log]);
    }
    
    public function reset(Request $request)
    {
        try {
            $updatedStocks = DB::table('inbound_detail_location')
                ->whereNull('date_picked')
                ->whereNull('date_outbounded')
                ->whereNotNull('order_detail_id')
                ->where('updated_at', '<', DB::raw('DATE_SUB(now(), INTERVAL 6 HOUR)'))
                ->update([
                    'date_ordered'    => null,
                    'order_detail_id' => null
                ]);
            
            $updatedOrder = DB::table('orders')
                ->where('updated_at', '<', DB::raw('DATE_SUB(now(), INTERVAL 6 HOUR)'))
                ->where('status', '=', 'READY_FOR_OUTBOUND')
                ->update([
                    'status' => 'PENDING'
                ]);
            
            echo "There are " . $updatedStocks . " stocks affected.<br>There are " . $updatedOrder . " orders affected.";
        } catch (\Illuminate\Database\QueryException $e) {
            echo $e->getMessage();
        }
    }
    
    public function synchronizeStockJubelio()
    {
        $jproduct = new JProduct();
        
        $jubelio_products = DB::table('jubelio_sync_product')
            ->select('pakde_product_id', 'jubelio_variant_item_code', 'client_id', 'pakde_inbound_detail_sku')
            ->where('jubelio_variant_item_code', '!=', "")
            ->get();
        
        foreach ($jubelio_products as $i => $jubelio_product) {
            $client_id = $jubelio_product->client_id;
            
            $v = DB::table('inbound_detail')
                ->select('product.id as product_id', 'product.name as product_name', 'inbound_detail.id as inbound_detail_id', 'inbound_detail.sku as inbound_detail_sku', 'inbound_detail.name', 'inbound_detail.price', 'product_type_size.name as size_name', 'inbound_detail.actual_qty', 'inbound_detail.color', 'inbound_detail.product_type_size_id', 'inbound_detail.sku as sku')
                ->leftJoin('product', 'product.id', '=', 'inbound_detail.product_id')
                ->leftJoin('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->where('inbound_detail.sku', '=', $jubelio_product->pakde_inbound_detail_sku)
                ->groupBy('inbound_detail.product_type_size_id', 'inbound_detail.color')
                ->first();
            
            $jubelio_product->available = DB::table('inbound_detail_location')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->where('inbound_detail.product_type_size_id', '=', $v->product_type_size_id)
                ->where('inbound_detail.color', '=', $v->color)
                ->where('inbound_detail.product_id', '=', $v->product_id)
                ->whereNotNull('inbound_detail_location.shelf_id')
                ->whereNull('inbound_detail_location.order_detail_id')
                ->whereNull('inbound_detail_location.date_picked')
                ->whereNull('inbound_detail_location.date_outbounded')
                ->count();
            
            $jproduct->synchronizeStockToJubelio($jubelio_product->jubelio_variant_item_code, $jubelio_product->pakde_inbound_detail_sku, $client_id, $jubelio_product->available);
        }
        
        // disini ada kode produknya jubelio dan berapa quantity seharusnya (available)
//        dd($jubelio_products);
    }
}
