<?php

require_once __DIR__.'/includes/FrisbeeService.php';

if (!class_exists('Controller')) {
    class Controller extends \Opencart\System\Engine\Controller {}
}

class ControllerPaymentFrisbee extends Controller
{
    const PRECISION = 2;

    public function index()
    {
        if (!empty($this->request->get['_'])) {
            switch ($this->request->get['_']) {
                case 'thankYou': return $this->thankYou();
                case 'callback': return $this->callback();
            }
        }

        $this->language->load('payment/frisbee');
        $order_id = $this->session->data['order_id'];
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

        if (version_compare(VERSION, '3.9.9.9', '>')) {
            $backref = $this->url->link('extension/frisbee/payment/frisbee&_=thankYou', '', 'SSL');
            $callback = $this->url->link('extension/frisbee/payment/frisbee&_callback', '', 'SSL');
        } else {
            $backref = $this->url->link('payment/frisbee/thankYou', '', 'SSL');
            $callback = $this->url->link('payment/frisbee/callback', '', 'SSL');
        }

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

        try {
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
        } catch (\Exception $exception) {
            $out = array(
                'result' => false,
                'message' => $exception->getMessage(),
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

            $frisbeeService->handleCallbackData($data);

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
                if ($order_info['order_status_id'] == $orderStatusPending) {
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

    public function thankYou()
    {
        if (version_compare(VERSION, '2.0.0.0', '<=')) {
            $this->load->language('payment/frisbee');
        } else {
            $this->load->language('extension/frisbee/extension/payment/frisbee');
        }

        $this->document->setTitle($this->language->get('success_heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_success'),
            'href' => $this->url->link('checkout/success', 'language=' . $this->config->get('config_language'))
        ];

        if ($this->customer->isLogged()) {
            $data['text_message'] = sprintf($this->language->get('text_customer'), $this->url->link('account/account', 'language=' . $this->config->get('config_language')), $this->url->link('account/order', 'language=' . $this->config->get('config_language')), $this->url->link('account/download', 'language=' . $this->config->get('config_language')), $this->url->link('information/contact', 'language=' . $this->config->get('config_language')));
        } else {
            $data['text_message'] = sprintf($this->language->get('text_guest'), $this->url->link('information/contact', 'language=' . $this->config->get('config_language')));
        }

        $data['continue'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        if (version_compare(VERSION, '2.0.0.0', '<=')) {
            $data['heading_title'] = $this->language->get('success_heading_title');
            $data['button_continue'] = 'Continue';

            if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/common/success.tpl')) {
                $this->response->setOutput($this->load->view($this->config->get('config_template').'/template/common/success.tpl', $data));
            } else {
                $this->response->setOutput($this->load->view('default/template/common/success.tpl', $data));
            }
        } else {
            $this->response->setOutput($this->load->view('common/success', $data));
        }
    }

    protected function modelCheckoutOrderUpdate($orderId, $orderStatusId, $comment, $notify = false)
    {
        if (version_compare(VERSION, '3.0.0.0', '<')) {
            $this->model_checkout_order->addOrderHistory($orderId, $orderStatusId, $comment, $notify, false);
        } else {
            $this->model_checkout_order->addHistory($orderId, $orderStatusId, $comment, $notify, false);
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
            'path' => $_SERVER['REQUEST_URI'],
            'uuid' => isset($_SERVER['HTTP_USER_AGENT']) ? base64_encode($_SERVER['HTTP_USER_AGENT']) : time()
        );

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
