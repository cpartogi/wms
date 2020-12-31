<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Mail;
use DB;

use App\OrderDetail;
use App\Client;
use App\Customer;
use App\InboundLocation;

class SendOutboundEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $client;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $arr = array();

        $orders = DB::table('order_detail')
            ->select('order_detail.inbound_detail_id','order_detail.inbound_detail_location_id','orders.*')
            ->join('orders','orders.id','=','order_detail.orders_id')
            ->where('orders.client_id','=',$this->client->id)
            ->whereDate('orders.created_at',date('Y-m-d'))
            ->where('orders.status','SHIPPED')
            ->get();

        if(count($orders) > 0){
            foreach($orders as $key => $detail){
                $customer = Customer::find($detail->customer_id);
                if(!array_key_exists($detail->inbound_detail_id, $arr)){
                    // Get total of
                    $product_count = InboundLocation::where('inbound_detail_id',$detail->inbound_detail_id)
                        ->whereNull('order_detail_id')
                        ->whereNotNull('shelf_id')
                        ->count();

                    $order_count = OrderDetail::where('inbound_detail_id',$detail->inbound_detail_id)->where('orders_id',$detail->id)->count();

                    $pending_count = OrderDetail::where('inbound_detail_id',$detail->inbound_detail_id)->where('orders_id',$detail->id)->whereNull('inbound_detail_location_id')->count();

                    $product_detail = DB::table('product')
                        ->select('product.id as product_id','inbound_detail_location.id as inbound_location_id','product.name','inbound_detail.color','product_type_size.name as size_name')
                        ->leftJoin('inbound_detail','inbound_detail.product_id','=','product.id')
                        ->leftJoin('inbound_detail_location','inbound_detail_location.inbound_detail_id','=','inbound_detail.id')
                        ->leftJoin('product_type_size','product_type_size.id','=','inbound_detail.product_type_size_id')
                        ->where('inbound_detail.id','=',$detail->inbound_detail_id)
                        ->first();

                    $arr[$detail->inbound_detail_id] = array('date' => $detail->shipment_date, 'order_number' => $detail->order_number, 'product_name' => $product_detail->name, 'customer_name' => $customer->name, 'customer_address' => $customer->address, 'customer_phone' => $customer->phone,'color' => $product_detail->color, 'size' => $product_detail->size_name, 'count' => $order_count, 'total' => $product_count, 'pending' => $pending_count, 'status' => $detail->status, 'courier' => $detail->courier);
                }
            }

            $report_no = "PKD/".date('Ymd')."/ORDER/".$this->client->acronym;

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8', 
                'format' => [215, 280], 
                'orientation' => 'P',
                'setAutoTopMargin' => 'stretch',
                'autoMarginPadding' => 5
            ]);

            $mpdf->SetHTMLHeader(view('dashboard.pdf.header')->render());
            $mpdf->SetHTMLFooter(view('dashboard.pdf.footer')->render());

            $mpdf->WriteHTML(view('dashboard.pdf.outbound-report-bulk',[
                'orders' => $arr,
                'report_no' => $report_no,
                'client' => $this->client
            ])->render());

            $pdf_path = public_path()."/format/";
            
            $mpdf->Output($pdf_path.'outbound-report.pdf','F');

            $client = $this->client;

            $cc = array('kezia@clientname.co.id','tanaya@clientname.co.id','nicko.batubara@clientname.co.id','prawedhi.s@clientname.co.id','vicky.siregar@clientname.co.id','robi@clientname.co.id');
            Mail::send('emails.outbound-report', ['client' => $client->name], function($message) use ($client, $pdf_path){
                $pdf_path = public_path()."/format/";
                $message->to($client->email);
                $message->cc($cc);
                $message->subject('Outbound Report - '.$client->name.' - '.date('d M Y'));
                $message->from('outbound@clientname.co.id');
                $message->attach($pdf_path.'outbound-report.pdf', array(
                    'as' => 'outbound-report.pdf',
                    'mime' => 'application/pdf')
                );
            });
            unlink($pdf_path.'outbound-report.pdf');
        }
    }
}
