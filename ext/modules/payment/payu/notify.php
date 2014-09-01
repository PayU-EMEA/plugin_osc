<?php

/*
    ver. 1.0.5
    PayU Account Payment plugin for osCommerce 2.3.1

    @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
    @copyright  Copyright (c) 2012 PayU
    http://www.payu.com
*/

include_once 'top.php';


$body = file_get_contents ( 'php://input' );
$data =  trim ( $body );

$result = OpenPayU_Order::consumeNotification ( $data );

$response = $result->getResponse();

if ($response->order->orderId) {
		
	$payu_account->update_order($response->order);
	$rsp = OpenPayU::buildOrderNotifyResponse ( $response->order->orderId );

	header("Content-Type: application/json");
	echo $rsp;
}