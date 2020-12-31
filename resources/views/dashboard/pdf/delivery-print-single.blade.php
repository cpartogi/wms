<div style="display:block;">
    <div style="display:inline-block;width:60%;float:left;">
        <img src="{{ asset('images/logo/logo.png') }}" width="120"/>
    </div>
    <div style="display:inline-block;width:40%;float:right;text-align:right;">
        <h2 style="font-family: 'Raleway', sans-serif;color:#1a40ba;">Delivery Order</h2>
    </div>
</div>

<div style="display:block;">
	<div style="display:inline-block;width:48%;float:left;font-size:12px;">
		<div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:5px;">
		    <div style="display:inline-block;width:20%;float:left;">Customer </div>
		    <div style="display:inline-block;width:80%;float:left;"> : {{ $customer->name }}</div>
		</div>
        <div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:5px;">
            <div style="display:inline-block;width:20%;float:left;">Phone </div>
            <div style="display:inline-block;width:80%;float:left;"> : {{ $customer->phone }}</div>
        </div>
		<div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:5px;">
		    <div style="display:inline-block;width:20%;float:left;">Address </div>
		    <div style="display:inline-block;width:80%;float:left;text-align:justify;"> : {{ $customer->address }}</div>
		</div>
    </div>
    <div style="display:inline-block;width:48%;float:right;font-size:12px;">
        <div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:5px;">
            <div style="display:inline-block;width:20%;float:left;">Order No.</div>
            <div style="display:inline-block;width:80%;float:left;"> : {{ $order->order_number }}</div>
        </div>
        <div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:5px;">
		    <div style="display:inline-block;width:20%;float:left;">Date </div>
		    <div style="display:inline-block;width:80%;float:left;"> : {{ date('d M Y') }}</div>
		</div>
		<div style="display:block;font-family: 'Raleway', sans-serif;margin-bottom:5px;">
		    <div style="display:inline-block;width:20%;float:left;">Courier </div>
		    <div style="display:inline-block;width:80%;float:left;"> : {{ $order->courier }}</div>
		</div>
    </div>
</div>

<table style="border:2px solid #000000;width:100%;margin-top:10px;font-size:12px;">
    <thead>
        <tr>
            <th width="15%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;background-color:#1a40ba;color:#ffffff;">No.</th>
            <th width="40%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;background-color:#1a40ba;color:#ffffff;">Description</th>
            <th width="20%" style="border-right:1px solid #000000;border-bottom:2px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;background-color:#1a40ba;color:#ffffff;">Quantity</th>
            <th width="25%" style="border-bottom:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;background-color:#1a40ba;color:#ffffff;">Notes</th>
        </tr>
    </thead>
    <tbody>
        @php
            $i = 0;
        @endphp
        @foreach($things as $key => $val)
        @if($i < count($things) - 1)
            <tr>
                <td width="15%" style="border-right:1px solid #000000;border-bottom:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;">{{ $i + 1 }}</td>
                <td width="40%" style="border-right:1px solid #000000;border-bottom:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;">{{ $val->name }}</td>
                <td width="20%" style="font-family: 'Raleway', sans-serif;display:block;text-align: center;border-right:1px solid #000000;border-bottom:1px solid #000000;">{{ $val->qty }}</td>
                <td width="25%" style="border-bottom:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;">{{ $val->color }}</td>
            </tr>
        @else
            <tr>
                <td width="15%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;text-align: center;">{{ $i + 1 }}</td>
                <td width="40%" style="border-right:1px solid #000000;font-family: 'Raleway', sans-serif;display:block;">{{ $val->name }}</td>
                <td width="20%" style="font-family: 'Raleway', sans-serif;display:block;text-align: center;border-right:1px solid #000000;">{{ $val->qty }}</td>
                <td width="25%" style="font-family: 'Raleway', sans-serif;display:block;text-align: center;">{{ $val->color }}</td>
            </tr>
        @endif
        @php
            $i++;
        @endphp
        @endforeach
    </tbody>
</table>

<div style="font-family: 'Raleway', sans-serif;display:block;margin-top:20px;font-size:12px;padding:5px;border:2px solid #000000;">
    <div style="display:inline-block;float:left;width:48%;">
        <p><strong>Given by:</strong></p>
        <div style="clear:both;display:block;height:50px;"></div>
        <hr>
        <p><strong>Name:</strong></p>
        <p><strong>Date:</strong></p>
    </div>
    <div style="display:inline-block;float:left;width:48%;margin-left:5px;">
        <p><strong>Received by:</strong></p>
        <div style="clear:both;display:block;height:50px;"></div>
        <hr>
        <p><strong>Name:</strong></p>
        <p><strong>Date:</strong></p>
    </div>
</div>