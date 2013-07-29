<?php

/*
    ver. 1.0.5
    PayU Account Payment plugin for osCommerce 2.3.1

    Copyright (c) 2012 PayU
    http://www.payu.com
*/

require_once 'payu/openpayu.php';

class payu_account
{
    public $code = '', $title = '', $description = '', $enabled = FALSE, $host = '', $response = null, $order_status = 0;
    private $session_id = '', $orders_id = 0;

    function payu_account()
    {
        global $order, $language;

        $this->signature = 'payu|payu_account|1.0.5|2.3.1';

        $this->code = 'payu_account';
        $this->title = MODULE_PAYMENT_PAYU_ACCOUNT_TEXT_TITLE;
        $this->public_title = MODULE_PAYMENT_PAYU_ACCOUNT_TEXT_PUBLIC_TITLE;
        $this->description = payu_version('1.0.5', '');
        $this->sort_order = MODULE_PAYMENT_PAYU_ACCOUNT_SORT_ORDER;

        $this->enabled = ((MODULE_PAYMENT_PAYU_ACCOUNT_STATUS == 'Yes') ? TRUE : FALSE);
        $this->show_in_cart = ((MODULE_PAYMENT_PAYU_ACCOUNT_SHOW_IN_CART == 'Yes') ? TRUE : FALSE);

        OpenPayU_Configuration::setEnvironment((MODULE_PAYMENT_PAYU_ACCOUNT_ENVIRONMENT == 'Sandbox') ? 'sandbox' : 'secure');
        OpenPayU_Configuration::setMerchantPosId(MODULE_PAYMENT_PAYU_ACCOUNT_POS_ID);
        OpenPayU_Configuration::setPosAuthKey(MODULE_PAYMENT_PAYU_ACCOUNT_POS_AUTH_KEY);
        OpenPayU_Configuration::setClientId(MODULE_PAYMENT_PAYU_ACCOUNT_POS_ID);
        OpenPayU_Configuration::setClientSecret(MODULE_PAYMENT_PAYU_ACCOUNT_CLIENT_SECRET);
        OpenPayU_Configuration::setSignatureKey(MODULE_PAYMENT_PAYU_ACCOUNT_SIGN_KEY);

        if ((int)MODULE_PAYMENT_PAYU_ACCOUNT_TRANSACTIONS_ORDER_STATUS_ID > 0) {
            $this->order_status = MODULE_PAYMENT_PAYU_ACCOUNT_TRANSACTIONS_ORDER_STATUS_ID;
        }

        $this->order = $order;
        $this->language = $language;
    }

    function keys()
    {
        return array(
            'MODULE_PAYMENT_PAYU_ACCOUNT_VERSION',
            'MODULE_PAYMENT_PAYU_ACCOUNT_STATUS',
            'MODULE_PAYMENT_PAYU_ACCOUNT_ENVIRONMENT',
            'MODULE_PAYMENT_PAYU_ACCOUNT_SHOW_IN_CART',

            'MODULE_PAYMENT_PAYU_ACCOUNT_POS_ID',
            'MODULE_PAYMENT_PAYU_ACCOUNT_CLIENT_SECRET',
            'MODULE_PAYMENT_PAYU_ACCOUNT_SIGN_KEY',
            'MODULE_PAYMENT_PAYU_ACCOUNT_POS_AUTH_KEY',

            'MODULE_PAYMENT_PAYU_ACCOUNT_TRANSACTIONS_ORDER_STATUS_ID',
            'MODULE_PAYMENT_PAYU_ACCOUNT_ZONE',
            'MODULE_PAYMENT_PAYU_ACCOUNT_SORT_ORDER',
            'MODULE_PAYMENT_PAYU_ACCOUNT_ORDER_VALIDITY_TIME',
            'MODULE_PAYMENT_PAYU_ACCOUNT_IMAGE_BUTTON'
        );
    }

    function addLog($text)
    {
        file_put_contents('payu.log', date('Y-m-d H:m:i') . ' - ' . $text . "\n", FILE_APPEND);
    }

    function check()
    {
        if (!isset($this->_check)) {
            $check_query = tep_db_query("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_PAYU_ACCOUNT_STATUS'");
            $this->_check = tep_db_num_rows($check_query);
        }
        return $this->_check;
    }

    function remove()
    {
        tep_db_query("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')");
    }

