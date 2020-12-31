<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use DB;

use App\Client;
use App\Order;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $params = array("total" => 0,"ready" => 0,"packing" => 0,"awaits" => 0,"shipped" => 0);

        if(Auth::user()->roles == 'client'){
            $client = Client::where('id',Auth::user()->client_id)->first();
            $orders = Order::whereRaw('updated_at >= "'.date('Y-m-d 00:00:00').'" AND updated_at < "'.date('Y-m-d 23:59:59').'"')->where('client_id','=',$client->id)->get();

            foreach($orders as $order){
                $params['total'] += 1;
                if($order->status == 'READY_FOR_OUTBOUND'){
                    $params['ready'] += 1;
                }else if($order->status == 'READY_TO_PACK'){
                    $params['packing'] += 1;
                }else if($order->status == 'AWAITING_FOR_SHIPMENT'){
                    $params['awaits'] += 1;
                }else if($order->status == 'SHIPPED'){
                    $params['shipped'] += 1;
                }
            }

        } else {
            $qorders = Order::whereRaw('updated_at >= "'.date('Y-m-d 00:00:00').'" AND updated_at < "'.date('Y-m-d 23:59:59').'"');

            if(Auth::user()->roles == 'crew' || Auth::user()->roles == 'head'){
                $qorders->whereNotNull('warehouse_id')->where('warehouse_id','=',Auth::user()->warehouse_id);
            }

            $orders = $qorders->get();

            foreach($orders as $order){
                $params['total'] += 1;
                if($order->status == 'READY_FOR_OUTBOUND'){
                    $params['ready'] += 1;
                }else if($order->status == 'READY_TO_PACK'){
                    $params['packing'] += 1;
                }else if($order->status == 'AWAITING_FOR_SHIPMENT'){
                    $params['awaits'] += 1;
                }else if($order->status == 'SHIPPED'){
                    $params['shipped'] += 1;
                }
            }
        }
        return view('dashboard.home',['params' => $params]);
    }

    public function get_tops(Request $request)
    {
        $columns = array('id', 'name', 'price', 'sum');

        $total = null;
        $boots = null;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $order = (($order == 'id')?'orders.id':$order);
        $dir = $request->input('order.0.dir');

        if(Auth::user()->roles == 'client'){
            $client = Client::where('id',Auth::user()->client_id)->first();
            $total = DB::table('orders')
            ->join('order_detail','order_detail.orders_id','=','orders.id')
            // ->join('inbound_detail','inbound_detail.id','=','order_detail.inbound_detail_id')
            // ->join('product','product.id','=','inbound_detail.product_id')
            // ->select('product.id','product.name','product.price',DB::raw('COUNT(order_detail.inbound_detail_id) as sum'))
            ->groupBy('order_detail.inbound_detail_id')
            ->orderBy(DB::raw('COUNT(order_detail.inbound_detail_id)'),'desc')
            ->limit(10)
            ->where('orders.client_id','=',$client->id)
            ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
            ->count();

        }else{

            $t_boots = DB::table('orders')
            ->join('order_detail','order_detail.orders_id','=','orders.id')
            // ->join('inbound_detail','inbound_detail.id','=','order_detail.inbound_detail_id')
            // ->join('product','product.id','=','inbound_detail.product_id')
            // ->select('product.id','product.name','product.price',DB::raw('COUNT(order_detail.inbound_detail_id) as sum'))
            ->groupBy('order_detail.inbound_detail_id')
            ->orderBy(DB::raw('COUNT(order_detail.inbound_detail_id)'),'desc')
            ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
            ->limit(10);

            if(Auth::user()->roles == 'crew' || Auth::user()->roles == 'head'){
                $t_boots->whereNotNull('orders.warehouse_id')->where('orders.warehouse_id','=',Auth::user()->warehouse_id);
            }

            $total = $t_boots->count();

        }

        $totalFiltered = $total;

        if(empty($request->input('search.value'))){
            
            if(Auth::user()->roles == 'client'){
                $client = Client::where('id',Auth::user()->client_id)->first();
                $t_boots = DB::table('orders')
                ->join('order_detail','order_detail.orders_id','=','orders.id')
                ->join('inbound_detail','inbound_detail.id','=','order_detail.inbound_detail_id')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->select('product.id','product.name','product.price',DB::raw('COUNT(order_detail.inbound_detail_id) as sum'))
                ->groupBy('order_detail.inbound_detail_id')
                ->orderBy(DB::raw('COUNT(order_detail.inbound_detail_id)'),'desc')
                ->limit(10)
                ->where('orders.client_id','=',$client->id)
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->offset($start)
                ->orderBy($order, $dir);

                $boots = $t_boots->get();
                $totalFiltered = count($boots);

            }else{

                $t_boots = DB::table('orders')
                ->join('order_detail','order_detail.orders_id','=','orders.id')
                ->join('inbound_detail','inbound_detail.id','=','order_detail.inbound_detail_id')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->select('product.id','product.name','product.price',DB::raw('COUNT(order_detail.inbound_detail_id) as sum'))
                ->groupBy('order_detail.inbound_detail_id')
                ->orderBy(DB::raw('COUNT(order_detail.inbound_detail_id)'),'desc')
                ->limit(10)
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->offset($start)
                ->orderBy($order, $dir);

                if(Auth::user()->roles == 'crew' || Auth::user()->roles == 'head'){
                    $t_boots->whereNotNull('orders.warehouse_id')->where('orders.warehouse_id','=',Auth::user()->warehouse_id);
                }

                $boots = $t_boots->get();
                $totalFiltered = count($boots);

            }

        } else {

            $search = $request->input('search.value'); 
            if(Auth::user()->roles == 'client'){
                $client = Client::where('id',Auth::user()->client_id)->first();
                $t_boots = DB::table('orders')
                ->join('order_detail','order_detail.orders_id','=','orders.id')
                ->join('inbound_detail','inbound_detail.id','=','order_detail.inbound_detail_id')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->select('product.id','product.name','product.price',DB::raw('COUNT(order_detail.inbound_detail_id) as sum'))
                ->groupBy('order_detail.inbound_detail_id')
                ->orderBy(DB::raw('COUNT(order_detail.inbound_detail_id)'),'desc')
                ->limit(10)
                ->where('orders.client_id','=',$client->id)
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->where('product.name','LIKE','%'.$search.'%')
                ->offset($start)
                ->orderBy($order, $dir);

                $boots = $t_boots->get();
                $totalFiltered = count($boots);

            }else{

                $t_boots = DB::table('orders')
                ->join('order_detail','order_detail.orders_id','=','orders.id')
                ->join('inbound_detail','inbound_detail.id','=','order_detail.inbound_detail_id')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->select('product.id','product.name','product.price',DB::raw('COUNT(order_detail.inbound_detail_id) as sum'))
                ->groupBy('order_detail.inbound_detail_id')
                ->orderBy(DB::raw('COUNT(order_detail.inbound_detail_id)'),'desc')
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->where('product.name','LIKE','%'.$search.'%')
                ->limit(10)
                ->offset($start)
                ->orderBy($order, $dir);

                if(Auth::user()->roles == 'crew' || Auth::user()->roles == 'head'){
                    $t_boots->whereNotNull('orders.warehouse_id')->where('orders.warehouse_id','=',Auth::user()->warehouse_id);
                }

                $boots = $t_boots->get();
                $totalFiltered = count($boots);
            }

        }

        $data = array();
        if(!empty($boots))
        {
            foreach($boots as $stock)
            {
                $obj = array();
                $obj['id'] = $stock->id;
                $obj['name'] = $stock->name;
                $obj['price'] = number_format($stock->price,0,',','.');
                $obj['sum'] = $stock->sum;
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

    public function get_list(Request $request)
    {
        $columns = array('id', 'name', 'price', 'stocks');

        $total = null;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        if(Auth::user()->roles == 'client'){
            $client = Client::where('id',Auth::user()->client_id)->first();
            $total = DB::table('inbound_detail_location')
                ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->select('product.id','product.name','product.price',DB::raw('COUNT(inbound_detail_location.inbound_detail_id) as sum'))
                ->groupBy('product.id')
                ->orderBy(DB::raw('COUNT(inbound_detail_location.inbound_detail_id)'),'asc')
                ->whereNull('inbound_detail_location.date_outbounded')
                ->whereNotNull('inbound_detail_location.shelf_id')
                ->where('product.client_id','=',$client->id)
                ->havingRaw('COUNT(inbound_detail_location.inbound_detail_id) <= ?',array(5))
                ->count();
        } else {
            $qtotal = DB::table('inbound_detail_location')
                ->leftJoin('shelf','shelf.id','=','inbound_detail_location.shelf_id')
                ->leftJoin('rack','rack.id','=','shelf.rack_id')
                ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->select('product.id','product.name','product.price',DB::raw('COUNT(inbound_detail_location.inbound_detail_id) as sum'))
                ->groupBy('product.id')
                ->orderBy(DB::raw('COUNT(inbound_detail_location.inbound_detail_id)'),'asc')
                ->whereNull('inbound_detail_location.date_outbounded')
                ->whereNotNull('inbound_detail_location.shelf_id')
                ->havingRaw('COUNT(inbound_detail_location.inbound_detail_id) <= ?',array(5));

            if(Auth::user()->roles == 'crew' || Auth::user()->roles == 'head'){
                $qtotal->whereNotNull('inbound_detail_location.shelf_id')->where('rack.warehouse_id','=',Auth::user()->warehouse_id);
            }

            $total = $qtotal->count();
        }

        $totalFiltered = $total;

        $limit = $request->input('length');
        $start = $request->input('start');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $boots = null;

        if(empty($request->input('search.value')))
        {
            if(Auth::user()->roles == 'client'){
                $client = Client::where('id',Auth::user()->client_id)->first();
                $boots = DB::table('inbound_detail_location')
                    ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                    ->join('product','product.id','=','inbound_detail.product_id')
                    ->select('product.id','product.name','product.price',DB::raw('COUNT(inbound_detail_location.inbound_detail_id) as sum'))
                    ->groupBy('product.id')
                    ->orderBy(DB::raw('COUNT(inbound_detail_location.inbound_detail_id)'),'asc')
                    ->whereNull('inbound_detail_location.date_outbounded')
                    ->whereNotNull('inbound_detail_location.shelf_id')
                    ->where('product.client_id','=',$client->id)
                    ->havingRaw('COUNT(inbound_detail_location.inbound_detail_id) <= ?',array(5))
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();

            } else {
                $qboots = DB::table('inbound_detail_location')
                    ->leftJoin('shelf','shelf.id','=','inbound_detail_location.shelf_id')
                    ->leftJoin('rack','rack.id','=','shelf.rack_id')
                    ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                    ->join('product','product.id','=','inbound_detail.product_id')
                    ->select('product.id','product.name','product.price',DB::raw('COUNT(inbound_detail_location.inbound_detail_id) as sum'))
                    ->groupBy('product.id')
                    ->orderBy(DB::raw('COUNT(inbound_detail_location.inbound_detail_id)'),'asc')
                    ->whereNull('inbound_detail_location.date_outbounded')
                    ->whereNotNull('inbound_detail_location.shelf_id')
                    ->havingRaw('COUNT(inbound_detail_location.inbound_detail_id) <= ?',array(5))
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir);

                if(Auth::user()->roles == 'crew' || Auth::user()->roles == 'head'){
                    $qboots->whereNotNull('inbound_detail_location.shelf_id')->where('rack.warehouse_id','=',Auth::user()->warehouse_id);
                }

                $boots = $qboots->get();
            }

        } else {

            $search = $request->input('search.value'); 

            if(Auth::user()->roles == 'client'){
                $user = User::find(Auth::user()->id);

                $boots = DB::table('inbound_detail_location')
                    ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                    ->join('product','product.id','=','inbound_detail.product_id')
                    ->select('product.id','product.name','product.price',DB::raw('COUNT(inbound_detail_location.inbound_detail_id) as sum'))
                    ->groupBy('product.id')
                    ->orderBy(DB::raw('COUNT(inbound_detail_location.inbound_detail_id)'),'asc')
                    ->whereNull('inbound_detail_location.date_outbounded')
                    ->whereNotNull('inbound_detail_location.shelf_id')
                    ->where('product.client_id','=',$client->id)
                    ->where('product.name','LIKE','%'.$search.'%')
                    ->havingRaw('COUNT(inbound_detail_location.inbound_detail_id) <= ?',array(5))
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir)
                    ->get();

                $totalFiltered = DB::table('inbound_detail_location')
                    ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                    ->join('product','product.id','=','inbound_detail.product_id')
                    ->select('product.id','product.name','product.price',DB::raw('COUNT(inbound_detail_location.inbound_detail_id) as sum'))
                    ->groupBy('product.id')
                    ->orderBy(DB::raw('COUNT(inbound_detail_location.inbound_detail_id)'),'asc')
                    ->whereNull('inbound_detail_location.date_outbounded')
                    ->whereNotNull('inbound_detail_location.shelf_id')
                    ->where('product.client_id','=',$client->id)
                    ->havingRaw('COUNT(inbound_detail_location.inbound_detail_id) <= ?',array(5))
                    ->where('product.name','LIKE','%'.$search.'%')
                    ->count();

            } else {

                $qboots = DB::table('inbound_detail_location')
                    ->leftJoin('shelf','shelf.id','=','inbound_detail_location.shelf_id')
                    ->leftJoin('rack','rack.id','=','shelf.rack_id')
                    ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                    ->join('product','product.id','=','inbound_detail.product_id')
                    ->select('product.id','product.name','product.price',DB::raw('COUNT(inbound_detail_location.inbound_detail_id) as sum'))
                    ->groupBy('product.id')
                    ->orderBy(DB::raw('COUNT(inbound_detail_location.inbound_detail_id)'),'asc')
                    ->whereNull('inbound_detail_location.date_outbounded')
                    ->whereNotNull('inbound_detail_location.shelf_id')
                    ->where('product.name','LIKE','%'.$search.'%')
                    ->havingRaw('COUNT(inbound_detail_location.inbound_detail_id) <= ?',array(5))
                    ->offset($start)
                    ->limit($limit)
                    ->orderBy($order, $dir);

                if(Auth::user()->roles == 'crew' || Auth::user()->roles == 'head'){
                    $qboots->whereNotNull('inbound_detail_location.shelf_id')->where('rack.warehouse_id','=',Auth::user()->warehouse_id);
                }

                $boots = $qboots->get();

                $qtotalFiltered = DB::table('inbound_detail_location')
                    ->leftJoin('shelf','shelf.id','=','inbound_detail_location.shelf_id')
                    ->leftJoin('rack','rack.id','=','shelf.rack_id')
                    ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
                    ->join('product','product.id','=','inbound_detail.product_id')
                    ->select('product.id','product.name','product.price',DB::raw('COUNT(inbound_detail_location.inbound_detail_id) as sum'))
                    ->groupBy('product.id')
                    ->orderBy(DB::raw('COUNT(inbound_detail_location.inbound_detail_id)'),'asc')
                    ->whereNull('inbound_detail_location.date_outbounded')
                    ->whereNotNull('inbound_detail_location.shelf_id')
                    ->where('product.name','LIKE','%'.$search.'%')
                    ->havingRaw('COUNT(inbound_detail_location.inbound_detail_id) <= ?',array(5));

                if(Auth::user()->roles == 'crew' || Auth::user()->roles == 'head'){
                    $qtotalFiltered->whereNotNull('inbound_detail_location.shelf_id')->where('rack.warehouse_id','=',Auth::user()->warehouse_id);
                }

                $totalFiltered = $qtotalFiltered->count();
            }
        }

        $data = array();
        if(!empty($boots))
        {
            foreach($boots as $stock)
            {
                $obj = array();
                $obj['id'] = $stock->id;
                $obj['name'] = $stock->name;
                $obj['price'] = number_format($stock->price,0,',','.');
                $obj['stocks'] = $stock->sum;
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

    public function logout(Request $request)
    {
        Auth::logout();
        return redirect('/login');
    }
}
