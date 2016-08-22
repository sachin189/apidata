<?php defined('BASEPATH') OR exit('No direct script access allowed');

class MYREST_Controller extends REST_Controller {

	public function __construct()
	{
		parent::__construct();
		$_POST = $this->post();
		$this->custom_auth_override = $this->_custom_auth_override_check();

		//Do your magic here
		if ($this->custom_auth_override === FALSE)
		{
			$this->_custom_prepare_basic_auth();
		}
	}

	/**
	 * Retrieve the validation errors array and send as response.
	 * 26/12/2014 16:46
	 * @return none
	 */

	public function send_validation_errors()
	{	
		$errors = $this->form_validation->error_array();
		//$message = $errors[array_keys($errors)[0]];
		$message = implode(" <br> ", $errors);
		$return[config_item('rest_status_field_name')]  = FALSE;
		$return[config_item('rest_message_field_name')] = $this->form_validation->error_array();
		$return['message'] = $message;
		$this->response($return, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
	}

	/**
	 * [send_email description]
	 * @MethodName send_email 
	 * @Summary This function used send email
	 * @param      string  to email
	 * @param      string  Email subject
	 * @param      string  Email messge
	 * @param      string  From Email 
	 * @param      string  From Name
	 * @return     Boolean
	 */
	function send_email($to, $subject = "", $message = "", $from_email = FROM_ADMIN_EMAIL, $from_name = FROM_EMAIL_NAME)
	{
		//return false;
		$this->load->library('email');

		$config['wordwrap']		= TRUE;
		$config['mailtype']		= 'html';
		$config['charset']		= "utf-8";
		$config['protocol']		= PROTOCOL;
		$config['smtp_user']	= SMTP_USER;
		$config['smtp_pass']	= SMTP_PASS;
		$config['smtp_host']	= SMTP_HOST;
		$config['smtp_port']	= SMTP_PORT;
		$config['bcc_batch_mode']	= TRUE;
		$config['smtp_crypto']	= SMTP_CRYPTO;
		$config['newline']		= "\r\n";  // SES hangs with just \n

		$this->email->initialize($config);

		$this->email->clear();
		$this->email->from($from_email, $from_name);
		$this->email->to(ADMIN_EMAIL);
		$this->email->bcc($to);
		$this->email->subject($subject);
		$this->email->message($message);
		$this->email->send();
		//echo $email->print_debugger();
		return true;
	}

	/**
	 * [generate_active_login_key description]
	 * @MethodName generate_active_login_key
	 * @Summary This genrate new key and insert in database
	 * @param      [int]  [User Id]
	 * @return     [key]
	 */
	public function generate_active_login_key($admin_id = "", $device_type = "1")
	{
		$key = random_string('unique');
		$insert_data = array(
						'key'			=> $key,
						'user_id'		=> $admin_id,
						'device_type'	=> $device_type,
						'date_created'	=> date('Y-m-d H:i:s')
					);
		$this->db->insert(ACTIVE_LOGIN, $insert_data);
		return $key;
	}

	public function delete_active_login_key($key, $device_type = "1")
	{
		$this->db->where('key', $key)->where('device_type', $device_type)->delete(ACTIVE_LOGIN);
	}

	/**
	 * [seesion_initialization description]
	 * @MethodName seesion_initialization
	 * @Summary This function used for initialize user session
	 * @param      [array]  [User Data Array]
	 * @return     [boolean]
	 */
	protected function seesion_initialization($data_arr)
	{
		$this->user_id = $data_arr['user_id'];
		$this->session->set_userdata($data_arr);
		return true;
	}

	/**
	 * Check if there is a specific auth type set for the current class/method/HTTP-method being called
	 *
	 * @access protected
	 * @return bool
	 */
	protected function _custom_auth_override_check()
	{
		// Assign the class/method auth type override array from the config
		$auth_override_class_method = $this->config->item('auth_override_class_method');

		// Check to see if the override array is even populated
		if (!empty($auth_override_class_method))
		{
			// check for wildcard flag for rules for classes
			if (!empty($auth_override_class_method[$this->router->class]['*'])) // Check for class overrides
			{
				// None auth override found, prepare nothing but send back a TRUE override flag
				if ($auth_override_class_method[$this->router->class]['*'] === 'none')
				{
					return TRUE;
				}

				// Basic auth override found, prepare basic
				if ($auth_override_class_method[$this->router->class]['*'] === 'custom')
				{
					$this->_custom_prepare_basic_auth();

					return TRUE;
				}
			}

			// Check to see if there's an override value set for the current class/method being called
			if (!empty($auth_override_class_method[$this->router->class][$this->router->method]))
			{
				// None auth override found, prepare nothing but send back a TRUE override flag
				if ($auth_override_class_method[$this->router->class][$this->router->method] === 'none')
				{
					return TRUE;
				}

				// Basic auth override found, prepare basic
				if ($auth_override_class_method[$this->router->class][$this->router->method] === 'custom')
				{
					$this->_custom_prepare_basic_auth();

					return TRUE;
				}
			}
		}

		// Assign the class/method/HTTP-method auth type override array from the config
		$auth_override_class_method_http = $this->config->item('auth_override_class_method_http');

		// Check to see if the override array is even populated
		if (!empty($auth_override_class_method_http))
		{
			// check for wildcard flag for rules for classes
			if(!empty($auth_override_class_method_http[$this->router->class]['*'][$this->request->method]))
			{
				// None auth override found, prepare nothing but send back a TRUE override flag
				if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'none')
				{
					return TRUE;
				}

				// Basic auth override found, prepare basic
				if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'custom')
				{
					$this->_custom_prepare_basic_auth();

					return TRUE;
				}
			}

			// Check to see if there's an override value set for the current class/method/HTTP-method being called
			if(!empty($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method]))
			{
				// None auth override found, prepare nothing but send back a TRUE override flag
				if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'none')
				{
					return TRUE;
				}

				// Basic auth override found, prepare basic
				if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'custom')
				{
					$this->_custom_prepare_basic_auth();

					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Prepares for basic authentication
	 *
	 * @access protected
	 * @return void
	 */
	protected function _custom_prepare_basic_auth()
	{
		$key = $this->input->get_request_header(AUTH_KEY);
		if($key===NULL&&$this->session->userdata(AUTH_KEY)) $key = $this->session->userdata(AUTH_KEY);
		$this->load->model("Auth_model");
		$key_detail = $this->Auth_model->check_user_key($key);		
		if(!empty($key_detail))
		{
			
			$user_data = $this->Auth_model->get_user_detail(array('user_id'=>$key_detail['user_id']));
			$data_arr = array(
								'user_id'        => $user_data['user_id'],
								'user_unique_id' => $user_data['user_unique_id'],
								'user_name'      => $user_data['user_name'],
								'added_date'     => $user_data['added_date'],
								'city'           => $user_data['city'],
								'dob'            => $user_data['dob'],
								'email'          => $user_data['email'],
								'first_name'     => $user_data['first_name'],
								'last_name'      => $user_data['last_name'],
								'status'         => $user_data['status'],
								'roles'         => $user_data['roles'],
							);
			$this->seesion_initialization($data_arr);
			return TRUE;
		}
		else
		{
		
			if($this->request->method==='get')
			{
				

				redirect('login');
			}
			else
			{
				$this->response([
					$this->config->item('rest_status_field_name') => FALSE,
					$this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unauthorized')
				], self::HTTP_UNAUTHORIZED);
			}
		}
	}
}
/* End of file MYREST_Controller.php */
/* Location: ./application/controllers/MYREST_Controller.php */