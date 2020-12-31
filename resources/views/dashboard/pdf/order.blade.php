<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>
<body class="WMS-O">
    <div style="display:block;font-size:12px;width:100%;">
        <div class="row header">
            <div style="width:40%;float:left;font-size:18px;">
                @{{if not .Params.Merchant.ImagePath}}
                    @{{.Params.Consigner.Name }}
                @{{else}}
                    <img src="@{{.Params.Merchant.ImagePath}}" width="74%"/>
                @{{end}}
            </div>

            <div style="width:60%;float:right;">
                <div style="display:block;text-align:right;margin-right:-15px;">
                    <img src="@{{.Params.ShowBarcode.SecondBarcode.Path}}"/>
                </div>

                <div style="display:block;text-align:right;margin-right:-5px;font-size:18px;">
                    @{{.Params.ShowBarcode.SecondBarcode.Value}}
                </div>
            </div>
        </div>
        <div style="display:block;text-align:right;font-size:18px;margin-top:20px">
            @{{.Params.Order.CreationDate}}
        </div>
        <div style="display:block;">
            <div style="width:80%;float:left;font-size:18px;">Order: @{{.Params.Order.OrderID.Value}}</div>
            <div style="width:20%;font-weight:bold;font-size:18px;text-align:right;float:right;">@{{.Params.Courier.Name}}</div>
        </div>
        <br/>
        <br/>
        <br/>
        <div style="display:block;font-size:18px;">Ship To:</div>
        <div style="display:block;">
            <div style="display:block;font-size:18px;">@{{.Params.Consignee.Name}}</div>
            <div style="display:block;font-size:18px;">@{{.Params.Consignee.Address}}</div>
            <div style="display:block;font-size:18px;">Kode Pos: <br/>@{{.Params.Consignee.PostCode}}</div>
            <div style="display:block;font-size:18px;">@{{.Params.Consignee.Phone}}</div>
        </div>
        <div style="display:block;font-size:18px;">* @{{.Params.Order.Notes}}</div>
    </div>

    <div style="position:absolute;bottom:0;width:100%;">
        <div style="float:left;">
            <img src="@{{.Params.Order.OrderID.QrPath}}" width="45%"/>
        </div>
        <div style="text-align:right;float:right;">
            <p style="color:#1a40ba;margin-right:10px;font-size:18px;">Fulfilled By:</p>
            <img style="margin-right:10px;" src="@{{.Logo}}" width="150"/>
        </div>
    </div>
</body>

<style>
    * {
	  box-sizing: border-box;
	}
    .WMS-O {
        background: white;
        display: block;
        width: 10.0cm;
        height: 13.20cm;
    }
    .row:after {
        content: "";
        display: table;
        clear: both;
    }
    .header{
        height:80px;
        width:100%;
    }
</style>