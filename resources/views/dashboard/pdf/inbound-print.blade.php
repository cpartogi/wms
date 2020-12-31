@foreach($barcodes as $barcode)
<div style="display:block;font-size:9px;font-family: 'Raleway', sans-serif;">
	<div style="display:inline-block;width:35%;float:left;text-align:center;"><barcode code="{{ $barcode->qrcode }}" type="QR" class="barcode" size="0.6" error="M" disableborder="1"/></div>
	<div style="display:inline-block;width:65%;float:left;">
		<div style="margin-bottom:2px;">
		    <div style="display:inline-block;width:20%;float:left;">Batch </div>
		    <div style="display:inline-block;width:75%;float:left;"> : {{ '#'.str_pad($barcode->id,5,'0',STR_PAD_LEFT) }}</div>
		</div>
		<div style="margin-bottom:2px;">
		    <div style="display:inline-block;width:20%;float:left;">Item </div>
		    <div style="display:inline-block;width:75%;float:left;"> : {{ $barcode->product_name }} ({{ $barcode->size_name }})</div>
		</div>
		<!-- <div style="margin-bottom:2px;">
		    <div style="display:inline-block;width:20%;float:left;">Price </div>
		    <div style="display:inline-block;width:75%;float:left;"> : Rp. {{ $barcode->price }}</div>
		</div> -->
		<div style="margin-bottom:2px;">
		    <div style="display:inline-block;width:100%;float:left;">{{ $barcode->qrcode }}</div>
		</div>
	</div>
</div>
@endforeach