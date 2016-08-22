<?php
class MY_Model extends CI_Model {

	public $prefix="";
	public $testingNode =FALSE;
	//name of the table for this Model
	public $table_name="";

	//name of the primary key in this table
	public $primary_key="";

	private $default=array();

	function __construct()
	{
		// Call the Model constructor
		parent::__construct();

		//initialize all the global variables
		$this->init();
	}

	function get($config=array()){
		// debug($config);
		$config=array_merge($this->default,$config);

		if($config["fields"]){
			$this->db->select($config["fields"],false);
		}

		$this->db->from($this->table_name);

		if($config["join"]!=""){
			if(is_array($config["join"])){

				foreach($config["join"] as $join_table=>$join_condition){
					if(isset($config["join_type"]) && $config["join_type"]!=""){
						$join_type=$config["join_type"];
					}else{
						$join_type="";
					}
					$this->db->join($join_table,$join_condition,$join_type);
				}
			}
		}
		$where     ="";
		$where_arr =array();

		if(isset( $config["where"] ) && $config["where"] ){
			$this->db->where( $config["where"] );
		}

		if(isset($config["or_where"])){
			// $this->db->or_where($config["or_where"]);
			foreach($config['or_where'] as $field=>$value){
				$where_arr[] = "$field '$value'" ;
			}
			$where=implode(" OR ", $where_arr);
			$this->db->where(" ($where) ", NULL, FALSE);
		}

		if(isset($config['where_in'])){
			foreach ($config['where_in'] as $field => $value) {
				$values = @implode(",", $value);
				$this->db->where_in($field,$value);
			}
		}

		if(isset($config['Orwhere'])){
			foreach($config['Orwhere'] as $field=>$value){
				$this->db->or_where($field, $value); 
			}
		}

		if(isset($config['between'])){
			foreach($config['between'] as $field=>$value){
				foreach($value as $keys => $between_value){
					$this->db->where($field.' '.$between_value, NULL, FALSE); 
				}
			}
		}

		if(isset($config['mysql_find_set'])){
			$this->db->where($config['mysql_find_set']);
		}

		if(isset($config['mysql_function'])){
			if(is_array($config["mysql_function"])){
				foreach ($config["mysql_function"] as $key => $value) {
					$this->db->where($value);
				}
			}else{
				$this->db->where($config['mysql_function']);
			}
		}

		if(isset($config['or_where_in'])){
			foreach($config['or_where_in'] as $field => $value){
				$this->db->or_where_in($field,$value);
			}
		}

		if(isset($config['min'])){
			foreach ($config['min'] as $key => $value) {
				$this->db->select_min($key,$value);
			}
		}		

		if(isset($config['max'])){
			foreach ($config['max'] as $key => $value) {
				$this->db->select_max($key,$value);
			}
		}		

		if(isset($config['group_by'])){
			$this->db->group_by($config['group_by']); 
		}

		if($config["limit"]!="" && !$config["count_only"]){
			$this->db->limit($config["limit"],$config["start"]);
		}

		if($config["count_only"]){
			$result = $this->db->count_all_results();
		}else{
			if($config["sort_field"] != ""){
				// debug("Sort field was present");
				$this->db->order_by($config["sort_field"],$config["sort_order"]);
			}
			$query = $this->db->get();
			$result =  !$config["only_one_record"]  ? $query->result_array() : $query->row_array() ;
		}
		// debug($this->db->last_query());
		return $result;
	}

	/*common function used to get all data from any table
	* @param String $select
	* @param String $table
	* @param Array/String $where
	*/
	function get_all_table_data ($select = '*', $table, $where = "") {
		$this->db->select($select);
		$this->db->from($table);
		if ($where != "") {
			$this->db->where($where);
		}
		$query = $this->db->get();
		return $query->result_array();
	}

	function get_single_row ($select = '*', $table, $where = "") {
		$this->db->select($select);
		$this->db->from($table);
		if ($where != "") {
			$this->db->where( $where );
		}
		$query = $this->db->get();
	    $this->db->last_query();
		return $query->row_array();
	}

	function insert($data){
		$this->db->insert($this->table_name, $data);         
		if ($this->db->affected_rows() == 1)
		{
			return $this->db->insert_id();
		}
		
		return FALSE;       
	}

	function insert_batch( $data )
	{
		$this->db->insert_batch( $this->table_name , $data );         
		if ($this->db->affected_rows() > 0 )
		{
			return TRUE;
		}
		
		return FALSE;       
	}

