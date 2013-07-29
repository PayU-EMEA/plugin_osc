<?php

/*
    ver. 1.0.5
    PayU Account Payment plugin for osCommerce 2.3.1

    @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
    @copyright  Copyright (c) 2012 PayU
    http://www.payu.com
*/

include_once 'top.php';

$payu_session_id = '';
$payu_code = '';

// Check is payu_session registered
if (tep_session_is_registered('payu_session')) {
    $payu_code = $_GET['code'];

    if (!tep_session_is_registered('payu_session')) {
        $payu_session = array();
        tep_session_register('payu_session');
    }

    if (!empty($payu_session['session_id'])) {

        $url = tep_href_link("ext/modules/payment/payu/auth.php", NULL, 'SSL') . '%3Fpayu_session_id=' . $payu_session['session_id'];

        // if code is empty, throw Exception
        if (empty($payu_code)) {
            Throw new Exception("Error: Not set - PayU Code param");
        }

        $result = OpenPayU_OAuth::accessTokenByCode($payu_code, $url);

        // If retrieve success, go to Summary page
        if ($result->getSuccess() == TRUE) {
            tep_redirect(OpenPayu_Configuration::getSummaryUrl() . '?sessionId=' . $payu_session['session_id'] . '&oauth_token=' . $result->getAccessToken());
            exit;
        } else {
            Throw new Exception("accessTokenByCode error: " . $result->getError());
        }
    } else {
        Throw new Exception("Error: Not set - sessionId");
    }
}