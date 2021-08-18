<?php

require_once __DIR__ . '/includes/FrisbeeService.php';

class ControllerPaymentFrisbee extends Controller
{
    const PRECISION = 2;

    public function index()
    {
        $this->language->load('payment/frisbee');
        $order_id = $this->session->data['order_id'];
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $backref = $this->url->link('payment/frisbee/success', '', 'SSL');
        $callback = $this->url->link('payment/frisbee/callback', '', 'SSL');
        if (($this->config->get('frisbee_currency'))) {
            $frisbee_currency = $this->config->get('frisbee_currency');
        } else {
            if (version_compare(VERSION, '3.0.0.0', '>=')) {
                $frisbee_currency = $order_info['currency_code'];
            } else {
                $frisbee_currency = $this->currency->getCode();
            }
        }

        $frisbeeService = new FrisbeeService();
        $frisbeeService->setMerchantId($this->getMerchantId());
        $frisbeeService->setSecretKey($this->getSecretKey());
        $frisbeeService->setRequestParameterOrderId($order_id);
        $frisbeeService->setRequestParameterOrderDescription($this->generateOrderDescriptionParameter());
        $frisbeeService->setRequestParameterAmount(round($order_info['total'] * $order_info['currency_value']));
        $frisbeeService->setRequestParameterCurrency($frisbee_currency);
        $frisbeeService->setRequestParameterServerCallbackUrl($callback);
        $frisbeeService->setRequestParameterResponseUrl($backref);
        $frisbeeService->setRequestParameterLanguage($this->config->get('frisbee_language'));
        $frisbeeService->setRequestParameterSenderEmail($order_info['email']);
        $frisbeeService->setRequestParameterReservationData($this->generateReservationDataParameter($order_info));

        $checkoutUrl = $frisbeeService->retrieveCheckoutUrl($order_id);

        if (!$checkoutUrl) {
            $out = array(
                'result' => false,
                'message' => $frisbeeService->getRequestResultErrorMessage()
            );
        } else {
            $out = array(
                'result' => true,
                'url' => $checkoutUrl
            );
        }

        $data['frisbee'] = $out;
        $data['styles'] = $this->config->get('frisbee_styles');
        $data['button_confirm'] = $this->language->get('button_confirm');

        if (version_compare(VERSION, '2.0.0.0', '<')) {
            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/frisbee.tpl')) {
                $this->template = $this->config->get('config_template') . '/template/payment/frisbee.tpl';
            } else {
                $this->template = 'default/template/payment/frisbee.tpl';
            }
            $this->data = $data;

            return $this->render();
        } elseif (version_compare(VERSION, '2.0.0.0', '>=')
            && version_compare(VERSION, '2.3.0.0', '<')
        ) {
            if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/payment/frisbee.tpl')) {
                return $this->load->view($this->config->get('config_template').'/template/payment/frisbee.tpl', $data);
            } else {
                return $this->load->view('/payment/frisbee.tpl', $data);
            }
        } elseif (version_compare(VERSION, '2.3.0.0', '>=')
            && version_compare(VERSION, '3.0.0.0', '<')
        ) {
            return $this->load->view('payment/frisbee.tpl', $data);
        } elseif (version_compare(VERSION, '3.0.0.0', '>=')
            && version_compare(VERSION, '3.9.9.9', '<')
        ) {
            return $this->load->view('/extension/payment/frisbee', $data);
        } else {
            return $this->load->view('extension/frisbee/payment/frisbee_v4', $data);
        }
    }

    public function callback()
    {
        $this->language->load('payment/frisbee');

        $this->load->model('checkout/order');
        $orderStatusPending = $this->config->get('frisbee_order_process_status_id') ?: $this->config->get('payment_frisbee_order_process_status_id');
        $notify = false;

        try {
            $frisbeeService = new FrisbeeService();
            $data = $frisbeeService->getCallbackData();
            $orderId = $frisbeeService->parseFrisbeeOrderId($data);
            $frisbeeService->setMerchantId($this->getMerchantId());
            $frisbeeService->setSecretKey($this->getSecretKey());

            $result = $frisbeeService->handleCallbackData($data);

            $order_info = $this->model_checkout_order->getOrder($orderId);

            if (empty($orderStatusPending)) {
                $orderStatusPending = 1;
            }

            if ($frisbeeService->isOrderDeclined()) {
                $orderStatus = $this->config->get('frisbee_order_cancelled_status_id') ?: $this->config->get('payment_frisbee_order_cancelled_status_id');
                if (empty($orderStatus)) {
                    $orderStatus = 8;
                }
            } elseif ($frisbeeService->isOrderExpired()) {
                if ($order_info['order_status'] == $orderStatusPending) {
                    $orderStatus = 14;
                } else {
                    die();
                }
            } elseif ($frisbeeService->isOrderApproved()) {
                $orderStatus = $this->config->get('frisbee_order_status_id') ?: $this->config->get('payment_frisbee_order_status_id');
                $notify = true;
            } elseif ($frisbeeService->isOrderFullyReversed() || $frisbeeService->isOrderPartiallyReversed()) {
                $orderStatus = 11;
            }

            $message = $frisbeeService->getStatusMessage();
        } catch (\Exception $exception) {
            $orderStatus = $orderStatusPending;
            echo $message = $exception->getMessage();
            http_response_code(500);
        }

        $comment = 'Frisbee ID: '.$data['order_id'].' Payment ID: '.$data['payment_id'] . ' Message: ' . $message;

        $this->modelCheckoutOrderUpdate($orderId, $orderStatus, $comment, $notify);
    }

    protected function modelCheckoutOrderUpdate($orderId, $orderStatusId, $comment, $notify = false)
    {
        if (version_compare(VERSION, '2.0.0.0', '<')) {
            $this->model_checkout_order->update($orderId, $orderStatusId, $comment, $notify, false);
        } else {
            $this->model_checkout_order->addOrderHistory($orderId, $orderStatusId, $comment, $notify, false);
        }
    }

    /**
     * @return mixed
     */
    protected function getMerchantId()
    {
        $merchantId = $this->config->get('frisbee_merchant');

        if (empty($merchantId)) {
            $merchantId = $this->config->get('payment_frisbee_merchant');
        }

        return $merchantId;
    }

    /**
     * @return mixed
     */
    protected function getSecretKey()
    {
        $secretKey = $this->config->get('frisbee_secretkey');

        if (empty($secretKey)) {
            $secretKey = $this->config->get('payment_frisbee_secretkey');
        }

        return $secretKey;
    }

    /**
     * @param $orderDetails
     * @return string
     */
    protected function generateReservationDataParameter($orderDetails)
    {
        $reservationData = array(
            'phonemobile' => $orderDetails['telephone'],
            'customer_address' => $orderDetails['payment_address_1'] . ' ' . $orderDetails['payment_address_2'],
            'customer_country' => $orderDetails['payment_iso_code_3'],
            'customer_state' => $orderDetails['payment_zone'],
            'customer_name' => $orderDetails['firstname'] . ' ' . $orderDetails['lastname'],
            'customer_city' => $orderDetails['payment_city'],
            'customer_zip' => $orderDetails['payment_postcode'],
            'account' => $orderDetails['customer_id'],
            'products' => $this->generateProductsParameter(),
            'cms_name' => 'Opencart',
            'cms_version' => defined('VERSION') ? VERSION : '',
            'shop_domain' => $_SERVER['SERVER_NAME'] ?: $_SERVER['HTTP_HOST'],
            'path' => $_SERVER['REQUEST_URI']
        );
        $reservationData['uuid'] = sprintf('%s_%s', $reservationData['shop_domain'], $reservationData['cms_name']);

        return base64_encode(json_encode($reservationData));
    }

    /**
     * @return string
     */
    protected function generateOrderDescriptionParameter()
    {
        $description = '';
        foreach ($this->cart->getProducts() as $item) {
            $description .= "Name: $item[name] ";
            $description .= "Price: $item[price] ";
            $description .= "Qty: $item[quantity] ";
            $description .= "Amount: $item[total]\n";
        }

        return $description;
    }

    /**
     * @return array
     */
    protected function generateProductsParameter()
    {
        $products = [];

        foreach ($this->cart->getProducts() as $key => $item) {
            $products[] = [
                'id' => $key,
                'name' => $item['name'],
                'price' => number_format(floatval($item['price']), self::PRECISION),
                'total_amount' => number_format($item['total'], self::PRECISION),
                'quantity' => number_format(floatval($item['quantity']), self::PRECISION),
            ];
        }

        return $products;
    }
}

if (version_compare(VERSION, '3.9.9.9', '>')) {
    require_once 'includes/frisbee_v4.php';
}

?>
