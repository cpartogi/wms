<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use DB;
use Excel;
use Validator;
use Config;

use Carbon\Carbon;

use App\Client;
use App\User;
use App\Inbound;
use App\Order;
use App\OrderDetail;
use App\Warehouse;

class ReportController extends Controller
{

    public function index(Request $request)
    {
        $qInbound = Inbound::whereRaw('created_at >= "'.date('Y-m-d 00:00:00').'" AND created_at < "'.date('Y-m-d 23:59:59').'"');
        $qInboundBef = Inbound::whereRaw('created_at >= "'.date('Y-m-d 00:00:00',strtotime('-1 days')).'" AND created_at < "'.date('Y-m-d 23:59:59',strtotime('-1 days')).'"');
        $qOrder = Order::whereRaw('created_at >= "'.date('Y-m-d 00:00:00').'" AND created_at < "'.date('Y-m-d 23:59:59').'"');
        $qOrderBef = Order::whereRaw('created_at >= "'.date('Y-m-d 00:00:00',strtotime('-1 days')).'" AND created_at < "'.date('Y-m-d 23:59:59',strtotime('-1 days')).'"');
        $qOut = Order::whereRaw('created_at >= "'.date('Y-m-d 00:00:00').'" AND created_at < "'.date('Y-m-d 23:59:59').'"')->where('status','SHIPPED');
        $qOutBef = Order::whereRaw('created_at >= "'.date('Y-m-d 00:00:00',strtotime('-1 days')).'" AND created_at < "'.date('Y-m-d 23:59:59',strtotime('-1 days')).'"')->where('status','SHIPPED');
        $qToday = DB::table('inbound_detail')
            ->join('product','product.id','=','inbound_detail.product_id')
            ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
            ->groupBy('product.id','product_type_size.id')
            ->orderBy('inbound_detail.created_at')
            ->select('product.id','product.name','product_type_size.name as size_name',DB::raw('SUM(inbound_detail.actual_qty) as inbound'),'inbound_detail.id as inbound_detail_id')
            ->whereRaw('inbound_detail.created_at >= "'.date('Y-m-d 00:00:00').'" AND inbound_detail.created_at < "'.date('Y-m-d 23:59:59').'"')
            ->take(5);

        if(Auth::user()->roles == 'client'){
            $qInbound->where('client_id',Auth::user()->client_id);
            $qInboundBef->where('client_id',Auth::user()->client_id);
            $qOrder->where('client_id',Auth::user()->client_id);
            $qOrderBef->where('client_id',Auth::user()->client_id);
            $qOut->where('client_id',Auth::user()->client_id);
            $qOutBef->where('client_id',Auth::user()->client_id);
            $qToday->where('product.client_id',Auth::user()->client_id);
        }

        $today = $qToday->get();

        $total = array(
            'inbound' => $qInbound->count(),
            'inbound_before' => $qInboundBef->count(),
            'order' => $qOrder->count(),
            'order_before' => $qOrderBef->count(),
            'outbound' => $qOut->count(),
            'outbound_before' => $qOutBef->count()
        );

        foreach($today as $key => $stock){
            $today[$key]->inbound = intval($stock->inbound);
            $qOutbound = DB::table('inbound_detail_location')
                ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->where('inbound_detail.id','=',$stock->inbound_detail_id)
                ->whereRaw('inbound_detail_location.date_outbounded >= "'.date('Y-m-d 00:00:00').'" AND inbound_detail_location.date_outbounded < "'.date('Y-m-d 23:59:59').'"');

            if(Auth::user()->roles == 'client'){
                $qOutbound->where('product.client_id','=',Auth::user()->client_id);
            }

            $today[$key]->outbound = $qOutbound->count();
        }

        $clients = Client::all();

    	return view('dashboard.report.index',['clients' => $clients, 'total' => $total, 'today' => $today]);
    }

