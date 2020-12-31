<div style="display:block;font-size:9px;font-family: 'Raleway', sans-serif;">
	<div style="display:inline-block;width:35%;float:left;text-align:center;"><barcode code="{{ $shelf->code }}" type="QR" class="barcode" size="0.6" error="M" disableborder="1"/></div>
	<div style="display:inline-block;width:65%;float:left;text-align:center;font-size:14px;padding-top:5px;">
		<h1 style="padding:0;margin:0;">{{ $shelf->name }}</h1>
	</div>
</div>