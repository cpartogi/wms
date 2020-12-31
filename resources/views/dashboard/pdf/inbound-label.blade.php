<!DOCTYPE html>
<html>
<head>
    <title>inbound</title>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
        }
        .container{
            width: 80mm;
            height: 25mm;
            word-wrap: break-word;
        }
        #barcode{
            float: left;
            height: 25mm;
            width: 30mm;
            margin-top: 3mm;
        }
        #description{
            float: left;
            height: 25mm;
            width: 45mm;
            margin-top: 3mm;
        }
        #barcode>img{
            height: 25mm;
            width: 25mm;
            margin-left: 2mm;
        }
        #description>span{
            display: inline-block;
            min-width: 8mm;
        }
    </style>
</head>
<body>
<div class="container">
    <div id="barcode">
        <img src="@{{.Params.Order.OrderID.QrPath}}">
    </div>
    <div id="description">
        <span><b>Batch</b></span> :  @{{ .Params.Inbound.ID }} </br>
        <span><b>Item</b></span> :  @{{ .Params.Inbound.ProductName }} (@{{ .Params.Inbound.SizeName }}) </br>
        <i> @{{ .Params.Inbound.Code }} </i>
    </div>
</div>
</body>
</html>