	function insert_unique($condition, $data){
		$arr_where=array();
		foreach($condition as $field_name=>$field_value){
			$arr_where[$field_name]=$field_value;
		}
		$config["where"]=$arr_where;

		$resp=$this->get($config);

		if (count($resp)>=1) {
			$resp[0][0];
			//return the first field of the first record
		}
		//debug($data);

		//Otherwise return the insert id
		$insert_id=$this->insert($data);
		return $insert_id;
	}

	function update($condition,$data){
		if(is_array($condition)){
			$this->db->where($condition);    
		}else{
			$this->db->where($this->primary_key,$condition);
		}

		$this->db->update($this->table_name, $data);
		if ($this->db->affected_rows())
		{
			return TRUE;
		}
		
		return FALSE;       
	}
	
	function delete($condition){
		if(is_array($condition)){
			$this->db->where($condition);
		}else{
			$this->db->where($this->primary_key,$condition);
		}
		$this->db->delete($this->table_name);
		if ($this->db->affected_rows() == 1)
		{
			return TRUE;
		}
		
		return FALSE;        
	}   
	
	function count($config){
		$config["count_only"]=true;
		return $this->get($config);
	}

	function get_combo_data( $value_field , $label_field , $combo_data=array() , $condition="" ){

		$config["fields"]     ="$value_field,$label_field";
		$config["sort_field"] =$label_field;
		$config["sort_order"] ="ASC";
		if($condition!=""){
			$config["where"]=$condition;
		}
		$response=$this->get($config);

		if($response){
			// $combo_data=array(""=>"Select");
			foreach($response as $row){
				$index=$row[$value_field];
				$combo_data["$index"]=$row[$label_field];
				// debug()
			}
			return $combo_data;
		}else{
			return $response;
		}
	}

	function run_query($sql,$result_type = 'result_array'){
		$rs = $this->db->query($sql);
		if ($rs->num_rows() > 0) {
			if ($result_type == 'row_array') {
				return $rs->row_array();
			} else {
				return $rs->result_array();
			}
		} else {
			return false;
		}
	}
	//This function clears all global variables so that we can use it again.
	//
	function init(){
		$this->default=array(
			"fields"          => "*",
			"join"            => "",
			"where"           => "",
			"search_exact"    => false,
			"sort_field"      => $this->primary_key,
			"sort_order"      => "DESC",
			"limit"           => "",
			"start"           => 0,
			"only_one_record" => false,
			"count_only"      => false,
			"format"          => "array" //json, or objects
		);
	}
	
	
	/**
	 * Replace into Batch statement
	 *
	 * Generates a replace into string from the supplied data
	 *
	 * @access    public
	 * @param    string    the table name
	 * @param    array    the update data
	 * @return    string
	 */
	public function replace_into_batch($table, $data)
	{
		$column_name	= array();
		$update_fields	= array();
		$append			= array();
		foreach($data as $i=>$outer)
		{
			$column_name = array_keys($outer);
			$coloumn_data = array();
			foreach ($outer as $key => $val) 
			{

				if($i == 0)
				{
					// $column_name[]   = "`" . $key . "`";
					$update_fields[] = "`" . $key . "`" .'=VALUES(`'.$key.'`)';
				}

				if (is_numeric($val)) 
				{
					$coloumn_data[] = $val;
				} 
				else 
				{
					$coloumn_data[] = "'" . replace_quotes($val) . "'";
				}
			}
			$append[] = " ( ".implode(', ', $coloumn_data). " ) ";
		}

		/*

			INSERT INTO `vi_player_temp` (`player_unique_id`, `salary`, `weightage`) VALUES ('000bc6c6-c9a8-4631-92d6-1cea5aaa1644',0,'')
			ON DUPLICATE KEY UPDATE `player_unique_id`=VALUES(`player_unique_id`), `salary`=VALUES(`salary`), `weightage`=VALUES(`weightage`)
		*/

		// $sql = "REPLACE INTO " . $this->db->dbprefix($table) . " ( " . implode(", ", $column_name) . " ) VALUES " . implode(', ', $append) ;

		$sql = "INSERT INTO " . $this->db->dbprefix($table) . " ( " . implode(", ", $column_name) . " ) VALUES " . implode(', ', $append) . " ON DUPLICATE KEY UPDATE " .implode(', ', $update_fields);
		// $sql = "INSERT INTO ". $this->db->dbprefix($table) ." (".implode(', ', $keys).") VALUES (".implode(', ', $values).") ON DUPLICATE KEY UPDATE ".implode(', ', $update_fields);

		
		$this->db->query($sql);
	}

}
//End of file