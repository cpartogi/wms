<div style="display:block;">
    <div style="display:inline-block;width:60%;float:left;">
        <img src="{{ asset('images/logo/logo.png') }}" width="150"/>
    </div>
    <div style="display:inline-block;width:40%;float:right;text-align:right;">
        <h1 style="font-family: 'Raleway', sans-serif;color:#1a40ba;">Inbound Report</h1>
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
		    <div style="display:inline-block;width:85%;float:left;"> : {{ $batch->client_name }}</div>
		</div>

		<div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:10px;">
		    <div style="display:inline-block;width:15%;float:left;">Address </div>
		    <div style="display:inline-block;width:85%;float:left;"> : {{ $batch->client_address }}</div>
		</div>
    </div>
    <div style="display:inline-block;width:35%;float:right;">
        <div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:100px;">
		    <div style="display:inline-block;width:40%;float:left;">Date </div>
		    <div style="display:inline-block;width:60%;float:left;"> : {{ date('d M Y') }}</div>
		</div>

		<div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:10px;">
		    <div style="display:inline-block;width:40%;float:left;">Delivery </div>
		    <div style="display:inline-block;width:60%;float:left;"> : {{ $batch->courier }}</div>
		</div>

		<div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:10px;">
		    <div style="display:inline-block;width:40%;float:left;">By </div>
		    <div style="display:inline-block;width:60%;float:left;"> : {{ $batch->sender_name }}</div>
		</div>

		<div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:10px;">
		    <div style="display:inline-block;width:40%;float:left;">Cost </div>
		    <div style="display:inline-block;width:60%;float:left;"> : Rp {{ $batch->shipping_cost }}</div>
		</div>
    </div>
</div>

<table style="border:2px solid #000000;width:100%;" cellpadding="5">
    <thead>
        <tr>
            <th width="15%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;background-color:#1a40ba;color:#ffffff;" rowspan="2">No.</th>
            <th width="40%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;background-color:#1a40ba;color:#ffffff;" rowspan="2">Description</th>
            <th width="15%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;background-color:#1a40ba;color:#ffffff;" rowspan="2">Size</th>
            <th width="30%" style="border-bottom:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;background-color:#1a40ba;color:#ffffff;" colspan="3">Quantity</th>
        </tr>
        <tr>
        	<th width="10%" style="border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;border-right:1px solid #000000;background-color:#1a40ba;color:#ffffff;">Stated</th>
        	<th width="10%" style="border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;border-right:1px solid #000000;background-color:#1a40ba;color:#ffffff;">Actual</th>
        	<th width="10%" style="border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;background-color:#1a40ba;color:#ffffff;">Reject</th>
        </tr>
    </thead>
    <tbody>
    	@php
    		$stated = 0;
    		$actual = 0;
    		$reject = 0;
    		$i = 0;
    	@endphp
    	@foreach($variants as $key => $val)
        <tr>
            <td width="15%" style="border-right:1px solid #000000;border-bottom:1px solid #000000;font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align: center;">{{ $i + 1 }}</td>
            <td width="40%" style="border-right:1px solid #000000;border-bottom:1px solid #000000;font-family: 'Raleway', sans-serif;font-size:14px;display:block;">{{ $val['product_name'] }}</td>
            <td width="15%" style="border-right:1px solid #000000;border-bottom:1px solid #000000;font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align: center;">{{ $val['size_name'] }}</td>
            <td width="10%" style="font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align: center;border-right:1px solid #000000;border-bottom:1px solid #000000;">{{ $val['stated'] }}</td>
            <td width="10%" style="font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align: center;border-right:1px solid #000000;border-bottom:1px solid #000000;">{{ $val['actual'] }}</td>
            <td width="10%" style="font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align: center;border-bottom:1px solid #000000;">{{ $val['reject'] }}</td>
            @php
            	$stated += $val['stated'];
            	$actual += $val['actual'];
            	$reject += $val['reject'];
            	$i++;
            @endphp
        </tr>
        @endforeach
        <tr>
            <td width="15%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align: center;">&nbsp;</td>
            <td width="40%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align:center;">Total</td>
            <td width="15%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align: center;">&nbsp;</td>
            <td width="10%" style="font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align: center;border-right:1px solid #000000;">{{ $stated }}</td>
            <td width="10%" style="font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align: center;border-right:1px solid #000000;">{{ $actual }}</td>
            <td width="10%" style="font-family: 'Raleway', sans-serif;font-size:14px;display:block;text-align: center;">{{ $reject }}</td>
        </tr>
    </tbody>
</table>

<div style="font-family: 'Raleway', sans-serif;display:block;margin-top:50px;">
    <div style="display:inline-block;float:left;color:#1a40ba;">
        <p>This is an automatically generated report from Pakdé WMS, please kindly inform us if there is discrepancy within 1 day by replying the email</p>
    </div>
</div>