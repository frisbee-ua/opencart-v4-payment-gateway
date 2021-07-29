<?php

namespace Opencart\Application\Model\Extension\Frisbee\Payment;

class Frisbee extends \Opencart\System\Engine\Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/frisbee/extension/payment/frisbee');

        return array(
            'code' => 'frisbee',
            'terms' => '',
            'title' => $this->language->get('text_title'),
            'sort_order' => $this->config->get('frisbee_sort_order'),
        );
    }
}

?>
