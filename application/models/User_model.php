<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class User_model extends MY_Model {

	function __construct() {
		parent::__construct();
	}

	/*
	* Get user details for login users
	* You can add colunm name for optimiuze thi query like, username email etc.
	* return arary() 
	*/

	public function user_profile_data($email, $password)
	{
		$this->db->select('*')
				->from(USERS)
				->where('email',$email)
				->where('password',$password);
		$query =  $this->db->get();		
		return $query->row_array(); 	
	}
}