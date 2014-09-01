<?php

/*
    ver. 1.0.5
    PayU Account Payment plugin for osCommerce 2.3.1

    @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
    @copyright  Copyright (c) 2012 PayU
    http://www.payu.com
*/

include_once 'top.php';

if (!empty($_POST['DOCUMENT'])) {
    $xml = stripslashes($_POST['DOCUMENT']);

    $result = OpenPayU_Order::consumeMessage($xml);
    if ($result->getMessage() == 'ShippingCostRetrieveRequest') {
        header("Content-Type:text/xml");
        echo $payu_account->get_shipping_cost_recalc($result->getSessionId(), $result->getCountryCode(), $result->getReqId());
    }
}