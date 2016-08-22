<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Auth_model extends MY_Model 
{
	public function __construct()
	{
		parent::__construct();

		// $this->load->model('Mail_model');
	}

	/**
	 * [check_user_key description]
	 * @MethodName check_user_key
	 * @Summary This function used for check user key exist
	 * @param      [varchar]  [Login key]
	 * @return     [array]
	 */
	public function check_user_key($key)
	{
		$sql = $this->db->select("*")
						->from(ACTIVE_LOGIN)
						->where("key",$key)
						->get();
		$result = $sql->row_array();
		return ($result)?$result:array();
	}

	public function get_user_data($input_arry=array())
	{
		$this->db->select()->from(USER);
		if(array_key_exists('email', $input_arry) && $input_arry['email'] )
		{
			$this->db->or_where('email', strtolower($input_arry['email']));
		}

		if(array_key_exists('facebook_id', $input_arry) && $input_arry['facebook_id'] )
		{
			$this->db->or_where('facebook_id', $input_arry['facebook_id']);
		}

		if(array_key_exists('user_name', $input_arry) && $input_arry['user_name'] )
		{
			$this->db->or_where('user_name', $input_arry['user_name']);
		}

		$rs = $this->db->get();
		$result = $rs->row_array();

		return $result;
	}

	public function registration($post)
	{
		if(isset($post['email'])){
			$post['email'] = strtolower($post['email']);
		}
		$user_data = $this->get_user_data($post);

		$post['language'] = $this->session->userdata('language');
		
		$response = array(
					'status'   =>FALSE,
					'msg'      =>'Some thing went wrong',
					'acc_type' => '',
					'data'     => array()
				);

		if(empty($user_data))
		{
			// Registration for new user
			$reset_key = $post['user_unique_id'] = self::_generate_key();

			
			 
			 if(empty($post['status'])){
			 	$post['status'] = '2';	
			 }
			$this->db->insert(USER, $post);
			$inserted_id = $this->db->insert_id();
			$rows        = $this->get_single_row('*',USER,array('user_id'=>$inserted_id));

			$rows['first_name'] = $post['first_name'];
			$rows['last_name']  = $post['last_name'];

			$post['user_id'] = $inserted_id;
			

			$time=time();
			$link_verify = FALSE;
			if ((isset($post['facebook_id']) && $post['facebook_id'] != "") )
			{
				$link_verify = TRUE;
				$response = array(
							'status'   =>TRUE,
							'msg'      => $this->lang->line('registration_successfull'),
							'acc_type' =>'facebook',
							'data'     => $post
						);
			}
		}
		else
		{
			if(array_key_exists('email', $post) &&  $post['email'] == $user_data['email']){
				// Email already exist in db
				// Check user comes as a  facebook user
				if((isset($post['facebook_id']) && $post['facebook_id'] != ""))
				{
					$update_data = array('facebook_id'=> $post['facebook_id'],'image'=>$post['image'],'last_login'=>date('Y-m-d H:i:s'));
					$update_data['language'] = $this->session->userdata('language');
					$where       = array('user_id'=> $user_data['user_id']);
					$this->db->update(USER, $update_data , $where);

					$response = array(
								'status'   =>TRUE,
								'msg'      =>'',
								'acc_type' => 'facebook',
								'data'     => $user_data
							);
				}
				else
				{
					// Returning ERROR 
					$response = array(
							'status' => FALSE,
							'msg'    => $this->lang->line('email_exist')
						);
				}
			}
			else if(array_key_exists('user_name', $post) && $post['user_name'] == $user_data['user_name'])
			{
				// Returning ERROR 
				$response = array(
							'status' => FALSE,
							'msg'    => $this->lang->line('username_exist')
						);
			}
			else if(isset($post['facebook_id']) && $post['facebook_id'] == $user_data['facebook_id'])
			{
				$update_data = array('image'=>$post['image'],'last_login'=>date('Y-m-d H:i:s'));
				$update_data['language'] = $this->session->userdata('language');
				$where       = array('user_id'=> $user_data['user_id']);
				$this->db->update(USER,$update_data , $where);
				// Facebook id exist in database
				$response = array(
						'status'	=>TRUE,
						'msg'		=>'',
						'acc_type'	=> 'facebook',
						'data'		=> $user_data
					);
			}
		}
		return $response;
	}

	public function clear_attempts($ip_address, $login, $expire_period = 86400)
	{
		$this->db->where(array('ip_address' => $ip_address, 'login' => $login));
		// Purge obsolete login attempts
		// $this->db->or_where('UNIX_TIMESTAMP(time) <', time() - $expire_period);
		$this->db->delete(LOGIN_ATTEMPTS);
	}
	


   //check username exist
   public function check_user_name($username = '')
   {
	  $result = $this->db->select()
							->from('user')
							->where('user_name', $username)
							->get();
	 return $result->num_rows;   
   }

   //check email exist
   public function check_user_email($email = '')
   {
	  $result = $this->db->select()
							->from('user')
							->where('email', $email)
							->get();
	  return $result->num_rows;   
   }

   public function check_password_update()
   {
	   $is_login = ($this->user_id) ? $this->user_id : 0;
	   $result = $this->db->select('password')
							->from('user')
							->where('user_id', $is_login)
							->get();	 
	  return $result->row_array();
   }


	private function _generate_key() 
	{
		$this->load->helper('security');

		do {
			$salt = do_hash(time() . mt_rand());
			$new_key = substr($salt, 0, 10);
		}

		// Already in the DB? Fail. Try again
		while (self::_key_exists($new_key));

		return $new_key;
	}

	private function _key_exists($key)
	{
		return $this->db->where('user_unique_id', $key)->count_all_results(USER) > 0;
	}

	public function get_user_detail($where_clause)
    {
    	$sql = $this->db->select()
    					->from(USER)
    					->where($where_clause)
    					->get();
    	$result = $sql->row_array();
    	return $result;
    }

    public function get_user_native_detail($username,$password)
    {


    	$sql = $this->db->select("user_id, user_name, added_date, city, dob, email, first_name, last_name, image, user_unique_id, status,roles,language")
    					->from(USER)
    					->where("password", $password)
    					->group_start()
						->where("user_name", $username)
						->or_where("email", strtolower($username))
						->group_end()
    					->get();
    	$result = $sql->row_array();
    	return $result;
    }

    /*common function used to get single row from any table
	* @param String $select
	* @param String $table
	* @param Array/String $where
	*/
	function get_single_row($select = '*', $table, $where = "", $group_by = "", $order_by = "", $offset = '', $limit = '')
	{
		$this->db->select($select);
		$this->db->from($table);
		if ($where != "") {
			$this->db->where($where);
		}
		if ($group_by != "") {
			$this->db->group_by($group_by);
		}
		if ($order_by != "") {
			$this->db->order_by($order_by);
		}
		if ($limit != "") {
			$this->db->limit($offset, $limit);
		}
		$query = $this->db->get();
		return $query->row_array();
	}
}