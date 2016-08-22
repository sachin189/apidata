<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @package    Auth
 * @author     Jitendsra kulmi 
 * @version    Release: @package_version@
 * 
 */

class Auth extends MYREST_Controller {

	function __construct()
	{
		// Construct the parent class
		parent::__construct();
		$_POST = $this->post();
		$this->load->model('User_model');
		$this->load->model('Auth_model');

	}

	public function index(){

	}
	

	/**
	 * [_custom_login description]
	 * @MethodName _custom_login
	 * @Summary This function used manage custom login (Native Login)
	 * @return  array
	 */
	public function login_post()
	{
		if ($this->input->post())
		{
			$this->form_validation->set_error_delimiters('', '');
			$custom_login_rules = array(
				
				array(
						'field' => 'email',
						'label' => $this->lang->line('email'), 
						'rules' => 'trim|required', 
						'errors' => array()
					),
				array(
						'field' => 'password',
						'label' => $this->lang->line('password'), 
						'rules' => 'required|trim', 
						'errors' => array()
					),
				
			);
			
			$this->form_validation->set_rules($custom_login_rules);

			$email			= $this->input->post('email');
			$password		= md5($this->input->post('password'));
			$remember_me	= $this->input->post('remember_me');

			if (!$this->form_validation->run()) 
			{
				$this->send_validation_errors();
			}


			$data_array = array(
							'profile_type'=>'native',
							'email' => $email,
							'password' => $password
						);
			$profile_data = $this->_get_user_profile_data($data_array);

			//print_r($profile_data);exit;
			if(!empty($profile_data))
			{
				/*=========================== Check User Status And Return Response ====================*/
				switch ($profile_data['status']) 
				{
					case 0:
						$return[config_item('rest_status_field_name')] = FALSE;
						$return['message'] = $this->lang->line('user_inactive');
						$this->response($return, rest_controller::HTTP_INTERNAL_SERVER_ERROR);
					break;
					
					case 2:

						//call function for resend varification link to email
						$this->resend_email_verification_link($profile_data);


						$return[config_item('rest_status_field_name')] = FALSE;
						$return['message'] = $this->lang->line('email_not_confirmed');
						$this->response($return, rest_controller::HTTP_INTERNAL_SERVER_ERROR);
					break;

					case 3:
						$return[config_item('rest_status_field_name')] = FALSE;
						$return['message'] = $this->lang->line('user_deleted');
						$this->response($return, rest_controller::HTTP_INTERNAL_SERVER_ERROR);
						break;
				}
				/*=====================================================================================*/

				/*Remember_me Section for auto login*/
				if($remember_me)
					$this->_set_remember_me($email);

				/*Remove Active Login Key*/
				$this->delete_active_login_key($profile_data['user_id']);

				/*Generate Active Login Key*/
				$new_key = $this->generate_active_login_key($profile_data['user_id']);

				if($new_key == '0')
				{
					$return[config_item('rest_status_field_name')] = FALSE;
					$return['message'] = $this->lang->line('unable_create_key');
					$this->response($return, rest_controller::HTTP_INTERNAL_SERVER_ERROR);
				}
				else
				{
					//Remove Null value from array
					//$profile_data = remove_null_values($profile_data);
					//$profile_data['image'] = ($profile_data['image']) ? $profile_data['image'] : site_url(DEFAULT_PROFILE); 
					//$profile_data['image'] = show_user_image($profile_data['image'],TRUE); 
					$profile_data['is_login'] = TRUE; 
					$profile_data[AUTH_KEY] = $new_key; 

					$is_profile_complete = true;
					if(empty($profile_data['email']) || ( empty($profile_data['dob']) || is_null($profile_data['dob']))){
						$is_profile_complete = false;
					}

					if(!empty($profile_data['dob']) && !is_null($profile_data['dob'])){
						$profile_data['dob'] = date("M d, Y",strtotime($profile_data['dob']));
					}

					$profile_data['is_profile_complete'] = $is_profile_complete;

					/*Set Profile data in session and this variable*/
					$this->seesion_initialization($profile_data);

					$profile_data = prepareUserProfile($profile_data);//function defined in default helper
					$data = array(AUTH_KEY=>$new_key, 'profile_data'=>$profile_data);


					$return[config_item('rest_status_field_name')] = TRUE;
					$return['message'] = $this->lang->line('login_successfull');
					$return['data'] = $data;
					$this->response($return, rest_controller::HTTP_OK);
				}
			}
			else
			{
				$return[config_item('rest_status_field_name')] = FALSE;
				$return['message'] = $this->lang->line('invalid_login_detail');
				$this->response($return, rest_controller::HTTP_INTERNAL_SERVER_ERROR);
			}
		}
		else
		{
			$return[config_item('rest_status_field_name')] = FALSE;
			$return['message'] = $this->lang->line('invalid_type');
			$this->response($return, rest_controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	/**
	 * [_get_user_profile_data description]
	 * @MethodName _get_user_profile_data
	 * @Summary This function used to get user profile data
	 * @param      array   $data_array
	 * @return     array
	 */
	private function _get_user_profile_data($data_array = array())
	{
		switch ($data_array['profile_type']) {
			case 'facebook':
					$profile_data = $this->Auth_model->get_user_detail(array('facebook_id' => $data_array['facebook_id']));
					return $profile_data;
				break;
			case 'native':
					$profile_data = $this->Auth_model->get_user_native_detail($data_array['email'], $data_array['password']);	
					return $profile_data;
				break;
			default:
				break;
		}
	}

	/**
	 * [logout_post description]
	 * @MethodName logout_post
	 * @Summary This function used to distory session and cookies
	 * @return     array
	 */
	public function logout_post()
	{
		$key =  $this->input->get_request_header(AUTH_KEY);
		
		$response = $this->db->where(config_item('rest_key_column'), $key)->delete(config_item('rest_keys_table'));

		$language = $this->session->userdata('language');
		//manage user activity log

		$this->load->helper('cookie');
		delete_cookie('users');

		$this->session->sess_destroy();

		$return[config_item('rest_status_field_name')] = TRUE;
		$return['message'] = $this->lang->line('logout_successfully');
		$return['language'] = $language;

		$this->response($return, rest_controller::HTTP_OK);
	}


	public function signup_post()
	{
		 //print_r($this->input->post());
		 //die;
		if ($this->input->post()) 
		{
			$this->form_validation->set_error_delimiters('', '');

			$signup_rules = array(
				
				array(
						'field' => 'first_name',
						'label' => $this->lang->line('first_name'), 
						'rules' => 'required|trim|max_length[50]|min_length[2]', 
						'errors' => array()
					),
				array(
						'field' => 'last_name',
						'label' => $this->lang->line('last_name'), 
						'rules' => 'required|trim|max_length[50]|min_length[2]', 
						'errors' => array()
					),
				array(
						'field' => 'email',
						'label' => $this->lang->line('email'), 
						'rules' => 'is_unique['.USER.'.email]|max_length[100]', 
						'errors' => array('is_unique' => $this->lang->line('email_unique_error'))
					),
				array(
						'field' => 'username',
						'label' => $this->lang->line('user_name'), 
						'rules' => 'trim|required|is_unique['.USER.'.user_name]|max_length[150]|min_length[2]', 
						'errors' => array('is_unique' => $this->lang->line('username_exist'))
					),

				array(
						'field' => 'password',
						'label' => $this->lang->line('password'), 
						'rules' => 'required|trim|min_length[6]|max_length[255]', 
						'errors' => array()
					),
				array(
						'field' => 'confirm_password',
						'label' => $this->lang->line('confirm_password'), 
						'rules' => 'required|trim|min_length[6]|max_length[255]|matches[password]', 
						'errors' => array()
					)
			);
			
			$this->form_validation->set_rules($signup_rules);
			if ($this->form_validation->run() == FALSE)
			{
				$this->send_validation_errors();
			}
			else 
			{

				$post_values['first_name']     = $this->input->post('first_name');
				$post_values['last_name']      = $this->input->post('last_name');
				$post_values['email']          = strtolower($this->input->post('email'));
				$post_values['user_name']      = $this->input->post('username');
				$post_values['password']       = md5($this->input->post('password'));

				// code for signup with Facebook
				if( $this->input->post('roles') > 0 ){
					$post_values['roles']    =  $this->input->post('roles');
				}
				$post_values['status'] =  '1';
				$date								= date("Y-m-d H:i:s");
				$post_values['added_date'] 			= $date;
				$post_values['modified_date'] 		= $date;
				$post_values['last_ip'] 	 		= $this->input->ip_address();

				$post_values['country'] 	= $this->input->post('country');
				$post_values['state'] 	= $this->input->post('state');
				$post_values['city'] 				= $this->input->post('city');
				$post_values['address'] 			= $this->input->post('street');
				$post_values['dob'] 				= date("Y-m-d",strtotime($this->input->post('dob')));
				
				$email_exist = $this->User_model->get_single_row( 'email' , USER , array('email' => $post_values['email'], 'status' => '1'));

				if(!empty($email_exist['email']))
				{
					$error['email'] = $this->lang->line('email_exist');
					$response 		= array(
											config_item('rest_status_field_name')=>FALSE,
											'message'=> $error['email'] 
										);
					$this->response($response, rest_controller::HTTP_INTERNAL_SERVER_ERROR);
				}
				$uuid = $this->input->post('uuid');
				if(!empty($uuid))
				{
					$this->set_referral_data($uuid,$post_values['email']);
				}

				
				$response_data = $this->Auth_model->registration($post_values);
				$response 		= array(
										config_item('rest_status_field_name')=>TRUE,
										'message'	=> $this->lang->line('registration_successfull'),
										'data'		=> $response_data 
									);
				$this->response($response, rest_controller::HTTP_OK);
			}
		}
		else
		{
			$response 		= array(
									config_item('rest_status_field_name')=>FALSE,
									'message'	=> $this->lang->line('invalid_type')										
								);
			$this->response($response, rest_controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

}