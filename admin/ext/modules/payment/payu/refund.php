<?php
global $order;
chdir('../../../../../');

require_once 'includes/application_top.php';

include(DIR_WS_CLASSES . 'order.php');

// Load PayU Module
require_once 'includes/modules/payment/payu_account.php';
require_once DIR_WS_LANGUAGES . $language . '/modules/payment/payu_account.php';

// Create Instance
$payu_account = new payu_account();

$order = new order($_GET['oID']);
$total = 0;
for ($i=0, $n=sizeof($order->products); $i<$n; $i++) {
	$total += round(tep_add_tax($order->products[$i]['final_price'], $order->products[$i]['tax'], true) * $order->products[$i]['qty'] * 100);
}
 try {

	$refund = OpenPayU_Refund::create(
			$payu_account->get_session_id_by_order_id($_GET['oID']),
			'PayU Refund',
			round($order->info['total']*100)
	);

	if($refund->getStatus() == 'SUCCESS')
		$refund_error = 'false';
	else{
		$refund_error = 'true';
	}

} catch (OpenPayU_Exception $e) {
	$refund_error = 'true';
}

tep_redirect(tep_href_link('admin/orders.php?oID='.$_GET['oID'].'&action=edit&refund_error=' . $refund_error, tep_get_all_get_params(array('oID', 'action'))));