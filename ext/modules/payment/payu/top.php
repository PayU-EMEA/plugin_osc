<?php

	/*
		ver. 1.0.1
		PayU Account Payment plugin for osCommerce 2.3.1
		
		@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
		@copyright  Copyright (c) 2012 PayU
		http://www.payu.com
	*/

	error_reporting(0);
	ini_set('errors_display', 0);
	
	chdir('../../../../');
	require_once 'includes/application_top.php';
  
	// Load PayU Module
	require_once 'includes/modules/payment/payu_account.php';
	require_once DIR_WS_LANGUAGES . $language . '/modules/payment/payu_account.php';
	
	// Create Instance
	$payu_account = new payu_account();

?>