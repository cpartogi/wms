<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

use Illuminate\Support\Collection;

use Auth;
use Config;
use DB;
use Excel;
use Log;
use Mail;
use Validator;
use Session;
use Redirect;

use App\Client;
use App\Inbound;
use App\Inbound_detail;
use App\InboundBatch;
use App\InboundLocation;
use App\Product;
use App\ProductDetail;
use App\ProductLocation;
use App\ProductType;
use App\ProductTypeSize;
use App\User;

use Carbon\Carbon;
use GuzzleHttp\Client as HttpClient;

class InboundController extends Controller
{
    
    public function index (Request $request) {
        $clients = Client::orderBy('name')->get();
        return view('dashboard.inbound.index',  compact('clients'));
    }
   
    public function get_list(Request $request) {
        $query = null;

        $limit = $request->input('length');
        $start = $request->input('start');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $clientId = $request->input('client');
        $search = $request->input('search.value');

        // Date Processing
        if (!empty($startDate) && !empty($endDate)) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay(); // Make sure the time still latest cause its 00:00:00

            // Will add validation on front end as well, just to make sure in back end
            if ($startDate->diffInDays($endDate) > 31) 
                $endDate = $startDate->copy()->addDays(31);
        } else {
            // If Somehow the date is empty, it will take last 1 week, will add on front end also
            $startDate = Carbon::now()->subDays(30);
            $endDate = Carbon::now()->endOfDay(); // Make sure the time still latest
        }

