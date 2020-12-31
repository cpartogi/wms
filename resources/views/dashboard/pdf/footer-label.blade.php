<div>
    <div style="display:inline-block;width:40%;float:left;">
        <barcode code="{{ $label->order_number }}" type="QR" class="barcode" size="0.6" error="M" disableborder="1"/>
    </div>
    <div style="display:inline-block;width:60%;float:right;text-align:right;">
    	<p style="color:#1a40ba;">Fulfilled By:</p>
        <img src="{{ asset('images/logo/logo.png') }}" width="100"/>
    </div>
</div>