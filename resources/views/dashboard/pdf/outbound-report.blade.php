<div style="display:block;">
    <div style="display:inline-block;width:40%;float:left;text-align:left">
        <h1 style="font-family: 'Raleway', sans-serif;color:#1a40ba;">Outbound Report</h1>
    </div>
</div>

<div style="display:block;">
	<div style="display:inline-block;width:65%;float:left;">
        <div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:10px;">
		    <div style="display:inline-block;width:15%;float:left;">No</div>
		    <div style="display:inline-block;width:85%;float:left;"> : {{ $report_no }}</div>
		</div>

		<div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:10px;">
		    <div style="display:inline-block;width:15%;float:left;">Client </div>
		    <div style="display:inline-block;width:85%;float:left;"> : {{ $client->name }}</div>
		</div>

		<div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:10px;">
		    <div style="display:inline-block;width:15%;float:left;">Address </div>
		    <div style="display:inline-block;width:85%;float:left;"> : {{ $client->address }}</div>
		</div>
    </div>
</div>

<table style="border:2px solid #000000;width:100%;margin-top:50px;" cellpadding="5">
    <thead>
        <tr>
            <th width="10%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">Date</th>
            <th width="10%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">Order No.</th>
            <th width="30%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">Customer</th>
            <th width="20%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">Items</th>
            <th width="10%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">Quantity</th>
            <th width="10%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">Courier</th>
            <th width="10%" style="border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($orders as $key => $val)
        @if($key < count($orders))
        <tr>
            <td width="10%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ date('d M Y',strtotime($val['date'])) }}</td>
            <td width="10%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ $val['order_number'] }}</td>
            <td width="30%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;color:#1a40ba;">{{ $val['customer_name'] }}<br>{{ $val['customer_address'] }}<br>{{ $val['customer_phone'] }}</td>
            <td width="20%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ $val['product_name'] }}</td>
            <td width="10%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ $val['count'] }}</td>
            <td width="10%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ $val['courier'] }}</td>
            <td width="10%" style="border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ $val['status'] }}</td>
        </tr>
        @else
        <tr>
            <td width="10%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ date('d M Y',strtotime($val['date'])) }}</td>
            <td width="10%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ $val['order_number'] }}</td>
            <td width="30%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;color:#1a40ba;">{{ $val['customer_name'] }}<br>{{ $val['customer_address'] }}<br>{{ $val['customer_phone'] }}</td>
            <td width="20%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ $val['product_name'] }}</td>
            <td width="10%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ $val['count'] }}</td>
            <td width="10%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ $val['courier'] }}</td>
            <td width="10%" style="font-family: 'Raleway', sans-serif;display:block;text-align: center;color:#1a40ba;">{{ $val['status'] }}</td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>