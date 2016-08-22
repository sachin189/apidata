<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
	
	public $data           = array();

	public $success_message     = "";
	public $error_message       = "";
	public $warning_message     = "";
	public $information_message = "";
	public $user_id = FALSE;
	public $user_unique_id = FALSE;

	function __construct()
	{
		parent::__construct();
		echo $this->session->userdata('language');
		die;
		if(!$this->session->userdata('language'))
		{
			$this->session->set_userdata('language', $this->config->item('language'));
		}

		$this->config->set_item('language', $this->session->userdata('language'));
		$this->lang->load('general' , $this->session->userdata('language'));
		$this->lang->load('form_validation' , $this->session->userdata('language'), TRUE);

		$this->InitializeUserSessiondata();
	}

	public function get_messages()
	{
		$warning_message     = $this->session->flashdata('warning_message');
		$information_message = $this->session->flashdata('information_message');
		$success_message     = $this->session->flashdata('success_message');
		$error_message       = $this->session->flashdata('error_message');

		$this->data['open_login'] = $this->session->flashdata( 'login' );

		if ( $warning_message )		$this->data['warning_message']		= $warning_message;
		if ( $information_message ) $this->data['information_message']	= $information_message;
		if ( $success_message )		$this->data['success_message']		= $success_message;
		if ( $error_message )		$this->data['error_message']		= $error_message;
	}

	public function init_post_data()
	{
		$handle = fopen('php://input','r');
		$jsonInput = fgets($handle);
		$data = json_decode($jsonInput,true);
		$_POST = $data;
		return $_POST;
	}

	function echo_Jason( $data )
	{
		exit(json_encode($data));
	}

	private function InitializeUserSessiondata()
	{
		$this->user_unique_id = $this->session->userdata('user_unique_id');
		$this->user_id        = $this->session->userdata('user_id');
	}
}

/* End of file MY_Controller.php */
/* Location: application/core/MY_Controller.php */