<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Validator;
use DB;
use AWS;
use Auth;
use Mail;
use Excel;
use PHPExcel_Worksheet_Drawing;

use App\Outbound;
use App\Order;
use App\OrderDetail;
use App\Client;
use App\Customer;
use App\InboundLocation;

use App\Jobs\SendOutboundEmail;

class OutboundController extends Controller
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
    	$orders = DB::table('orders')
                ->select('orders.*','client.name as client_name','customer.name as customer_name','product.name as product_name')
                ->join('order_detail','order_detail.orders_id','=','orders.id')
                ->join('inbound_detail','inbound_detail.id','=','order_detail.inbound_detail_id')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->join('customer','customer.id','=','orders.customer_id')
                ->join('client','client.id','=','orders.client_id')
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->orderBy('orders.created_at','desc')
                ->groupBy('orders.id')
                ->where('orders.status','=','READY_TO_PACK')
                ->get();

        foreach($orders as $key => $order){
            $orders[$key]->ready = DB::table('inbound_detail_location')
                ->join('order_detail','inbound_detail_location.id','=','order_detail.inbound_detail_location_id')
                ->join('orders','order_detail.orders_id','=','orders.id')
                ->where('orders.status','=','READY_TO_PACK')
                ->where('order_detail.orders_id','=',$order->id)
                ->whereNotNull('inbound_detail_location.date_picked')
                ->count();
            $orders[$key]->total = DB::table('inbound_detail_location')
                ->join('order_detail','inbound_detail_location.id','=','order_detail.inbound_detail_location_id')
                ->join('orders','order_detail.orders_id','=','orders.id')
                ->where('orders.status','=','READY_TO_PACK')
                ->where('order_detail.orders_id','=',$order->id)
                ->count();
        }

        return view('dashboard.outbound.index',['orders' => $orders]);
    }

    public function shipment(Request $request)
    {
        $orders = DB::table('orders')
                ->select('orders.*','client.name as client_name','customer.name as customer_name','product.name as product_name')
                ->join('order_detail','order_detail.orders_id','=','orders.id')
                ->join('inbound_detail','inbound_detail.id','=','order_detail.inbound_detail_id')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->join('customer','customer.id','=','orders.customer_id')
                ->join('client','client.id','=','orders.client_id')
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->orderBy('orders.created_at','desc')
                ->groupBy('orders.id')
                ->where('orders.status','=','AWAITING_FOR_SHIPMENT')
                ->get();

        foreach($orders as $key => $order){
            $orders[$key]->packed = DB::table('inbound_detail_location')
                ->join('order_detail','inbound_detail_location.id','=','order_detail.inbound_detail_location_id')
                ->join('orders','order_detail.orders_id','=','orders.id')
                ->where('orders.status','=','AWAITING_FOR_SHIPMENT')
                ->where('order_detail.orders_id','=',$order->id)
                ->whereNotNull('inbound_detail_location.date_outbounded')
                ->count();
            $orders[$key]->total = DB::table('inbound_detail_location')
                ->join('order_detail','inbound_detail_location.id','=','order_detail.inbound_detail_location_id')
                ->join('orders','order_detail.orders_id','=','orders.id')
                ->where('orders.status','=','AWAITING_FOR_SHIPMENT')
                ->where('order_detail.orders_id','=',$order->id)
                ->count();
        }

        return view('dashboard.outbound.shipment',['orders' => $orders]);
    }

    public function done(Request $request)
    {
        $orders = DB::table('orders')
                ->select('orders.*','client.name as client_name','customer.name as customer_name','product.name as product_name')
                ->join('order_detail','order_detail.orders_id','=','orders.id')
                ->join('inbound_detail','inbound_detail.id','=','order_detail.inbound_detail_id')
                ->join('product','product.id','=','inbound_detail.product_id')
                ->join('customer','customer.id','=','orders.customer_id')
                ->join('client','client.id','=','orders.client_id')
                ->where('orders.created_at','>=',date('Y-m-01 00:00:00',strtotime('-1 month')))
                ->orderBy('orders.created_at','desc')
                ->groupBy('orders.id')
                ->where('orders.status','=','SHIPPED')
                ->get();
        return view('dashboard.outbound.done',['orders' => $orders]);
    }

    public function singlePrint(Request $request, $id)
    {
        $order = Order::find($id);
        $customer = Customer::find($order->customer_id);
        $things = DB::table('order_detail')
            ->join('orders','orders.id','=','order_detail.orders_id')
            ->join('inbound_detail_location','inbound_detail_location.id','=','order_detail.inbound_detail_location_id')
            ->join('inbound_detail','inbound_detail.id','=','inbound_detail_location.inbound_detail_id')
            ->select('inbound_detail.name','inbound_detail.color',DB::raw('count(*) as qty'))
            ->where('order_detail.orders_id','=',$id)
            ->groupBy('order_detail.inbound_detail_id')
            ->get();

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8', 
            'format' => [150, 215], 
            'orientation' => 'L',
            'margin_top' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_bottom' => 10
        ]);

        //$mpdf->SetHTMLFooter(view('dashboard.pdf.footer')->render());

        $mpdf->WriteHTML(view('dashboard.pdf.delivery-print-single',[
            'order' => $order,
            'customer' => $customer,
            'things' => $things
        ])->render());

        return $mpdf->Output();
    }

    public function sendReport(Request $request)
    {
        $clients = Client::all();
        foreach($clients as $client){
            SendOutboundEmail::dispatch($client);
        }

        $request->session()->flash('success', 'Outbound email has successfully sent!');
        return redirect('report');
    }

    public function courierCheckPacking(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'checking_date' => 'required',
            'courier' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('/outbound/shipment')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $date = $request->input('checking_date');
            $courier = $request->input('courier');

            $raws = DB::table('orders')
                ->join('client','client.id','=','orders.client_id')
                ->join('customer','customer.id','=','orders.customer_id')
                ->where(DB::raw('DATE(orders.packed_date)'),'=',$date)
                ->where('orders.courier','LIKE','%'.$courier.'%')
                ->select('orders.packed_date','orders.order_number','customer.name as customer_name','client.name as client_name')
                ->get();

            $datas = array();
            foreach($raws as $raw){
                $datas[] = array(
                    $raw->order_number,
                    $raw->customer_name,
                    $raw->client_name,
                    null
                );
            }

            Excel::create('courier-check-packing-'.date('Ymd',strtotime($date)), function ($excel) use ($datas, $date, $courier) {
                $excel->sheet('Orders', function ($sheet) use ($datas, $date, $courier) {
                    
                    $sheet->setWidth('A', 30);
                    $sheet->setHeight(1, 40);
                    $sheet->setWidth('B', 30);
                    $sheet->setWidth('C', 30);
                    $sheet->setWidth('D', 30);

                    $image = new PHPExcel_Worksheet_Drawing;
                    $image->setPath(public_path('images/logo/logo-small.png')); //your image path
                    $image->setCoordinates('A1');
                    $image->setWorksheet($sheet);

                    $sheet->row(3, array(
                         'Tanggal:', date('d M Y',strtotime($date))
                    ));

                    $sheet->row(4, array(
                         'Kurir:', $courier
                    ));

                    $sheet->row(5, array('','','',''));

                    $sheet->row(6, array(
                        'No Order', 'Nama Customer', 'Client', 'TTD Kurir'
                    ));

                    $sheet->cells('A6:D6', function($cells) {
                        $cells->setValignment('center');
                        $cells->setAlignment('center');
                        $cells->setFontWeight('bold');
                    });

                    $sheet->rows(
                        $datas
                    );

                });
            })->download();
        }
    }

    public function courierCheckShipped(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'checking_date' => 'required',
            'courier' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect('/outbound/done')
                        ->withErrors($validator)
                        ->withInput();
        } else {
            $date = $request->input('checking_date');
            $courier = $request->input('courier');

            $raws = DB::table('orders')
                ->join('client','client.id','=','orders.client_id')
                ->join('customer','customer.id','=','orders.customer_id')
                ->where(DB::raw('DATE(orders.shipment_date)'),'=',$date)
                ->where('orders.courier','LIKE','%'.$courier.'%')
                ->select('orders.packed_date','orders.order_number','customer.name as customer_name','client.name as client_name')
                ->get();

            $datas = array();
            foreach($raws as $raw){
                $datas[] = array(
                    $raw->order_number,
                    $raw->customer_name,
                    $raw->client_name,
                    null
                );
            }

            Excel::create('courier-check-shipment-'.date('Ymd',strtotime($date)), function ($excel) use ($datas, $date, $courier) {
                $excel->sheet('Orders', function ($sheet) use ($datas, $date, $courier) {
                    
                    $sheet->setWidth('A', 30);
                    $sheet->setHeight(1, 40);
                    $sheet->setWidth('B', 30);
                    $sheet->setWidth('C', 30);
                    $sheet->setWidth('D', 30);

                    $image = new PHPExcel_Worksheet_Drawing;
                    $image->setPath(public_path('images/logo/logo-small.png')); //your image path
                    $image->setCoordinates('A1');
                    $image->setWorksheet($sheet);

                    $sheet->row(3, array(
                         'Tanggal:', date('d M Y',strtotime($date))
                    ));

                    $sheet->row(4, array(
                         'Kurir:', $courier
                    ));

                    $sheet->row(5, array('','','',''));

                    $sheet->row(6, array(
                        'No Order', 'Nama Customer', 'Client', 'TTD Kurir'
                    ));

                    $sheet->cells('A6:D6', function($cells) {
                        $cells->setValignment('center');
                        $cells->setAlignment('center');
                        $cells->setFontWeight('bold');
                    });

                    $sheet->rows(
                        $datas
                    );

                });
            })->download();
        }
    }
}
