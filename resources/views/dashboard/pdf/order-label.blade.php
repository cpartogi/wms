<div style="display:block;font-size:12px;">
    <div style="display:block;height:80px;">
        <div style="display:inline-block;width:40%;float:left;">
            @if($label->logo_url != null)
                <img src="https://s3-ap-southeast-1.amazonaws.com/static-pakde/{{ str_replace(' ','+',$label->logo_url) }}" width="74%"/>
            @else
                {{ $label->client_name }}
            @endif
        </div>

        <div style="display:inline-block;width:60%;float:right;">
            <div style="display:block;text-align:right;margin-right:-15px;">
                <barcode code="{{ $label->awb_number }}" type="C128B" height="2" class="barcode" size="0.6" error="M" disableborder="0"/>
            </div>

            <div style="display:block;text-align:right;margin-right:-5px;">
                {{ $label->awb_number }}
            </div>
        </div>
    </div>
    <div style="display:block;text-align:right;">
        {{ date('d/m/Y') }}
    </div>
    <div style="display:block;">
        <div style="display:inline-block;width:80%;float:left;">Order: {{ $label->order_number }}</div>
        <div style="display:inline-block;width:20%;float:right;font-weight: bold;text-align:right;">{{ $label->courier }}</div>
    </div>
    <div style="display:block;">Ship To:</div>
    <div style="display:block;">
        <div style="display:block;">{{ $label->customer_name }}</div>
        <div style="display:block;">{{ $label->address }}</div>
        <div style="display:block;">Kode Pos: {{ $label->zip_code}}</div>
        <div style="display:block;">{{ $label->phone }}</div>
    </div>
    <div style="display:block;">* {{ $label->notes }}</div>
</div>