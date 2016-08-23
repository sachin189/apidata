<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Project_model extends MY_Model {

	function __construct() {
		parent::__construct();
	}


	/*
	* Get project list of admin
	* You can add colunm name for optimiuze thi query etc.
	* @return arary() 
	*/
	public function get_admin_project_list()
	{
		$this->db->select('P.pid,P.title')
				->from(RELATION_PROJECT_USER.' RPU')
				->join(PROJECTS.' P','P.pid=RPU.p_id','INNER')
				->join(USER.' U','U.user_id=RPU.user_id','INNER')
				->join(ROLES.' R','R.id=RPU.role','LEFT');

		$quey = $this->db->get();
		$res = $quey->result_array();
		return $res;	
	}

	/*
	* Get project list of manager
	* You can add colunm name for optimiuze thi query etc.
	* @return arary() 
	*/
	public function get_manager_project_list()
	{
		$this->db->select('P.pid,P.title,R.name')
				->from(RELATION_PROJECT_USER.' RPU')
				->join(PROJECTS.' P','P.pid=RPU.p_id','INNER')
				->join(USER.' U','U.user_id=RPU.user_id','INNER')
				->join(ROLES.' R','R.id=RPU.role','LEFT')
				->where('RPU.user_id',$this->session->userdata('user_id'))
				->where('R.id',2);

		$quey = $this->db->get();
		$res = $quey->result_array();
		return $res;	
	}

	/*
	* Get project list of Dev
	* You can add colunm name for optimiuze thi query etc.
	* @return arary() 
	*/
	public function get_dev_project_list()
	{
		$this->db->select('P.pid,P.title,R.name')
				->from(RELATION_PROJECT_USER.' RPU')
				->join(PROJECTS.' P','P.pid=RPU.p_id','INNER')
				->join(USER.' U','U.user_id=RPU.user_id','INNER')
				->join(ROLES.' R','R.id=RPU.role','LEFT')
				->where('RPU.user_id',$this->session->userdata('user_id'))
				->where('R.id',3);

		$quey = $this->db->get();
		$res = $quey->result_array();
		return $res;	
	}

	/*
	* Get project Developer list
	* You can add colunm name for optimiuze thi query etc.
	* @return arary() 
	*/
	public function get_project_dev_list($devarray)
	{
		$this->db->select('RPU.p_id,U.user_id,U.first_name,U.last_name')
				->from(RELATION_PROJECT_USER.' RPU')
				->join(PROJECTS.' P','P.pid=RPU.p_id','INNER')
				->join(USER.' U','U.user_id=RPU.user_id','INNER')
				->join(ROLES.' R','R.id=RPU.role','LEFT')
				->where_in('RPU.p_id',$devarray)
				->where('R.id',3)
				->group_by('U.user_id');
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
	* Get All project list
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

	/*
	* Check Project valid users
	* You can add colunm name for optimiuze thi query etc.
	* @return arary() 
	*/
	public function check_valid_users($pid,$roles,$uid)
	{
		$this->db->select('count(id) AS count')
				->from(RELATION_PROJECT_USER)
				->where('p_id',$pid)
				->where('role',$roles)
				->where('user_id',$uid);
		$query =  $this->db->get();		
		return $query->row_array(); 	
	}

}