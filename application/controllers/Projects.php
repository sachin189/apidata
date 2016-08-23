<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Projects extends MYREST_Controller {

	function __construct()
	{
		// Construct the parent class
		parent::__construct();
		$_POST = $this->post();
		$this->load->model('Project_model');
	}

	public function index(){

	}

	/**
	 * [get_project_ description]
	 * @MethodName get_project_
	 * @Summary This function used to get data for admin user to see all project detaols
	 * @return 
	*/
	public function get_project_post()
	{
		$result = array();
		if($this->session->userdata('roles')==1)
		{
			$result['project_list'] = $this->Project_model->get_admin_project_list();
			$response 		= array(
									config_item('rest_status_field_name')=>TRUE,
									'data'=>$result,
									'message'	=> ''										
								);
			$this->response($response, rest_controller::HTTP_OK);
		}
		else
		{
			$response 		= array(
									config_item('rest_status_field_name')=>FALSE,
									'message'	=> 'You are not uthorized to see all projects details.'										
								);
			$this->response($response, rest_controller::HTTP_OK);
		}
	}

	/**
	 * [get_manager_project description]
	 * @MethodName get_project_
	 * @Summary This function used to get projects as role manager.
	 * @return 
	*/
	public function get_manager_project_post()
	{
		if($this->session->userdata('roles')==1)
		{
			$response 		= array(
									config_item('rest_status_field_name')=>FALSE,
									'message'	=> 'You are login as admin'										
								);
			$this->response($response, rest_controller::HTTP_OK);
		}

		$result = array();
		$project_array = []; 
		$project_list = $this->Project_model->get_manager_project_list();
		$result['project_list'] = $project_list;
		foreach ($project_list as $key => $value) {
			$project_array[] =  $value['pid'];
		}

		if(count($project_array)>0)
		{
			$result['dev_list'] = $this->Project_model->get_project_dev_list($project_array);
		}

		$response 		= array(
									config_item('rest_status_field_name')=>TRUE,
									'data'=>$result,
									'message'	=> ''										
								);
		$this->response($response, rest_controller::HTTP_OK);
	}

	/**
	 * [get_manager_project description]
	 * @MethodName get_project_
	 * @Summary This function used to get project as role developer 
	 * @return 
	*/
	public function get_dev_project_post()
	{
		if($this->session->userdata('roles')==1)
		{
			$response 		= array(
									config_item('rest_status_field_name')=>FALSE,
									'message'	=> 'You are login as admin'										
								);
			$this->response($response, rest_controller::HTTP_OK);
		}
		$result = array();
		$result['project_list'] = $this->Project_model->get_dev_project_list();
		
		$response 		= array(
									config_item('rest_status_field_name')=>TRUE,
									'data'=>$result,
									'message'	=> ''										
								);
		$this->response($response, rest_controller::HTTP_OK);
	}

	/**
	 * [new_project description]
	 * @MethodName new_project
	 * @Summary This function used to create project
	 * @return 
	*/
	public function new_project_post()
	{
		if ($this->input->post()) 
		{
			$this->form_validation->set_error_delimiters('', '');
			$project_rules = array(
				
				array(
						'field' => 'title',
						'label' => $this->lang->line('project_title'), 
						'rules' => 'required|trim|max_length[200]|min_length[2]', 
						'errors' => array()
					),
				array(
						'field' => 'description',
						'label' => $this->lang->line('project_description'), 
						'rules' => 'required|trim', 
						'errors' => array()
					),
				array(
						'field' => 'summary',
						'label' => $this->lang->line('project_summary'), 
						'rules' => 'required|trim|max_length[200]|min_length[2]', 
						'errors' => array()
					),
				array(
						'field' => 'status',
						'label' => 'status', 
						'rules' => 'required|trim', 
						'errors' => array()
					)
			);

			$this->form_validation->set_rules($project_rules);
			if ($this->form_validation->run() == FALSE)
			{
				$this->send_validation_errors();
			}
			else 
			{
				$object['title'] = $this->input->post('title');
				$object['description'] = $this->input->post('description');
				$object['summary'] = $this->input->post('summary');
				$object['user_id'] = $this->session->userdata('user_id');
				$object['status'] = $this->session->userdata('status');
				
				if($this->input->post('pid'))
				{
					$object['modified_date'] = date("Y-m-d H:i:s");
					$this->db->where('pid', $this->input->post('pid'));
					$this->db->where('user_id', $this->session->userdata('user_id'));
					$this->db->update(PROJECTS, $object);
					$response 		= array(
									config_item('rest_status_field_name')=>TRUE,
									'message'	=> $this->lang->line('porject_update_successfully')										
								);
					$this->response($response, rest_controller::HTTP_OK);
				}
				else
				{
					$object['created_date'] = date("Y-m-d H:i:s");
					$last_id = $this->db->insert(PROJECTS, $object);
					$response 		= array(
									config_item('rest_status_field_name')=>TRUE,
									'message'	=> $this->lang->line('porject_successfully')										
								);
					$this->response($response, rest_controller::HTTP_OK);
				}
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


	/**
	 * [project_user_data description]
	 * @MethodName project_user_data
	 * @Summary This function used to get relational data for project like users,rols projects etc.
	 * @return array()
	*/
	public function project_user_data_post()
	{
		$result = array();
		$result['roles'] = $this->Project_model->get_roles_list();
		$result['users'] = $this->Project_model->get_user_list();
		$result['projects'] = $this->Project_model->get_project_list();
		$response 		= array(
									config_item('rest_status_field_name')=>TRUE,
									'data'=>$result,
									'message'	=> ''										
								);

		$this->response($response, rest_controller::HTTP_OK);

	}

	/**
	 * [project_assign description]
	 * @MethodName project_assign
	 * @Summary This function used to assign project to user
	 * @parms rols,project id, user id
	 * @return 
	*/
	public function project_assign_post()
	{
		if ($this->input->post()) 
		{
			$project_rules = array(
				
				array(
						'field' => 'p_id',
						'label' => $this->lang->line('porject_error'), 
						'rules' => 'required', 
						'errors' => array()
					),
				array(
						'field' => 'user_id',
						'label' => $this->lang->line('porject_user'), 
						'rules' => 'required', 
						'errors' => array()
					),
				array(
						'field' => 'role',
						'label' => $this->lang->line('porject_role'), 
						'rules' => 'required', 
						'errors' => array()
					)
			);

			$this->form_validation->set_rules($project_rules);
			if ($this->form_validation->run() == FALSE)
			{
				$this->send_validation_errors();
			}
			else 
			{
				$check = $this->Project_model->check_valid_users($this->input->post('p_id'),$this->input->post('role'),$this->input->post('user_id'));
				if($check['count']==0)
				{
					$object['p_id'] = $this->input->post('p_id');
					$object['user_id'] = $this->input->post('user_id');
					$object['role'] = $this->input->post('role');
					$object['created_date'] = date("Y-m-d H:i:s");
					$this->db->insert(RELATION_PROJECT_USER, $object);

					$response 		= array(
										config_item('rest_status_field_name')=>TRUE,
										'message'	=> 'Project assign successfully'										
									);

					$this->response($response, rest_controller::HTTP_OK);
				}
				else
				{
					$response 		= array(
										config_item('rest_status_field_name')=>TRUE,
										'message'	=> 'User already assign in this game'										
									);

					$this->response($response, rest_controller::HTTP_INTERNAL_SERVER_ERROR);
				}
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

/* End of file projects.php */
/* Location: ./application/controllers/projects.php */