    function install()
    {
        $check_query = tep_db_query("SELECT orders_status_id FROM " . TABLE_ORDERS_STATUS . " WHERE orders_status_name = 'PayU Account [Transactions]' limit 1");

        if (tep_db_num_rows($check_query) < 1) {
            $status_query = tep_db_query("SELECT max(orders_status_id) as status_id FROM " . TABLE_ORDERS_STATUS);
            $status = tep_db_fetch_array($status_query);

            $status_id = $status['status_id'] + 1;

            $languages = tep_get_languages();

            foreach ($languages as $lang) {
                tep_db_query("INSERT INTO " . TABLE_ORDERS_STATUS . " (orders_status_id, language_id, orders_status_name) values ('" . $status_id . "', '" . $lang['id'] . "', 'PayU Account [Transactions]')");
            }

            $flags_query = tep_db_query("DESCRIBE " . TABLE_ORDERS_STATUS . " public_flag");
            if (tep_db_num_rows($flags_query) == 1) {
                tep_db_query("UPDATE " . TABLE_ORDERS_STATUS . " SET public_flag = 0 AND downloads_flag = 0 WHERE orders_status_id = '" . $status_id . "'");
            }
        } else {
            $check = tep_db_fetch_array($check_query);

            $status_id = $check['orders_status_id'];
        }

        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('PayU Account', 'MODULE_PAYMENT_PAYU_ACCOUNT_VERSION', '1.0.5', '', '6', '0', '', 'payu_version(', now())");

        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable payments with PayU Account', 'MODULE_PAYMENT_PAYU_ACCOUNT_STATUS', 'No', 'Do you want to accept payments with PayU Account?', '6', '1', 'tep_cfg_select_option(array(\'Yes\', \'No\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Point of sale id number (POS ID)', 'MODULE_PAYMENT_PAYU_ACCOUNT_POS_ID', '', 'OAuth protocol - client_id', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Point of sale authorization key (pos_auth_key)', 'MODULE_PAYMENT_PAYU_ACCOUNT_POS_AUTH_KEY', '', '', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Key (MD5)', 'MODULE_PAYMENT_PAYU_ACCOUNT_CLIENT_SECRET', '', 'OAuth protocol  - client_secret', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Second key (MD5)', 'MODULE_PAYMENT_PAYU_ACCOUNT_SIGN_KEY', '', 'Symmetric key to encrypt the comminication', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Select the mode of payments with PayU Account', 'MODULE_PAYMENT_PAYU_ACCOUNT_ENVIRONMENT', 'Sandbox', 'Which mode do you want to enable?', '6', '1', 'tep_cfg_select_option(array(\'Sandbox\', \'Live\'), ', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable PayU in the shopping cart', 'MODULE_PAYMENT_PAYU_ACCOUNT_SHOW_IN_CART', 'Yes', 'Do you want to enable PayU in the shopping cart?', '6', '1', 'tep_cfg_select_option(array(\'Yes\', \'No\'), ', now())");

        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_PAYU_ACCOUNT_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'tep_get_zone_class_title', 'tep_cfg_pull_down_zone_classes(', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_PAYU_ACCOUNT_SORT_ORDER', '0', 'Sort order of display. The lowest is displayed first.', '6', '0', now())");

        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('PayU transactions order status level', 'MODULE_PAYMENT_PAYU_ACCOUNT_TRANSACTIONS_ORDER_STATUS_ID', '" . $status_id . "', 'Include PayU transaction information in this order status level', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Validity time for orders initiated with PayU Account', 'MODULE_PAYMENT_PAYU_ACCOUNT_ORDER_VALIDITY_TIME', '60', 'Time in minutes', '6', '0', now())");
        tep_db_query("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('PayU Button', 'MODULE_PAYMENT_PAYU_ACCOUNT_IMAGE_BUTTON', '1', '', '6', '0', 'payu_button_image_draw', 'payu_image_selection(', now())");

        #add orders_payu table
        tep_db_query("CREATE TABLE IF NOT EXISTS `orders_payu` (`orders_id` INT( 11 ) UNSIGNED NOT NULL , `payu_session_id` VARCHAR( 255 ) NOT NULL, `shopping_cart` TINYINT( 1 ) UNSIGNED NOT NULL ,	INDEX ( `orders_id` ) , UNIQUE ( `payu_session_id` ), INDEX ( `shopping_cart` )) ENGINE = MYISAM;");

    }

    function get_error()
    {
        return false;
    }

    function javascript_validation()
    {
        return false;
    }

    function selection()
    {
        return array(
            'id' => $this->code,
            'module' => $this->public_title
        );
    }

    function pre_confirmation_check()
    {
        return false;
    }

    function confirmation()
    {
        return false;
    }

    function process_button()
    {
        return false;
    }

    function before_process()
    {
        global $payu_session, $order;

        $this->order = $order;
        $payu_session_id = $this->create_order();

        if (empty($payu_session_id)) {
            tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, 'error_message=' . MODULE_PAYMENT_PAYU_ACCOUNT_TEXT_ERROR_IN_CREATING_ORDER));
        }

        if (!is_array($payu_session)) {
            $payu_session = array();
        }

        $payu_session['session_id'] = $payu_session_id;
        $payu_session['shopping_cart'] = ($this->order->info['cart_flow']) ? 1 : 0;
        if (!tep_session_is_registered('payu_session')) {
            tep_session_register('payu_session');
        }
    }

    function after_process()
    {
        global $insert_id, $payu_session;

        $res = tep_db_query("INSERT INTO orders_payu (orders_id, payu_session_id, shopping_cart) VALUES('{$insert_id}', '" . mysql_real_escape_string($payu_session['session_id']) . "', '" . mysql_real_escape_string($payu_session['shopping_cart']) . "')");

        $language_query = tep_db_query("SELECT code FROM " . TABLE_LANGUAGES . " WHERE directory='" . tep_db_input($this->language) . "' ORDER BY sort_order LIMIT 1");
        $language = tep_db_fetch_array($language_query);

        $result = OpenPayU_OAuth::accessTokenByClientCredentials();
        header('Location: ' . OpenPayu_Configuration::getSummaryUrl() . '?sessionId=' . $payu_session['session_id'] . '&lang=' . strtolower($language['code']) . '&oauth_token=' . $result->getAccessToken());
    }

    function checkout_initialization_method()
    {
        global $cart;

        $string = '<a href="' . tep_href_link('ext/modules/payment/payu/standard.php', '', 'SSL') . '">' . payu_button_image_draw(MODULE_PAYMENT_PAYU_ACCOUNT_IMAGE_BUTTON) . '</a>';

        return $string;
    }

    /**
     * Return customer ip address
     * @access private
     */
    private function get_ip()
    {
        if ($_SERVER['REMOTE_ADDR'] == "::1" || $_SERVER['REMOTE_ADDR'] == "::" || !preg_match("/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m", $_SERVER['REMOTE_ADDR'])) {
            $ip = '127.0.0.1';
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Return products list for PayU order
     * @access private
     */
    private function get_products_list()
    {
        global $currencies, $cart;

        $products = array();
        $this->total_price = array();
        $i = 0;

        if ($cart->count_contents() > 0) {
            foreach ($cart->get_products() as $product) {
                $products[$i] = array();
                $products[$i]['ShoppingCartItem'] = array();
                $products[$i]['ShoppingCartItem']['Quantity'] = $product['quantity'];
                $products[$i]['ShoppingCartItem']['Product'] = array();
                $products[$i]['ShoppingCartItem']['Product']['Name'] = $product['name'];

                $products[$i]['ShoppingCartItem']['Product']['Discount'] = 0;

                $gross = $currencies->calculate_price($product['final_price'],
                        tep_get_tax_rate($product['tax_class_id']), 1) * 100;
                $nett = $product['price'] * 100;

                $item_price = array
                (
                    'Gross' => $gross,
                    'Net' => $nett,
                    'Tax' => $gross - $nett,
                    'CurrencyCode' => $this->order->info['currency']
                );

                $products[$i]['ShoppingCartItem']['Product']['UnitPrice'] = $item_price;

                $this->total_price['Gross'] += $item_price['Gross'] * $product['quantity'];
                $this->total_price['Net'] += $item_price['Net'];
                $this->total_price['Tax'] += $item_price['Tax'];
                $this->total_price['TaxRate'] = $product['tax'];
                $this->total_price['CurrencyCode'] = $this->order->info['currency'];

                $i++;
            }
        }
        return $products;
    }

    /**
     * Return shipping cost list for PayU order
     * @access private
     */
    private function get_shipping_list()
    {
        if ($this->order->content_type == 'physical') {
            $shipping_cost = array();
            $ship_to_other_countries = false;

            if (empty($this->order->delivery['country']['id'])) {
                $tmp = tep_get_countries(STORE_COUNTRY, true);
                $country = $tmp["countries_iso_code_2"];

                if (MODULE_PAYMENT_PAYU_ACCOUNT_ZONE == 0) {
                    $ship_to_other_countries = true;
                } else {
                    $check_query = tep_db_query("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_PAYU_ACCOUNT_ZONE);

                    $ship_to_other_countries = false;
                    while ($check = tep_db_fetch_array($check_query)) {
                        if ($check['country_id'] != STORE_COUNTRY) {
                            $ship_to_other_countries = true;
                        }
                    }
                }
            } else {
                $country = $this->order->delivery['country']['iso_code_2'];
                $ship_to_other_countries = false;
            }

            if (!empty($this->order->info['shipping_method'])) {
                $shipping_cost_list[0]['ShippingCost'] = array
                (
                    'Type' => $this->order->info['shipping_method'],
                    'CountryCode' => $country,
                    'Discount' => 0,
                    'Price' => array
                    (
                        'Net' => $this->order->info['shipping_cost'] * 100,
                        'Gross' => $this->order->info['shipping_cost'] * 100,
                        'Tax' => $this->order->info['shipping_cost'] * 100,
                        'CurrencyCode' => $this->order->info['currency']
                    )
                );
            } else {
                require(DIR_WS_CLASSES . 'shipping.php');

                if ($this->order->info['total'] >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER && $this->order->delivery['country']['countries_id'] == STORE_COUNTRY) {
                    include(DIR_WS_LANGUAGES . $this->language . '/modules/order_total/ot_shipping.php');

                    $shipping_cost_list[0]['ShippingCost'] = array
                    (
                        'Type' => FREE_SHIPPING_TITLE,
                        'CountryCode' => $country,
                        'Discount' => 0,
                        'Price' => array
                        (
                            'Net' => 0,
                            'Gross' => 0,
                            'Tax' => 0,
                            'CurrencyCode' => $this->order->info['currency']
                        )
                    );
                } else {
                    $shipping_modules = new shipping;
                    if ((tep_count_shipping_modules() > 0)) {
                        $list = $shipping_modules->quote();
                        $i = 0;

                        foreach ($list as $k => $v) {
                            foreach ($v['methods'] as $k2 => $v2) {
                                $shipping_cost_list[$i]['ShippingCost'] = array
                                (
                                    'Type' => $v['module'] . ' (' . $v2['title'] . ')',
                                    'CountryCode' => $country,
                                    'Discount' => 0,
                                    'Price' => array
                                    (
                                        'Net' => $v2['cost'] * 100,
                                        'Gross' => $v2['cost'] * 100,
                                        'Tax' => 0,
                                        'CurrencyCode' => $this->order->info['currency']
                                    )
                                );
                            }
                            $i++;
                        }
                    }
                }
            }

            $shipping_cost = array(
                'CountryCode' => $country,
                'ShipToOtherCountry' => ($ship_to_other_countries === FALSE) ? 0 : 1,
                'ShippingCostList' => $shipping_cost_list
            );

            return array(
                'AvailableShippingCost' => $shipping_cost,
                'ShippingCostsUpdateUrl' => tep_href_link('ext/modules/payment/payu/shipping_cost.php')
            );
        }
    }

    /**
     * Return customer data for PayU order
     * @access private
     */
    private function get_customer_data()
    {
        if (!empty($this->order->customer['firstname']) && !empty($this->order->customer['lastname'])) {
            if ($this->order->customer['email_address'])
                $customer = array(
                    'Email' => $this->order->customer['email_address'],
                    'Phone' => $this->order->customer['telephone'],
                    'FirstName' => $this->order->customer['firstname'],
                    'LastName' => $this->order->customer['lastname']
                );

            if (!empty($this->order->delivery['street_address'])) {
                $address = $this->parse_address($this->order->delivery['street_address']);
                $customer['Shipping'] = array
                (
                    'Street' => $address['street'],
                    'HouseNumber' => $address['houseNumber'],
                    'ApartmentNumber' => $address['apartmentNumber'],
                    'PostalCode' => $this->order->delivery['postcode'],
                    'City' => $this->order->delivery['city'],
                    'State' => $this->order->delivery['state'],
                    'CountryCode' => $this->order->delivery['country']['iso_code_2'],
                    'AddressType' => 'SHIPPING',
                    'RecipientName' => trim($this->order->delivery['company'] . ' ' . $this->order->delivery['firstname'] . ' ' . $this->order->delivery['lastname'])
                );
            }

            if (!empty($this->order->billing['street_address'])) {
                $address = $this->parse_address($this->order->billing['street_address']);
                $customer['Invoice'] = array
                (
                    'Street' => $address['street'],
                    'HouseNumber' => $address['houseNumber'],
                    'ApartmentNumber' => $address['apartmentNumber'],
                    'PostalCode' => $this->order->billing['postcode'],
                    'City' => $this->order->billing['city'],
                    'State' => $this->order->billing['state'],
                    'CountryCode' => $this->order->billing['country']['iso_code_2'],
                    'AddressType' => 'BILLING',
                    'RecipientName' => trim($this->order->billing['company'] . ' ' . $this->order->billing['firstname'] . ' ' . $this->order->billing['lastname'])
                );
            }

            return $customer;
        }
    }

    /**
     * Create and returns an array with the data order for PayU
     * @access private
     */
    private function create_order()
    {
        $this->session_id = md5(rand() * time());
        $this->req_id = md5(rand() * rand() * microtime(true));

        if (!empty($this->orders_id))
            $order_url = tep_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . (int)$this->orders_id);
        else
            $order_url = tep_href_link('ext/modules/payment/payu/redirect.php', 'payu_session_id=' . $this->session_id);

        $shopping_cart = $this->get_products_list();

        $order = array
        (
            'ReqId' => md5(rand() * time()),
            'CustomerIp' => $this->get_ip(),
            'NotifyUrl' => tep_href_link('ext/modules/payment/payu/notify.php'),
            'OrderCancelUrl' => tep_href_link('ext/modules/payment/payu/cancel.php'),
            'OrderCompleteUrl' => tep_href_link('ext/modules/payment/payu/complete.php'),
            'Order' => array
            (
                'MerchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
                'SessionId' => $this->session_id,
                'OrderUrl' => $order_url,
                'OrderCreateDate' => date('c'),
                'OrderDescription' => (trim($this->order->info['comments']) ? $this->order->info['comments'] : MODULE_PAYMENT_PAYU_ACCOUNT_TEXT_NO_DESCRIPTION),
                'ValidityTime' => ((int)MODULE_PAYMENT_PAYU_ACCOUNT_ORDER_VALIDITY_TIME > 0) ? (int)MODULE_PAYMENT_PAYU_ACCOUNT_ORDER_VALIDITY_TIME : 60,
                'OrderType' => $this->order->content_type == 'physical' ? 'MATERIAL' : 'VIRTUAL',
                'ShoppingCart' => array(
                    'GrandTotal' => $this->total_price['Gross'],
                    'CurrencyCode' => $this->order->info['currency'],
                    'ShoppingCartItems' => $shopping_cart
                ),
                'MerchantAuthorizationKey' => OpenPayU_Configuration::getPosAuthKey()
            )
        );

        #prepare customer data
        $customer = $this->get_customer_data();
        if (!empty($customer)) {
            $order['Customer'] = $customer;
        }

        #prepare shipping cost list
        $shipping_cost = $this->get_shipping_list();
        if (!empty($shipping_cost)) {
            $order['ShippingCost'] = $shipping_cost;
        }

        #create order in PayU system, return response
        $result = OpenPayU_Order::create($order);

        #if response success is true return session_id
        if ($result->getSuccess() == TRUE) {
            return $this->session_id;
        } else {
            $this->addLog($result->getError() . ' ' . $result->getMessage() . ' [request: ' . serialize($result->getRequest()) . ', response: ' . serialize($result->getResponse()) . ']');
            return null;
        }
    }

    /**
     * Parse street address string to array
     * @access private
     * @param string street
     */
    private function parse_address($street)
    {
        if (preg_match("/^(?P<street>.*) (?P<houseNumber>[0-9]+)\/(?P<apartmentNumber>[0-9]+)$/i", $street, $result)) {
            return array('street' => $result['street'], 'houseNumber' => $result['houseNumber'], 'apartmentNumber' => $result['apartmentNumber']);
        } else if (preg_match("/^(?P<street>.*) (?P<houseNumber>[0-9]+) ?m\.? ?(?P<apartmentNumber>[0-9]+)$/i", $street, $result)) {
            return array('street' => $result['street'], 'houseNumber' => $result['houseNumber'], 'apartmentNumber' => $result['apartmentNumber']);
        } else if (preg_match("/^(?P<street>.*) (?P<houseNumber>[0-9]+)$/i", $street, $result)) {
            return array('street' => $result['street'], 'houseNumber' => $result['houseNumber'], 'apartmentNumber' => '');
        } else {
            return array('street' => $street, 'houseNumber' => 0, 'apartmentNumber' => 0);
        }
    }

    /**
     * Get orders_id from orders_payu by payu_session_id
     * @access public
     * @param string payu_session_id
     */
    function get_order_id_by_session($payu_session_id)
    {
        $query = tep_db_query('SELECT orders_id FROM orders_payu WHERE payu_session_id="' . stripslashes($payu_session_id) . '"');
        $order_query = tep_db_fetch_array($query);

        if ($order_query['orders_id']) {
            return intval($order_query['orders_id']);
        }

        return null;
    }

    /**
     * Update orders data by order received from PayU System
     * @access public
     * @param xml ord
     */
    function update_order($ord)
    {
        global $order, $currencies;

        $ord = $ord['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse'];
        $payu_session_id = $ord['SessionId'];
        $orders_id = $this->get_order_id_by_session($payu_session_id);

        if (!empty($orders_id)) {
            require(DIR_WS_CLASSES . 'order.php');
            $order = new order($orders_id);

            $orders_status = (MODULE_PAYMENT_PAYU_ACCOUNT_TRANSACTIONS_ORDER_STATUS_ID > 0 ? (int)MODULE_PAYMENT_PAYU_ACCOUNT_TRANSACTIONS_ORDER_STATUS_ID : (int)DEFAULT_ORDERS_STATUS_ID);
            if ($order->info['status'] == 0 || MODULE_PAYMENT_PAYU_ACCOUNT_TRANSACTIONS_ORDER_STATUS_ID == $order->info['status']) {
                $order->info['status'] = (int)MODULE_PAYMENT_PAYU_ACCOUNT_TRANSACTIONS_ORDER_STATUS_ID;
            }

            $order->customer['name'] = trim($ord['CustomerRecord']['FirstName'] . ' ' . $ord['CustomerRecord']['LastName']);
            $order->customer['company'] = '';
            $order->customer['street_address'] = '';
            $order->customer['city'] = '';
            $order->customer['suburb'] = '';
            $order->customer['postcode'] = '';
            $order->customer['state'] = '';
            $order->customer['telephone'] = trim($ord['CustomerRecord']['Phone']);
            $order->customer['email_address'] = trim($ord['CustomerRecord']['Email']);

            # if client exist get customer_id
            $order->customer['customer_id'] = $this->get_customer_id_by_mail($order->customer['email_address']);

            $format_id = 1;
            $country_delivery = $this->get_country_title_by_iso_code_2($ord['Shipping']['Address']['CountryCode']);

            $order->delivery['name'] = trim($ord['Shipping']['Address']['RecipientName']);

            $order->delivery['company'] = '';
            $order->delivery['street_address'] = (trim($ord['Shipping']['Address']['Street'])) ? trim($ord['Shipping']['Address']['Street']) . ' ' . $ord['Shipping']['Address']['HouseNumber'] . ((!empty($ord['Shipping']['Address']['ApartmentNumber'])) ? '/' . $ord['Shipping']['Address']['ApartmentNumber'] : '') : '';
            $order->delivery['city'] = trim($ord['Shipping']['Address']['City']);
            $order->delivery['suburb'] = '';
            $order->delivery['postcode'] = trim($ord['Shipping']['Address']['PostalCode']);
            $order->delivery['state'] = trim($ord['Shipping']['Address']['State']);
            $order->delivery['country'] = $country_delivery;
            $order->delivery['format_id'] = $format_id;

            // if get empty delivery field
            if (empty($order->delivery['name'])) {
                $order->delivery['name'] = $order->customer['name'];
                $order->delivery['company'] = $order->customer['company'];
                $order->delivery['street_address'] = $order->customer['street_address'];
                $order->delivery['city'] = $order->customer['city'];
                $order->delivery['suburb'] = $order->customer['suburb'];
                $order->delivery['postcode'] = $order->customer['postcode'];
                $order->delivery['state'] = $order->customer['state'];
                $order->delivery['country'] = $country_delivery;
                $order->delivery['format_id'] = $format_id;
            }

            // If retrieve Billing data
            if (isset($ord['Invoice']['Billing'])) {
                $country_billing = $this->get_country_title_by_iso_code_2($ord['Invoice']['Billing']['CountryCode']);

                $order->billing['name'] = trim($ord['Invoice']['Billing']['RecipientName']);
                $order->billing['company'] = '';

                if (isset($ord['Invoice']['Billing']['Street'])) {
                    $order->billing['street_address'] = $ord['Invoice']['Billing']['Street'] . ' ' . $ord['Invoice']['Billing']['HouseNumber'];
                    if (isset($ord['Invoice']['Billing']['ApartmentNumber'])) {
                        $order->billing['street_address'] .= '/' . $ord['Invoice']['Billing']['ApartmentNumber'];
                    }
                }

                $order->billing['city'] = trim($ord['Invoice']['Billing']['City']);
                $order->billing['suburb'] = '';
                $order->billing['postcode'] = trim($ord['Invoice']['Billing']['PostalCode']);
                $order->billing['state'] = trim($ord['Invoice']['Billing']['State']);
                $order->billing['country'] = $country_billing;
                $order->billing['format_id'] = $format_id;
            } // If not retrieve assign the customer
            else {
                $order->billing['name'] = $order->customer['name'];
                $order->billing['company'] = $order->customer['company'];
                $order->billing['street_address'] = $order->customer['street_address'];
                $order->billing['city'] = $order->customer['city'];
                $order->billing['suburb'] = $order->customer['suburb'];
                $order->billing['postcode'] = $order->customer['postcode'];
                $order->billing['state'] = $order->customer['state'];
                $order->billing['country'] = $country_delivery;
                $order->billing['format_id'] = $format_id;
            }

            #update order data
            if ($ord['OrderStatus'] == 'ORDER_STATUS_PENDING') {
                $sql_data_array = array
                (
                    'customers_id' => $order->customer['customer_id'],
                    'customers_name' => $order->customer['name'],
                    'customers_company' => $order->customer['company'],
                    'customers_street_address' => $order->customer['street_address'],
                    'customers_suburb' => $order->customer['suburb'],
                    'customers_city' => $order->customer['city'],
                    'customers_postcode' => $order->customer['postcode'],
                    'customers_state' => $order->customer['state'],
                    'customers_country' => $order->customer['country']['title'],
                    'customers_telephone' => $order->customer['telephone'],
                    'customers_email_address' => $order->customer['email_address'],
                    'customers_address_format_id' => $order->customer['format_id'],
                    'delivery_name' => $order->delivery['name'],
                    'delivery_company' => $order->delivery['company'],
                    'delivery_street_address' => $order->delivery['street_address'],
                    'delivery_suburb' => $order->delivery['suburb'],
                    'delivery_city' => $order->delivery['city'],
                    'delivery_postcode' => $order->delivery['postcode'],
                    'delivery_state' => $order->delivery['state'],
                    'delivery_country' => $order->delivery['country']['title'],
                    'delivery_address_format_id' => $order->delivery['format_id'],
                    'billing_name' => $order->billing['name'],
                    'billing_company' => $order->billing['company'],
                    'billing_street_address' => $order->billing['street_address'],
                    'billing_suburb' => $order->billing['suburb'],
                    'billing_city' => $order->billing['city'],
                    'billing_postcode' => $order->billing['postcode'],
                    'billing_state' => $order->billing['state'],
                    'billing_country' => $order->billing['country']['title'],
                    'billing_address_format_id' => $order->billing['format_id'],
                    'payment_method' => $order->info['payment_method'],
                    'cc_type' => $order->info['cc_type'],
                    'cc_owner' => $order->info['cc_owner'],
                    'cc_number' => $order->info['cc_number'],
                    'cc_expires' => $order->info['cc_expires'],
                    'last_modified' => 'now()',
                    'date_purchased' => 'now()',
                    'orders_status' => $order->info['order_status'],
                    'currency' => $order->info['currency'],
                    'currency_value' => $order->info['currency_value']
                );

                tep_db_perform(TABLE_ORDERS, $sql_data_array, 'update', 'orders_id=' . $orders_id);
            }
            #

            $order->info['shipping_method'] = $ord['Shipping']['ShippingType'];

            //if(!empty($ord['PaidAmount']))
            {
                require(DIR_WS_CLASSES . 'order_total.php');
                $order_total_modules = new order_total;

                // Remove orders shipping info
                tep_db_query('DELETE FROM ' . TABLE_ORDERS_TOTAL . ' WHERE orders_id = ' . tep_db_input($orders_id));

                // Add orders shipping info
                $order_totals = $order_total_modules->process();
                $sizeof = sizeof($order_totals);
                for ($i = 0, $n = $sizeof; $i < $n; $i++) {
                    if ($order_totals[$i]['code'] == 'ot_shipping') {
                        $order_totals[$i]['title'] = $order->info['shipping_method'];
                        $order_totals[$i]['value'] = ($ord['Shipping']['ShippingCost']['Gross'] / 100);
                        $order_totals[$i]['text'] = $currencies->format($ord['Shipping']['ShippingCost']['Gross'] / 100);
                    } elseif ($order_totals[$i]['code'] == 'ot_total') {
                        $order_totals[$i]['value'] = ($ord['PaidAmount'] / 100);
                        $order_totals[$i]['text'] = '<strong>' . $currencies->format($ord['PaidAmount'] / 100) . '</strong>';
                    } elseif ($order_totals[$i]['code'] == 'ot_subtotal') {
                        $order_totals[$i]['value'] = (($ord['PaidAmount'] - $ord['Shipping']['ShippingCost']['Gross']) / 100);
                        $order_totals[$i]['text'] = $currencies->format(($ord['PaidAmount'] - $ord['Shipping']['ShippingCost']['Gross']) / 100);
                    }

                    $sql_data_array = array(
                        'orders_id' => $orders_id,
                        'title' => $order_totals[$i]['title'],
                        'text' => $order_totals[$i]['text'],
                        'value' => $order_totals[$i]['value'],
                        'class' => $order_totals[$i]['code'],
                        'sort_order' => $order_totals[$i]['sort_order']
                    );

                    tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);
                }
            }

            #add order status
            if ($ord['OrderStatus'] == 'ORDER_STATUS_COMPLETE' || $ord['PaymentStatus'] == 'PAYMENT_STATUS_END') {
                $order_status = $order->info['status'];
                $comment_order_status = MODULE_PAYMENT_PAYU_ACCOUNT_TEXT_COMMENT_TRANSACTION_FINISHED;
                $notify = TRUE;
            } else {
                $order_status = (int)MODULE_PAYMENT_PAYU_ACCOUNT_TRANSACTIONS_ORDER_STATUS_ID;
                $notify = FALSE;
                $comment_order_status = $ord['OrderStatus'];
            }

            $status = array(
                'orders_id' => $orders_id,
                'orders_status_id' => $order_status,
                'date_added' => 'now()',
                'customer_notified' => ($notify == TRUE) ? '1' : '0',
                'comments' => trim(MODULE_PAYMENT_PAYU_ACCOUNT_TEXT_COMMENT_NOTIFICATION . ' [ ' . $comment_order_status . ' ]')
            );

            $this->add_order_status_history($status);
            #
        }
    }

    /**
     * Return country name from countries table by countries_iso_code
     * @access private
     * @param string isocode
     */
    private function get_country_title_by_iso_code_2($isocode)
    {
        $country_query = tep_db_query("SELECT * FROM " . TABLE_COUNTRIES . " WHERE countries_iso_code_2 = '" . tep_db_input($isocode) . "' LIMIT 1");

        $country['title'] = '';

        if (tep_db_num_rows($country_query)) {
            $tmp = tep_db_fetch_array($country_query);

            $country['title'] = $tmp['countries_name'];
        }

        return $country;
    }

    /**
     * Return customers_id from customers table by mail address
     * @access private
     * @param string mail
     */
    private function get_customer_id_by_mail($mail)
    {
        $res = tep_db_query("SELECT customers_id FROM " . TABLE_CUSTOMERS . " WHERE customers_email_address = '" . tep_db_input($mail) . "' LIMIT 1");
        if (tep_db_num_rows($res)) {
            $row = tep_db_fetch_array($res);
            return intval($row['customer_id']);
        }

        return false;
    }

    /**
     * Return shipping cost list for PayU order
     * @access private
     * @param string payu_session_id
     * @param string country_iso_code
     * @param string req
     */
    function get_shipping_cost_recalc($payu_session_id, $country_iso_code, $req)
    {
        global $order, $language;
        $orders_id = $this->get_order_id_by_session($payu_session_id);

        if (!empty($orders_id)) {
            require(DIR_WS_CLASSES . 'order.php');
            $order = new order($orders_id);

            //check is country
            $country_query = tep_db_query("SELECT * FROM " . TABLE_COUNTRIES . " WHERE countries_iso_code_2 = '" . tep_db_input($country_iso_code) . "' LIMIT 1");
            if (tep_db_num_rows($country_query)) {
                $country = tep_db_fetch_array($country_query);
                $order->delivery['country_id'] = $country['countries_id'];
            }
            $order->delivery['country']['iso_code_2'] = $country_iso_code;

            require(DIR_WS_CLASSES . 'shipping.php');
            $shipping_list = array();

            $pass = false;
            switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
                case 'national':
                    if ($order->delivery['country_id'] == STORE_COUNTRY) {
                        $pass = true;
                    }
                    break;
                case 'international':
                    if ($order->delivery['country_id'] != STORE_COUNTRY) {
                        $pass = true;
                    }
                    break;
                case 'both':
                    $pass = true;
                    break;
            }
            $pass = true;
            if ($pass == true) {
                if ($order->info['total'] >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) {
                    include(DIR_WS_LANGUAGES . $language . '/modules/order_total/ot_shipping.php');

                    $shipping_cost_list['ShippingCostList'][]['ShippingCost'] = array
                    (
                        'Type' => FREE_SHIPPING_TITLE,
                        'CountryCode' => $country_iso_code,
                        'Discount' => 0,
                        'Price' => array
                        (
                            'Net' => 0,
                            'Gross' => 0,
                            'Tax' => 0,
                            'CurrencyCode' => $order->info['currency']
                        )
                    );
                } else {
                    $shipping_modules = new shipping;
                    if ((tep_count_shipping_modules() > 0)) {
                        $list = $shipping_modules->quote();

                        foreach ($list as $k => $v) {
                            foreach ($v['methods'] as $k2 => $v2) {
                                if (!isset($v['error'])) {
                                    $shipping_cost_list['ShippingCostList'][$k]['ShippingCost'] = array
                                    (
                                        'Type' => $v['module'] . ' (' . $v2['title'] . ')',
                                        'CountryCode' => $country_iso_code,
                                        'Discount' => 0,
                                        'Price' => array
                                        (
                                            'Net' => $v2['cost'] * 100,
                                            'Gross' => $v2['cost'] * 100,
                                            'Tax' => 0,
                                            'CurrencyCode' => $order->info['currency']
                                        )
                                    );
                                }
                            }
                        }
                    }
                }

                $shipping_cost = array(
                    'CountryCode' => $country_iso_code,
                    'ShipToOtherCountry' => ($ship_to_other_countries === FALSE) ? 0 : 1,
                    'ShippingCostList' => $shipping_cost_list
                );

                return OpenPayU::buildShippingCostRetrieveResponse($shipping_cost_list, $req);
            }

            return false;

        }
    }

    /**
     * Add status information for PayU orders
     * @access private
     * @param array status
     */
    private function add_order_status_history($status)
    {
        $sql = "UPDATE " . TABLE_ORDERS . " SET orders_status='" . $status['orders_status_id'] . "' WHERE orders_id='" . (int)$status['orders_id'] . "'";
        tep_db_query($sql);

        $status_query = tep_db_query("SELECT orders_status_history_id FROM " . TABLE_ORDERS_STATUS_HISTORY . " WHERE orders_id='" . (int)$status['orders_id'] . "' AND comments = '" . $status['comments'] . "'");
        $status_history = tep_db_fetch_array($status_query);

        if ($status_history['orders_status_history_id'] == 0) {
            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $status);
        }
    }
}


/**
 * Returns PayU payments buttton
 * @access public
 * @param int type
 */
function payu_button_image_draw($type)
{
    if (MODULE_PAYMENT_PAYU_ACCOUNT_SHOW_IN_CART == TRUE) {
        $json_data = get_payu_json_data('http://www.openpayu.com/' . strtolower(MODULE_PAYMENT_PAYU_ACCOUNT_LANGUAGE_LOCALE) . '/goods/json');
        $buttons = $json_data->media->buttons;

        switch ($type) {
            default:
                return '<img src="' . $buttons->{$type} . '" alt="' . MODULE_PAYMENT_PAYU_ACCOUNT_TEXT_BUTTON . '" />';
                break;
        }
    }
}

/**
 * Returns PayU payments buttton
 * @access public
 * @param int type
 */
function payu_image_selection($type, $name)
{
    $html = '';

    $name = 'configuration[' . $name . ']';

    $json_data = get_payu_json_data('http://www.openpayu.com/' . strtolower(MODULE_PAYMENT_PAYU_ACCOUNT_LANGUAGE_LOCALE) . '/goods/json');

    $buttons = count((array)$json_data->media->buttons);

    for ($i = 1; $i <= $buttons; $i++) {
        $html .= tep_draw_radio_field($name, $i, ($type == $i ? true : false)) . payu_button_image_draw($i) . '<br /><br />';
    }

    return $html;
}

function payu_version($type, $name)
{
    $aver = convert_version($type);
    $json_plugin = get_payu_json_data('http://www.openpayu.com/' . strtolower(MODULE_PAYMENT_PAYU_ACCOUNT_LANGUAGE_LOCALE) . '/goods/plugin/osc/231/' . $aver . '/json');

    $json = get_payu_json_data('http://www.openpayu.com/' . strtolower(MODULE_PAYMENT_PAYU_ACCOUNT_LANGUAGE_LOCALE) . '/goods/json');
    $osc_json_version = $json->plugins->osc->{"2.3.1"};
    $new_version_plugin = convert_version($osc_json_version->version);

    //$txt = '<input type="hidden" value="'.$type.'" name="configuration['.$name.']">'.$type;

    $txt = '';

    if ($new_version_plugin > $aver) {
        $txt .= '<p>' . MODULE_PAYMENT_PAYU_ACCOUNT_NEW_VERSION . '<br /><a href="http://' . $osc_json_version->repository . '">' . $osc_json_version->repository . '</a></p>';
    }

    $txt .= '<p>' . $json_plugin->description . '</p>';

    if ($json_plugin->docs) {
        foreach ($json_plugin->docs as $doc) {
            $docs_arr = (array)$doc;
            if (is_array($docs_arr) && !empty($docs_arr)) {
                $docs = $doc;
                foreach ($docs as $doc) {
                    $txt .= '<img src="images/icon_popup.gif" border="0" /> <a href="' . $doc->url . '" target="_blank">' . $doc->name . '</a><br />';
                }
            } else {
                $txt .= '<img src="images/icon_popup.gif" border="0" /> <a href="' . $doc->url . '" target="_blank">' . $doc->name . '</a><br />';
            }
        }
    }

    return $txt;
}

function convert_version($version)
{
    return intval(str_replace('.', '', $version));
}

function get_payu_json_data($url)
{
    if (!empty($url)) {
        $data = get_json($url);

        if (!empty($data)) {
            return json_decode($data);
        }
    }

    return null;
}

function get_json($url)
{
    if ($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        return $response;
    }

    return null;

}