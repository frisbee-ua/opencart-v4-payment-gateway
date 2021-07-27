<?php

class ControllerPaymentFrisbee extends Controller
{
    protected $RESPONCE_SUCCESS = 'success';

    protected $RESPONCE_FAIL = 'failure';

    protected $ORDER_SEPARATOR = '_';

    protected $SIGNATURE_SEPARATOR = '|';

    protected $ORDER_APPROVED = 'approved';

    protected $ORDER_DECLINED = 'declined';

    protected $ORDER_EXPIRED = 'expired';

    protected $ORDER_PROCESSING = 'processing';

    public function index()
    {
        $this->language->load('payment/frisbee');
        $order_id = $this->session->data['order_id'];
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $backref = $this->url->link('payment/frisbee/response', '', 'SSL');
        $callback = $this->url->link('payment/frisbee/callback', '', 'SSL');
        $desc = $this->language->get('order_desq').$order_id;
        if (($this->config->get('frisbee_currency'))) {
            $frisbee_currency = $this->config->get('frisbee_currency');
        } else {
            $frisbee_currency = $this->currency->getCode();
        }

        if ($this->config->get('frisbee_is_test')) {
            $url = 'https://dev2.pay.fondy.eu';
            $merchantId = '1601318';
            $secretKey = 'test';
        } else {
            $url = 'https://api.fondy.eu';
            $merchantId = $this->config->get('frisbee_merchant');
            $secretKey = $this->config->get('frisbee_secretkey');
        }

        $frisbee_args = [
            'order_id' => $order_id.$this->ORDER_SEPARATOR.time(),
            'merchant_id' => $merchantId,
            'order_desc' => $desc,
            'amount' => round($order_info['total'] * $order_info['currency_value'] * 100),
            'currency' => $frisbee_currency,
            'response_url' => $backref,
            'server_callback_url' => $callback,
            'lang' => $this->config->get('frisbee_language'),
            'sender_email' => $order_info['email'],
        ];

        $frisbee_args['signature'] = $this->getSignature($frisbee_args, $secretKey);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url.'/api/checkout/url/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['request' => $frisbee_args]));
        $result = json_decode(curl_exec($ch));
        if ($result->response->response_status == 'failure') {
            $out = [
                'result' => false,
                'message' => $result->response->error_message,
            ];
        } else {
            $out = [
                'result' => true,
                'url' => $result->response->checkout_url,
            ];
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
        } elseif (version_compare(VERSION, '2.1.0.2', '>')) {
            if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/payment/frisbee.tpl')) {
                return $this->load->view($this->config->get('config_template').'/template/payment/frisbee.tpl', $data);
            } else {
                return $this->load->view('/payment/frisbee.tpl', $data);
            }
        } else {
            if (file_exists(DIR_TEMPLATE.$this->config->get('config_template').'/template/payment/frisbee.tpl')) {
                return $this->load->view($this->config->get('config_template').'/template/payment/frisbee.tpl', $data);
            } else {
                return $this->load->view('default/template/payment/frisbee.tpl', $data);
            }
        }
    }

    public function response()
    {
        $this->language->load('payment/frisbee');
        $this->load->model('checkout/order');
        $options = [
            'merchant' => $this->config->get('frisbee_merchant'),
            'secretkey' => $this->config->get('frisbee_secretkey'),
        ];

        $paymentInfo = $this->isPaymentValid($options, $this->request->post);
        $this->cart->clear();
        if ($paymentInfo === true && $this->request->post['order_status'] != $this->ORDER_DECLINED) {
            $backref = $this->url->link('checkout/success', '', 'SSL');
            $this->response->redirect($backref);
        } else {
            if ($this->request->post['order_status'] == $this->ORDER_DECLINED) {
                $this->session->data ['frisbee_error'] = $this->language->get('error_frisbee').' '.$this->request->post['response_description'].'. '.$this->language->get('error_kod').$this->request->post['response_code'];
                $this->response->redirect($this->url->link('checkout/confirm', '', 'SSL'));
            }
            $this->session->data ['frisbee_error'] = $this->language->get('error_frisbee').' '.$this->request->post['response_description'].'. '.$this->language->get('error_kod').$this->request->post['response_code'];
            $this->response->redirect($this->url->link('checkout/confirm', '', 'SSL'));
        }
    }

    public function callback()
    {
        if (empty($this->request->post)) {
            $callback = json_decode(file_get_contents("php://input"));
            if (empty($callback)) {
                die();
            }
            $this->request->post = [];
            foreach ($callback as $key => $val) {
                $this->request->post[$key] = $val;
            }
        }

        $this->language->load('payment/frisbee');

        $merchantId = $this->config->get('frisbee_merchant');
        $secretKey = $this->config->get('frisbee_secretkey');

        if (empty($merchantId)) {
            $merchantId = $this->config->get('payment_frisbee_merchant');
        }

        if (empty($secretKey)) {
            $secretKey = $this->config->get('payment_frisbee_secretkey');
        }

        $options = [
            'merchant' => $merchantId,
            'secretkey' => $secretKey,
        ];

        $paymentInfo = $this->isPaymentValid($options, $this->request->post);

        list($order_id,) = explode($this->ORDER_SEPARATOR, $this->request->post['order_id']);
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        $total = round($order_info['total'] * $order_info['currency_value'] * 100);

        if ($paymentInfo === true) {

            if ($this->request->post['order_status'] == $this->ORDER_APPROVED and $total == $this->request->post['amount']) {
                $comment = "Frisbee payment id : ".$this->request->post['payment_id'];
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('frisbee_order_status_id'), $comment, $notify = true, $override = false);
                die('Ok');
            } else {
                if ($this->request->post['order_status'] == $this->ORDER_PROCESSING) {
                    $comment = "Frisbee payment id : ".$this->request->post['payment_id'].$paymentInfo;
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('frisbee_order_process_status_id'), $comment, $notify = false, $override = false);
                    die($paymentInfo);
                } else {
                    if ($this->request->post['order_status'] == $this->ORDER_DECLINED || $this->request->post['order_status'] == $this->ORDER_EXPIRED) {
                        $comment = "Payment cancelled";
                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('frisbee_order_cancelled_status_id'), $comment, $notify = false, $override = false);
                    }
                }
            }
        }
    }

    public function isPaymentValid($frisbeeSettings, $response)
    {
        $this->language->load('payment/frisbee');
        if ($frisbeeSettings['merchant'] != $response['merchant_id']) {
            return $this->language->get('error_merchant');
        }

        $responseSignature = $response['signature'];
        if (isset($response['response_signature_string'])) {
            unset($response['response_signature_string']);
        }
        if (isset($response['signature'])) {
            unset($response['signature']);
        }
        if (self::getSignature($response, $frisbeeSettings['secretkey']) != $responseSignature) {
            return $this->language->get('error_signature');
        }

        return true;
    }

    public function getSignature($data, $password, $encoded = true)
    {
        $data = array_filter($data, function ($var) {
            return $var !== '' && $var !== null;
        });
        ksort($data);

        $str = $password;
        foreach ($data as $k => $v) {
            $str .= $this->SIGNATURE_SEPARATOR.$v;
        }

        if ($encoded) {
            return sha1($str);
        } else {
            return $str;
        }
    }
}

?>
