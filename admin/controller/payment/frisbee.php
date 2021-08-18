<?php

if (!class_exists('Controller')) {
	class Controller extends \Opencart\System\Engine\Controller {}
}

class ControllerPaymentFrisbee extends Controller
{
	/**
	 * @var array
	 */
	private $error = [];

	public function install()
	{

	}

	public function uninstall()
	{

	}

	public function index()
	{
		if (version_compare(VERSION, '2.0.0.0', '<')) {
			$this->index_v1_5();
		} elseif (version_compare(VERSION, '2.0.0.0', '>=')
			&& version_compare(VERSION, '2.3.0.0', '<')
		) {
			$this->index_v2_0();
		} elseif (version_compare(VERSION, '2.3.0.0', '>=')
			&& version_compare(VERSION, '3.0.0.0', '<')
		) {
			$this->index_v2_3();
		} elseif (version_compare(VERSION, '3.0.0.0', '>=')
			&& version_compare(VERSION, '3.9.9.9', '<=')
		) {
			$this->index_v3_0();
		} else {
			$this->index_v4_0();
		}
	}

	public function index_v1_5()
	{
		$this->load->language('payment/frisbee');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('frisbee', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$arr = array(
			'heading_title', 'text_payment', 'text_success', 'text_pay', 'text_card',
			'entry_merchant', 'entry_secretkey', 'entry_order_status', 'entry_order_status_cancelled',
			'entry_currency', 'entry_backref', 'entry_server_back', 'entry_language', 'entry_status',
			'entry_sort_order', 'error_permission', 'error_merchant', 'error_secretkey');

		foreach ($arr as $value) {
			$this->data[$value] = $this->language->get($value);
		}

		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');
		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['entry_order_process_status'] = $this->language->get('entry_order_process_status');

		$arr = array('warning', 'merchant', 'secretkey', 'type');
		foreach ($arr as $value) {
			$this->data['error_'.$value] = (isset($this->error[$value])) ? $this->error[$value] : '';
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('payment/frisbee', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['action'] = $this->url->link('payment/frisbee', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		$this->load->model('localisation/order_status');

		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$this->data['frisbee_currencyc']= array('EUR','USD','GBP','RUB','UAH');
		$arr = array( 'frisbee_merchant', 'frisbee_secretkey', 'frisbee_currency', 'frisbee_backref', 'frisbee_server_back',
			'frisbee_language', 'frisbee_status', 'frisbee_sort_order', 'frisbee_order_status_id', 'frisbee_order_process_status_id', 'frisbee_order_cancelled_status_id');

		foreach ($arr as $value) {
			$this->data[$value] = ( isset($this->request->post[$value]) ) ? $this->request->post[$value] : $this->config->get($value);
		}

		if (empty($data['frisbee_order_status_id'])) {
			$data['frisbee_order_status_id'] = 15;
		}

		if (empty($data['frisbee_order_process_status_id'])) {
			$data['frisbee_order_process_status_id'] = 2;
		}

		$this->template = 'payment/frisbee_v1_5.tpl';
		$this->children = array(
			'common/header',
			'common/footer',
		);

		$this->response->setOutput($this->render());
	}

	public function index_v2_0()
	{
		$this->load->language('payment/frisbee');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages();
		foreach ($languages as $language) {
			if (isset($this->error['bank'.$language['language_id']])) {
				$data['error_bank'.$language['language_id']] = $this->error['bank'.$language['language_id']];
			} else {
				$data['error_bank'.$language['language_id']] = '';
			}
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('frisbee', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/payment', 'token='.$this->session->data['token'], 'SSL'));
		}

		$arr = [
			'heading_title',
			'text_payment',
			'text_success',
			'text_pay',
			'text_card',
			'entry_merchant',
			'entry_styles',
			'entry_secretkey',
			'entry_order_status',
			'entry_frisbee_result',
			'entry_currency',
			'entry_backref',
			'entry_server_back',
			'entry_language',
			'entry_status',
			'entry_order_status_cancelled',
			'entry_sort_order',
			'error_permission',
			'error_merchant',
			'error_secretkey',
			'text_edit',
			'entry_help_lang'
		];

		foreach ($arr as $value) {
			$data[$value] = $this->language->get($value);
		}
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['entry_order_process_status'] = $this->language->get('entry_order_process_status');

		$arr = ['warning', 'merchant', 'secretkey', 'type'];
		foreach ($arr as $value) {
			$data['error_'.$value] = (isset($this->error[$value])) ? $this->error[$value] : '';
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'token='.$this->session->data['token'], 'SSL'),
			'separator' => false,
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/payment', 'token='.$this->session->data['token'], 'SSL'),
			'separator' => ' :: ',
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/frisbee', 'token='.$this->session->data['token'], 'SSL'),
			'separator' => ' :: ',
		];

		$data['action'] = $this->url->link('payment/frisbee', 'token='.$this->session->data['token'], 'SSL');
		$data['cancel'] = $this->url->link('extension/payment', 'token='.$this->session->data['token'], 'SSL');

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$data['payment_frisbee_currencies'] = ['UAH', 'EUR', 'USD', 'GBP', 'RUB'];
		$arr = [
			'frisbee_merchant',
			'frisbee_secretkey',
			'frisbee_result',
			'frisbee_backref',
			'frisbee_server_back',
			'frisbee_language',
			'frisbee_status',
			'frisbee_sort_order',
			'frisbee_order_status_id',
			'frisbee_order_process_status_id',
			'frisbee_order_cancelled_status_id',
			'frisbee_currency',
			'frisbee_styles'
		];

		foreach ($arr as $value) {
			$data[$value] = (isset($this->request->post[$value])) ? $this->request->post[$value] : $this->config->get($value);
		}

		if (empty($data['frisbee_order_status_id'])) {
			$data['frisbee_order_status_id'] = 15;
		}

		if (empty($data['frisbee_order_process_status_id'])) {
			$data['frisbee_order_process_status_id'] = 2;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/frisbee.tpl', $data));
	}

	public function index_v2_3()
	{
		$this->load->language('extension/payment/frisbee');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();
		foreach ($languages as $language) {
			if (isset($this->error['bank'.$language['language_id']])) {
				$data['error_bank'.$language['language_id']] = $this->error['bank'.$language['language_id']];
			} else {
				$data['error_bank'.$language['language_id']] = '';
			}
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('frisbee', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('extension/extension', 'token='.$this->session->data['token'].'&type=payment', true));
		}

		$arr = [
			'heading_title',
			'text_payment',
			'text_success',
			'text_pay',
			'text_card',
			'entry_merchant',
			'entry_styles',
			'entry_secretkey',
			'entry_order_status',
			'entry_currency',
			'entry_backref',
			'entry_server_back',
			'entry_payment_type',
			'entry_common_type',
			'entry_preauth_type',
			'entry_language',
			'entry_status',
			'entry_order_status_cancelled',
			'entry_sort_order',
			'error_permission',
			'error_merchant',
			'error_secretkey',
			'text_edit',
			'entry_help_lang'
		];

		foreach ($arr as $value) {
			$data[$value] = $this->language->get($value);
		}

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['entry_order_process_status'] = $this->language->get('entry_order_process_status');

		$arr = ['warning', 'merchant', 'secretkey', 'type'];
		foreach ($arr as $value) {
			$data['error_'.$value] = (isset($this->error[$value])) ? $this->error[$value] : '';
		}

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token='.$this->session->data['token'], 'SSL'),
			'separator' => false,
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('extension/payment', 'token='.$this->session->data['token'], 'SSL'),
			'separator' => ' :: ',
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/frisbee', 'token='.$this->session->data['token'], 'SSL'),
			'separator' => ' :: ',
		];

		$data['action'] = $this->url->link('extension/payment/frisbee', 'token='.$this->session->data['token'], 'SSL');
		$data['cancel'] = $this->url->link('extension/extension', 'token='.$this->session->data['token'], 'SSL');
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$data['frisbee_currencies'] = array('UAH', 'EUR', 'USD', 'GBP', 'RUB');

		$arr = [
			'frisbee_merchant',
			'frisbee_secretkey',
			'frisbee_backref',
			'frisbee_server_back',
			'frisbee_order_cancelled_status_id',
			'frisbee_language',
			'frisbee_status',
			'frisbee_sort_order',
			'frisbee_order_status_id',
			'frisbee_order_process_status_id',
			'frisbee_currency',
			'frisbee_payment_type',
			'frisbee_styles'
		];

		foreach ($arr as $value) {
			$data[$value] = (isset($this->request->post[$value])) ? $this->request->post[$value] : $this->config->get($value);
		}

		if (empty($data['frisbee_order_status_id'])) {
			$data['frisbee_order_status_id'] = 15;
		}

		if (empty($data['frisbee_order_process_status_id'])) {
			$data['frisbee_order_process_status_id'] = 2;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/frisbee.tpl', $data));
	}

	public function index_v3_0()
	{
		$this->load->language('extension/payment/frisbee');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();
		foreach ($languages as $language) {
			if (isset($this->error['bank' . $language['language_id']])) {
				$data['error_bank' . $language['language_id']] = $this->error['bank' . $language['language_id']];
			} else {
				$data['error_bank' . $language['language_id']] = '';
			}
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_frisbee', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$arr = array(
			'heading_title', 'text_payment', 'text_success', 'text_pay', 'text_card', 'entry_geo_zone', 'text_all_zones',
			'entry_merchant', 'entry_styles', 'entry_secretkey', 'entry_order_status','entry_currency', 'entry_backref',
			'entry_server_back', 'entry_payment_type', 'entry_common_type', 'entry_preauth_type', 'entry_language',
			'entry_status', 'entry_order_status_cancelled', 'entry_sort_order', 'error_permission', 'error_merchant',
			'error_secretkey', 'text_edit', 'entry_help_lang'
		);

		foreach ($arr as $value) {
			$data[$value] = $this->language->get($value);
		}

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['entry_order_process_status'] = $this->language->get('entry_order_process_status');

		$arr = array('warning', 'merchant', 'secretkey', 'type');
		foreach ($arr as $value) {
			$data['error_'.$value] = (isset($this->error[$value])) ? $this->error[$value] : '';
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => false
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => ' :: '
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/frisbee', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => ' :: '
		);

		$data['action'] = $this->url->link('extension/payment/frisbee', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$this->load->model('localisation/order_status');
		$this->load->model('localisation/geo_zone');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$data['payment_frisbee_currencies'] = array('UAH', 'EUR', 'USD', 'GBP', 'RUB');

		$array_data = array(
			'payment_frisbee_merchant',
			'payment_frisbee_secretkey',
			'payment_frisbee_backref',
			'payment_frisbee_server_back',
			'payment_frisbee_order_cancelled_status_id',
			'payment_frisbee_geo_zone_id',
			'payment_frisbee_language',
			'payment_frisbee_status',
			'payment_frisbee_sort_order',
			'payment_frisbee_order_status_id',
			'payment_frisbee_order_process_status_id',
			'payment_frisbee_currency',
			'payment_frisbee_type'
		);

		foreach ($array_data as $value) {
			$data[$value] = (isset($this->request->post[$value])) ? $this->request->post[$value] : $this->config->get($value);
		}

		if (empty($data['frisbee_order_status_id'])) {
			$data['frisbee_order_status_id'] = 15;
		}

		if (empty($data['frisbee_order_process_status_id'])) {
			$data['frisbee_order_process_status_id'] = 2;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/frisbee', $data));
	}

	public function index_v4_0()
	{
		$this->load->language('extension/frisbee/extension/payment/frisbee');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();
		foreach ($languages as $language) {
			if (isset($this->error['bank' . $language['language_id']])) {
				$data['error_bank' . $language['language_id']] = $this->error['bank' . $language['language_id']];
			} else {
				$data['error_bank' . $language['language_id']] = '';
			}
		}

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_frisbee', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		$arr = array(
			'heading_title', 'text_payment', 'text_success', 'text_pay', 'text_card', 'entry_geo_zone', 'text_all_zones',
			'entry_merchant', 'entry_styles', 'entry_secretkey', 'entry_order_status','entry_currency', 'entry_backref',
			'entry_server_back', 'entry_payment_type', 'entry_common_type', 'entry_preauth_type', 'entry_language',
			'entry_status', 'entry_order_status_cancelled', 'entry_sort_order', 'error_permission', 'error_merchant',
			'error_secretkey', 'text_edit', 'entry_help_lang'
		);

		foreach ($arr as $value) {
			$data[$value] = $this->language->get($value);
		}

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['entry_order_process_status'] = $this->language->get('entry_order_process_status');

		$arr = array('warning', 'merchant', 'secretkey', 'type');
		foreach ($arr as $value) {
			$data['error_'.$value] = (isset($this->error[$value])) ? $this->error[$value] : '';
		}

		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => false
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_payment'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => ' :: '
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/frisbee/payment/frisbee', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => ' :: '
		);

		$data['action'] = $this->url->link('extension/frisbee/payment/frisbee', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		$this->load->model('localisation/order_status');
		$this->load->model('localisation/geo_zone');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$data['payment_frisbee_currencies'] = array('UAH', 'EUR', 'USD', 'GBP', 'RUB');

		$array_data = array(
			'payment_frisbee_merchant',
			'payment_frisbee_secretkey',
			'payment_frisbee_backref',
			'payment_frisbee_server_back',
			'payment_frisbee_order_cancelled_status_id',
			'payment_frisbee_geo_zone_id',
			'payment_frisbee_language',
			'payment_frisbee_status',
			'payment_frisbee_sort_order',
			'payment_frisbee_order_status_id',
			'payment_frisbee_order_process_status_id',
			'payment_frisbee_currency',
			'payment_frisbee_type',
			'payment_frisbee_is_test'
		);

		foreach ($array_data as $value) {
			$data[$value] = (isset($this->request->post[$value])) ? $this->request->post[$value] : $this->config->get($value);
		}

		if (empty($data['frisbee_order_status_id'])) {
			$data['frisbee_order_status_id'] = 15;
		}

		if (empty($data['frisbee_order_process_status_id'])) {
			$data['frisbee_order_process_status_id'] = 2;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/frisbee/payment/frisbee_v4', $data));
	}

	private function validate()
	{
		if (!$this->user->hasPermission('modify', 'payment/frisbee')
			&& !$this->user->hasPermission('modify', 'extension/payment/frisbee')
			&& !$this->user->hasPermission('modify', 'extension/frisbee/payment/frisbee')
		) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (empty($this->request->post['frisbee_merchant']) && empty($this->request->post['payment_frisbee_merchant'])) {
			$this->error['merchant'] = $this->language->get('error_merchant');
		}

		if (empty($this->request->post['frisbee_secretkey']) && empty($this->request->post['payment_frisbee_secretkey'])) {
			$this->error['secretkey'] = $this->language->get('error_secretkey');
		}

		return (! $this->error) ? true : false;
	}
}

if (version_compare(VERSION, '3.9.9.9', '>')) {
	require_once __DIR__ . '/../includes/frisbee_v4.php';
}

?>
