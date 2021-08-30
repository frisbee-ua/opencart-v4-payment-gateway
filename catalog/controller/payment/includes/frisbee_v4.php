<?php

namespace Opencart\Application\Controller\Extension\Frisbee\Payment;

if (!class_exists('Controller')) {
    class Controller extends \Opencart\System\Engine\Controller {}
}

class Frisbee extends \ControllerPaymentFrisbee
{
    public function success()
    {
        $this->load->language('extension/frisbee/extension/payment/frisbee');

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

        $this->response->setOutput($this->load->view('common/success', $data));
    }
}

?>
