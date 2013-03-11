<?php

	/*
		ver. 1.0.3
		PayU Account Payment plugin for osCommerce 2.3.1
		
		@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
		@copyright  Copyright (c) 2012 PayU
		http://www.payu.com
	*/

	include_once 'top.php';
	
	$session_id = urldecode($_GET['payu_session_id']);
	if(!empty($session_id))
	{
		$orders_id = $payu_account->get_order_id_by_session($session_id);
		if($orders_id)
		{	
			tep_redirect(tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $orders_id));
			exit;
		}
	}
	
	tep_redirect(tep_href_link(FILENAME_ACCOUNT_HISTORY, '', 'SSL'));

?>