        $query = DB::connection('read_replica')
            ->table('inbound_batch')
            ->select('inbound_batch.id as batch_id','inbound_batch.arrival_date','inbound_batch.status',
                'inbound_batch.updated_at','inbound_batch.inbound_partner_id','inbound_batch.template_url',
                'ip.external_inbound_batch as external_inbound_batch',
                'client.name as client_name', 'inbound.id as inbound_id')
            ->join('client', 'client.id', '=', 'inbound_batch.client_id')
            ->join('inbound', 'inbound.batch_id' , '=', 'inbound_batch.id')
            ->join('inbound_detail', 'inbound_detail.inbound_id', '=', 'inbound.id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->leftJoin('omnichannel_db.inbound_partner as ip', 'ip.id', '=', 'inbound_batch.inbound_partner_id')
            ->orderBy('inbound_batch.created_at', 'DESC')
            ->where('inbound_batch.created_at','>=',  $startDate->format('Y-m-d H:i:s'))
            ->where('inbound_batch.created_at','<=', $endDate->format('Y-m-d H:i:s'));

        if (Auth::user()->roles == 'client') {
            $user   = User::find(Auth::user()->id);
            $clientId = $user->client_id;
        }
        
        if (!empty($clientId)) {
            $query->where('inbound_batch.client_id','=',$clientId);
        }

        // Count all data
        $total = $query->count(DB::raw("DISTINCT inbound_batch.id"));

        // Handle search
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('inbound_batch.id', 'LIKE', $search.'%')
                ->orWhere('ip.external_inbound_batch', 'LIKE', $search.'%')
                ->orWhere('product.name', 'LIKE', $search.'%');
            });
        }

        // Count filtered data
        $totalFiltered = $query->count(DB::raw("DISTINCT inbound_batch.id"));

        $inbound_data = $query->limit($limit)->offset($start)->groupBy('inbound_batch.id')->get();

        $data = array();
        for ($i=0; $i < count($inbound_data); $i++) {
            $obj = new \stdClass; 
            $obj->id = '#'.str_pad($inbound_data[$i]->batch_id,5,'0',STR_PAD_LEFT);
            $obj->arrival_date = date('d M Y H:i', strtotime($inbound_data[$i]->arrival_date));
            $obj->status = $inbound_data[$i]->status;
            $obj->source = is_null($inbound_data[$i]->inbound_partner_id) ? "WMS" : "Jubelio";
            $obj->client_name = $inbound_data[$i]->client_name;

            $details = DB::connection('read_replica')
                ->table('inbound')
                ->join('inbound_detail', 'inbound_detail.inbound_id', '=', 'inbound.id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->select(DB::raw('COUNT(inbound_detail.id) as count'),
                    'product.name',
                    'product_type_size.name as size')
                ->where('inbound.batch_id', '=', $inbound_data[$i]->batch_id)
                ->groupBy('product.id')
                ->groupBy('product_type_size.id')
                ->get();

            // Generate Detail Product
            $obj->product_name = '<ul class="list-styled">';  
            foreach ($details as $key => $detail)
                $obj->product_name .= '<li>' . $detail->name . '(' . $detail->size . ')</li>';
            $obj->product_name .= '</ul>';  
            
            if (!count($details))
                $obj->product_name = '-';


            $obj->products = Inbound::where('batch_id',$inbound_data[$i]->batch_id)->count()." / ".DB::table('inbound_detail')->leftJoin('inbound','inbound.id','=','inbound_detail.inbound_id')->leftJoin('inbound_batch','inbound_batch.id','=','inbound.batch_id')->where('inbound.batch_id','=',$inbound_data[$i]->batch_id)->count();
            $url ="";
            
            $obj->external_inbound_batch = $inbound_data[$i]->external_inbound_batch;
            

            $diff = Carbon::now()->diffInHours(Carbon::parse($inbound_data[$i]->updated_at));

            $url = $inbound_data[$i]->template_url;

            if (is_null($url) || $url == '' || $diff > 60) {
                $url = "/inbound/barcode/bulk/".$inbound_data[$i]->batch_id;                                    
            }

            $actionview = 
            "<button class='btn btn-primary dropdown-toggle' type='button' id='dropdownMenuButton' data-toggle='dropdown' aria-haspopup='true' aria-expanded='false'>
                Action
            </button>
            <div class='dropdown-menu' aria-labelledby='dropdownMenuButton'>
                <a href='/inbound/edit/".$inbound_data[$i]->batch_id."' title='Edit Inbound' class='dropdown-item'>Edit Inbound</a>
                <a href='/inbound/location/".$inbound_data[$i]->batch_id."' title='View Location' class='dropdown-item'>View Location</a>
                <a href='".$url."' title='Print Barcode' class='dropdown-item'>Print Barcode</a>";

            if(Auth::user()->roles != 'crew' && Auth::user()->roles != 'client' && Auth::user()->roles != 'investor'){
                $actionview .= "<a href='#' title='Delete Batch' class='dropdown-item delete-batch' data-id='".$inbound_data[$i]->batch_id."'>Delete Batch</a>";
            }
            $actionview .= "</div>";

            $obj->last_updated = date('d M Y h:i',strtotime($inbound_data[$i]->updated_at));
            $obj->action = $actionview; 
            $data[] = $obj;
        }

        return json_encode(array(
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $total,
            'recordsFiltered' => $totalFiltered,
            'startDate'       => $startDate,
            'endDate'         => $endDate,
            'clientId'        => $clientId,
            'data'            => $data
        ));
        
    }

    public function add(Request $request){
        $client_id = $request->input('id');
        if(isset($client_id)){
            $check = Client::where('id',$client_id)->count();
            if($check > 0){
                return view('dashboard.inbound.new',['client_id' => $client_id]);
            }
        }
        return view('dashboard.inbound.new',['client_id' => $client_id]);
    }

    public function get_product(Request $request, $id){
        $product = Product::where('client_id', '=', $id)->get();
        return $product;
    }

    public function get_variance(Request $request, $id){
        $variants = ProductTypeSize::where('product_type_id', '=', $id)->where('active',1)->get();
        return $variants;
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(),[
            'client_id' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect('/inbound/add')
                        ->withErrors($validator)
                        ->withInput();
        } else {

            $client_id = $request->input('client_id');
            $client = Client::find($client_id);
            $inbound_product_data = $request->except(['client_id', '_token', 'baseurl','status','arrival_date','notes']);
            $data = array();

            $rproduct = array();

            // Same product id checking, to prevent duplication
            for($i = 0; $i < count($request->input('product_id.*')); $i++ ){
                $data[$i] = array();
                foreach ($inbound_product_data as $column => $value) {
                    if(isset($value[$i]) && $value[$i] != '') {
                        $data[$i][$column] = $value[$i];
                    }
                }

                if($inbound_product_data['product_id'][$i] != null && $inbound_product_data['product_id'][$i] != ""){
                    if(!array_key_exists($data[$i]['product_id'],$rproduct)){
                        $tsize = array();
                        $psize = array();

                        foreach($data[$i]['product_type_size_id'] as $ksize => $vsize){
                            $tsize[$vsize] = (isset($data[$i]['stated_qty'][$ksize]))?intval($data[$i]['stated_qty'][$ksize]):0;
                            $psize[$vsize] = $data[$i]['product_type_size_name'][$ksize];
                        }

                        $rproduct[$data[$i]['product_id']] = array(
                            "color" => $data[$i]['product_color'][0],
                            "variant_id" => $tsize,
                            "variant_name" => $psize
                        );
                    } else {
                        foreach($data[$i]['product_type_size_id'] as $ksize => $vsize){
                            $rproduct[$data[$i]['product_id']]["variant_id"][$vsize] += (isset($data[$i]['stated_qty'][$ksize]))?intval($data[$i]['stated_qty'][$ksize]):0;
                        }
                    }
                }
            }

            if(count($rproduct) > 0){
                // add new inbound batch
                $batch = new InboundBatch;
                $batch->client_id = $client_id;
                $batch->arrival_date = date('Y-m-d H:i:s',strtotime($request->input('arrival_date')));
                $batch->notes = $request->input('notes');
                $batch->status = $request->input('status');
                $batch->receiver_id = Auth::user()->id;
                $batch->courier = $request->input('courier');
                $batch->sender_name = $request->input('sender_name');
                $batch->shipping_cost = $request->input('shipping_cost');
                $batch->save();

                //saving inbound
                foreach($rproduct as $product_id => $data){
                    $product = DB::table('product')
                        ->select('product.*','client.name as client_name','product_type.name as product_type_name')
                        ->join('client','client.id','=','product.client_id')
                        ->join('product_type','product_type.id','=','product.product_type_id')
                        ->where('product.id', $product_id)->first();

                    $variants = array_keys($data["variant_id"]);
                    $product_type_size = ProductTypeSize::where('id', $variants[0])->first();
                    $inbound = new Inbound;
                    $inbound->client_id = $client_id;
                    $inbound->batch_id = $batch->id;
                    $inbound->name = $product->name;
                    $inbound->product_id = $product_id;
                    $inbound->product_type_id = $product_type_size->product_type_id;
                    $inbound->created_at = date('Y-m-d H:i:s');
                    $inbound->updated_at = date('Y-m-d H:i:s');
                    $inbound->status = $request->input('status');
                    $inbound->save();

                    //saving inbound detail
                    $last_inbound = Inbound::orderBy('created_at', 'desc')->where('client_id', $client_id)->where('product_id', $product_id)->first();
                    if($data["variant_id"] != null){
                        foreach($data["variant_id"] as $variant_id => $stated_qty){
                            if($stated_qty != null){
                                $inbound_detail = Inbound_detail::where('product_id', $product_id)->where('product_type_size_id', $variant_id)->where('color',$data['color'])->where('inbound_id',$last_inbound->id);
                                if($inbound_detail->count() == 0) {

                                    $inbound_detail = new Inbound_detail;
                                    $inbound_detail->actual_qty = $stated_qty;
                                    $inbound_detail->code = generate_code();
                                    $inbound_detail->color = $data['color'];
                                    $inbound_detail->name = $product->name;
                                    $inbound_detail->price = $product->price;
                                    $inbound_detail->product_id = $product_id;
                                    $inbound_detail->inbound_id = $last_inbound->id;
                                    $inbound_detail->product_type_size_id = $variant_id;
                                    $inbound_detail->sku = str_replace(" ","",strtoupper($product->client_name))."/".str_replace(" ","",strtoupper($product->product_type_name))."/".str_replace(" ","",strtoupper($product->name))."/".str_replace(" ","",strtoupper($data['color']))."/".$data['variant_name'][$variant_id];
                                    $inbound_detail->stated_qty = $stated_qty;
                                    $inbound_detail->status = 'ACTIVE';
                                    $inbound_detail->save();

                                } else {
                                    $inbound_detail = $inbound_detail->first();
                                }

                                for($x=0;$x<intval($stated_qty);$x++){
                                    $ilocation = new InboundLocation;
                                    $ilocation->version = 0;
                                    $ilocation->code = $client->acronym.time().substr(md5(uniqid(mt_rand(), true)) , 0, 11);
                                    $ilocation->inbound_detail_id = $inbound_detail->id;
                                    $ilocation->save();
                                }
                            }
                        }
                    }
                }
            } else {
                $request->session()->flash('error', 'Please input at least 1 product to inbound');
            }
            
            $request->session()->flash('success', 'New Inbounds has been successfully added');
        }

        return redirect ('/inbound');
    }

    public function bulkUpload(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'bulk-inbound' => 'required'
        ]);

        $labelParams = [
            "data" => [
                "label" => []
            ]
        ];

        if ($validator->fails()) {
            return redirect('/inbound')
                    ->withErrors($validator)
                    ->withInput();
        } else {

            if($request->hasFile('bulk-inbound')){
                $path = $request->file('bulk-inbound')->getRealPath();
                $spreadsheet = Excel::load($path)->get();

                if($spreadsheet->count()){
                    if((Auth::user()->roles != 'client' && $spreadsheet[0]->client != null) || (Auth::user()->roles == 'client' && $spreadsheet[0]->product_name != null)){
                        //add new inbound batch
                        $client = null;
                        if(Auth::user()->roles == 'client'){
                            $client = Client::find(Auth::user()->client_id);
                        } else {
                            $client = Client::where('name',$spreadsheet[0]->client)->first();
                        }

                        if($client != null){

                            $rproduct = array();

                            foreach ($spreadsheet as $key => $value) {
                                $product = Product::where('name',$value->product_name)->where('client_id',$client->id)->first();
                                if($product != null){
                                    $str_product = strval($product->id);
                                    if(!array_key_exists($str_product,$rproduct)){

                                        $tsize = array();
                                        $psize = array();

                                        $variants = ProductTypeSize::where('product_type_id',$product->product_type_id)->get();
                                        foreach($variants as $vkey => $vval){
                                            $tsize[$vval->id] = 0;
                                            $psize[$vval->id] = $vval->name;
                                        }

                                        $rproduct[$str_product] = array(
                                            "color" => (($product->color != null && $product->color != "")?$product->color:"White"),
                                            "variant_id" => $tsize,
                                            "variant_name" => $psize
                                        );
                                    }

                                    $product_type_size_id = array_search ($value->size,$rproduct[$str_product]["variant_name"]);
                                    if($product_type_size_id == false){
                                        $pdtype = ProductType::find($product->product_type_id);
                                        $request->session()->flash('error', 'Product type '.$pdtype->name.' has no variant size '.$value->size.'. Please add the sizing first.');
                                        return redirect('/inbound');
                                    }
                                    $rproduct[$str_product]["variant_id"][$product_type_size_id] += $value->quantity;
                                } else {
                                    $request->session()->flash('error', 'Unable to find product on line '.strval($key+2).'. Please register the product first.');
                                    return redirect('/inbound');
                                }
                            }

                            //init label
                            $label = $this->createNewLabel();

                            //init inbound process
                            $batch = new InboundBatch;
                            $batch->client_id = $client->id;
                            $batch->arrival_date = date('Y-m-d H:i:s',strtotime($request->input('arrival_date')));
                            $batch->status = $request->input('status');
                            $batch->receiver_id = Auth::user()->id;
                            $batch->courier = $request->input('courier');
                            $batch->sender_name = $request->input('sender_name');
                            $batch->shipping_cost = $request->input('shipping_cost');
                            $batch->save();

                            foreach($rproduct as $product_id => $data){
                                $product_id = intval($product_id);
                                $product = DB::table('product')
                                    ->select('product.*','client.name as client_name','product_type.name as product_type_name')
                                    ->join('client','client.id','=','product.client_id')
                                    ->join('product_type','product_type.id','=','product.product_type_id')
                                    ->where('product.id', $product_id)->first();

                                $variants = array_keys($data["variant_id"]);
                                $product_type_size = ProductTypeSize::where('id', $variants[0])->first();
                                $inbound = new Inbound;
                                $inbound->client_id = $client->id;
                                $inbound->batch_id = $batch->id;
                                $inbound->name = $product->name;
                                $inbound->product_id = $product_id;
                                $inbound->product_type_id = $product_type_size->product_type_id;
                                $inbound->created_at = date('Y-m-d H:i:s');
                                $inbound->updated_at = date('Y-m-d H:i:s');
                                $inbound->status = $request->input('status');
                                $inbound->save();

                                //saving inbound detail
                                $last_inbound = Inbound::orderBy('created_at', 'desc')->where('client_id', $client->id)->where('product_id', $product_id)->first();
                                if($data["variant_id"] != null){
                                    foreach($data["variant_id"] as $variant_id => $stated_qty){
                                        if($stated_qty != null){
                                            $inbound_detail = Inbound_detail::where('product_id', $product_id)->where('product_type_size_id', $variant_id)->where('color',$data['color'])->where('inbound_id',$last_inbound->id);
                                            if($inbound_detail->count() == 0) {

                                                $inbound_detail = new Inbound_detail;
                                                $inbound_detail->actual_qty = $stated_qty;
                                                $inbound_detail->code = generate_code();
                                                $inbound_detail->color = $data['color'];
                                                $inbound_detail->name = $product->name;
                                                $inbound_detail->price = $product->price;
                                                $inbound_detail->product_id = $product_id;
                                                $inbound_detail->inbound_id = $last_inbound->id;
                                                $inbound_detail->product_type_size_id = $variant_id;
                                                $inbound_detail->sku = str_replace(" ","",strtoupper($product->client_name))."/".str_replace(" ","",strtoupper($product->product_type_name))."/".str_replace(" ","",strtoupper($product->name))."/".str_replace(" ","",strtoupper($data['color']))."/".$data['variant_name'][$variant_id];
                                                $inbound_detail->stated_qty = $stated_qty;
                                                $inbound_detail->status = 'ACTIVE';
                                                $inbound_detail->save();

                                            } else {
                                                $inbound_detail = $inbound_detail->first();
                                            }

                                            for($x=0;$x<intval($stated_qty);$x++){
                                                $ilocation = new InboundLocation;
                                                $ilocation->version = 0;
                                                $ilocation->code = $client->acronym.time().substr(md5(uniqid(mt_rand(), true)) , 0, 11);
                                                $ilocation->inbound_detail_id = $inbound_detail->id;
                                                $ilocation->save();
                                                
                                                //get label param
                                                $labelParam = $this->makeLabelParam($ilocation->code, $batch->id , $product->name, $data['variant_name'][$variant_id]);
                                                $labelParam['type'] = $label['label_id'];
                                                array_push($labelParams['data']['label'], $labelParam);
                                            }
                                        }
                                    }
                                }
                            }

                            //generate label
                            $this->generateLabel($labelParams, $batch->id);

                            $request->session()->flash('success', 'Bulk inbound has succesfully uploaded.');
                        } else {
                            $request->session()->flash('error', 'Unable to find client, please register the client first.');
                        }
                    } else {
                        $request->session()->flash('error', 'You uploaded the wrong excel file. Please download it first from Bulk Format button.');
                    }
                } else {
                    $request->session()->flash('error', 'Please put at least one new product inbound on excel.');
                }
            } else {
                $request->session()->flash('error', 'Please upload the formatted bulk file first.');
            }
        }

        return redirect('/inbound');
    }

    public function generateLabel($labelParams, $inboundBatchID){
        $clientHttp = app(HttpClient::class);
        try {
            $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_LABEL');

            $response = $clientHttp->request("POST", $url, ['body' => json_encode($labelParams)]);
            $response = json_decode($response->getBody()->getContents());
            DB::table('inbound_batch')
                ->where('id', '=', $inboundBatchID)
                ->update([
                    'template_url' => $response->data->url,
                    'updated_at' => Carbon::now()
                ]);

            return $response;
        } catch (\Exception $exception) {
            Log::error("Failed to call API to create label: ". $exception);
            throw $exception;
        }        
    }

    public function makeLabelParam($code, $batch_id, $product_name, $size){
        $labelParam = [];
        $labelParam['page_size'] = Config::get('constants.label.PAGE_SIZE_INBOUND');
        $labelParam['page_margin'] = [
            'top' => 0,
            'right' => 0,
            'bottom' => 0,
            'left' => 0
        ];
        $labelParam['params']['show_barcode']['barcode_type'] = 'qr';
        $labelParam['params']['order']['order_id']['value'] = $code;
        $labelParam['params']['order']['creation_date'] = date('d/m/Y');
        $labelParam['params']['inbound']['code'] = $code;
        $labelParam['params']['inbound']['id'] = $batch_id;
        $labelParam['params']['inbound']['product_name'] = substr($product_name,0,150);
        $labelParam['params']['inbound']['size_name'] = $size;

        return $labelParam;
    }

    public function createNewLabel() {
        $clientHttp = app(HttpClient::class);
        $label = json_decode(json_encode(DB::table('label')
        ->where('name', '=', Config::get('constants.label.INBOUND'))
        ->first()), true);

        if ($label == null) {
            try {
                $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_LABEL_TYPE');
                $body = [];
                $body['data'] = [
                    'labeltype' => [
                        'name' => 'WMS Inbound Label',
                        'type' => 'LBL'
                    ]
                ];

                $response = $clientHttp->request("POST", $url, ['body' => json_encode($body)]);
                $response = json_decode($response->getBody()->getContents());
                $label = [];
                $label['label_id'] = $response->data->labeltype->id;
                DB::table('label')
                ->insert([
                    'name' => Config::get('constants.label.INBOUND'),
                    'label_id' => $label['label_id']
                ]);
            } catch (\Exception $exception) {
                Log::error("Failed to call API to create label type: ". $exception);
                throw $exception;
            }

            try {
                $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_TEMPLATE');
                $html = view('dashboard.pdf.inbound-label')->render();
                $body = [];
                $body['data'] = [
                    'template' => [
                        "description"=> "Label for wms inbound",
                        "title"=> "WMS Inbound Template",
                        "type" => [
                            "id" => $label['label_id']
                        ],
                        "version"=> [
                            "html" => $html,
                            "number" => "1.0.0"
                        ]
                    ]
                ];

                $response = $clientHttp->request("POST", $url, ['body' => json_encode($body)]);

            } catch (\Exception $exception) {
                Log::error("Failed to call API to create template: ". $exception);
                throw $exception;
            }
        }

        return $label;
    }

    public function edit(Request $request, $id){

        if(isset(Auth::user()->client_id) && InboundBatch::where('client_id',Auth::user()->client_id)->where('id',$id)->count() == 0){
            $request->session()->flash('error', 'You are not allowed to access this inbound batch');
            return redirect('inbound');
        }

        $batch = InboundBatch::find($id);
        $client = Client::find($batch->client_id);
        $product = Product::where('client_id', $client->id)->get();
        $inbound = Inbound::where('batch_id',$id)->get();
        $datas = array();
        if ($inbound->count() > 0){
            foreach($inbound as $ib){
                $variance = Inbound_detail::where('inbound_id', $ib->id)->get();
                $datas[] = array("inbound" => $ib, "variance" => $variance);
            }
        };

        return view('dashboard.inbound.edit', ['batch' => $batch, 'client' => $client, 'product' => $product, 'datas' => $datas]);
    }

    public function update(Request $request, $id){
        $validator = Validator::make($request->all(),[
            'product_type_id.*' => 'required' 
        ]);
        if ($validator->fails()) {
            return redirect('/inbound/edit/'.$id)
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $batch = InboundBatch::find($id);
            $batch->arrival_date = date('Y-m-d H:i:s',strtotime($request->input('arrival_date')));
            $batch->notes = $request->input('notes');
            $batch->status = $request->input('status');
            $batch->courier = $request->input('courier');
            $batch->sender_name = $request->input('sender_name');
            $batch->shipping_cost = $request->input('shipping_cost');
            $batch->save();

            // foreach($request->input('inbound_id') as $x => $inbound_id){
            //     $inbound = Inbound::find($inbound_id);
            //     $inbound->product_id = $request->input('product_id')[$x];
            //     $inbound->status = $request->input('status');
            //     $inbound->save();
            //     $inbound_update_data = $request->except('_token', 'inbound_id','product_id', 'name', 'client_id','status','notes','arrival_date');
            //     $client_id = $request->input('client_id');
            //     $data = [];
            //     for ($i=0; $i < count($request->input('product_type_size_id')[$x]); $i++){
            //         $data = array();
            //         foreach ($inbound_update_data as $column => $value) {
            //             $data[$x][$i][$column] = $value[$x][$i];
            //         }
                    
            //         $inbound_detail = Inbound_detail::where('inbound_id', $id)->where('product_type_size_id', $data[$x][$i]['product_type_size_id'])->first();
            //         if ($inbound_detail != null){
            //             $inbound_detail->stated_qty = $data[$x][$i]['stated_qty'];
            //             $inbound_detail->save();
            //         }else if($inbound_detail == null){
            //             if($data[$x][$i]['stated_qty'] != null){
            //                 $product = Product::where('id', $request->input('product_id')[$x])->first();
            //                 $product_detail = ProductDetail::where('product_id', $request->input('product_id')[$x])->where('product_type_size_id', $data[$x][$i]['product_type_size_id'])->first();
            //                 if($product_detail != null){
            //                     $inbound_detail = new Inbound_detail;
            //                     $inbound_detail->actual_qty = $data[$x][$i]['stated_qty'];
            //                     $inbound_detail->code = $product_detail->code;
            //                     $inbound_detail->color = $product_detail->color;
            //                     $inbound_detail->inbound_id = $id;
            //                     $inbound_detail->name = $product->name;
            //                     $inbound_detail->product_detail_id = $product_detail->id;
            //                     $inbound_detail->product_type_size_id = $product_detail->product_type_size_id;
            //                     $inbound_detail->sku = $product_detail->sku;
            //                     $inbound_detail->stated_qty = $data[$x][$i]['stated_qty'];
            //                     $inbound_detail->created_at = date('Y-m-d H:i:s');
            //                     $inbound_detail->updated_at = date('Y-m-d H:i:s');
            //                     $inbound_detail->status = 'DRAFT';
            //                     $inbound_detail->save();
            //                 }
            //                 else $request->session()->flash('error', 'Product detail data is not found');
            //             };
            //         }
            //     }
            // }
            $request->session()->flash('success', 'Inbound has been successfully updated');
        }
        return redirect ('/inbound'); 
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'batch-id' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect('/inbound')
                ->withErrors($validator)
                ->withInput();
        } else {

            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            $inbounds = Inbound::where('batch_id',$request->input('batch-id'))->get();
            foreach($inbounds as $inbound){
                $details = Inbound_detail::where('inbound_id',$inbound->id)->get();
                foreach($details as $detail){
                    InboundLocation::where('inbound_detail_id',$detail->id)->delete();
                }
                Inbound_detail::where('inbound_id',$inbound->id)->delete();
            }
            Inbound::where('batch_id',$request->input('batch-id'))->delete();
            InboundBatch::where('id',$request->input('batch-id'))->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');

            $request->session()->flash('success', 'Batch inbound has successfully deleted.');
        }

        return redirect('/inbound');
    }

    public function location(Request $request, $id)
    {

        if(isset(Auth::user()->client_id) && InboundBatch::where('client_id',Auth::user()->client_id)->where('id',$id)->count() == 0){
            $request->session()->flash('error', 'You are not allowed to access this inbound location');
            return redirect('inbound');
        }

        $detail = DB::table('inbound_batch')
            ->select('inbound_batch.id as batch_id','client.name as client_name','inbound_batch.arrival_date','users.name as receiver_name','inbound_batch.status', 'inbound_batch.template_url', 'inbound_batch.updated_at')
            ->join('client', 'client.id', '=', 'inbound_batch.client_id')
            ->leftJoin('users', 'users.id', '=', 'inbound_batch.receiver_id')
            ->where('inbound_batch.id','=',$id)
            ->first();

        $locations = DB::table('inbound_detail_location')
            ->select('inbound_detail_location.id','inbound.name as product_name','inbound_detail_location.code','shelf.name as shelf_name','product_type_size.name as size_name','inbound_detail.color')
            ->join('inbound_detail','inbound_detail_location.inbound_detail_id','=','inbound_detail.id')
            ->join('inbound','inbound.id','=','inbound_detail.inbound_id')
            ->join('inbound_batch','inbound_batch.id','=','inbound.batch_id')
            ->join('product_type','product_type.id','=','inbound.product_type_id')
            ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
            ->leftJoin('shelf','shelf.id','=','inbound_detail_location.shelf_id')
            ->where('inbound_batch.id','=',$id)
            ->get();
            
        $detail->diff_updated = Carbon::now()->diffInHours(Carbon::parse($detail->updated_at));

        return view('dashboard.inbound.location',['inbound' => $detail, 'locations' => $locations]);
    }

    public function previewPdf(Request $request)
    {
        $batch_id = 1;

        $report_no = "PKD/".date('Ymd')."/".str_pad($batch_id,5,'0',STR_PAD_LEFT);
        $batch = DB::table('inbound_batch')
            ->join('client','client.id','=','inbound_batch.client_id')
            ->select('inbound_batch.*','client.name as client_name','client.address as client_address')
            ->where('inbound_batch.id','=',$batch_id)
            ->first();

        $variants = array();

        $products = DB::table('inbound_detail_location')
            ->select('inbound.name as product_name','inbound.product_id','inbound_detail.id as inbound_detail_id','inbound_detail.product_type_size_id','product_type_size.name as size_name','inbound_detail.stated_qty','inbound_detail_location.shelf_id','inbound_detail_location.date_rejected')
            ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
            ->join('inbound','inbound.id','=','inbound_detail.inbound_id')
            ->join('inbound_batch','inbound_batch.id','=','inbound.batch_id')
            ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
            ->where('inbound.batch_id','=',$batch_id)
            ->get();

        foreach($products as $key => $val){
            if(!array_key_exists($val->inbound_detail_id,$variants)){
                $variants[$val->inbound_detail_id] = array(
                    "product_name" => $val->product_name,
                    "size_name" => $val->size_name,
                    "stated" => $val->stated_qty,
                    "actual" => 0,
                    "reject" => 0,
                );
            }
            
            if($val->shelf_id != null){
                $variants[$val->inbound_detail_id]["actual"] += 1;
            }

            if($val->date_rejected != null){
                $variants[$val->inbound_detail_id]["reject"] += 1;
            }
        }

        $responseData = $variants;

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8', 
            'format' => [215, 280], 
            'orientation' => 'P',
            'setAutoTopMargin' => 'stretch',
            'autoMarginPadding' => 5
        ]);

        $mpdf->SetHTMLFooter(view('dashboard.pdf.footer')->render());

        $mpdf->WriteHTML(view('dashboard.pdf.inbound-report',[
            'variants' => $responseData,
            'batch' => $batch,
            'report_no' => $report_no
        ])->render());

        // $pdf_path = public_path()."/format/";
        // $mpdf->Output($pdf_path.'inbound-report.pdf','F');
        
        $res = $mpdf->Output();
        return $res;

        // Mail::send('emails.inbound-report', $variants, function($message) use ($pdf_path,$batch){
        //     $message->to('thelightjedimaster@gmail.com');
        //     $message->subject('Inbound Report - '.$batch->client_name.' - '.date('d M Y'));
        //     $message->from('admin@clientname.co.id');
        //     $message->attach($pdf_path.'inbound-report.pdf', array(
        //         'as' => 'inbound-report.pdf', 
        //         'mime' => 'application/pdf')
        //     );
        // });

        // unlink($pdf_path.'inbound-report.pdf');

        // $inbound_batch = InboundBatch::find($input->batch_id);
        // $inbound_batch->is_done = 1;
        // $inbound_batch->save();
    }

    public function bulkPrint(Request $request, $id)
    {

        if(isset(Auth::user()->client_id) && InboundBatch::where('client_id',Auth::user()->client_id)->where('id',$id)->count() == 0){
            $request->session()->flash('error', 'You are not allowed to access this inbound bulk print');
            return redirect('inbound');
        }

        $responseData = DB::table('inbound_detail_location')
            ->where('inbound_batch.id','=',$id)
            ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('inbound','inbound.id','=','inbound_detail.inbound_id')
            ->join('inbound_batch','inbound_batch.id','=','inbound.batch_id')
            ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
            ->select('inbound_detail_location.code as qrcode','product.name as product_name','product_type_size.name as size_name','inbound_detail.sku','inbound_detail.price','inbound_batch.id')
            ->get();


        $labelParams = [
            "data" => [
                "label" => []
            ]
        ];
        //init label
        $label = $this->createNewLabel();
        
        foreach($responseData as $item) {
            //get label param
            $labelParam = $this->makeLabelParam($item->qrcode, $item->id, $item->product_name, $item->size_name);
            $labelParam['type'] = $label['label_id'];
            array_push($labelParams['data']['label'], $labelParam);            
        }
        //generate label
        $response = $this->generateLabel($labelParams, $responseData[0]->id);
        return Redirect::away($response->data->url);
    }

    public function singlePrint(Request $request, $id)
    {
        $responseData = DB::table('inbound_detail_location')
            ->where('inbound_detail_location.id','=',$id)
            ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('inbound','inbound.id','=','inbound_detail.inbound_id')
            ->join('inbound_batch','inbound_batch.id','=','inbound.batch_id')
            ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
            ->select('inbound_detail_location.id','inbound_detail_location.code as qrcode','product.name as product_name','product_type_size.name as size_name','inbound_detail.sku','inbound_detail.price')
            ->first();

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

        $mpdf->WriteHTML(view('dashboard.pdf.inbound-print-single',[
            'barcode' => $responseData,
        ])->render());

        return $mpdf->Output();
    }

    public function copyNotExist(Request $request)
    {
        // $inbounds = Inbound_detail::all();

        // foreach($inbounds as $key => $value){
        //     $detail = ProductDetail::find($value->product_detail_id);
        //     $update = Inbound_detail::find($value->id);
        //     $update->product_id = $detail->product_id;
        //     $update->save();
        // }

        // $products = ProductLocation::all();
        // foreach($products as $key => $value){
        //     $update = InboundLocation::where('code', $value->code)->first();
        //     $update->date_stored = $value->date_stored;
        //     $update->save();
        // }
    }

    /**
    * Inbound Excel
    */

    public function downloadExcel(Request $request)
    {    
        // Array data
        $result = [];

        // Input variable
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $clientId = $request->input('client');
        $search = $request->input('search');

        // Date Processing
        if (!empty($startDate) && !empty($endDate)) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay(); // Make sure the time still latest cause its 00:00:00

            // Will add validation on front end as well, just to make sure in back end
            if ($startDate->diffInDays($endDate) > 31) 
                $endDate = $startDate->copy()->addDays(31)->endOfDay();
        } else {
            // If Somehow the date is empty, it will take last 1 week, will add on front end also
            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $endDate = Carbon::now()->endOfDay(); // Make sure the time still latest
        }

        // Build query for result
        $query = DB::connection('read_replica')
            ->table('inbound_batch')
            ->join('inbound', 'inbound.batch_id', '=', 'inbound_batch.id')
            ->join('client', 'client.id', '=', 'inbound_batch.client_id')
            ->join('inbound_detail', 'inbound_detail.inbound_id', '=', 'inbound.id')
            ->join('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->leftJoin('omnichannel_db.inbound_partner as ip', 'ip.id', '=', 'inbound_batch.inbound_partner_id')
            ->orderBy('inbound_batch.arrival_date','DESC')
            ->select('inbound_batch.id as batch_id', 'client.name as client_name', 'product.name as product_name',
                'product_type_size.name as size_name', 'inbound_detail.stated_qty', 'inbound_detail_location.shelf_id',
                'inbound_detail.color', 'inbound_batch.arrival_date', 'inbound_batch.status', 'inbound_detail_location.date_rejected',
                'inbound_detail_location.date_adjustment', 'ip.external_inbound_batch as external_inbound_batch')
            ->where('inbound_batch.arrival_date', '>=', $startDate->format('Y-m-d H:i:s'))
            ->where('inbound_batch.arrival_date', '<=', $endDate->format('Y-m-d H:i:s'));

        // Check if user loggedin is client or admin
        if (Auth::user()->roles == 'client') { // This way faster for Admin, and cause not having loop 
            $query->where('client.id', '=', Auth::user()->client_id);
        }
        
        // check client id
        if (!empty($clientId)) {
            $query->where('client.id', '=', $clientId);
        }
        
        //check search 
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('inbound_batch.id', 'LIKE', $search.'%')
                ->orWhere('ip.external_inbound_batch', 'LIKE', $search.'%')
                ->orWhere('product.name', 'LIKE', $search.'%');
            });
        }    

        foreach ($query->get() as $inbound) { 
            $index = $inbound->batch_id.'_'.$inbound->product_name.'_'.$inbound->color.'_'.$inbound->size_name;
        
            // Push new inbound
            if (!array_key_exists($index, $result))
                $result[$index] = array(
                    'Batch' => '#'.str_pad($inbound->batch_id, 5, '0', STR_PAD_LEFT),
                    'External Inbound Batch' => $inbound->external_inbound_batch,
                    'Client Name' => $inbound->client_name,
                    'Product Name' => $inbound->product_name,
                    'Size Name' => $inbound->size_name,
                    'Stated Qty' => $inbound->stated_qty,
                    'Actual Qty' => 0,
                    'Rejected Qty' => 0,
                    'Inbounded At' => date('d M Y H:i', strtotime($inbound->arrival_date)),
                    'Status' => $inbound->status
                );
            
            if ($inbound->shelf_id != null) {
                if ($inbound->date_rejected != null) {
                    $result[$index]['Rejected Qty']++;
                } else {
                    $result[$index]['Actual Qty']++;
                }
            } else {
                if ($inbound->date_rejected != null) {
                    $result[$index]['Rejected Qty']++;
                }
            }
        }

        if (!$result && count($result) == 0) {
            $request->session()->flash('error', 'There is no data to download.');
            return redirect('/inbound');
        }

        // Generate Excel
        Excel::create('inbound-export-'.date('Ymd-His'), function ($excel) use ($result) {
            $excel->sheet('Inbound Report', function ($sheet) use ($result) {
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
}