<?php

namespace App\Http\Controllers;

use App\Client;
use App\Customer;
use App\Http\Services\ThirdPartyLogistic\Jne;
use App\Http\Services\ThirdPartyLogistic\Tpl;
use App\Http\Services\OmnichannelSvc\SalesOrder;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use App\Inbound;
use App\Inbound_detail;
use App\InboundLocation;
use App\Order;
use App\OrderCopy;
use App\OrderDetail;
use App\OrderDetailCopy;
use App\OrderSource;
use App\OrderHistory;
use App\OrderTrackingHistory;
use App\Product;
use App\ProductTypeSize;
use App\User;
use App\Warehouse;

use Auth;
use Config;
use DB;
use Excel;
use Exception;
use Log;
use QRCode;
use Session;
use Validator;
use Redirect;

use Carbon\Carbon;
use Mpdf\Mpdf;
use GuzzleHttp\Client as HttpClient;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $restrict = ($request->get('r') != null && $request->get('r') == 0);

        $clients = Client::orderBy('name')->get();
        
        $warehouses = Warehouse::where('is_active', '=', 1)->get();
        
        return view('dashboard.order.index', compact('restrict','clients', 'warehouses'));
    }
    
    public function get_list(Request $request)
    {
        $columns = [
            'id',
            'order_number',
            'external_order_number',
            'order_partner_id',
            'client_name',
            'customer_name',
            'details',
            'courier',
            'no_resi',
            'shipping_cost',
            'due_date',
            'created_at',
            'status'
        ];
        $query = null;

        // Input variable
        $isCopy = !empty($request->input('copy'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $startDueDate = $request->input('start_due_date');
        $endDueDate = $request->input('end_due_date');
        $clientId = $request->input('client');
        $status = $request->input('status');
        $limit = $request->input('length');
        $start = $request->input('start');
        $warehouse = $request->input('warehouse');
        $orderIndex = $request->input('order.0.column');
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

        // Date Processing
        if (!empty($startDueDate) && !empty($endDueDate)) {
            $startDueDate = Carbon::parse($startDueDate)->startOfDay();
            $endDueDate = Carbon::parse($endDueDate)->endOfDay(); // Make sure the time still latest cause its 00:00:00

            // Will add validation on front end as well, just to make sure in back end
            if ($startDueDate->diffInDays($endDueDate) > 31) 
                $endDueDate = $startDueDate->copy()->addDays(31);
        } else {
            // If Somehow the date is empty, it will take last 1 week, will add on front end also
            $startDueDate = Carbon::now()->subDays(7);
            $endDueDate = Carbon::now()->endOfDay(); // Make sure the time still latest
        }

        // Set source table
        $tableSource = $isCopy ? 'orders_copy' : 'orders';
        $tableSourceDetail = $isCopy ? 'order_detail_copy' : 'order_detail';

        // Build query for result
        $query = DB::connection('read_replica')
            ->table($tableSource)
            ->join($tableSourceDetail, $tableSourceDetail.'.orders_id', '=', $tableSource.'.id')
            ->join('inbound_detail', 'inbound_detail.id', '=', $tableSourceDetail.'.inbound_detail_id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('customer', 'customer.id', '=', $tableSource.'.customer_id')
            ->join('client', 'client.id', '=', $tableSource.'.client_id')
            ->leftJoin('omnichannel_db.order_partner as op', 'op.id', '=', $tableSource.'.order_partner_id')
            ->where($tableSource.'.created_at', '>=', $startDate->format('Y-m-d H:i:s'))
            ->where($tableSource.'.created_at', '<=', $endDate->format('Y-m-d H:i:s'))
            ->where($tableSource.'.due_date', '>=', $startDueDate->format('Y-m-d H:i:s'))
            ->where($tableSource.'.due_date', '<=', $endDueDate->format('Y-m-d H:i:s'));

        // Check if admin or client
        if (Auth::user()->roles == 'client') {
            unset($columns[array_search('client_name', $columns)]);
            unset($columns[array_search('external_order_number', $columns)]);
            $query->where('client.id', '=', Auth::user()->client_id);
        }
        else {
            unset($columns[array_search('shipping_cost', $columns)]);
            if (!empty($clientId))
                $query->where('client.id', '=', $clientId);
        }

        $columns = array_values($columns);
        $orderColumn = $orderIndex == 0 ? 'created_at' : $columns[$orderIndex];

        // Check status filter
        if (!empty($status))
            $query->where($tableSource.'.status', '=', $status);

        // Check warehouse
        if (!empty($warehouse))
            $query->where($tableSource.'.warehouse_id', '=', $warehouse);

        // Count all data
        $total = $query->count(DB::raw('DISTINCT '.$tableSource.'.id'));

        // Handle search
        if (!empty($search))
            if (Auth::user()->role != 'client') {
                $query->where(function($q) use ($search) {
                    $q->Where('customer.name', 'LIKE', $search.'%')
                    ->Orwhere('order_number', 'LIKE', $search.'%')
                    ->OrWhere('external_order_number', 'LIKE', $search.'%');
                });
            } else {
                $query->where('order_number', 'LIKE', $search.'%');
            }
            

        // Count filtered data
        $totalFiltered = $query->count(DB::raw('DISTINCT '.$tableSource.'.id'));

        // Build selected column
        $query->select(...array_map(function ($item) use ($tableSource) {
            if ($item == 'client_name')
                return 'client.name as client_name';
            else if ($item == 'customer_name')
                return 'customer.name as customer_name';
            else if ($item == 'details')
                return 'product.name as details';
            else if ($item == 'external_order_number')
                return 'op.external_order_number as external_order_number';
            else
                return $tableSource.'.'.$item;
        }, $columns));

        // Pagination handler
        $query->limit($limit)->offset($start)->groupBy($tableSource.'.id');

        // Array data to show
        $result = [];

        // Preparing data, max loop only 10 data
        foreach ($query->orderBy($orderColumn, $orderDirection)->get()->toArray() as $order) {
            $order = json_decode(json_encode($order), true);

            // Get Product Detail
            $details = DB::connection('read_replica')
                ->table('product')
                ->select(DB::raw('COUNT('.$tableSourceDetail.'.id) as count'),
                    'product.name',
                    'product_type_size.name as size')
                ->leftJoin('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                ->leftJoin('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                ->leftJoin('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join($tableSourceDetail, $tableSourceDetail.'.inbound_detail_id', '=', 'inbound_detail.id')
                ->where($tableSource.'_id', '=', $order['id'])
                ->groupBy($tableSourceDetail.'.inbound_detail_id')
                ->groupBy('inbound_detail_location.id')
                ->distinct()
                ->get();

            // Generate source
            $order['source'] = empty($order['order_partner_id']) ? "WMS" : "Jubelio";
            
            // Generate Detail Product
            $order['details'] = '<ul class="list-styled">';  
            foreach ($details as $key => $detail)
                $order['details'] .= '<li>' . $detail->name . '(' . $detail->size . ') : ' . $detail->count . '</li>';
            $order['details'] .= '</ul>';  
            
            if (!count($details))
                $order['details'] = '-';

            // Check no resi
            $order['no_resi'] = $order['no_resi'] != null ? $order['no_resi'] : '-';

            // Formating created at
            $order['created_at'] = date('d-M-Y H:i', strtotime($order['created_at']));

            // Change status to label
            $order['status'] = Config::get('constants.order_status.'.$order['status']);

            $due_date = new Carbon($order['due_date']);
            $now = Carbon::now();
            $diff = $now->diffInHours($due_date, false);
            $order['due_date'] = date('d M Y H:i', strtotime($order['due_date']));
            
            if ($diff < 0) {
                $order['due_date'] = '<b style="color: #2F4F4F">' . $order['due_date'] . '</b>';
            } else if ($diff < 24) {
                $order['due_date'] = '<b style="color: #FF0000">' . $order['due_date'] . '</b>';
            } else if ($diff < 48) {
                $order['due_date'] = '<b style="color: #E6AC00">' . $order['due_date'] . '</b>';
            } else {
                $order['due_date'] = '<b style="color: #006400">' . $order['due_date'] . '</b>';
            }

            // Action button
            $order['action'] = '<div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Action
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="/order/edit/' . $order['id'] . '">Edit</a>
                    <a class="dropdown-item" href="order/location/' . $order['id'] . '">View Location</a>
                    <a class="dropdown-item" href="order/tpl/history/' . $order['id'] . '">Trace & Track</a>
                </div>
            </div>';

            // Add prepared data to array
            $result[] = $order;
        }
            
        return json_encode(array(
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => intval($total),
            'recordsFiltered' => intval($totalFiltered),
            'data'            => $result
        ));
    }
    
    public function ready(Request $request)
    {
        $order = null;
        if (Auth::user()->roles == 'client') {
            $user   = User::find(Auth::user()->id);
            $orders = DB::table('orders')
                ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
                ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('customer', 'customer.id', '=', 'orders.customer_id')
                ->join('client', 'client.id', '=', 'orders.client_id')
                ->orderBy('orders.created_at', 'desc')
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->groupBy('orders.id')
                ->where('orders.client_id', '=', $user->client_id)
                ->get();
        } else {
            $orders = DB::table('orders')
                ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
                ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('customer', 'customer.id', '=', 'orders.customer_id')
                ->join('client', 'client.id', '=', 'orders.client_id')
                ->orderBy('orders.created_at', 'desc')
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->groupBy('orders.id')
                ->where('orders.status', '=', 'READY_FOR_OUTBOUND')
                ->get();
        }
        
        return view('dashboard.order.ready', ['orders' => $orders]);
    }
    
    public function get_ready_list(Request $request)
    {
        $columns = array('id', 'order_number', 'order_type', 'customer', 'client', 'details', 'courier', 'no_resi', 'shipping_cost', 'created_at', 'action');
        
        if (Auth::user()->roles == 'client') {
            if (($key = array_search('order_type', $columns)) !== false) {
                unset($columns[$key]);
            }
            
            if (($key = array_search('client', $columns)) !== false) {
                unset($columns[$key]);
            }
        }
        
        if (Auth::user()->roles != 'client') {
            if (($key = array_search('details', $columns)) !== false) {
                unset($columns[$key]);
            }
            
            if (($key = array_search('courier', $columns)) !== false) {
                unset($columns[$key]);
            }
            
            if (($key = array_search('no_resi', $columns)) !== false) {
                unset($columns[$key]);
            }
            
            if (($key = array_search('shipping_cost', $columns)) !== false) {
                unset($columns[$key]);
            }
        }
        
        $total = null;
        
        if (Auth::user()->roles == 'client') {
            $user  = User::find(Auth::user()->id);
            $total = DB::table('orders')
                ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
                ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('customer', 'customer.id', '=', 'orders.customer_id')
                ->join('client', 'client.id', '=', 'orders.client_id')
                ->orderBy('orders.created_at', 'desc')
                ->groupBy('orders.id')
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->where('orders.client_id', '=', $user->client_id)
                ->count();
        } else {
            $total = DB::table('orders')
                ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
                ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('customer', 'customer.id', '=', 'orders.customer_id')
                ->join('client', 'client.id', '=', 'orders.client_id')
                ->orderBy('orders.created_at', 'desc')
                ->groupBy('orders.id')
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->where('orders.status', '=', 'READY_FOR_OUTBOUND')
                ->count();
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
                $boots = DB::table('orders')
                    ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
                    ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('customer', 'customer.id', '=', 'orders.customer_id')
                    ->join('client', 'client.id', '=', 'orders.client_id')
                    ->orderBy('orders.created_at', 'desc')
                    ->groupBy('orders.id')
                    ->where('orders.client_id', '=', $user->client_id)
                    ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
            } else {
                $boots = DB::table('orders')
                    ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
                    ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('customer', 'customer.id', '=', 'orders.customer_id')
                    ->join('client', 'client.id', '=', 'orders.client_id')
                    ->orderBy('orders.created_at', 'desc')
                    ->groupBy('orders.id')
                    ->where('orders.status', '=', 'READY_FOR_OUTBOUND')
                    ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
            }
            
        } else {
            
            $search = $request->input('search.value');
            
            if (Auth::user()->roles == 'client') {
                $user  = User::find(Auth::user()->id);
                $boots = DB::table('orders')
                    ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
                    ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('customer', 'customer.id', '=', 'orders.customer_id')
                    ->join('client', 'client.id', '=', 'orders.client_id')
                    ->orderBy('orders.created_at', 'desc')
                    ->groupBy('orders.id')
                    ->whereRaw('orders.client_id = ' . $user->client_id . ' AND product.client_id = ' . $user->client_id . ' AND (orders.order_number = "' . $search . '")')
                    ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
                
                $totalFiltered = DB::table('orders')
                    ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
                    ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('customer', 'customer.id', '=', 'orders.customer_id')
                    ->join('client', 'client.id', '=', 'orders.client_id')
                    ->orderBy('orders.created_at', 'desc')
                    ->groupBy('orders.id')
                    ->whereRaw('orders.client_id = ' . $user->client_id . ' AND product.client_id = ' . $user->client_id . ' AND (orders.order_number = "' . $search . '")')
                    ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                    ->count();
                
            } else {
                $boots = DB::table('orders')
                    ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
                    ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('customer', 'customer.id', '=', 'orders.customer_id')
                    ->join('client', 'client.id', '=', 'orders.client_id')
                    ->orderBy('orders.created_at', 'desc')
                    ->groupBy('orders.id')
                    ->where('orders.status', '=', 'READY_FOR_OUTBOUND')
                    ->whereRaw('orders.order_number = "' . $search . '"')
                    ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();
                
                $totalFiltered = DB::table('orders')
                    ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
                    ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                    ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                    ->join('customer', 'customer.id', '=', 'orders.customer_id')
                    ->join('client', 'client.id', '=', 'orders.client_id')
                    ->orderBy('orders.created_at', 'desc')
                    ->groupBy('orders.id')
                    ->where('orders.status', '=', 'READY_FOR_OUTBOUND')
                    ->whereRaw('orders.order_number = "' . $search . '"')
                    ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                    ->count();
            }
        }
        
        $data = array();
        if (!empty($boots)) {
            foreach ($boots as $order) {
                $obj                 = array();
                $obj['id']           = $order->id;
                $obj['order_number'] = $order->order_number;
                if (Auth::user()->roles != 'client') {
                    $pretty            = \App\Order::orderType();
                    $obj['order_type'] = $pretty[$order->order_type];
                    $obj['client']     = $order->client_name;
                }
                $obj['customer'] = $order->customer_name;
                
                if (Auth::user()->roles == 'client') {
                    $details = OrderDetail::where('orders_id', $order->id)->get();
                    $arr     = array();
                    foreach ($details as $dkey => $dvalue) {
                        if (!array_key_exists($dvalue->inbound_detail_id, $arr)) {
                            $order_count    = OrderDetail::where('inbound_detail_id', $dvalue->inbound_detail_id)->where('orders_id', $order->id)->count();
                            $product_detail = DB::table('product')
                                ->select('product.id as product_id', 'inbound_detail_location.id as inbound_location_id', 'product.name', 'inbound_detail.color', 'product_type_size.name as size_name')
                                ->leftJoin('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                                ->leftJoin('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                                ->leftJoin('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                                ->where('inbound_detail.id', '=', $dvalue->inbound_detail_id)
                                ->first();
                            
                            if ($product_detail != null) {
                                $arr[$dvalue->inbound_detail_id] = array('name' => $product_detail->name, 'color' => $product_detail->color, 'size' => $product_detail->size_name, 'count' => $order_count);
                            }
                        }
                    }
                    
                    
                    if (count($arr) > 0) {
                        $list = '<ul class="list-styled">';
                        foreach ($arr as $detail) {
                            $list .= '<li>' . $detail['name'] . '(' . $detail['size'] . ') : ' . $detail['count'] . '</li>';
                        }
                        $list .= '</ul>';
                        $obj['details'] = $list;
                    } else {
                        $obj['details'] = '-';
                    }
                    $obj['courier']       = $order->courier;
                    $obj['no_resi']       = ($order->no_resi != null) ? $order->no_resi : '-';
                    $obj['shipping_cost'] = ($order->shipping_cost != null) ? 'Rp. ' . $order->shipping_cost : '-';
                }
                
                $obj['created_at'] = date('d-M-Y H:i', strtotime($order->created_at));
                $actionview        = '<div class="dropdown">
                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Action
                            </button>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item" href="order/edit/' . $order->id . '">Edit</a>
                                <a class="dropdown-item" href="order/location/' . $order->id . '">View Location</a>
                                <a class="dropdown-item" href="order/tpl/history/' . $order->id . '">Trace & Track</a>';
                
                if (Auth::user()->roles != 'crew' || Auth::user()->roles != 'client') {
                    $actionview .= '<a class="dropdown-item delete-btn" data-id="' . $order->id . '">Force Delete</a>';
                }
                
                $actionview .= '</div></div>';
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
    
    public function canceled(Request $request)
    {
        $orders = DB::table('orders')
            ->select('orders.*', 'client.name as client_name', 'customer.name as customer_name', 'product.name as product_name')
            ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
            ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
            ->join('product', 'product.id', '=', 'inbound_detail.product_id')
            ->join('customer', 'customer.id', '=', 'orders.customer_id')
            ->join('client', 'client.id', '=', 'orders.client_id')
            ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
            ->orderBy('orders.created_at', 'desc')
            ->groupBy('orders.id')
            ->where('orders.status', '=', 'CANCELED')
            ->get();
        
        return view('dashboard.order.canceled', ['orders' => $orders]);
    }
    
    public function add(Request $request)
    {
        $raws     = Client::all();
        $clients  = array();
        $products = null;
        foreach ($raws as $client) {
            array_push($clients, $client->name);
        }
        
        if (Auth::user()->roles == 'client') {
            $user     = User::find(Auth::user()->id);
            $products = DB::table('product')
                ->select('product.id as product_id', 'inbound_detail.id as inbound_detail_id', 'inbound_detail_location.id as inbound_location_id', 'product.name', 'inbound_detail.color', 'product_type_size.name as size_name', 'inbound_detail.actual_qty as qty', 'product_type_size.id as product_type_size_id')
                ->join('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                ->leftJoin('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('client', 'client.id', '=', 'product.client_id')
                ->where('client.id', '=', $user->client_id)
                ->groupBy('product.id', 'product.name', 'inbound_detail.color', 'product_type_size.name')
                ->get();
            
            if ($products != null) {
                foreach ($products as $key => $product) {
                    $product_count       = DB::table('inbound_detail_location')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                        ->where('inbound_detail.product_id', '=', $product->product_id)
                        ->where('inbound_detail.product_type_size_id', $product->product_type_size_id)
                        ->whereNotNull('inbound_detail_location.shelf_id')
                        ->whereNull('inbound_detail_location.order_detail_id')
                        ->whereNull('inbound_detail_location.date_picked')
                        ->whereNull('inbound_detail_location.date_outbounded')
                        ->count();
                    $products[$key]->qty = $product_count;
                }
            }
        }
        
        return view('dashboard.order.new', ['clients' => json_encode($clients), 'products' => $products]);
    }
    
    public function edit(Request $request, Jne $jne, $id)
    {
        $inputs = $request->all();
        
        $order    = null;
        $clients  = null;
        $customer = null;
        
        $restrict = ($request->get('r') != null && $request->get('r') == 0);
        
        if ($restrict) {
            
            if (isset(Auth::user()->client_id) && OrderCopy::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
                $request->session()->flash('error', 'You are not allowed to access this order');
                
                return redirect('order?r=0');
            }
            
            $order    = OrderCopy::find($id);
            $details  = OrderDetailCopy::where('orders_id', $id)->get();
            $arr      = array();
            $clients  = Client::all();
            $customer = Customer::find($order->customer_id);
            
            foreach ($details as $key => $detail) {
                if (!array_key_exists($detail->inbound_detail_id, $arr)) {
                    $detail_product = Inbound_detail::find($detail->inbound_detail_id);
                    // Get total of
                    $product_count = DB::table('inbound_detail_location')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                        ->where('inbound_detail.product_id', '=', $detail_product->product_id)
                        ->where('inbound_detail.product_type_size_id', '=', $detail_product->product_type_size_id)
                        ->whereNull('order_detail_id')
                        ->whereNotNull('shelf_id')
                        ->count();
                    
                    $order_count = DB::table('order_detail_copy')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail_copy.inbound_detail_id')
                        ->where('order_detail_copy.orders_id', '=', $id)
                        ->where('inbound_detail.product_id', '=', $detail_product->product_id)
                        ->where('inbound_detail.product_type_size_id', '=', $detail_product->product_type_size_id)
                        ->count();
                    
                    $pending_count = DB::table('order_detail_copy')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail_copy.inbound_detail_id')
                        ->where('order_detail_copy.orders_id', '=', $id)
                        ->where('inbound_detail.product_id', '=', $detail_product->product_id)
                        ->where('inbound_detail.product_type_size_id', '=', $detail_product->product_type_size_id)
                        ->whereNull('order_detail_copy.inbound_detail_location_id')
                        ->count();
                    
                    $product_detail = DB::table('product')
                        ->select('product.id as product_id', 'inbound_detail_location.id as inbound_location_id', 'product.name', 'inbound_detail.color', 'product_type_size.name as size_name')
                        ->leftJoin('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                        ->leftJoin('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                        ->leftJoin('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                        ->where('inbound_detail.id', '=', $detail->inbound_detail_id)
                        ->first();
                    
                    $arr[$detail->inbound_detail_id] = array('product_id' => $product_detail->product_id, 'product_location_id' => $product_detail->inbound_location_id, 'name' => $product_detail->name, 'color' => $product_detail->color, 'size' => $product_detail->size_name, 'count' => $order_count, 'total' => $product_count, 'pending' => $pending_count);
                }
            }
        } else {
            if (isset(Auth::user()->client_id) && Order::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
                $request->session()->flash('error', 'You are not allowed to access this order');
                
                return redirect('order');
            }
            
            $order    = Order::find($id);
            $job_code = $jne->setJOB($order->order_number);
            $details  = OrderDetail::where('orders_id', $id)->get();
            $arr      = array();
            $clients  = Client::all();
            $customer = Customer::find($order->customer_id);
            
            foreach ($details as $key => $detail) {
                if (!array_key_exists($detail->inbound_detail_id, $arr)) {
                    $detail_product = Inbound_detail::find($detail->inbound_detail_id);
                    // Get total of
                    $product_count = DB::table('inbound_detail_location')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                        ->where('inbound_detail.product_id', '=', $detail_product->product_id)
                        ->where('inbound_detail.product_type_size_id', '=', $detail_product->product_type_size_id)
                        ->whereNull('order_detail_id')
                        ->whereNotNull('shelf_id')
                        ->count();
                    
                    $order_count = DB::table('order_detail')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                        ->where('order_detail.orders_id', '=', $id)
                        ->where('inbound_detail.product_id', '=', $detail_product->product_id)
                        ->where('inbound_detail.product_type_size_id', '=', $detail_product->product_type_size_id)
                        ->count();
                    
                    $pending_count = DB::table('order_detail')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                        ->where('order_detail.orders_id', '=', $id)
                        ->where('inbound_detail.product_id', '=', $detail_product->product_id)
                        ->where('inbound_detail.product_type_size_id', '=', $detail_product->product_type_size_id)
                        ->whereNull('order_detail.inbound_detail_location_id')
                        ->count();
                    
                    $product_detail = DB::table('product')
                        ->select('product.id as product_id', 'inbound_detail_location.id as inbound_location_id', 'product.name', 'inbound_detail.color', 'product_type_size.name as size_name')
                        ->leftJoin('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                        ->leftJoin('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                        ->leftJoin('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                        ->where('inbound_detail.id', '=', $detail->inbound_detail_id)
                        ->first();
                    
                    $arr[$detail->inbound_detail_id] = array('product_id' => $product_detail->product_id, 'product_location_id' => $product_detail->inbound_location_id, 'name' => $product_detail->name, 'color' => $product_detail->color, 'size' => $product_detail->size_name, 'count' => $order_count, 'total' => $product_count, 'pending' => $pending_count);
                }
            }
        }
        
        return view('dashboard.order.edit', ['order' => $order, 'clients' => $clients, 'details' => $arr, 'customer' => $customer, 'ref' => (isset($inputs['ref'])) ? $inputs['ref'] : null, 'restrict' => $restrict]);
    }
    
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required',
            'email'    => 'required|email',
            'phone'    => 'required',
            'address'  => 'required',
            'zip_code' => 'required'
        ]);
        
        if ($request->input('restricted') != null && $request->input('restricted') == "true") {
            if ($validator->fails()) {
                return redirect('/order/edit/' . $id . '?r=0')
                    ->withErrors($validator)
                    ->withInput();
            } else {
                $order                = OrderCopy::find($id);
                $order->version       = 1;
                $order->courier       = $request->input('courier');
                $order->no_resi       = $request->input('no_resi');
                $order->shipping_cost = ($request->input('shipping_cost') != null) ? $request->input('shipping_cost') : 0;
                $order->total         = $order->shipping_cost + $order->client_pricing_order;
                
                if ($request->input('is_shipped') != null) {
                    if ($request->input('is_shipped') == "done" && $request->input('courier') != null && $request->input('no_resi') != null && $request->input('shipping_cost') != null) {
                        $order->shipment_date = date('Y-m-d H:i:s');
                        $order->shipper_id    = Auth::user()->id;
                        $order->status        = 'SHIPPED';
                    } else {
                        $request->session()->flash('error', 'Please fill Courier, No. Resi, and Shipping Cost field to confirm shipment.');
                    }
                }
                
                $order->notes = $request->input('notes');
                $order->save();
                
                // Update deleted status files
                if ($request->input('old_source') != null) {
                    DB::table('order_sources')
                        ->whereNotIn('id', $request->input('old_source'))
                        ->update([
                            'status' => 0
                        ]);
                } else {
                    DB::table('order_sources')
                        ->where('order_id', $id)
                        ->update([
                            'status' => 0
                        ]);
                }
                
                // Upload source file
                if ($request->file('source_order') != null) {
                    foreach ($request->file('source_order') as $key => $file) {
                        $ext     = $file->getClientOriginalExtension();
                        $size    = $file->getSize();
                        $newName = $request->input('order_number') . "_" . date('Ymd') . "_" . rand(100000, 1001238912) . "." . $ext;
                        $file->move('images/sources', $newName);
                        
                        $file_path = public_path() . "/images/sources/";
                        
                        $s3     = AWS::createClient('s3');
                        $upload = $s3->putObject(array(
                            'Bucket'     => 'static-pakde',
                            'Key'        => $newName,
                            'SourceFile' => $file_path . $newName,
                            'ACL'        => 'public-read'
                        ));
                        
                        unlink($file_path . $newName);
                        $source               = new OrderSource;
                        $source->order_id     = $id;
                        $source->source_order = $newName;
                        $source->save();
                    }
                }
                
                $customer           = Customer::find($order->customer_id);
                $customer->address  = $request->input('address');
                $customer->email    = $request->input('email');
                $customer->name     = $request->input('name');
                $customer->phone    = $request->input('phone');
                $customer->zip_code = $request->input('zip_code');
                $customer->save();
                
                $request->session()->flash('success', 'Order has successfully updated');
            }
            
            return redirect('/order/edit/' . $id . "?r=0");
        } else {
            if ($validator->fails()) {
                return redirect('/order/edit/' . $id)
                    ->withErrors($validator)
                    ->withInput();
            } else {
                $order                = Order::find($id);
                $order->version       = 1;
                $order->courier       = $request->input('courier');
                $order->no_resi       = $request->input('no_resi');
                $order->shipping_cost = ($request->input('shipping_cost') != null) ? $request->input('shipping_cost') : 0;
                $order->total         = $order->shipping_cost + $order->client_pricing_order;
                
                if ($request->input('is_shipped') != null) {
                    if ($request->input('is_shipped') == "done" && $request->input('courier') != null && $request->input('no_resi') != null && $request->input('shipping_cost') != null) {
                        $order->shipment_date = date('Y-m-d H:i:s');
                        $order->shipper_id    = Auth::user()->id;
                        $order->status        = 'SHIPPED';
                    } else {
                        $request->session()->flash('error', 'Please fill Courier, No. Resi, and Shipping Cost field to confirm shipment.');
                    }
                }
                
                $order->notes = $request->input('notes');
                $order->save();
                
                // Update deleted status files
                if ($request->input('old_source') != null) {
                    DB::table('order_sources')
                        ->whereNotIn('id', $request->input('old_source'))
                        ->update([
                            'status' => 0
                        ]);
                } else {
                    DB::table('order_sources')
                        ->where('order_id', $id)
                        ->update([
                            'status' => 0
                        ]);
                }
                
                // Upload source file
                if ($request->file('source_order') != null) {
                    foreach ($request->file('source_order') as $key => $file) {
                        $ext     = $file->getClientOriginalExtension();
                        $size    = $file->getSize();
                        $newName = $request->input('order_number') . "_" . date('Ymd') . "_" . rand(100000, 1001238912) . "." . $ext;
                        $file->move('images/sources', $newName);
                        
                        $file_path = public_path() . "/images/sources/";
                        
                        $s3     = AWS::createClient('s3');
                        $upload = $s3->putObject(array(
                            'Bucket'     => 'static-pakde',
                            'Key'        => $newName,
                            'SourceFile' => $file_path . $newName,
                            'ACL'        => 'public-read'
                        ));
                        
                        unlink($file_path . $newName);
                        $source               = new OrderSource;
                        $source->order_id     = $id;
                        $source->source_order = $newName;
                        $source->save();
                    }
                }
                
                $customer           = Customer::find($order->customer_id);
                $customer->address  = $request->input('address');
                $customer->email    = $request->input('email');
                $customer->name     = $request->input('name');
                $customer->phone    = $request->input('phone');
                $customer->zip_code = $request->input('zip_code');
                $customer->save();
                
                $request->session()->flash('success', 'Order has successfully updated');
            }
            
            if ($request->input('is_shipped') != null) {
                return redirect('/outbound/done');
            } else {
                return redirect('/order/edit/' . $id);
            }
        }
    }
    
    public function delete(Request $request)
    {
        $input  = $request->all();
        $orders = explode(',', $input['order_id']);
        $hitung = 0;
        foreach ($orders as $key => $ord_number) {
            if ($request->input('restricted') != null && $request->input('restricted') == "true") {
                $order = OrderCopy::where('order_number', $ord_number)->first();
            } else {
                $order = Order::where('order_number', $ord_number)->first();
            }
            if ($order == null) {
                $request->session()->flash('error', 'Order with order number ' . $ord_number . ' is missing');
            } else if ($order->status != "READY_TO_PACK" || $order->status != "AWAITING_FOR_SHIPMENT" || $order->status != "SHIPPED") {
                
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                
                if ($request->input('restricted') != null && $request->input('restricted') == "true") {
                    $hitung = $hitung + 1;

                    $order = OrderCopy::where('order_number', $ord_number)->first();

                    if($order != null){
                        DB::table('order_detail_copy')
                            ->where('orders_id', '=', $order->id)
                            ->delete();
                        $order->delete();
                    }
                    
                } else {
                    $details = DB::table('order_detail')
                        ->join('orders', 'orders.id', '=', 'order_detail.orders_id')
                        ->where('orders.order_number', '=', $ord_number)
                        ->pluck('order_detail.id')->toArray();

                    $hitung = count($details);
                    
                    DB::table('inbound_detail_location')
                        ->whereIn('inbound_detail_location.order_detail_id',$details)
                        ->update([
                            'date_ordered'    => null,
                            'order_detail_id' => null,
                            'date_picked'     => null,
                            'date_outbounded' => null,
                        ]);

                    $order = Order::where('order_number', $ord_number)->first();

                    if($order != null){
                        DB::table('order_detail')
                            ->where('orders_id', '=', $order->id)
                            ->delete();
                        $order->delete();
                    }
                }
                
                
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                
                $request->session()->flash('success', $hitung . ' orders have been successfully deleted');
                
            } else {
                $request->session()->flash('error', 'This order has been locked as done');
            }
        }
        
        if ($request->has('ready')) {
            return redirect('/order/ready');
        } else {
            if ($request->input('restricted') != null && $request->input('restricted') == "true") {
                return redirect('/order?r=0');
            } else {
                return redirect('/order');
            }
        }
    }
    
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_type'    => 'required',
            'name'          => 'required',
            'email'         => 'required|email',
            'phone'         => 'required',
            'address'       => 'required',
            'zip_code'      => 'required',
            'products'      => 'required',
            'client_id'     => 'required',
            'order_details' => 'required',
            'order_pricing' => 'required'
        ]);
        
        if ($validator->fails()) {
            return redirect('/order/add')
                ->withErrors($validator)
                ->withInput();
        } else {
            $order  = new Order;
            $client = null;
            if (Auth::user()->roles == 'client') {
                $user             = User::find(Auth::user()->id);
                $order->client_id = $user->client_id;
                $client           = Client::find($user->client_id);
            } else {
                $client           = Client::where('name', $request->input('client_id'))->first();
                $order->client_id = $client->id;
            }
            $order->version      = 1;
            $ocount              = count(DB::select("SELECT * FROM `orders` WHERE created_at >= '" . date('Y-m-d 00:00:00') . "' AND created_at < '" . date('Y-m-d 23:59:59') . "'"));
            $order_number        = $client->acronym . date('Ymd') . str_pad($ocount + 1, 6, '0', STR_PAD_LEFT);
            $order->order_number = $order_number;
            $order->order_type   = $request->input('order_type');
            
            $order->client_pricing_order = $request->input('order_pricing');
            $order->client_pricing_qty   = $request->input('order_pricing');
            $order->code                 = 'ORD' . str_pad(mt_rand(intval(1000000000), intval(9999999999)), 10, "0", STR_PAD_LEFT);
            $order->courier              = $request->input('courier');
            $order->no_resi              = $request->input('no_resi');
            //$order->status = 'READY_FOR_OUTBOUND';
            $order->status        = 'PENDING';
            $order->shipping_cost = ($request->input('shipping_cost') != null) ? $request->input('shipping_cost') : 0;
            $order->total         = $request->input('order_pricing') + $order->shipping_cost;
            $order->notes         = $request->input('notes');
            
            if (Auth::user()->roles == 'crew' || Auth::user()->roles == 'head') {
                $order->warehouse_id = Auth::user()->warehouse_id;
            }
            
            // check customer
            // $customer = Customer::where('phone',trim($request->input('phone')))->first();
            // if($customer != null){
            //     $order->customer_id = $customer->id;
            //     $customer->address = $request->input('address');
            //     $customer->email = $request->input('email');
            //     $customer->name = $request->input('name');
            //     $customer->phone = $request->input('phone');
            //     $customer->zip_code = $request->input('zip_code');
            //     $customer->save();
            // } else {
            //     $ncustomer = new Customer;
            //     $ncustomer->version = 1;
            //     $ncustomer->address = $request->input('address');
            //     $ncustomer->email = $request->input('email');
            //     $ncustomer->name = $request->input('name');
            //     $ncustomer->phone = $request->input('phone');
            //     $ncustomer->zip_code = $request->input('zip_code');
            //     $ncustomer->save();
            
            //     $order->customer_id = $ncustomer->id;
            // }
            $ncustomer           = new Customer;
            $ncustomer->version  = 1;
            $ncustomer->address  = $request->input('address');
            $ncustomer->email    = $request->input('email');
            $ncustomer->name     = $request->input('name');
            $ncustomer->phone    = $request->input('phone');
            $ncustomer->zip_code = $request->input('zip_code');
            $ncustomer->save();
            
            $order->customer_id = $ncustomer->id;
            $order->save();

            $hist = new OrderHistory;
            $hist->order_id = $order->id;
            $hist->status = 'PENDING';
            $hist->user_id = Auth::user()->id;
            $hist->save();
            
            // Check if there's a copied order number
            while (count(DB::select("SELECT * FROM `orders` WHERE `order_number` = '" . $order_number . "'")) > 1) {
                $ocount              = count(DB::select("SELECT * FROM `orders` WHERE created_at >= '" . date('Y-m-d 00:00:00') . "' AND created_at < '".date('Y-m-d 23:59:59')."'"));
                $order_number        = $client->acronym . date('Ymd') . str_pad($ocount + 1, 6, '0', STR_PAD_LEFT);
                $order->order_number = $order_number;
                $order->save();
            }
            
            // Upload source file
            if ($request->file('source_order') != null) {
                foreach ($request->file('source_order') as $key => $file) {
                    $ext     = $file->getClientOriginalExtension();
                    $size    = $file->getSize();
                    $newName = $request->input('order_number') . "_" . date('Ymd') . "_" . rand(100000, 1001238912) . "." . $ext;
                    $file->move('images/sources', $newName);
                    
                    $file_path = public_path() . "/images/sources/";
                    
                    $s3     = AWS::createClient('s3');
                    $upload = $s3->putObject(array(
                        'Bucket'     => 'static-pakde',
                        'Key'        => $newName,
                        'SourceFile' => $file_path . $newName,
                        'ACL'        => 'public-read'
                    ));
                    
                    unlink($file_path . $newName);
                    $source               = new OrderSource;
                    $source->order_id     = $order->id;
                    $source->source_order = $newName;
                    $source->save();
                }
            }
            
            // Create order detail
            $details = json_decode($request->input('order_details'));
            $amounts = $request->input('products');
            $actuals = $request->input('actuals');
            foreach ($details as $key => $detail) {
                
                $split = explode('|', $detail);
                
                for ($x = 0; $x < $amounts[$key]; $x++) {
                    
                    $order_detail                    = new OrderDetail;
                    $order_detail->version           = 0;
                    $order_detail->orders_id         = $order->id;
                    $order_detail->inbound_detail_id = $split[1];
                    $order_detail->save();
                    
                    if ($actuals[$key] - $x > 0) {
                        // Update product location status
                        $llocation = DB::table('inbound_detail_location')
                            ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                            ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                            ->join('rack', 'rack.id', '=', 'shelf.rack_id')
                            ->join('warehouse', 'warehouse.id', '=', 'rack.warehouse_id')
                            ->whereNotNull('inbound_detail_location.shelf_id')
                            ->whereNull('inbound_detail_location.date_picked')
                            ->whereNull('inbound_detail_location.date_ordered')
                            ->where('inbound_detail.product_id', '=', $split[0])
                            ->where('inbound_detail.product_type_size_id', '=', $split[4])
                            ->where('warehouse.id', '=', $split[3])
                            ->select('inbound_detail_location.id')
                            ->first();
                        
                        // DB::table('inbound_detail_location')
                        //     ->where('id','=',$llocation->id)
                        //     ->update([
                        //         'date_ordered' => date('Y-m-d H:i:s'),
                        //         'order_detail_id' => $order_detail->id
                        //     ]);
                        
                        $order_detail_2                             = OrderDetail::find($order_detail->id);
                        $order_detail_2->inbound_detail_location_id = $llocation->id;
                        $order_detail_2->warehouse_id               = $split[3];
                        $order_detail_2->save();
                    }
                    
                }
                
                // Update quantity
                $inbound_count              = InboundLocation::where('inbound_detail_id', $split[1])
                    ->whereNull('order_detail_id')
                    ->count();
                $inbound_detail             = Inbound_detail::find($split[1]);
                $inbound_detail->actual_qty = $inbound_count;
                $inbound_detail->save();
            }
            $request->session()->flash('success', 'New order hasAlter table order_batch modify template_url varchar(255) NULL successfully added.');
        }
        
        return redirect('/order/add');
    }

    public function bulkUpdateAwb(Request $request, Jne $jne)
    {

        if ($request->hasFile('bulk-update-awb')) {
            // Reading DataAlter table order_batch modify template_url varchar(255) NULL
            $path = $request->file('bulk-update-awb')->getRealPath();
            $data = Excel::load($path)->get();

            if ($data->count()) {
                $counter = 0;
                $errorList = [];
                $orderList = [];
                $awbList = [];
                $client_id = 0;

                // Loop data from excel
                foreach ($data as $key => $value) {
                    $row = ($key+2).' of '.count($data).' rows';

                    $error = [];
                    $error['Row'] = $key + 2;
                    $error['Problem'] = '';
                    $error['Value'] = '';
                    $orderNumber = trim($value->order_number);
                    $awbNumber = trim($value->airwaybill_number);
                    $courier = trim($value->courier);

                    if (empty($orderNumber)) {
                        $error['Problem'] .= "Order Number is empty\n";
                        $error['Value'] .= "\n";
                    }
                    else {
                        $query = DB::connection('read_replica')
                            ->table('orders')
                            ->where('order_number', $orderNumber);

                        if (Auth::user()->roles == 'client') {
                            $query->where('client_id', '=', Auth::user()->client_id);
                        }

                        $order = $query->first();

                        if (empty($order)) {
                            $error['Problem'] .= "Order with order number ". $orderNumber ." is not exist.\n";
                            $error['Value'] .=  $orderNumber . "\n";
                        } else {
                            $value->order_id = $order->id;
                            if ($client_id == 0 ) 
                            {
                                $client_id = $order->client_id;
                            }
                            else if ($client_id != $order->client_id) {
                                $error['Problem'] .= "Can not bulk update airwaybill from multiple client at once. Please input orders only with same client ID\n";
                                $error['Value'] .=  $orderNumber . "\n";
                            }
                        }
                    }

                    if (empty($awbNumber)) {
                        $error['Problem'] .= "Airwaybill order is empty\n";
                        $error['Value'] .= "\n";
                    }

                    if (empty($courier)) {
                        $error['Problem'] .= "Courier is empty\n";
                        $error['Value'] .= "\n";
                    }

                    if (in_array($awbNumber, $awbList) || DB::table('orders')
                        ->where(DB::raw('TRIM(no_resi)'), '=', $awbNumber)
                        ->where('order_number', '!=', $orderNumber)
                        ->first() != null) {
                        $error['Problem'] .= "Airwaybill is duplicate\n";
                        $error['Value'] .= $awbNumber . "\n";
                    } else {
                        array_push($awbList, $awbNumber);
                    }

                    if (strpos($awbNumber, '<') !== false || strpos($awbNumber, '>') !== false) {
                        $error['Problem'] .= "Airwaybill is contain character \"<\" or \">\"!\n";
                        $error['Value'] .= $awbNumber . "\n";
                    }

                    // Save error list
                    if (!empty($error['Problem']))
                        $errorList[] = $error;

                    // If have error, skip from here to bellow
                    if (!empty($errorList))
                        continue;

                    array_push($orderList, $value);
                }

                // Check if have any error
                if (!empty($errorList))
                    return Excel::create('bulk-update-awb-problems-' . date('Ymd-His'), function ($excel) use ($errorList) {
                        $excel->sheet('Order Details', function ($sheet) use ($errorList) {
                            $sheet->fromArray($errorList);
                            
                            // Set header background color
                            $sheet->row(1, function($row) {
                                $row->setBackground('#191970');
                                $row->setFontColor('#ffffff');
                                $row->setFontWeight('bold');
                            });
                        });
                    })->download();

                // Begin transaction to make sure
                try {
                    $i = 1;
                    $bulkUpdateRequestBody = [];
                    foreach ($orderList as $key => $value) {
                        $orderNumber = trim($value->order_number);
                        $awbNumber = trim($value->airwaybill_number);
                        $courier = trim($value->courier);

                        $updatedRow = DB::table('orders')
                            ->where('orders.order_number', '=', $orderNumber)
                            ->update([
                                'courier'   => $courier,
                                'no_resi'   => $awbNumber
                            ]);

                        $singleOrder = new \stdClass;
                        $singleOrder->order_id = $value->order_id;
                        $singleOrder->courier_name = (string)$value->courier;
                        $singleOrder->airwaybill_num = (string)$value->airwaybill_number;
                        array_push($bulkUpdateRequestBody, $singleOrder);
                    }

                    //Send to Jubelio system
                    $partner_name = 'jubelio';
                    $salesOrder = new SalesOrder();
                    $resp = $salesOrder->patchBulkUpdateAirwaybilltoPartner($partner_name, $client_id, $bulkUpdateRequestBody);

                    if ($resp->status() != 200)
                    {
                        //If there is error eventhough saving to DB was success, operator need to re-submit the form
                        $request->session()->flash('error', "Failed to update to external partner system(". $partner_name ."). Please check your data or contact administrator.");
                    }

                } catch(Exception $e) {
                    Session::flash('error', $e->getMessage());
                    return redirect('/order');
                }

            } else {
                Session::flash('error', 'Please put at least one new order on excel.');
            }
            
        } else {
            Session::flash('error', 'Please upload the formatted bulk file first.');
        }

        return redirect('/order');
    }
    
    public function bulkUpload(Request $request, Jne $jne)
    {
        if ($request->hasFile('bulk-order')) {
            // Prepare support variable
            Session::flash('order_progress', 'Preparing...');
            Session::save();
            $path = $request->file('bulk-order')->getRealPath();
            $isCopy = !$request->has('restrict') || $request->input('restrict') == false;
            $tableSource = $isCopy ? 'orders_copy' : 'orders';
            $tableSourceDetail = $isCopy ? 'order_detail_copy' : 'order_detail';
            $isAutoPrint = $request->has('autoprint') && $request->input('autoprint') == true;
            $clientHttp = app(HttpClient::class);

            $labelParams = [
                "data" => [
                    "label" => []
                ]
            ];

            // Ready Data
            Session::flash('order_progress', 'Reading Data');
            Session::save();
            $data = Excel::load($path)->get();

            if ($data->count()) {
                $orderTypes = Order::orderTypeReversed();
                $counter = 0;
                $orderIDList = [];
                $orderList = [];
                $orderDetailList = [];
                $customerList = [];
                $prevIndex = null;
                $client = null;
                $details = [];
                $errorList = [];

                // Loop data from excel
                foreach ($data as $key => $value) {
                    $tempClient = null;
                    $row = ($key+2).' of '.count($data).' rows';

                    $error = [];
                    $error['Row'] = $key + 2;
                    $error['Problem'] = '';
                    $error['Value'] = '';

                    $product = null;
                    $productSize = null;
                    $inbound = null;
                    $inboundDetail = null;

                    Session::flash('order_progress', 'Beginning processing - '.$row);
                    Session::save();
                    if (Auth::user()->roles == 'client') 
                        $tempClient = DB::connection('read_replica')
                            ->table('client')
                            ->find(Auth::user()->client_id);
                    else
                        $tempClient = DB::connection('read_replica')
                            ->table('client')
                            ->where('name', $value->client)
                            ->first();

                    // Check order type
                    if (!array_key_exists($value->order_type, $orderTypes)) {
                        $error['Problem'] .= "Order Type should one of " . implode(", ", Order::orderType()) . "\n";
                        $error['Value'] .= $value->size . "\n";
                    }

                    // Check client
                    Session::flash('order_progress', 'Checking client - '.$row);
                    Session::save();
                    if ($tempClient == null) {
                        $error['Problem'] .= "Client not exists\n";
                        $error['Value'] .= $value->client . "\n";
                    } else {
                        if ($client == null) {
                            $client = $tempClient;
                        } else if($tempClient != $client) {
                            $error['Problem'] .= "Multiple client detected\n";
                            $error['Value'] .= $client->name . " and " . $value->client . "\n";
                        }

                        // Check product
                        Session::flash('order_progress', 'Checking product - '.$row);
                        Session::save();
                        $product = DB::connection('read_replica')
                            ->table('product')
                            ->where('name', $value->product)
                            ->where('client_id', $client->id)
                            ->first();

                        if ($product == null) {
                            $error['Problem'] .= "Product not exists\n";
                            $error['Value'] .= $value->product . "\n";
                        } else {
                            // Check product type size
                            Session::flash('order_progress', 'Checking product size - '.$row);
                            Session::save();
                            $productSize = DB::connection('read_replica')
                                ->table('product_type_size')
                                ->where('name', $value->size)
                                ->where('product_type_id', $product->product_type_id)
                                ->first();

                            if ($productSize == null) {
                                $error['Problem'] .= "Product size not exists\n";
                                $error['Value'] .= $value->size . "\n";
                            } else {
                                // Check inbound detail
                                Session::flash('order_progress', 'Checking inbound variant - '.$row);
                                Session::save();
                                $inboundDetail = DB::connection('read_replica')
                                    ->table('inbound_detail')
                                    ->where('product_id', $product->id)
                                    ->where('product_type_size_id', $productSize->id)
                                    ->first();

                                if ($inboundDetail == null) {
                                    $error['Problem'] .= "Inbound variant not exists\n";
                                    $error['Value'] .= $value->product . ' ' . $value->size . "\n";
                                }
                            }
                            // Check inbound
                            Session::flash('order_progress', 'Checking inbound - '.$row);
                            Session::save();
                            $inbound = DB::connection('read_replica')
                                ->table('inbound')
                                ->where('product_id', $product->id)
                                ->first();

                            if ($inbound == null) {
                                $error['Problem'] .= "Inbound not exists\n";
                                $error['Value'] .= $value->product . "\n";
                            }
                        }
                    }

                    // Check for mandatory field not to be empty
                    if (empty($value->name)) {
                        $error['Problem'] .= "Customer name is empty\n";
                        $error['Value'] .= "\n";
                    }
                    if (empty($value->phone)) {
                        $error['Problem'] .= "Customer phone is empty\n";
                        $error['Value'] .= "\n";
                    }
                    if (empty($value->address)) {
                        $error['Problem'] .= "Customer address is empty\n";
                        $error['Value'] .= "\n";
                    }

                    if (empty($value->quantity)) {
                        $error['Problem'] .= "Quantity is empty\n";
                        $error['Value'] .= "\n";
                    }

                    // Check receipt number
                    Session::flash('order_progress', 'Checking receipt number - '.$row);
                    Session::save();
                    if (strlen($value->receipt_number) > 18) {
                       $error['Problem'] .= "Receipt number is more than 18 \n";
                       $error['Value'] .= $value->receipt_number . "\n";
                    }

                    // Save error list
                    if (!empty($error['Problem']))
                        $errorList[] = $error;

                    // If have error, skip from here to bellow
                    if (!empty($errorList))
                        continue;

                    // Unique index order
                    $indexWithoutCounter = $client->name . '_' . $value->name . '_'
                        . $value->email . '_' . $value->address . '_' . $value->courier . '_'
                        . $value->receipt_number;
                    $counter = $prevIndex != $indexWithoutCounter ? $counter+1 : $counter;
                    $index = $indexWithoutCounter . '_' . $counter;

                    // Add new customer and order when index different
                    if (!array_key_exists($index, $orderList)) {
                        Session::flash('order_progress', 'Saving customer - '.$row);
                        Session::save();
                        $customerList[$index] = [
                            'address' => $value->address,
                            'email' => $value->email,
                            'name' => $value->name,
                            'phone' => $value->phone,
                            'zip_code' => $value->postcode,
                            'version' => 1,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];

                        $orderList[$index] = [
                            'client_id' => $client->id,
                            'client_pricing_order' => 0,
                            'client_pricing_qty' => 0,
                            'code' => 'ORD' . str_pad(rand(intval(pow(10, 6 - 1)), intval(pow(10, 6) - 1)), 6, "0", STR_PAD_LEFT),
                            'courier' => $value->courier,
                            'customer_id' => null,
                            'no_resi' => trim($value->receipt_number),
                            'notes' => $value->notes,
                            'order_number' => null,
                            'order_type' => $orderTypes[$value->order_type],
                            'shipping_cost' => intval($value->shipping_cost),
                            'total' => intval($value->shipping_cost),
                            'status' => 'PENDING',
                            'due_date' => Carbon::now()->addHours(48),
                            'version' => 1,
                            'warehouse_id' => User::where('client_id', $client->id)->first()->warehouse_id,
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];
                    }

                    // Insert order detail
                    Session::flash('order_progress', 'Saving order based on quantity - '.$row);
                    Session::save();
                    for ($i = 0; $i < $value->quantity; $i++) {
                        $temp = [];
                        $temp['orders_id'] = $index;
                        $temp['version'] = 0;
                        $temp['inbound_detail_id'] = $inboundDetail->id;

                        $details[] = $temp;
                    }

                    $prevIndex = $indexWithoutCounter;
                }

                Session::flash('order_progress', 'Validating error...');
                Session::save();
                // Check if have any error
                if (!empty($errorList))
                    return Excel::create('bulk-upload-order-problems-' . date('Ymd-His'), function ($excel) use ($errorList) {
                        $excel->sheet('Order Details', function ($sheet) use ($errorList) {
                            $sheet->fromArray($errorList);
                            
                            // Set header background color
                            $sheet->row(1, function($row) {
                                $row->setBackground('#191970');
                                $row->setFontColor('#ffffff');
                                $row->setFontWeight('bold');
                            });
                        });
                    })->download();

                    // Begin transaction to make sure
                try {
                    $i = 1;
                    $orderBatchID = DB::table('order_batch')->insertGetId([
                        'template_url' => null
                    ]);
                    foreach ($orderList as $key => $value) {

                        DB::beginTransaction();

                        $row = $i.' of '.count($orderList).' orders';

                        $customerId = DB::table('customer')
                            ->insertGetId($customerList[$key]);

                        // $client = DB::connection('read_replica')->table('client')->find($orderList[$key]['client_id']);

                        // Trying order number
                        $count = 10;
                        while(true) {
                            try {
                                // Generate order number
                                Session::flash('order_progress', 'Generating order number - '.$row);
                                Session::save();
                                $orderNumber = DB::connection('read_replica')
                                    ->table($tableSource)
                                    ->select(DB::raw('RIGHT(`order_number`, 14)+1 as order_number'))
                                    ->where('created_at', '>=', Carbon::now()->format('Y-m-d 00:00:00'))
                                    ->where('created_at', '<', Carbon::now()->addDays(1)->format('Y-m-d 00:00:00'))
                                    ->orderBy('order_number', 'DESC')
                                    ->first();

                                if ($orderNumber == null)
                                    $orderNumber = $client->acronym . date('Ymd') . str_pad(1, 6, '0', STR_PAD_LEFT);
                                else
                                    $orderNumber = $client->acronym . $orderNumber->order_number;

                                $orderList[$key]['customer_id'] = $customerId;
                                $orderList[$key]['order_number'] = $orderNumber;
                                $orderList[$key]['batch_id'] = $orderBatchID;

                                // Insert order into database
                                Session::flash('order_progress', 'Saving order - '.$row);
                                Session::save();
                                $orderIDList[$key] = DB::table($tableSource)->insertGetId($orderList[$key]);
                            } catch (Exception $e) {
                                Log::error($e->getMessage());
                                if ($count == 0) {
                                    throw new Exception("Maximum attempt to generate order number exceeded.");
                                } else {
                                    $count--;
                                    // try again
                                    continue;
                                }
                            }
                            break;
                        }

                        $hist = new OrderHistory;
                        $hist->order_id = $orderIDList[$key];
                        $hist->status = 'PENDING';
                        $hist->user_id = Auth::user()->id;
                        $hist->save();

                        Session::flash('order_progress', 'Inserting orders product - '.$row);
                        Session::save();

                        foreach ($details as $detail) {
                            if ($detail['orders_id'] == $key) {
                                $detail['orders_id'] = $orderIDList[$key];
        
                                DB::table($tableSourceDetail)
                                    ->insert($detail);
        
                                $inboundLocationCount = DB::connection('read_replica')
                                    ->table('inbound_detail_location')
                                    ->where('inbound_detail_id', $detail['inbound_detail_id'])
                                    ->whereNull('order_detail_id')
                                    ->count();
                                
                                DB::table('inbound_detail')
                                    ->where('id', '=', $detail['inbound_detail_id'])
                                    ->update([
                                        'actual_qty' => $inboundLocationCount,
                                        'updated_at' => Carbon::now(),
                                    ]);
                            }
                        }

                        // Print pdf shipping label
                        if ($isAutoPrint) {
                            $label = json_decode(json_encode(DB::table('label')
                                ->where('name', '=', Config::get('constants.label.ORDER'))
                                ->first()), true);
                            
                            if ($label == null) {
                                try {
                                    $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_LABEL_TYPE');
                                    $body = [];
                                    $body['data'] = [
                                        'labeltype' => [
                                            'name' => 'WMS Order Label',
                                            'type' => 'LBL'
                                        ]
                                    ];

                                    $response = $clientHttp->request("POST", $url, ['body' => json_encode($body)]);
                                    $response = json_decode($response->getBody()->getContents());
                                    $label = [];
                                    $label['label_id'] = $response->data->labeltype->id;
                                    DB::table('label')
                                    ->insert([
                                        'name' => Config::get('constants.label.ORDER'),
                                        'label_id' => $label['label_id']
                                    ]);
                                } catch (\Exception $exception) {
                                    Log::error("Failed to call API to create label type: ". $exception);
                                    throw $exception;
                                }

                                try {
                                    $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_TEMPLATE');
                                    $html = view('dashboard.pdf.order')->render();
                                    $body = [];
                                    $body['data'] = [
                                        'template' => [
                                            "description"=> "Label for wms orders",
                                            "title"=> "WMS Order Template",
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


                            $pdf = [];
                            $pdf['page_size'] = Config::get('constants.label.PAGE_SIZE_ORDER');
                            $pdf['page_margin'] = [
                                'top' => 4,
                                'right' => 4,
                                'bottom' => 4,
                                'left' => 4
                            ];
                            $pdf['logo'] = Config::get('constants.label.LOGO');
                            $pdf['type'] = $label['label_id'];
                            $pdf['params'] = [];

                            // Write footer pdf
                            Session::flash('order_progress', 'Preparing print label - '.$row);
                            Session::save();
                            $pdf['params']['consignee'] = [];
                            $pdf['params']['consignee']['address'] = $customerList[$key]['address'] . '';
                            $pdf['params']['consignee']['name'] = $customerList[$key]['name'] . '';
                            $pdf['params']['consignee']['phone'] = $customerList[$key]['phone'] . '';
                            $pdf['params']['consignee']['postcode'] = $customerList[$key]['zip_code'] . '';

                            $pdf['params']['consigner']['name'] = $client->name . '';

                            $pdf['params']['courier']['name'] = $orderList[$key]['courier'];

                            $client->logo_url != null ? $pdf['params']['merchant']['image_path'] = "https://s3-ap-southeast-1.amazonaws.com/static-pakde/$client->logo_url" : null;

                            $pdf['params']['order']['awb_number'] = [];
                            $pdf['params']['order']['awb_number']['value'] = $orderList[$key]['no_resi'] . '';

                            $pdf['params']['order']['creation_date'] = date('d/m/Y');

                            $pdf['params']['order']['order_id'] = [];
                            $pdf['params']['order']['order_id']['value'] = $orderNumber . '';

                            $pdf['params']['order']['notes'] = $orderList[$key]['notes'] . '';

                            $pdf['params']['show_barcode']['barcode_1']['title'] = 'Order ID';
                            $pdf['params']['show_barcode']['barcode_1']['type'] = 'orderID';

                            $pdf['params']['show_barcode']['barcode_2']['title'] = 'AWB Number';
                            $pdf['params']['show_barcode']['barcode_2']['type'] = 'awb';

                            $pdf['params']['show_barcode']['barcode_type'] = 'code128';

                            array_push($labelParams['data']['label'], $pdf);
                        }

                        $i++;
                        DB::commit();
                    }

                    if ($isAutoPrint) {
                        try {
                            $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_LABEL');

                            Log::info(json_encode($labelParams));

                            $response = $clientHttp->request("POST", $url, ['body' => json_encode($labelParams)]);
                            $response = json_decode($response->getBody()->getContents());
                            DB::table('order_batch')
                            ->where('id', '=', $orderBatchID)
                            ->update([
                                'template_url' => $response->data->url,
                                'user_id' => Auth::user()->id,
                                'client_id' => $client->id
                            ]);

                            Session::forget('order_progress');
                            Session::flash('success', 'Download label from this url: ' . $response->data->url);
                            return redirect('/order');
                        } catch (\Exception $exception) {
                            Log::error("Failed to call API to create label: ". $exception);
                            throw $exception;
                        }
                    }

                } catch(Exception $e) {
                    DB::table('order_batch')->where('id', '=', $orderBatchID)->delete();
                    DB::rollback();
                    Session::forget('order_progress');
                    Session::flash('error', $e->getMessage());
                    return redirect('/order');
                }

                Session::flash('order_progress', 'Complete '.count($orderIDList).' orders');
                Session::save();
            } else {
                Session::flash('error', 'Please put at least one new order on excel.');
                Session::forget('order_progress');
            }
            
        } else {
            Session::flash('error', 'Please upload the formatted bulk file first.');
            Session::forget('order_progress');
        }

        return redirect('/order');
    }

    public function regenerateShippinglabel(Request $request, $client_id, $orderBatchID)
    {      
        $labelParams = [
            "data" => [
                "label" => []
            ]
        ];
        //init label
        $label = $this->createNewLabel();

        $responseData = DB::connection('read_replica')
            ->table('orders')
            ->join('customer', 'customer.id', '=', 'orders.customer_id')
            ->join('client', 'client.id', '=', 'orders.client_id')
            ->select('orders.id as order_id', 'orders.batch_id as batch_id', 'orders.courier', 'orders.order_number', 
            'customer.address as address', 'customer.name as customer_name', 'customer.phone as customer_phone', 'customer.zip_code',
            'client.name as client_name', 'client.id as client_id', 'client.logo_url', 'orders.no_resi')
            ->where('orders.batch_id', '=', $orderBatchID)
            ->get();
        
        foreach($responseData as $item) {
            //get label param
            $labelParam = [];
            $pdf = $this->makeLabelParam($label, $item->logo_url, $item->courier, $item->address, $item->customer_name, $item->customer_phone, $item->zip_code, $item->client_name, $item->order_number, $item->no_resi);
            $labelParam['type'] = $label['label_id'];
            array_push($labelParams['data']['label'], $pdf);           
        }
        //generate label
        $response = $this->generateLabel($labelParams, $client_id, $orderBatchID);
        return Redirect::away($response->data->url);
    }

    public function makeLabelParam($label, $logo_url, $courier, $address, $customer_name, $customer_phone, $zip_code, $client_name, $orderNumber, $awbNumber) 
    {
        $pdf = [];
        $pdf['page_size'] = Config::get('constants.label.PAGE_SIZE_ORDER');
        $pdf['page_margin'] = [
            'top' => 4,
            'right' => 4,
            'bottom' => 4,
            'left' => 4
        ];
        $pdf['logo'] = Config::get('constants.label.LOGO');
        $pdf['type'] = $label['label_id'];
        $pdf['params'] = [];
        $pdf['params']['consignee'] = [];
        $pdf['params']['consignee']['address'] = $address . '';
        $pdf['params']['consignee']['name'] = $customer_name . '';
        $pdf['params']['consignee']['phone'] = $customer_phone . '';
        $pdf['params']['consignee']['postcode'] = $zip_code . '';

        $pdf['params']['consigner']['name'] = $client_name . '';

        $pdf['params']['courier']['name'] = $courier;

        $logo_url != null ? $pdf['params']['merchant']['image_path'] = "https://s3-ap-southeast-1.amazonaws.com/static-pakde/$logo_url" : null;

        $pdf['params']['order']['awb_number'] = [];
        $pdf['params']['order']['awb_number']['value'] = $awbNumber . '';

        $pdf['params']['order']['creation_date'] = date('d/m/Y');

        $pdf['params']['order']['order_id'] = [];
        $pdf['params']['order']['order_id']['value'] = $orderNumber . '';

        $pdf['params']['show_barcode']['barcode_1']['title'] = 'Order ID';
        $pdf['params']['show_barcode']['barcode_1']['type'] = 'orderID';

        $pdf['params']['show_barcode']['barcode_2']['title'] = 'AWB Number';
        $pdf['params']['show_barcode']['barcode_2']['type'] = 'awb';

        $pdf['params']['show_barcode']['barcode_type'] = 'code128';

        return $pdf;
    }

    public function createNewLabel()
    {
        $clientHttp = app(HttpClient::class);
        $label = json_decode(json_encode(DB::table('label')
                                ->where('name', '=', Config::get('constants.label.ORDER'))
                                ->first()), true);
        //create label type
        try {
            $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_LABEL_TYPE');
            $body = [];
            $body['data'] = [
                'labeltype' => [
                    'name' => 'WMS Order Label',
                    'type' => 'LBL'
                ]
            ];

            $response = $clientHttp->request("POST", $url, ['body' => json_encode($body)]);
            $response = json_decode($response->getBody()->getContents());
            $label = [];
            $label['label_id'] = $response->data->labeltype->id;
            DB::table('label')
            ->insert([
                'name' => Config::get('constants.label.ORDER'),
                'label_id' => $label['label_id']
            ]);
        } catch (\Exception $exception) {
            Log::error("Failed to call API to create label type: ". $exception);
            throw $exception;
        }

        try {
            $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_TEMPLATE');
            $html = view('dashboard.pdf.order')->render();
            $body = [];
            $body['data'] = [
                'template' => [
                    "description"=> "Label for wms orders",
                    "title"=> "WMS Order Template",
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
        return $label;
    }

    public function generateLabel($labelParams, $client_id, $orderBatchID)
    {
        $clientHttp = app(HttpClient::class);
        try {
            $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_LABEL');
            Log::info(json_encode($labelParams));

            $response = $clientHttp->request("POST", $url, ['body' => json_encode($labelParams)]);
            $response = json_decode($response->getBody()->getContents());
            DB::table('order_batch')
                ->where('id', '=', $orderBatchID)
                ->update([
                    'template_url' => $response->data->url,
                    'user_id' => Auth::user()->id,
                    'client_id' => $client_id,
                    'updated_at' => Carbon::now()
                ]);
        } catch (\Exception $exception) {
            Log::error("Failed to call API to create label: ". $exception);
            throw $exception;
        }

        return $response;
    }
    
    public function ajaxProduct(Request $request)
    {
        $responseCode    = '01';
        $responseMessage = 'No Product Listed';
        $responseData    = array("client" => null, "product" => null);
        
        $input = $request->all();
        if ($request->ajax()) {
            $client                  = $input['client'];
            $responseData["client"]  = Client::where('name', $client)->first();
            $responseData["product"] = DB::table('product')
                ->select('product.id as product_id', 'inbound_detail.id as inbound_detail_id', 'inbound_detail_location.id as inbound_location_id', 'product.name', 'inbound_detail.color', 'product_type_size.name as size_name', 'inbound_detail.actual_qty as qty', 'product_type_size.id as product_type_size_id')
                ->join('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                ->join('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('client', 'client.id', '=', 'product.client_id')
                ->where('client.name', '=', $client)
                ->groupBy('product.id', 'product.name', 'inbound_detail.color', 'product_type_size.name')
                ->get();
            
            if ($responseData["product"] != null) {
                foreach ($responseData["product"] as $key => $product) {
                    $product_count                      = DB::table('inbound_detail_location')
                        ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                        ->where('inbound_detail.product_id', '=', $product->product_id)
                        ->where('inbound_detail.product_type_size_id', $product->product_type_size_id)
                        ->whereNotNull('inbound_detail_location.shelf_id')
                        ->whereNull('inbound_detail_location.order_detail_id')
                        ->whereNull('inbound_detail_location.date_picked')
                        ->whereNull('inbound_detail_location.date_outbounded')
                        ->count();
                    $responseData["product"][$key]->qty = $product_count;
                }
                
                $responseCode    = '00';
                $responseMessage = 'Product Successfully Listed';
            } else {
                $responseCode    = '01';
                $responseMessage = 'Selected product is out of stock.';
            }
        }
        
        return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function ajaxCheckWarehouse(Request $request)
    {
        $responseCode    = '01';
        $responseMessage = 'No Warehouse Detected';
        $responseData    = null;
        
        $input = $request->all();
        if ($request->ajax()) {
            $productDetail = explode('|', $input['inbound_detail']);
            $responseData  = DB::table('inbound_detail_location')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                ->join('rack', 'rack.id', '=', 'shelf.rack_id')
                ->join('warehouse', 'warehouse.id', '=', 'rack.warehouse_id')
                ->where('inbound_detail.product_id', '=', $productDetail[0])
                ->where('inbound_detail.product_type_size_id', '=', $productDetail[7])
                ->whereNull('inbound_detail_location.order_detail_id')
                ->whereNotNull('inbound_detail_location.shelf_id')
                ->select('warehouse.id', 'warehouse.name', 'inbound_detail.actual_qty as qty')
                ->distinct('warehouse.id', 'inbound_detail_location.inbound_detail_id')
                ->groupBy('warehouse.id')
                ->get();
            
            foreach ($responseData as $key => $warehouse) {
                $product_count           = DB::table('inbound_detail_location')
                    ->join('inbound_detail', 'inbound_detail.id', '=', 'inbound_detail_location.inbound_detail_id')
                    ->join('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                    ->join('rack', 'rack.id', '=', 'shelf.rack_id')
                    ->join('warehouse', 'warehouse.id', '=', 'rack.warehouse_id')
                    ->where('inbound_detail.product_id', '=', $productDetail[0])
                    ->where('inbound_detail.product_type_size_id', '=', $productDetail[7])
                    ->whereNull('inbound_detail_location.order_detail_id')
                    ->whereNotNull('inbound_detail_location.shelf_id')
                    ->where('warehouse.id', '=', $warehouse->id)
                    ->count();
                $responseData[$key]->qty = $product_count;
            }
            
            $responseCode    = '00';
            $responseMessage = 'Warehouse Successfully Listed';
        }
        
        return response()->json(['code' => $responseCode, 'message' => $responseMessage, 'data' => $responseData, 'data-request' => $request->getContent()]);
    }
    
    public function outboundReady(Request $request)
    {
        $orders = Order::where('status', 'READY_FOR_OUTBOUND')->get();
        
        return view('dashboard.order.index', ['orders' => $orders]);
    }
    
    public function location(Request $request, $id)
    {
        $inputs = $request->all();
        
        $restrict  = ($request->get('r') != null && $request->get('r') == 0);
        $order     = null;
        $locations = null;
        
        if ($restrict) {
            
            if (isset(Auth::user()->client_id) && OrderCopy::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
                $request->session()->flash('error', 'You are not allowed to access this order locations');
                
                return redirect('order?r=0');
            }
            
            $order = DB::table('orders_copy')
                ->join('client', 'client.id', '=', 'orders_copy.client_id')
                ->where('orders_copy.id', '=', $id)
                ->select('orders_copy.*', 'client.name')
                ->first();
            
            $locations = DB::table('orders_copy')
                ->select('inbound_detail_location.code', 'shelf.name as shelf_name', 'inbound_detail_location.date_stored', 'product_type_size.name as size_name', 'product.color', 'product.name as product_name', 'warehouse.name as warehouse_name', 'inbound_detail_location.date_outbounded', 'inbound_detail_location.date_picked', 'orders_copy.picked_status')
                ->join('order_detail_copy', 'order_detail_copy.orders_id', '=', 'orders_copy.id')
                ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail_copy.inbound_detail_location_id')
                ->join('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('warehouse', 'warehouse.id', '=', 'order_detail_copy.warehouse_id')
                ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                ->where('order_detail_copy.orders_id', '=', $id)
                ->get();
        } else {
            
            if (isset(Auth::user()->client_id) && Order::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
                $request->session()->flash('error', 'You are not allowed to access this order locations');
                
                return redirect('order');
            }
            
            $order = DB::table('orders')
                ->join('client', 'client.id', '=', 'orders.client_id')
                ->where('orders.id', '=', $id)
                ->select('orders.*', 'client.name')
                ->first();
            
            $locations = DB::table('orders')
                ->select('inbound_detail_location.code', 'shelf.name as shelf_name', 'inbound_detail_location.date_stored', 'product_type_size.name as size_name', 'product.color', 'product.name as product_name', 'warehouse.name as warehouse_name', 'inbound_detail_location.date_outbounded', 'inbound_detail_location.date_picked', 'orders.picked_status')
                ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                ->join('inbound_detail_location', 'inbound_detail_location.id', '=', 'order_detail.inbound_detail_location_id')
                ->join('inbound_detail', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('product_type', 'product_type.id', '=', 'product.product_type_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->join('warehouse', 'warehouse.id', '=', 'order_detail.warehouse_id')
                ->leftJoin('shelf', 'shelf.id', '=', 'inbound_detail_location.shelf_id')
                ->where('order_detail.orders_id', '=', $id)
                ->get();
        }
        
        return view('dashboard.order.location', ['order' => $order, 'locations' => $locations, 'ref' => (isset($inputs['ref'])) ? $inputs['ref'] : null, 'restrict' => $restrict]);
    }
    
    public function tplHistory(Request $request, $id)
    {
        $inputs = $request->all();
        
        $restrict  = ($request->get('r') != null && $request->get('r') == 0);
        $order     = null;
        $locations = null;
        if ($restrict) {
            
            if (isset(Auth::user()->client_id) && OrderCopy::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
                $request->session()->flash('error', 'You are not allowed to access this order locations');
                
                return redirect('order?r=0');
            }
            
            $order = DB::table('orders_copy')
                ->join('client', 'client.id', '=', 'orders_copy.client_id')
                ->where('orders_copy.id', '=', $id)
                ->select('orders_copy.*', 'client.name')
                ->first();
            
            $histories = OrderTrackingHistory::select('order_tracking_history.courier', 'order_tracking_history.notes', 'order_tracking_history.tracking_at', 'order_tracking_history.created_at')
                ->join('orders_copy', 'orders_copy.id', '=', 'order_tracking_history.order_id')
                ->where('orders_copy.id', '=', $id)
                ->get();
        } else {
            if (isset(Auth::user()->client_id) && Order::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
                $request->session()->flash('error', 'You are not allowed to access this order locations');
                
                return redirect('order');
            }
            
            $order = DB::table('orders')
                ->join('client', 'client.id', '=', 'orders.client_id')
                ->where('orders.id', '=', $id)
                ->select('orders.*', 'client.name')
                ->first();
            
            Tpl::synchronize($order->id);

            $histories = DB::table('orders_history')
                ->select('order_tracking_history.courier', 'order_tracking_history.notes', 'order_tracking_history.tracking_at', 'order_tracking_history.created_at','orders_history.status','users.name','orders_history.updated_at')
                ->join('users','users.id','=','orders_history.user_id')
                ->join('orders', 'orders.id', '=', 'orders_history.order_id')
                ->leftjoin('order_tracking_history','order_tracking_history.order_id','=','orders.id')
                ->where('orders_history.order_id','=',$id)
                ->groupBy('orders_history.status')
                ->get();
        }
        
        return view('dashboard.order.tpl.history', ['order' => $order, 'histories' => $histories, 'ref' => (isset($inputs['ref'])) ? $inputs['ref'] : null, 'restrict' => $restrict]);
    }
    
    public function tplSynchronize(Request $request, tpl $tpl)
    {
        $tpl->synchronizeAll();
    
        $request->session()->flash('success', 'Searching and synchronizing all 3PL status on backend.');
        
        return "Oke";
    }
    
    public function printLabel(Request $request, Jne $jne, $id)
    {
        $restrict     = ($request->get('r') != null && $request->get('r') == 0);
        $responseData = null;
        
        if ($restrict) {
            
            if (isset(Auth::user()->client_id) && OrderCopy::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
                $request->session()->flash('error', 'You are not allowed to access this order print label');
                
                return redirect('order?r=0');
            }
            
            $responseData = DB::table('orders_copy')
                ->join('client', 'client.id', '=', 'orders_copy.client_id')
                ->join('customer', 'customer.id', '=', 'orders_copy.customer_id')
                ->select('orders_copy.*', 'client.name as client_name', 'client.logo_url', 'customer.address', 'customer.email', 'customer.name as customer_name', 'customer.phone', 'customer.zip_code')
                ->where('orders_copy.id', '=', $id)
                ->first();
        } else {
            
            if (isset(Auth::user()->client_id) && Order::where('client_id', Auth::user()->client_id)->where('id', $id)->count() == 0) {
                $request->session()->flash('error', 'You are not allowed to access this order print label');
                
                return redirect('order');
            }
    
            $responseData = DB::table('orders')
                ->join('client', 'client.id', '=', 'orders.client_id')
                ->join('customer', 'customer.id', '=', 'orders.customer_id')
                ->select('orders.*', 'client.name as client_name', 'client.logo_url', 'customer.address', 'customer.email', 'customer.name as customer_name', 'customer.phone', 'customer.zip_code')
                ->where('orders.id', '=', $id)
                ->first();
        }
        
        $job_code = $jne->setJOB($responseData->order_number);
    
        $responseData->awb_number = "";
        if($responseData->no_resi){
            $responseData->awb_number = $responseData->no_resi;
        }else{
            $responseData->awb_number = $responseData->job_jne;
        }
    
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => [100, 78],
            'orientation'   => 'L',
            'margin_left'   => 4,
            'margin_right'  => 4,
            'margin_top'    => 4,
            'margin_bottom' => 4,
            'margin_header' => 0,
            'margin_footer' => 4,
        ]);
        
        $mpdf->SetHTMLFooter(view('dashboard.pdf.footer-label', [
            'label' => $responseData
        ])->render());
        
        $mpdf->WriteHTML(view('dashboard.pdf.order-label', [
            'label' => $responseData
        ])->render());
        
        return $mpdf->Output();
    }
    
    public function printBulkLabel(Request $request, Jne $jne)
    {
        $input        = $request->all();
        $responseData = null;
        
        if ($request->input('restricted') != null && $request->input('restricted') == "true") {
            $responseData = DB::table('orders_copy')
                ->join('client', 'client.id', '=', 'orders_copy.client_id')
                ->join('customer', 'customer.id', '=', 'orders_copy.customer_id')
                ->select('orders_copy.*', 'client.name as client_name', 'client.logo_url', 'customer.address', 'customer.email', 'customer.name as customer_name', 'customer.phone', 'customer.zip_code')
                ->whereIn('orders_copy.order_number', explode(',', $input['n']))
                ->get();
        } else {
            $responseData = DB::table('orders')
                ->join('client', 'client.id', '=', 'orders.client_id')
                ->join('customer', 'customer.id', '=', 'orders.customer_id')
                ->select('orders.*', 'client.name as client_name', 'client.logo_url', 'customer.address', 'customer.email', 'customer.name as customer_name', 'customer.phone', 'customer.zip_code')
                ->whereIn('orders.order_number', explode(',', $input['n']))
                ->get();
        }
        
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => [100, 78],
            'orientation'   => 'L',
            'margin_left'   => 4,
            'margin_right'  => 4,
            'margin_top'    => 4,
            'margin_bottom' => 4,
            'margin_header' => 0,
            'margin_footer' => 4,
        ]);
        
        foreach ($responseData as $key => $label) {
            $job_code = $jne->setJOB($label->order_number) ?: $label->job_jne;
    
            $label->awb_number = "";
            if ($label->no_resi) {
                $label->awb_number = $label->no_resi;
            } else {
                $label->awb_number = $job_code;
            }
            
            $mpdf->SetHTMLFooter(view('dashboard.pdf.footer-label', [
                'label' => $label
            ])->render());
            $mpdf->WriteHTML(view('dashboard.pdf.order-label', [
                'label' => $label
            ])->render());
            if (count($responseData) != ($key + 1)) {
                $mpdf->AddPage();
            }
        }
        
        return $mpdf->Output();
    }
    
    public function printReport(Request $request, $id)
    {
        $order    = Order::find($id);
        $details  = OrderDetail::where('orders_id', $id)->get();
        $arr      = array();
        $customer = Customer::find($order->customer_id);
        $client   = Client::find($order->client_id);
        
        foreach ($details as $key => $detail) {
            if (!array_key_exists($detail->inbound_detail_id, $arr)) {
                // Get total of
                $product_count = InboundLocation::where('inbound_detail_id', $detail->inbound_detail_id)
                    ->whereNull('order_detail_id')
                    ->whereNotNull('shelf_id')
                    ->count();
                
                $order_count = OrderDetail::where('inbound_detail_id', $detail->inbound_detail_id)->where('orders_id', $id)->count();
                
                $pending_count = OrderDetail::where('inbound_detail_id', $detail->inbound_detail_id)->where('orders_id', $id)->whereNull('inbound_detail_location_id')->count();
                
                $product_detail = DB::table('product')
                    ->select('product.id as product_id', 'inbound_detail_location.id as inbound_location_id', 'product.name', 'inbound_detail.color', 'product_type_size.name as size_name')
                    ->leftJoin('inbound_detail', 'inbound_detail.product_id', '=', 'product.id')
                    ->leftJoin('inbound_detail_location', 'inbound_detail_location.inbound_detail_id', '=', 'inbound_detail.id')
                    ->leftJoin('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                    ->where('inbound_detail.id', '=', $detail->inbound_detail_id)
                    ->first();
                
                $arr[$detail->inbound_detail_id] = array('date' => $order->shipment_date, 'order_number' => $order->order_number, 'product_name' => $product_detail->name, 'customer_name' => $customer->name, 'customer_address' => $customer->address, 'customer_phone' => $customer->phone, 'color' => $product_detail->color, 'size' => $product_detail->size_name, 'count' => $order_count, 'total' => $product_count, 'pending' => $pending_count, 'status' => $order->status, 'courier' => $order->courier);
            }
        }
        
        $report_no = "PKD/" . date('Ymd') . "/ORDER/" . $client->acronym;
        
        $mpdf = new \Mpdf\Mpdf([
            'mode'              => 'utf-8',
            'format'            => [215, 280],
            'orientation'       => 'P',
            'setAutoTopMargin'  => 'stretch',
            'autoMarginPadding' => 5
        ]);
        
        $mpdf->SetHTMLHeader(view('dashboard.pdf.header')->render());
        $mpdf->SetHTMLFooter(view('dashboard.pdf.footer')->render());
        
        $mpdf->WriteHTML(view('dashboard.pdf.outbound-report', [
            'orders'    => $arr,
            'report_no' => $report_no,
            'customer'  => $customer,
            'client'    => $client
        ])->render());
        
        $res = $mpdf->Output();
        
        return $res;
    }
    
    /**
     * Order Excel
     */
    
    public function downloadExcel(Request $request)
    {
        // Array data
        $result = [];
        $i = 0;
        $offset = 5000;

        // Input variable
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $clientId = $request->input('client');
        $status = $request->input('status');

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

        while (true) {
            // Build query for result
            $query = DB::connection('read_replica')
                ->table('orders')
                ->join('order_detail', 'order_detail.orders_id', '=', 'orders.id')
                ->join('client', 'client.id', '=', 'orders.client_id')
                ->join('customer', 'customer.id', '=', 'orders.customer_id')
                ->join('inbound_detail', 'inbound_detail.id', '=', 'order_detail.inbound_detail_id')
                ->join('product', 'product.id', '=', 'inbound_detail.product_id')
                ->join('product_type_size', 'product_type_size.id', '=', 'inbound_detail.product_type_size_id')
                ->select('orders.order_number', 'inbound_detail.color', 'client.name as client_name',
                    'product.name as product_name', 'product.weight', 'product.dimension', 'product.price', 'product_type_size.name as size_name', 
                    'orders.status', 'customer.name as customer_name', 'customer.email as customer_email',
                    'customer.address as customer_address', 'customer.phone as customer_phone', 'orders.courier',
                    'orders.no_resi', 'orders.shipping_cost')
                ->where('orders.created_at', '>=', $startDate->format('Y-m-d H:i:s'))
                ->where('orders.created_at', '<=', $endDate->format('Y-m-d H:i:s'))
                ->orderBy('orders.created_at', 'DESC')
                ->skip($i * $offset)
                ->take($offset);

            // Check if user loggedin is client or admin
            if (Auth::user()->roles == 'client')
                $query->where('client.id', '=', Auth::user()->client_id);
            else if (!empty($clientId))
                $query->where('client.id', '=', $clientId);

            // Check status filter
            if (!empty($status))
                $query->where('orders.status', '=', $status);

            $data = $query->get();

            foreach ($data as $order) { 
                $index = $order->order_number.'_'.$order->product_name.'_'.$order->color.'_'.$order->size_name;
            
                // Push new order
                if (!array_key_exists($index, $result)) {
                    $dimension = json_decode($order->dimension);
                    $result[$index] = array(
                        'Order Number' => $order->order_number,
                        'Client Name'  => $order->client_name,
                        'Customer Name' => $order->customer_name,
                        'Customer Email' => $order->customer_email,
                        'Customer Phone' => preg_replace('/[^0-9.]+/', '', strval($order->customer_phone)),
                        'Customer Address' => $order->customer_address,
                        'Product Name' => $order->product_name,
                        'Size Name' => $order->size_name,
                        'Price' => $order->price,
                        'Weight (Kg)' => $order->weight,
                        'Dimension (W/H/D in cm)' => $dimension->w.'/'.$dimension->h.'/'.$dimension->d,
                        'Courier' => $order->courier,
                        'No Resi' => $order->no_resi,
                        'Quantity' => 0,
                        'Ongkir' => strval($order->shipping_cost),
                        'Status' => $order->status
                    );
                }

                // Count quantity
                $result[$index]['Quantity']++;
            }

            // Stop if data is not full
            if (count($data) < $offset)
                break;
            
            $i++;
        }
        
        // Generate Excel
        Excel::create('order-export-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd'), function ($excel) use ($result) {
            $excel->sheet('Order Details', function ($sheet) use ($result) {
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
    
    public function reset(Request $request)
    {
        try {
            $updatedRow = DB::table('inbound_detail_location')
                ->leftJoin('order_detail', 'order_detail.id', '=', 'inbound_detail_location.order_detail_id')
                ->whereNull('order_detail.is_missed')
                ->whereNotNull('inbound_detail_location.order_detail_id')
                ->where('inbound_detail_location.updated_at', '>=', date('Y-m-d 00:00:00'))
                ->update([
                    'inbound_detail_location.date_ordered' => null,
                    'inbound_detail_location.order_detail_id' => null,
                    'inbound_detail_location.date_picked'     => null,
                    'inbound_detail_location.date_outbounded' => null,
                ]);
            echo "There are " . $updatedRow . " rows affected.";
        } catch (\Illuminate\Database\QueryException $e) {
            echo $e->getMessage();
        }
    }

    public function editAirwaybill(Request $request)
    {
        return view('dashboard.order.update-airwaybill');
    }

    public function updateAirwaybill(Request $request)
    {
    	$validator = Validator::make($request->all(), [
            'order_num' => 'required',
            'logistic_name' => 'required',
	        'awb_num' => 'required',
        ]);

	    if ($validator->fails()) {
	    	return redirect('/order/airwaybill/edit')
                        ->withErrors($validator)
                        ->withInput();
	    } else {
            $order_num = $request->input('order_num');
            $order = DB::table('orders')
                ->where('order_number', '!=', $order_num)
                ->where('no_resi', '=', $request->input('awb_num'))
                ->first();

            if ($order != null) {
                Session::flash('error', 'Airwaybill is duplicate!');
                return redirect()->back();
            }
            $query = Order::where('order_number', $order_num);

            if (Auth::user()->roles == 'client') {
                $query->where('client_id', '=', Auth::user()->client_id);
            }

            $order = $query->first();

            if (empty($order)) {
                $request->session()->flash('error', 'Order with order number '.$order_num.' is not found. Please input the existing order number');
                return redirect()->back();
            }
            else {
                if (strpos($request->input('awb_num'), '<') !== false || strpos($request->input('awb_num'), '>') !== false) {
                    Session::flash('error', 'Airwaybill is contain character "<" or ">"!');
                    return redirect()->back();
                }

                $order->courier = $request->input('logistic_name');
                $order->no_resi = $request->input('awb_num');

                $order->save();

                //Send to Jubelio system
                $partner_name = 'jubelio';
                $salesOrder = new SalesOrder();
                $resp = $salesOrder->patchUpdateAirwaybilltoPartner($partner_name, $order->client_id, $order->id, $order->courier, $order->no_resi);

                if ($resp != null && $resp->status() != 200)
                {
                    //If there is error eventhough saving to DB was success, operator need to re-submit the form
                    $request->session()->flash('error', "Failed to update to external partner system(". $partner_name ."). Please check your data or contact administrator.");
                }
                $request->session()->flash('success', 'Order airwaybill has been successfully updated');

            }
	    }
        return redirect('order');
    }

    public function shippingLabel(Request $request)
    {
        return view('dashboard.order.shipping-label');
    }

    public function shippingLabelList(Request $request)
    {
        $limit = $request->input('length');
        $start = $request->input('start');
        $orderColumn = $request->input('order.0.column') + 1;
        $dir = $request->input('order.0.dir');

        $result = [];

        $query = DB::table('order_batch')
            ->select('order_batch.id', 'template_url as url', 'users.name as user_name', 'client.name as client_name', 'order_batch.count_success', 'order_batch.count_order', 'orders.created_at', 'orders.updated_at', 'client.id as client_id')
            ->join('users', 'users.id', '=', 'order_batch.user_id')
            ->join('client', 'client.id', '=', 'order_batch.client_id')
            ->join('orders', 'orders.batch_id', '=', 'order_batch.id')
            ->orderBy('order_batch.id', 'desc');

        if (Auth::user()->roles == 'client') {
            $query->where('client.id', '=', Auth::user()->client_id);
        }

        $start_date = (!empty($request->input('start_date'))) ? ($request->input('start_date')) : date('Y-m-d H:i:s');
        $end_date = (!empty($request->input('end_date'))) ? ($request->input('end_date')) : date('Y-m-d H:i:s');
        $start_due_date = (!empty($request->input('start_due_date'))) ? ($request->input('start_due_date')) : date('Y-m-d H:i:s');
        $end_due_date = (!empty($request->input('end_due_date'))) ? ($request->input('end_due_date')) : date('Y-m-d H:i:s');
        $is_printed = (!empty($request->input('is_printed'))) ? ($request->input('is_printed')) : ('');

        if($start_date && $end_date) {
            $start_date = Carbon::parse($start_date)->startOfDay();
            $end_date = Carbon::parse($end_date)->endOfDay(); 
          
            $query->where("orders.created_at", ">=", $start_date->format('Y-m-d H:i:s'))
                ->where("orders.created_at", "<=", $end_date->format('Y-m-d H:i:s'));
        }

        if($start_due_date && $end_due_date) {
            $start_due_date = Carbon::parse($start_due_date)->startOfDay();
            $end_due_date = Carbon::parse($end_due_date)->endOfDay();
          
            $query->where("orders.due_date", ">=", $start_due_date->format('Y-m-d H:i:s'))
                ->where("orders.due_date", "<=", $end_due_date->format('Y-m-d H:i:s'));
        }
        
        if($is_printed) {
            $query->where("order_batch.is_printed", "=", $is_printed);
        } else {
            $query->where("order_batch.is_printed", "=", "0");
        }

        $query_count = $query;
        $total = count($query_count->groupBy("order_batch.id")->get());

        $data = $query
            ->orderBy(DB::raw($orderColumn), $dir)
            ->limit($limit)
            ->offset($start)
            ->groupBy("order_batch.id")
            ->get();

        foreach ($data as $key => $value) {
            $value->orders = join(", ", DB::table('orders')
                ->select('order_number')
                ->where('batch_id', '=', $value->id)
                ->pluck('order_number')->toArray());
            $url = "/order/shippinglabel/download/".$value->id."/".$value->client_id;  
            $value->url = "<a href='$url'>Download</a>";  
            $value->order_summary = $value->count_success . '/' . $value->count_order;
            $value->created_at = Carbon::parse($value->created_at)->format('d-M-Y H:i');

            $result[$key] = $value;
        }

        return response()->json([
            'draw' => intval($request->input('draw')),  
            'recordsTotal' => intVal($total),
            'recordsFiltered' => intVal($total), 
            'data' => $result
        ]);
    }

    public function downloadBatchShippingLabel(Request $request)
    {
        $shipmentApproved = new \stdClass;
        $shipmentApproved->data = new \stdClass;
        $shipmentApproved->data->order = new \stdClass;
        $shipmentApproved->data->order->order_ids = array();

        $labelParams = [
            "data" => [
                "label" => []
            ]
        ];
        //init label
        $label = $this->createNewLabel();
        $batchIDs = explode(',', $request->get('order_ids'));

        if (count($batchIDs) > 20 ) {
            $request->session()->flash('error',   "You're request more than 20 batch orders");
            return back();
        }

        $shipmentApproved->data->order->order_ids = DB::connection('read_replica')->table('orders')->whereIn('orders.batch_id', $batchIDs)->pluck('id')->toArray();

        $awb_response = json_decode($this->saveMarketplaceAWB($shipmentApproved)->getBody());

        if (!!$awb_response && $awb_response->metadata->status_code != 200) {
            Log::info("Error get awb: " . $awb_response->metadata->message);
        }

        $responseData = DB::connection('read_replica')
            ->table('orders')
            ->join('customer', 'customer.id', '=', 'orders.customer_id')
            ->join('client', 'client.id', '=', 'orders.client_id')
            ->select('orders.id as order_id', 'orders.batch_id as batch_id', 'orders.courier', 'orders.order_number', 
            'customer.address as address', 'customer.name as customer_name', 'customer.phone as customer_phone', 'customer.zip_code',
            'client.name as client_name', 'client.id as client_id', 'client.logo_url', 'orders.no_resi')
            ->whereIn('orders.batch_id', $batchIDs)
            ->get();

        foreach($responseData as $item) {
            DB::table('order_batch')
                ->where('id', $item->batch_id)
                ->update([
                    'is_printed' => 1
                ]);

            //get label param
            $labelParam = [];
            $pdf = $this->makeLabelParam($label, $item->logo_url, $item->courier, $item->address, $item->customer_name, $item->customer_phone, $item->zip_code, $item->client_name, $item->order_number, $item->no_resi);
            $labelParam['type'] = $label['label_id'];
            array_push($labelParams['data']['label'], $pdf);           
        }

        $clientHttp = app(HttpClient::class);
        try {
            $url = env('LABELSVC_BASE_URL') . Config::get('constants.label.CREATE_LABEL');
            Log::info(json_encode($labelParams));

            $response = $clientHttp->request("POST", $url, ['body' => json_encode($labelParams)]);
            $response = json_decode($response->getBody()->getContents());
        } catch (\Exception $exception) {
            Log::error("Failed to call API to create label: ". $exception);
            throw $exception;
        }

        return Redirect::away($response->data->url);
    }

    public function downloadShippingLabel(Request $request, $batch_id, $client_id)
    {
        $shipmentApproved = new \stdClass;
        $shipmentApproved->data = new \stdClass;
        $shipmentApproved->data->order = new \stdClass;
        $shipmentApproved->data->order->order_ids = array();

        $order_batch = DB::table('order_batch')
            ->where('id', $batch_id)
            ->first();

        $orders = DB::table('orders')
            ->where('batch_id', $batch_id)
            ->get();

        foreach ($orders as $order) {
            array_push($shipmentApproved->data->order->order_ids, $order->id);
        }

        $awb_response = json_decode($this->saveMarketplaceAWB($shipmentApproved)->getBody());

        if (!!$awb_response && $awb_response->metadata->status_code != 200) {
            Log::info("Error get awb: " . $awb_response->metadata->message);
        }

        DB::table('order_batch')
            ->where('id', $batch_id)
            ->update([
                'is_printed' => 1
            ]);

        // check url
        $diff = Carbon::now()->diffInHours(Carbon::parse($order_batch->updated_at));

        // if (is_null($order_batch->template_url) || $order_batch->template_url == '' || $diff > 60) {
            $order_batch->template_url = "/order/shipping/label/regenerate/" . $client_id . "/" . $batch_id;
        // }
        
        return redirect($order_batch->template_url);
    }

    public function saveMarketplaceAWB($shipmentApproved) {
        $response = null;
        $httpClient = app(HttpClient::class);
        $jubelio = Config::get('constants.partners.jubelio');
        $url = env('OMNICHANNELSVC_BASE_URL') . $jubelio['URL_SAVE_MARKET_PLACE_AWB'];

        try{
            $response = $httpClient->request("PATCH", $url, ['body' => json_encode($shipmentApproved)]);
        } catch(\Exception $exception) {
            $response = $exception->getResponse();
        }
        
        return $response;
    }

    public function pendingOrder(Request $request)
    {
        $restrict = ($request->get('r') != null && $request->get('r') == 0);

        $clients = Client::orderBy('name')->get();
        
        $warehouses = Warehouse::where('is_active', '=', 1)->get();
        
        return view('dashboard.order.pending-order', compact('restrict','clients', 'warehouses'));
    }

    public function pendingOrderList(Request $request)
    {
        $columns = [
            'id',
            'order_number',
            'warehouse_name',
            'client_name',
            'due_date'
        ];
        $query = null;

        // Input variable
        $startDueDate = $request->input('start_due_date');
        $endDueDate = $request->input('end_due_date');
        $clientId = $request->input('client');
        $limit = $request->input('length');
        $start = $request->input('start');
        $warehouse = $request->input('warehouse');
        $orderIndex = $request->input('order.0.column');
        $orderDirection = $orderIndex == 0 ? 'desc' : $request->input('order.0.dir');

        // Date Processing
        if (!empty($startDueDate) && !empty($endDueDate)) {
            $startDueDate = Carbon::parse($startDueDate)->startOfDay();
            $endDueDate = Carbon::parse($endDueDate)->endOfDay(); // Make sure the time still latest cause its 00:00:00

            // Will add validation on front end as well, just to make sure in back end
            if ($startDueDate->diffInDays($endDueDate) > 31) 
                $endDueDate = $startDueDate->copy()->addDays(31);
        } else {
            // If Somehow the date is empty, it will take last 1 week, will add on front end also
            $startDueDate = Carbon::now()->subDays(7);
            $endDueDate = Carbon::now()->endOfDay(); // Make sure the time still latest
        }

       
        // Build query for result
        $query = DB::table('orders')
            ->select('orders.id', 'orders.order_number', 'warehouse.name as warehouse_name', 'client.name as client_name', 'orders.due_date' )
            ->join('client', 'orders.client_id', '=', 'client.id')
            ->join('warehouse', 'orders.warehouse_id', '=', 'warehouse.id')
            ->where('orders.status', '=', 'PENDING')
            ->whereNull('orders.picked_status')
            ->where('orders.due_date', '>=', $startDueDate->format('Y-m-d H:i:s'))
            ->where('orders.due_date', '<=', $endDueDate->format('Y-m-d H:i:s'));

        $columns = array_values($columns);
        $orderColumn = $orderIndex == 0 ? 'orders.created_at' : $columns[$orderIndex];

        // Check warehouse
        if (!empty($warehouse))  {
            $query->when($warehouse, function($subquery, $warehouse) {
                return $subquery->where('orders.warehouse_id', $warehouse)->orWhereNull('orders.warehouse_id');
                 });
        }   
        
        // check client id
        if (!empty($clientId))  {
            $query->where('orders.client_id', '=', $clientId);
        }   
       
        // Count all data
        $total =  $query->get()->count();

        // Count filtered data
        $totalFiltered =  $query->get()->count();

        $tableSource = "order_batch";    
        // Build selected column
        /*$query->select(...array_map(function ($item) use ($tableSource) {
                return $tableSource.'.'.$item;
        }, $columns)); */

        // Pagination handler
        $query->limit($limit)->offset($start);

        // Array data to show
        $result = [];

        // Preparing data, max loop only 10 data
        foreach ($query->orderBy($orderColumn, $orderDirection)->get()->toArray() as $order) {
            $order = json_decode(json_encode($order), true);

            $due_date = new Carbon($order['due_date']);
            $now = Carbon::now();
            $diff = $now->diffInHours($due_date, false);
            $order['due_date'] = date('d M Y H:i', strtotime($order['due_date']));
            
            if ($diff < 0) {
                $order['due_date'] = '<b style="color: #2F4F4F">' . $order['due_date'] . '</b>';
            } else if ($diff < 24) {
                $order['due_date'] = '<b style="color: #FF0000">' . $order['due_date'] . '</b>';
            } else if ($diff < 48) {
                $order['due_date'] = '<b style="color: #E6AC00">' . $order['due_date'] . '</b>';
            } else {
                $order['due_date'] = '<b style="color: #006400">' . $order['due_date'] . '</b>';
            }

            // Add prepared data to array
            $result[] = $order;
        }
            
        return json_encode(array(
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => intval($total),
            'recordsFiltered' => intval($totalFiltered),
            'data'            => $result
        ));
    }


    public function pendingOrderSubmit(Request $request)
    {
        $orderIds =  $request->input('order_id');
        $orderId = explode(",",$orderIds);
        $jmlData = count($orderId);
      
        $pendingApproved = new \stdClass;
        $pendingApproved->order_ids = array();
  
        for ($i=0;$i<$jmlData;$i++) {
            //generate shipping label
            $labelParams = [
                "data" => [
                    "label" => []
                ]
            ];
            //init label
            // $label = $this->createNewLabel();
            // $responseData = DB::connection('read_replica')
            //     ->table('orders')
            //     ->join('customer', 'customer.id', '=', 'orders.customer_id')
            //     ->join('client', 'client.id', '=', 'orders.client_id')
            //     ->select('orders.id as order_id', 'orders.batch_id as batch_id', 'orders.courier', 'orders.order_number', 
            //     'customer.address as address', 'customer.name as customer_name', 'customer.phone as customer_phone', 'customer.zip_code',
            //     'client.name as client_name', 'client.id as client_id', 'client.logo_url', 'orders.no_resi', 'orders.client_id as client_id', 'orders.batch_id as batch_id')
            //     ->where('orders.id', '=', intval($orderId[$i]))
            //     ->get();
            
            // foreach($responseData as $item) {
            //     //get label param
            //     $labelParam = [];
            //     $pdf = $this->makeLabelParam($label, $item->logo_url, $item->courier, $item->address, $item->customer_name, $item->customer_phone, $item->zip_code, $item->client_name, $item->order_number, $item->no_resi);
            //     $labelParam['type'] = $label['label_id'];
            //     array_push($labelParams['data']['label'], $pdf);
            //     $client_id = $item->client_id;
            //     $orderBatchID = $item->batch_id;
            //     //generate label
            //     $response = $this->generateLabel($labelParams, $client_id, $orderBatchID);       
            // }
            array_push($pendingApproved->order_ids, intval($orderId[$i]));
        }
    
        $data = array();
        $data["data"]["order"] = $pendingApproved;
        $response = null;
        $httpClient = app(HttpClient::class);
        $jubelio = Config::get('constants.partners.jubelio');
        $url = env('OMNICHANNELSVC_BASE_URL') . $jubelio['URL_APPROVE_PENDING_ORDER'];
        
        try{ 
            $response = $httpClient->request("POST", $url, ['body' => json_encode($data)]);
            $result = json_decode($response->getBody(true));
            $order_numbers = $result->data->order->order_numbers;
            Session::flash('success', 'Approved Order with Order Number: ' . (count($order_numbers) ? join(', ', $order_numbers) : "-"));
        } catch(\Exception $exception) {
            $response =  $exception->getResponse()->getBody(true);
            Session::flash('error', response()->json(json_decode($response)));
        }    
        return redirect('/order/pending/list');                               
    }

    public function orderIssueListIndex(Request $request) {
        return view('dashboard.order.order-issue-list-index');
    }

    public function getListOrderIssue(Request $request) {
        $httpClient = app(HttpClient::class);
        $createdAtGTE = $request->input('created_at__gte') . ' 00:00:00';
        $createdAtLTE = $request->input('created_at__lte') . ' 23:59:59';
        $status = $request->input('status');
        if (empty($createdAtGTE) && empty($createdAtLTE)) {
            $createdAtGTE = Carbon::now()->startOfMonth();
            $createdAtLTE = Carbon::now();
        }

        $internalServices = Config::get('constants.internal_services');
        $url = env('OMNICHANNELSVC_BASE_URL') . $internalServices['omnichannel']['URL_ORDER_ISSUE_LIST'];
        try {
            $response = $httpClient->request("GET", $url, ['query' => [
                'draw' => $request->input('draw'),
                'created_at__gte' => $createdAtGTE,
                'created_at__lte' => $createdAtLTE,
                'status' => $status,
                'external_order_number' => $request->input('external_order_number'),
                'page' => ((int) $request->input('start')/10) + 1,
                'limit' => (int) $request->input('length'),
                'sort_by' => "-created_at"
            ]]);
        }catch (RequestException $e) {
                if ($e->getResponse()->getStatusCode() == '404') {
                    return json_encode(array(
                        'draw'            => intval($request->input('draw')),
                        'recordsTotal'    => 0,
                        'recordsFiltered' => 0,
                        'data'            => []
                    ));
            }
        } catch (\Exception $e) {
            $response = $e->getResponse();
            return $response;
        } 

        $orderIssues = json_decode($response->getBody()->getContents());
        $data = array();
        foreach($orderIssues->data as $key => $orderIssue) {
            $orderIssue->created_at = date('d M Y H:i', strtotime($orderIssue->created_at));
            $orderIssue->updated_at = date('d M Y H:i', strtotime($orderIssue->updated_at));
            $orderIssue->action = '<div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Action
                </button>
                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                    <a class="dropdown-item" href="/order/issue/detail/' . $orderIssue->request_id .'/'. $orderIssue->created_at . '">View Detail</a>
                    <a class="dropdown-item" href="/order/detail/revalidate/' . $orderIssue->request_id . '">Revalidate Order</a>
                </div>
            </div>';
            $orderIssue->status = Config::get('constants.order_issue_status.'.$orderIssue->status);
            array_push($data, $orderIssue);
        }

        return json_encode(array(
            'draw'            => intval($request->input('draw')),
            'recordsTotal'    => $orderIssues->recordsTotal,
            'recordsFiltered' => $orderIssues->recordsFiltered,
            'data'            => $data
        ));
    }

    public function revalidateOrders(Request $request)
    {
        $httpClient = app(HttpClient::class);
        $input  = $request->all();
        $requestIDs = explode(',', $input['request_id']);

        //make the body request
        $bodyReq = new \stdClass;
        $bodyReq->data = array();
        foreach ($requestIDs as $key => $requestID) {
            $param = new \stdClass;
            $param->request_id = $requestID;
            array_push($bodyReq->data, $param);
        }
        
        try {
            $internalServices = Config::get('constants.internal_services');
            $url = env('OMNICHANNELSVC_BASE_URL') . $internalServices['omnichannel']['URL_RETRY_ORDER_VALIDATION'];
            $response = $httpClient->request("POST", $url, ['body' => json_encode($bodyReq)]);
        } catch (RequestException $exception) {
            $response =  $exception->getResponse()->getBody(true);
            return response()->json(json_decode($response));
        }

        return redirect('/order/issue/list/index');
    }   

    public function orderIssueDetail(Request $request, $requestID, $createdAt) 
    {
        $httpClient = app(HttpClient::class);
        $internalServices = Config::get('constants.internal_services');
        $url = env('OMNICHANNELSVC_BASE_URL') . sprintf($internalServices['omnichannel']['URL_ORDER_ISSUE_DETAIL'], $requestID, Carbon::parse($createdAt));
        try {
            $response = $httpClient->request("GET", $url, ['query'=>[]]);
        }catch (RequestException $e) {
            if ($e->getResponse()->getStatusCode() == '404') {
                return view('dashboard.order.order-issue-detail', ['problems' => [],
                'request_id' => $requestID,
                'created_at' => $createdAt]);
            }
        }catch (\Exception $e) {
            $response = $e->getResponse();
            return $response;
        }

        $problems = json_decode($response->getBody()->getContents());
        return view('dashboard.order.order-issue-detail', [
        'problems' => $problems, 
        'request_id' => $requestID,
        'created_at' => $createdAt]);
    }

    public function revalidateOrder(Request $request, $requestID)
    {
        $httpClient = app(HttpClient::class);
        //make the body request
        $bodyReq = new \stdClass;
        $bodyReq->data = array();
        $param = new \stdClass;
        $param->request_id = $requestID;
        array_push($bodyReq->data, $param);
        
        try {
            $internalServices = Config::get('constants.internal_services');
            $url = env('OMNICHANNELSVC_BASE_URL') . $internalServices['omnichannel']['URL_RETRY_ORDER_VALIDATION'];
            $response = $httpClient->request("POST", $url, ['body' => json_encode($bodyReq)]);
        } catch (RequestException $exception) {
            $response =  $exception->getResponse()->getBody(true);
            return response()->json(json_decode($response));
        }

        return redirect('/order/issue/list/index');
    }   
}