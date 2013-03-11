<?php

	/*
		ver. 1.0.3
		PayU Account Payment plugin for osCommerce 2.3.1
		
		@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
		@copyright  Copyright (c) 2012 PayU
		http://www.payu.com
	*/

	include_once 'top.php';
	
	$cart->reset(true);
	
	// Redirect to thank you page
	tep_redirect(tep_href_link(FILENAME_CHECKOUT_SUCCESS, '', 'SSL'));