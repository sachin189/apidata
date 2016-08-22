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
	 * [new_project description]
	 * @MethodName new_project
	 * @Summary This function used to create project
	 * @return 
	*/
	public function get_my_project_post()
	{
		$result = array();
		$result['project_list'] = $this->Project_model->get_my_project_list();
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