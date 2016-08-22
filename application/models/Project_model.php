<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Project_model extends MY_Model {

	function __construct() {
		parent::__construct();
	}


	/*
	* Get project list of users
	* You can add colunm name for optimiuze thi query etc.
	* @return arary() 
	*/
	public function get_my_project_list()
	{
		$this->db->select('P.pid,P.title')
				->from(RELATION_PROJECT_USER.' RPU')
				->join(PROJECTS.' P','P.pid=RPU.p_id','LEFT')
				->join(ROLES.' R','R.id=RPU.role','LEFT')
				->join(USER.' U','U.user_id=RPU.user_id','INNER')
				->where('RPU.user_id',$this->session->userdata('user_id'))
				->where('RPU.role',3);

		$quey = $this->db->get();
		$res = $quey->result_array();
		return $res;	
	}

	/*
	* Get project list
	* You can add colunm name for optimiuze thi query etc.
	* @return arary() 
	*/
	public function get_roles_list()
	{
		$this->db->select('*')
				->from(ROLES)
				->where('id>',1);
		$query =  $this->db->get();		
		return $query->result_array(); 	
	}

	/*
	* Get project list
	* You can add colunm name for optimiuze thi query etc.
	* @return arary() 
	*/
	public function get_project_list()
	{
		$this->db->select('*')
				->from(PROJECTS)
				->where('user_id',$this->session->userdata('user_id'));
		$query =  $this->db->get();		
		return $query->result_array(); 	
	}

	/*
	* Get user list
	* You can add colunm name for optimiuze thi query etc.
	* @return arary() 
	*/
	public function get_user_list()
	{
		$this->db->select('user_id,user_name,first_name,last_name')
				->from(USER)
				->where('roles!=',1);
		$query =  $this->db->get();		
		return $query->result_array(); 	
	}
}