    public function get_orders(Request $request)
    {
        $columns = array('id', 'order_number', 'client_name', 'customer_name', 'courier', 'no_resi');

        $total = null;
        $boots = null;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $order = (($order == 'id')?'orders.id':$order);
        $dir = $request->input('order.0.dir');

        $qOrders = DB::table('orders')
        ->select('orders.*','client.name as client_name','customer.name as customer_name','product.name as product_name')
        ->join('order_detail','order_detail.orders_id','=','orders.id')
        ->join('inbound_detail','inbound_detail.id','=','order_detail.inbound_detail_id')
        ->join('product','product.id','=','inbound_detail.product_id')
        ->join('customer','customer.id','=','orders.customer_id')
        ->join('client','client.id','=','orders.client_id')
        ->orderBy('orders.created_at','desc')
        ->groupBy('orders.id')
        ->where('orders.status','=','SHIPPED')
        ->whereNotNull('orders.shipment_date')
        ->whereRaw('orders.shipment_date >= "'.date('Y-m-d 00:00:00').'" AND orders.shipment_date < "'.date('Y-m-d 23:59:59').'"');

        if(Auth::user()->roles == 'client'){
            $qOrders->where('client.id','=',Auth::user()->client_id);
        }

        $total = $qOrders->count();
        $totalFiltered = $total;

        if(empty($request->input('search.value'))){
            $qOrders = $qOrders->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir);
        } else {
            $search = $request->input('search.value'); 
            $qOrders->where('orders.order_number','LIKE','%'.$search.'%')
                ->where('client.name','LIKE','%'.$search.'%')
                ->where('customer.name','LIKE','%'.$search.'%')
                ->where('orders.courier','LIKE','%'.$search.'%')
                ->where('orders.no_resi','LIKE','%'.$search.'%')
                ->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir);
        }

        $totalFiltered = $qOrders->count();
        $orders = $qOrders->get();

        $data = array();
        if(!empty($orders))
        {
            foreach($orders as $order)
            {
                $obj = array();
                $obj['id'] = $order->id;
                $obj['order_number'] = $order->order_number;
                $obj['client_name'] = $order->client_name;
                $obj['customer_name'] = $order->customer_name;
                $obj['courier'] = $order->courier;
                $obj['no_resi'] = $order->no_resi;
                $data[] = $obj;
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

    public function trace(Request $request)
    {
        $clients = Client::all();
        return view('dashboard.report.track', compact('clients'));
    }

    public function get_list(Request $request)
    {
        $columns = [
            'id',
            'order_number',
            'client_name',
            'customer_name',
            'courier',
            'no_resi',
            'status',
            'name',
            'updated_at',
        ];
        $query = null;

        // Input variable
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $clientId = $request->input('client');
        $limit = $request->input('length');
        $start = $request->input('start');
        $orderIndex = $request->input('order.0.column');
        $orderColumn = $orderIndex == 0 ? 'orders.created_at' : $columns[$orderIndex];
        $orderDirection = $orderIndex == 0 ? 'desc' : $request->input('order.0.dir');
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
            $startDate = Carbon::now()->subDays(7);
            $endDate = Carbon::now()->endOfDay(); // Make sure the time still latest
        }

        // Build query for result
        $query = DB::table('orders_history')
            ->select('orders_history.id','order.order_number','client.name as client_name','customer.name as customer_name','orders.courier','orders.no_resi','orders_history.status','users.name as name','orders_history.updated_at')
            ->join('orders','orders.id','=','orders_history.order_id')
            ->join('customer', 'customer.id', '=', 'orders.customer_id')
            ->join('users','users.id','=','orders_history.user_id')
            ->leftJoin('client', 'client.id', '=', 'orders.client_id')
            ->where('orders.created_at', '>=', $startDate->format('Y-m-d H:i:s'))
            ->where('orders.created_at', '<=', $endDate->format('Y-m-d H:i:s'));

        // Check if admin or client
        if (Auth::user()->roles == 'client') {
            unset($columns[array_search('client_name', $columns)]);
            $query->where('client.id', '=', Auth::user()->client_id);
        }
        else {
            if (!empty($clientId))
                $query->where('client.id', '=', $clientId);
        }

        // Count all data
        $total = $query->count(DB::raw('DISTINCT orders.id'));

        // Handle search
        if (!empty($search))
            $query->where(function($q) use ($search) {
                $q->where('customer.name', 'LIKE', $search.'%')
                    ->OrWhere('orders.order_number', '=', $search.'%');
            });

        // Count filtered data
        $totalFiltered = $query->count(DB::raw('DISTINCT orders.id'));

        // Build selected column
        $query->select(...array_map(function ($item) {
            if ($item == 'client_name')
                return 'client.name as client_name';
            else if ($item == 'customer_name')
                return 'customer.name as customer_name';
            else if ($item == 'name')
                return 'users.name as name';
            else
                return 'orders.'.$item;
        }, $columns));

        // Pagination handler
        $query->limit($limit)->offset($start)->groupBy('orders.id');

        // Array data to show
        $result = [];

        // Preparing data, max loop only 10 data
        $i = 0;
        foreach ($query->orderBy($orderColumn, $orderDirection)->get()->toArray() as $order) {
            $order = json_decode(json_encode($order), true);

            // Get Status Tracking
            $status_trackings = DB::table('orders_history')
                ->select('orders_history.status', 'name', 'orders_history.created_at')
                ->join('users', 'users.id', 'orders_history.user_id')
                ->where('orders_history.order_id', '=', $order["id"])
                ->get()
                ->toArray();

            // Check no resi
            $order['no_resi'] = $order['no_resi'] != null ? $order['no_resi'] : '-';

            // Formating created at
            $order['updated_at'] = date('d-M-Y H:i', strtotime($order['updated_at']));

            // Change status to label
            $order['status'] = Config::get('constants.order_status.'.$order['status']);

            //$order['status_trackings'] = DB::select("SELECT group_concat(concat('•',`status`,' by ',(select `name` from users where id = orders_history.user_id),' at ',created_at) separator '<br>') as track FROM orders_history where order_id = ".$order["id"])[0]->track;
            
            $order['status_trackings'] = implode("", array_map(function($item) {
                return "• " . Config::get('constants.order_status.'.$item['status']) . ' by ' . $item['name'] . ' at ' . Carbon::parse($item['created_at'])->format('Y-m-d H:i') . "<br/>\n";
            }, json_decode(json_encode($status_trackings), true)));
            
            //$order['status_trackings'] = "<ul><li>PENDING by Admin:2019-09-13     </li><li>PENDING by Admin:2019-09-13       </li><li>READY_TO_PACK by Admin:2019-09-16        </li></ul>";
            //dd($order['status_trackings']);
            $order['button_trackings'] = "<button class='btn btn-primary' onclick='showTrackings(this)' data-value='".$order['status_trackings']."'>View</button>";
            // Add prepared data to array
            $result[] = $order;
            $i++;
        }
        
        return json_encode(array(
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => intval($total),
            'recordsFiltered' => intval($totalFiltered),
            'data'            => $result
        ));
    }

    public function getOrdersStatus(Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start');
        $orderColumn = $request->input('order.0.column') + 1;
        $dir = $request->input('order.0.dir');
        $status = $request->input('status');
        $client = $request->input('client');

        $query = DB::table('orders')
            ->select(
                DB::raw('CAST(`created_at` as date) as date'),
                DB::raw('SUM(IF(status = "PENDING", 1, 0)) as total_pending'),
                DB::raw('SUM(IF(status = "CANCELED", 1, 0)) as total_canceled'),
                DB::raw('SUM(IF(status = "READY_FOR_OUTBOUND", 1, 0)) as total_ready_outbound'),
                DB::raw('SUM(IF(status = "READY_TO_PACK", 1, 0)) as total_ready_pack'),
                DB::raw('SUM(IF(status = "AWAITING_FOR_SHIPMENT", 1, 0)) as total_waiting_shipment'),
                DB::raw('SUM(IF(status = "SHIPPED", 1, 0)) as total_shipped'),
                DB::raw('count(id) as total')
            );

        if (Auth::user()->roles == 'client')
            $query->where('client_id', '=', Auth::user()->client_id);
        else if (!empty($client))
            $query->where('client_id', '=', $client);

        $total = $query->count(DB::raw('DISTINCT CAST(`created_at` as date)'));

        if (!empty($status))
            $query->having(DB::raw('SUM(IF(status = "SHIPPED", 1, 0)) + SUM(IF(status = "CANCELED", 1, 0))'), 
                $status == 'c' ? '=' : '!=',
                DB::raw('count(id)'));

        $totalFiltered = $query->count(DB::raw('DISTINCT CAST(`created_at` as date)'));

        $data = $query
            ->groupBy(DB::raw('CAST(`created_at` as date)'))
            // ->groupBy('status')
            ->orderBy(DB::raw($orderColumn), $dir)
            ->limit($limit)
            ->offset($start)
            ->get();

        return response()->json([
            'draw' => intval($request->input('draw')),  
            'recordsTotal' => intVal($total),
            'recordsFiltered' => intVal($totalFiltered), 
            'data' => array_map(function($item) {
                if ($item->total_pending > 0)
                    $item->total_pending = '<a href="/report/pending_order/'.$item->date.'">'.$item->total_pending.'</a>';
                return $item;
            }, $data->toArray()),
        ]);
    }

    public function getPendingOrders(Request $request)
    {
        $date = $request->get('date');
        $limit = $request->input('length');
        $start = $request->input('start');
        $client = $request->input('client');
        $result = [];

        $query = DB::table('orders')
            ->select(
                'order_number', 'customer.name as customer', 'customer.address', 'product.name as product',
                DB::raw('COUNT(order_detail.id) as count'), 'inbound_detail.color', 'inbound_detail.product_type_size_id',
                'product.id as product_id', 'orders.status', 'orders.id', 'order_detail.inbound_detail_id',
                'product_type_size.name as size_name'
            )
            ->leftJoin('customer', 'customer.id', '=', 'orders.customer_id')
            ->leftJoin('order_detail', 'order_detail.orders_id', '=', 'orders.id')
            ->leftJoin('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
            ->leftJoin('product', 'product.id', '=', 'inbound_detail.product_id')
            ->leftJoin('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
            ->leftJoin('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
            ->where('orders.created_at', '>=', date('Y-m-d 00:00:00', strtotime($date)))
            ->where('orders.created_at', '<', date('Y-m-d 00:00:00', strtotime($date." + 1 day")))
            ->where('orders.status', '=', 'PENDING');

        if (Auth::user()->roles == 'client')
            $query->where('orders.client_id', '=', Auth::user()->client_id);
        else if (!empty($client))
            $query->where('orders.client_id', '=', $client);

        $total = $query->count(DB::raw('DISTINCT orders.id, product.id'));

        foreach ($query->limit($limit)->offset($start)
            ->groupBy('orders.id')
            ->groupBy('product.id')
            ->orderBy('order_number', 'asc')
            ->orderBy('customer', 'asc')
            ->orderBy('address', 'asc')
            ->get() as $order) {

            $temp = [];
            $temp['order_number'] = $order->order_number;
            $temp['customer'] = $order->customer;
            $temp['address'] = $order->address;
            $temp['product'] = empty($order->product) ? 'Product not registerd' : $order->product;
            $temp['size'] = $order->size_name;
            $temp['ordered'] = OrderDetail::where('inbound_detail_id', $order->inbound_detail_id)
                ->where('orders_id', $order->id)
                ->count();
            $temp['stock'] = DB::table('inbound_detail_location')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->where('inbound_detail.product_type_size_id', '=', $order->product_type_size_id)
                // ->where('inbound_detail.color', '=', $order->color)
                ->where('inbound_detail.product_id', '=', $order->product_id)
                ->whereNotNull('inbound_detail_location.shelf_id')
                ->whereNull('inbound_detail_location.order_detail_id')
                ->whereNull('inbound_detail_location.date_picked')
                ->whereNull('inbound_detail_location.date_outbounded')
                ->count();
            $temp['warning'] = $temp['ordered'] > $temp['stock'] ? '<i class="fa fa-exclamation text-danger" title="Ordered is greater than stock"/>' : '';

            $result[] = $temp;
        }

        return response()->json([
            'draw' => intval($request->input('draw')),  
            'recordsTotal' => intVal($total),
            'recordsFiltered' => intVal($total), 
            'data' => $result,
        ]);
    }

    public function downloadDaily(Request $request)
    {
        $count = 0;
        $raws = null;

        if(Auth::user()->roles == 'client'){
            $user = User::find(Auth::user()->id);
            $count = DB::table('inbound_detail')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
                ->groupBy('product.id','product_type_size.id')
                ->orderBy('inbound_detail.created_at')
                ->select('product.id','product.name','product_type_size.name as size_name',DB::raw('SUM(inbound_detail.actual_qty) as inbound'),'inbound_detail.id as inbound_detail_id')
                ->whereRaw('inbound_detail.created_at >= "'.date('Y-m-d 00:00:00').'" AND inbound_detail.created_at < "'.date('Y-m-d 23:59:59').'"')
                ->where('product.client_id','=',$user->client_id)
                ->count();

        } else {
            $count = DB::table('inbound_detail')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
                ->groupBy('product.id','product_type_size.id')
                ->orderBy('inbound_detail.created_at')
                ->select('product.id','product.name','product_type_size.name as size_name',DB::raw('SUM(inbound_detail.actual_qty) as inbound'),'inbound_detail.id as inbound_detail_id')
                ->whereRaw('inbound_detail.created_at >= "'.date('Y-m-d 00:00:00').'" AND inbound_detail.created_at < "'.date('Y-m-d 23:59:59').'"')
                ->count();
        }
        
        $offset = 1000;

        $datas = array();

        for($i=0;$i<$count/$offset;$i++){
            if(Auth::user()->roles == 'client'){
                $user = User::find(Auth::user()->id);
                $raws = DB::table('inbound_detail')
                    ->join('product','product.id','=','inbound_detail.product_id')
                    ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
                    ->groupBy('product.id','product_type_size.id')
                    ->orderBy('inbound_detail.created_at')
                    ->select('product.id','product.name','product_type_size.name as size_name',DB::raw('SUM(inbound_detail.actual_qty) as inbound'),'inbound_detail.id as inbound_detail_id')
                    ->whereRaw('inbound_detail.created_at >= "'.date('Y-m-d 00:00:00').'" AND inbound_detail.created_at < "'.date('Y-m-d 23:59:59').'"')
                    ->where('product.client_id','=',$user->client_id)
                    ->skip($i*$offset)
                    ->take($offset)
                    ->get();

                foreach($raws as $key => $stock){
                    $raws[$key]->inbound = intval($stock->inbound);
                    $raws[$key]->outbound = DB::table('inbound_detail_location')->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')->where('inbound_detail.id','=',$stock->inbound_detail_id)
                    ->whereRaw('inbound_detail_location.date_outbounded >= "'.date('Y-m-d 00:00:00').'" AND inbound_detail_location.date_outbounded < "'.date('Y-m-d 23:59:59').'"')
                    ->count();
                }

            } else {
                $raws = DB::table('inbound_detail')
                    ->join('product','product.id','=','inbound_detail.product_id')
                    ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
                    ->groupBy('product.id','product_type_size.id')
                    ->orderBy('inbound_detail.created_at')
                    ->select('product.id','product.name','product_type_size.name as size_name',DB::raw('SUM(inbound_detail.actual_qty) as inbound'),'inbound_detail.id as inbound_detail_id')
                    ->whereRaw('inbound_detail.created_at >= "'.date('Y-m-d 00:00:00').'" AND inbound_detail.created_at < "'.date('Y-m-d 23:59:59').'"')
                    ->skip($i*$offset)
                    ->take($offset)
                    ->get();

                foreach($raws as $key => $stock){
                    $raws[$key]->inbound = intval($stock->inbound);
                    $raws[$key]->outbound = DB::table('inbound_detail_location')->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')->where('inbound_detail.id','=',$stock->inbound_detail_id)
                    ->whereRaw('inbound_detail_location.date_outbounded >= "'.date('Y-m-d 00:00:00').'" AND inbound_detail_location.date_outbounded < "'.date('Y-m-d 23:59:59').'"')
                    ->count();
                }
            }

            foreach($raws as $raw){
                $datas[$raw->name.'_'.$raw->size_name] = array(
                    'product_name' => $raw->name,
                    'size' => $raw->size_name,
                    'inbound' => ($raw->inbound == 0)?'-':$raw->inbound,
                    'outbound' => ($raw->outbound == 0)?'-':$raw->outbound
                );
            }
        }

        if(count($datas) == 0){
            $request->session()->flash('error_download', 'Unable to download, data is empty');
            return redirect('report');
        }

        Excel::create('daily-stocks-movement', function ($excel) use ($datas) {
            $excel->sheet('Stock Movement', function ($sheet) use ($datas) {
                $sheet->fromArray($datas);
            });
        })->download();

        // Excel::filter('chunk')->chunk(500, function ($excel) use ($datas) {
        //     $excel->sheet('Stocks', function ($sheet) use ($datas) {
        //         $sheet->fromArray($datas);
        //     });
        // })->download();
    }

    public function getPerformanceMonitor(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $clientId = $request->input('client');
        $warehouseId = $request->input('warehouse');

        // Date Processing
        if (!empty($startDate) && !empty($endDate)) {
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay(); // Make sure the time still latest cause its 00:00:00

            // Will add validation on front end as well, just to make sure in back end
            if ($startDate->diffInDays($endDate) > 31) 
                $endDate = $startDate->copy()->addDays(31);
        } else {
            // If Somehow the date is empty, it will take last 1 week, will add on front end also
            $startDate = Carbon::now()->subDays(7);
            $endDate = Carbon::now()->endOfDay(); // Make sure the time still latest
        }

        $query = DB::table('orders as ord')
            ->select('ord.created_at', 'clt.name as client', 'ord.status', 'ord.courier', 'wrh.name as warehouse')
            ->join('warehouse as wrh', 'wrh.id', '=', 'ord.warehouse_id')
            ->join('client as clt', 'clt.id', '=', 'ord.client_id')
            ->where('ord.created_at', '>=', $startDate->format('Y-m-d H:i:s'))
            ->where('ord.created_at', '<=', $endDate->format('Y-m-d H:i:s'));

        if (!empty($warehouseId))  {
            $query->where('ord.warehouse_id', '=', $warehouseId);
        }

        if (Auth::user()->roles == 'client') {
            $query->where('clt.id', '=', Auth::user()->client_id);
        }
        else {
            if (!empty($clientId))
                $query->where('clt.id', '=', $clientId);
        }

        $total = $query->count();

        $resultProgressTemp = [];
        $resultDailyReportTemp = [];
        $status = [];

        // Preparing data, max loop only 10 data
        foreach ($query->get()->toArray() as $order) {
            // Push new order
            if ($order->status == 'AWAITING_FOR_SHIPMENT' || $order->status == 'SHIPPED') {
                $indexProgress = $order->courier;

                if (!array_key_exists($indexProgress, $resultProgressTemp)) {
                    $resultProgressTemp[$indexProgress] = array(
                        'courier' => $order->courier,
                        'awaiting_for_shipment' => 0,
                        'shipped' => 0,
                    );
                }

                if ($order->status == 'AWAITING_FOR_SHIPMENT') {
                    $resultProgressTemp[$indexProgress]['awaiting_for_shipment']++;
                } else if ($order->status == 'SHIPPED') {
                    $resultProgressTemp[$indexProgress]['shipped']++;
                }
            }

            if ($order->status == 'SHIPPED' || $order->status == 'CANCELED') {
                $indexDailyReport = Carbon::parse($order->created_at)->format('Y-m-d') . '_' . $order->warehouse . '_' . $order->client;

                if (!array_key_exists($indexDailyReport, $resultDailyReportTemp)) {
                    $resultDailyReportTemp[$indexDailyReport] = array(
                        'date' => Carbon::parse($order->created_at)->format('Y-m-d'),
                        'warehouse' => $order->warehouse,
                        'client' => $order->client,
                        'total' => 0,
                        'shipped' => 0,
                        'canceled' => 0,
                    );
                }

                if ($order->status == 'SHIPPED') {
                    $resultDailyReportTemp[$indexDailyReport]['shipped']++;
                } else if ($order->status == 'CANCELED') {
                    $resultDailyReportTemp[$indexDailyReport]['canceled']++;
                }
                $resultDailyReportTemp[$indexDailyReport]['total']++;
            }
            
            isset($status[$order->status]) ? $status[$order->status]++ : $status[$order->status] = 1 ;
        }

        $resultProgress = [];
        foreach( $resultProgressTemp as $key => $value ) {
            $resultProgress[] = $value;
        }

        $resultDailyReport = [];
        foreach( $resultDailyReportTemp as $key => $value ) {
            $resultDailyReport[] = $value;
        }

        return json_encode(array(
            'courier_progress' => array(
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => count($resultProgress),
                'recordsFiltered' => count($resultProgress),
                'data'            => $resultProgress
            ),
            'daily_report' => array(
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => count($resultDailyReport),
                'recordsFiltered' => count($resultDailyReport),
                'data'            => $resultDailyReport
            ),
            'total'           => $total,
            'status'          => $status,
        ));
    }

    public function downloadStockMovement(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'start' => 'required',
            'end' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('/report')
                        ->withErrors($validator)
                        ->withInput();
        } else {

            $start_date = strtotime($request->input('start'));
            $end_date = strtotime($request->input('end'));

            if($end_date < $start_date){
                $end_date = $start_date;
            }

            $alphabets = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
            $columns = array();

            for($i=0;$i<count($alphabets);$i++){
                for($x=0;$x<count($alphabets);$x++){
                    $columns[] = $alphabets[$i].$alphabets[$x];
                }
            }

            $merged = array_merge($alphabets,$columns);

            // calculate how many days between date
            $datediff = $end_date - $start_date;
            $days = round($datediff / (60 * 60 * 24));
            $days = ($days > 31)?31:$days;

            $dQuery = DB::table('inbound_detail')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
                ->where('inbound_detail.created_at','<=',date('Y-m-d 23:59:59',strtotime('-1 days',$start_date)))
                ->groupBy('product.id','product_type_size.id')
                ->select('product.id as product_id','product_type_size.id as product_type_size_id','product.name as product_name','product_type_size.name as size_name',DB::raw('SUM(inbound_detail.actual_qty) as total_in'));

            if(Auth::user()->roles == 'client'){
                $dQuery->where('product.client_id','=',Auth::user()->client_id);
            }

            $details = $dQuery->get()->toArray();

            foreach($details as $key => $detail){
                $details[$key]->product_name = $details[$key]->product_name.' ('.$details[$key]->size_name.')';
                $details[$key]->total_in = intval($details[$key]->total_in);

                $qTotalOut = DB::table('inbound_detail_location')
                    ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                    ->join('product','product.id','=','inbound_detail.product_id')
                    ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
                    ->where('inbound_detail.product_id','=',$detail->product_id)
                    ->where('inbound_detail.product_type_size_id','=',$detail->product_type_size_id)
                    ->whereNotNull('inbound_detail_location.date_outbounded')
                    ->whereRaw('inbound_detail_location.date_outbounded >= "'.date('Y-m-d 00:00:00',strtotime('-1 days',$start_date)).'" AND inbound_detail_location.date_outbounded < "'.date('Y-m-d 23:59:59',strtotime('-1 days',$end_date)).'"');

                if(Auth::user()->roles == 'client'){
                    $qTotalOut->where('product.client_id','=',Auth::user()->client_id);
                }

                $details[$key]->total_out = $qTotalOut->count();
                $details[$key]->initial = ($details[$key]->total_in - $details[$key]->total_out);
                $details[$key]->days = array();

                for($d=0;$d<=$days;$d++){

                    $qIn = DB::table('inbound_detail')
                        ->join('product','product.id','=','inbound_detail.product_id')
                        ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
                        ->where('inbound_detail.product_id','=',$detail->product_id)
                        ->where('inbound_detail.product_type_size_id','=',$detail->product_type_size_id)
                        ->whereRaw('inbound_detail.created_at >= "'.date('Y-m-d 00:00:00',strtotime('+'.$d.' days',$start_date)).'" AND inbound_detail.created_at < "'.date('Y-m-d 23:59:59',strtotime('+'.$d.' days',$start_date)).'"')
                        ->select(DB::raw('SUM(inbound_detail.actual_qty) as total_in'));

                    if(Auth::user()->roles == 'client'){
                        $qIn->where('product.client_id','=',Auth::user()->client_id);
                    }

                    $query = $qIn->get()->toArray();

                    $qOut = DB::table('inbound_detail_location')
                        ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                        ->join('product','product.id','=','inbound_detail.product_id')
                        ->join('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
                        ->where('inbound_detail.product_id','=',$detail->product_id)
                        ->where('inbound_detail.product_type_size_id','=',$detail->product_type_size_id)
                        ->whereNotNull('inbound_detail_location.date_outbounded')
                        ->whereRaw('inbound_detail_location.date_outbounded >= "'.date('Y-m-d 00:00:00',strtotime('+'.$d.' days',$start_date)).'" AND inbound_detail_location.date_outbounded < "'.date('Y-m-d 23:59:59',strtotime('+'.$d.' days',$start_date)).'"');

                    if(Auth::user()->roles == 'client'){
                        $qOut->where('product.client_id','=',Auth::user()->client_id);
                    }

                    $details[$key]->days[date('Y-m-d',strtotime('+'.$d.' days',$start_date))] = array(
                        'in' => intval($query[0]->total_in),
                        'out' => $qOut->count()
                    );

                    $details[$key]->days[date('Y-m-d',strtotime('+'.$d.' days',$start_date))]['stock'] = $details[$key]->days[date('Y-m-d',strtotime('+'.$d.' days',$start_date))]['in'] - $details[$key]->days[date('Y-m-d',strtotime('+'.$d.' days',$start_date))]['out'];

                    if($d==0){
                        $details[$key]->days[date('Y-m-d',strtotime('+'.$d.' days',$start_date))]['stock'] = $details[$key]->initial + $details[$key]->days[date('Y-m-d',strtotime('+'.$d.' days',$start_date))]['in'] - $details[$key]->days[date('Y-m-d',strtotime('+'.$d.' days',$start_date))]['out'];
                    } else {
                        $details[$key]->days[date('Y-m-d',strtotime('+'.$d.' days',$start_date))]['stock'] = ($details[$key]->days[date('Y-m-d',strtotime('+'.($d-1).' days',$start_date))]['stock'] + $details[$key]->days[date('Y-m-d',strtotime('+'.$d.' days',$start_date))]['in']) - $details[$key]->days[date('Y-m-d',strtotime('+'.$d.' days',$start_date))]['out'];
                    }
                }
            }

            Excel::create(date('dmY',$start_date).'-'.date('dmY',$end_date).'-stocks-movement', function($excel) use ($merged, $details, $days, $start_date){
                $excel->sheet('Stock Movement', function($sheet) use ($merged, $details, $days, $start_date){

                    $sheet->setCellValue('A1', 'Product');
                    $sheet->setCellValue('B1', date('d M',strtotime("-1 days")));
                    $sheet->mergeCells('B1:C1');
                    $sheet->setCellValue('B2', 'IN');
                    $sheet->setCellValue('C2', 'OUT');
                    $sheet->setCellValue('D2', 'Initial');
                    $i=0;
                    $stake = $days + 1;
                    for($y=4;$y<(($stake+1)*3)+1;$y+=3){
                        $sheet->setCellValue($merged[$y].'1', date('d M Y',strtotime('+'.$i.' days',$start_date)));
                        $sheet->mergeCells($merged[$y].'1:'.$merged[$y+2].'1');
                        $sheet->setCellValue($merged[$y].'2', 'In');
                        $sheet->setCellValue($merged[$y+1].'2', 'Out');
                        $sheet->setCellValue($merged[$y+2].'2', 'Stock');
                        $i++;
                    }

                    $b = 3;
                    for($z=0;$z<count($details);$z++){
                        // Product name
                        $sheet->setCellValue('A'.strval($z+3), $details[$z]->product_name);
                        // Last stock in day - 1
                        $sheet->setCellValue('B'.strval($z+3), $details[$z]->total_in);
                        // Last stock out day - 1
                        $sheet->setCellValue('C'.strval($z+3), $details[$z]->total_out);
                        // Initial value of stock
                        $sheet->setCellValue('D'.strval($z+3), $details[$z]->total_in - $details[$z]->total_out);

                        $a = 4;
                        foreach($details[$z]->days as $day){
                            $sheet->setCellValue($merged[$a].$b, $day['in']);
                            $sheet->setCellValue($merged[$a+1].$b, $day['out']);
                            $sheet->setCellValue($merged[$a+2].$b, $day['stock']);
                            $a+=3;
                        }
                        $b++;
                    }

                });
            })->export('xls');
        }
    }

    public function pendingOrderPage(Request $request, $date)
    {
        $query = DB::table('orders')
            ->select(
                DB::raw('DISTINCT orders.client_id')
            )
            ->join('customer', 'customer.id', '=', 'orders.customer_id')
            ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
            ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
            ->where('orders.created_at', '>=', date('Y-m-d 00:00:00', strtotime($date)))
            ->where('orders.created_at', '<', date('Y-m-d 00:00:00', strtotime($date." + 1 day")))
            ->where('orders.status', '=', 'PENDING')
            ->groupBy('orders.id')
            ->groupBy('product.id');

        if (Auth::user()->roles == 'client')
            $query->where('orders.client_id', '=', Auth::user()->client_id);

    	return view('dashboard.report.pending-order', [
            'current_date' => $date,
            'clients' => Client::whereIn('id', $query->pluck('client_id'))->get()
        ]);
    }

    public function outboundAnalytic(Request $request)
    {
        $clients = Client::all();
        $warehouses = Warehouse::where('is_active', '=', 1)->get();
        return view('dashboard.report.outbound',['clients' => $clients, 'warehouses' => $warehouses]);
    }

}
