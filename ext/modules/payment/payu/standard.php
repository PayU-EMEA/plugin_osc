<?php

	/*
		ver. 1.0.3
		PayU Account Payment plugin for osCommerce 2.3.1
		
		@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
		@copyright  Copyright (c) 2012 PayU
		http://www.payu.com
	*/

	include_once 'top.php';

	// Check is cart and is empty
	if (!tep_session_is_registered('cartID')) tep_session_register('cartID');
		$cartID = $cart->cartID;
		
	if ($cart->count_contents() < 1 || !$payu_account->check() || !$payu_account->enabled)
	{
		tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
	}
	
	include(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT_PROCESS);
	
	require(DIR_WS_CLASSES . 'order.php');
	
	// Create order object
	$order = new order;
	$order->info['cart_flow'] = true;
	$order->info['order_status'] = $payu_account->order_status;
	$payu_account->order = $order;
	
	// Stock Check
	$any_out_of_stock = false;
	if (STOCK_CHECK == 'true')
	{
		$sizeof = sizeof($order->products);
		for ($i=0, $n=$sizeof; $i<$n; $i++)
		{
			if (tep_check_stock($order->products[$i]['id'], $order->products[$i]['qty']))
			{
				$any_out_of_stock = true;
			}
		}
		// Out of Stock
		if ( (STOCK_ALLOW_CHECKOUT != 'true') && ($any_out_of_stock == true) )
		{
			tep_redirect(tep_href_link(FILENAME_SHOPPING_CART));
		}
	}
	
	require(DIR_WS_CLASSES . 'order_total.php');
	$order_total_modules = new order_total;
	
	$order_totals = $order_total_modules->process();

	// load the before_process function from the payment modules
	$payu_account->before_process();
	
	//create order in db
	include 'order_create.php';
	
	// load the after_process function from the payment modules
	$payu_account->after_process();