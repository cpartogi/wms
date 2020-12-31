<?php
    return [

        /*
        |--------------------------------------------------------------------------
        | Order Status
        |--------------------------------------------------------------------------
        |
        | Constants and label for order status
        */
        
        'order_status' => [
            'PENDING' => 'Pending',
            'CANCELED' => 'Canceled',
            'READY_FOR_OUTBOUND' => 'Ready to Outbound',
            'READY_TO_PACK' => 'Ready to Pack',
            'AWAITING_FOR_SHIPMENT' => 'Await Shipment',
            'SHIPPED' => 'Shipped',
            'INSUFFICIENT_STOCK' => 'Insufficient Stock',
        ],

        /*
        |--------------------------------------------------------------------------
        | Pakde Order Status
        |--------------------------------------------------------------------------
        |
        | Constants and label for order status
        */
        
        'pakde_order_status' => [
            'PENDING' => 'PENDING',
            'CANCELED' => 'CANCELED',
            'READY_FOR_OUTBOUND' => 'READY_FOR_OUTBOUND',
            'READY_TO_PACK' => 'READY_TO_PACK',
            'AWAITING_FOR_SHIPMENT' => 'AWAITING_FOR_SHIPMENT',
            'SHIPPED' => 'SHIPPED',
        ],

        /*
        |--------------------------------------------------------------------------
        | Pakde Order Issue Status
        |--------------------------------------------------------------------------
        |
        | Constants and label for order issue status
        */
        
        'order_issue_status' => [
            '200' => 'SUCCESS',
            '500' => 'FAILED',
            '509' => 'ON PROGRESS',
        ],

        /*
        |--------------------------------------------------------------------------
        | Partners Info
        |--------------------------------------------------------------------------
        |
        | Partners Information
        */
        
        'partners' => [
            'jubelio' => [
                'ID' => 1,
                'NAME' => 'Jubelio',
                'URL_CREATE_INVOICE' => '/v1/omnichannel/salesorder/invoice/',
                'URL_UPDATE_PICKLIST_STATUS' => '/v1/omnichannel/salesorder/picklist/',
                'URL_OMNICHANNEL_INTEGRATION' => '/v1/omnichannel/integration',
                'URL_BILL_PURCHASEORDER' => '/v1/omnichannel/purchaseorder/bill',
                'URL_STOCK_ADJUSTMENT' => '/v1/omnichannel/stock/adjust',
                'URL_GET_ALL_PRODUCT' => '/v1/omnichannel/product/sync',
                'URL_SAVE_MARKET_PLACE_AWB' => '/v1/omnichannel/salesorder/airwaybill/save',
                'URL_APPROVE_PENDING_ORDER' => '/v1/omnichannel/salesorder/approve',
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Pakde Internal Services
        |--------------------------------------------------------------------------
        |
        | Constant for Pakde microservices endpoints
        */
        'internal_services' => [
            'omnichannel' => [
                'URL_SALESORDER_UPDATE_AWB' => '/v1/omnichannel/salesorder/airwaybill/',
                'URL_SALESORDER_BULK_UPDATE_AWB' => '/v1/omnichannel/salesorder/airwaybill/%d/%d/batch',
                'URL_ORDER_ISSUE_LIST' => '/v1/omnichannel/salesorder/issue/list',
                'URL_RETRY_ORDER_VALIDATION' => '/v1/omnichannel/salesorder/issue/retry',
                'URL_ORDER_ISSUE_DETAIL' => '/v1/omnichannel/salesorder/issue/detail/%s/%s'
            ]
        ],
        /*
        |--------------------------------------------------------------------------
        | Label Type
        |--------------------------------------------------------------------------
        |
        | Constant for printing label type
        */

        "label" => [
            "ORDER" => "ORDER",
            "PRODUCT" => "PRODUCT",
            "INBOUND"=> "INBOUND",

            'PAGE_SIZE_ORDER' => 'WMS-O',
            'PAGE_SIZE_INBOUND' => 'I5D',
            'LOGO' => 'wms',

            'CREATE_LABEL_TYPE' => '/v1/labeltype',
            'CREATE_TEMPLATE' => '/v1/label/template',
            'CREATE_LABEL' => '/v1/label'
        ]     
    ];