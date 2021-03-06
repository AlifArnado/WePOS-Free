<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class MasterProductPackage extends MY_Controller {
	
	public $table;
		
	function __construct()
	{
		parent::__construct();
		$this->prefix = config_item('db_prefix2');
		$this->load->model('model_masterproductpackage', 'm');
	}

	public function gridData()
	{
		$this->table = $this->prefix.'product_package';
		$this->product_img_url = RESOURCES_URL.'product/thumb/';
		
		//package_id
		$package_id = $this->input->post('package_id');
		
		if(empty($package_id)){
			$r = array('success' => false);
			die(json_encode($r));
		}
		
		//is_active_text
		$sortAlias = array(
			'is_active_text' => 'a.is_active'
		);		
		
		// Default Parameter
		$params = array(
			'fields'		=> 'a.*, b.product_name, b.product_image, b.product_desc, c.product_category_name',
			'primary_key'	=> 'a.id',
			'table'			=> $this->table.' as a',
			'join'			=> array(
									'many', 
									array( 
										array($this->prefix.'product as b','b.id = a.product_id','LEFT'),
										array($this->prefix.'product_category as c','c.id = b.category_id','LEFT')
									) 
								),
			'where'			=> array('a.is_deleted' => 0),
			'order'			=> array('a.id' => 'DESC'),
			'sort_alias'	=> $sortAlias,
			'single'		=> false,
			'output'		=> 'array' //array, object, json
		);		
		
		if(!empty($package_id)){
			$params['where'] = array('package_id' => $package_id);
		}
		
		//get data -> data, totalCount
		$get_data = $this->m->find_all($params);
		  		
  		$newData = array();
		if(!empty($get_data['data'])){
			foreach ($get_data['data'] as $s){
				if(empty($s['product_image'])){
					$s['product_image'] = 'no-image.jpg';
				}
				$s['product_image_show'] = '<img src="'.$this->product_img_url.$s['product_image'].'" style="max-width:80px; max-height:60px;"/>';
				$s['product_image_src'] = $this->product_img_url.$s['product_image'];
				
				array_push($newData, $s);
			}
		}
		
		$get_data['data'] = $newData;
		
      	die(json_encode($get_data));
	}
	
	public function productItem()
	{
		$this->table = $this->prefix.'product_package';
		$this->table2 = $this->prefix.'product';
		$this->product_img_url = RESOURCES_URL.'product/thumb/';
		
		//package_id
		$package_id = $this->input->post('package_id');
		
		if(empty($package_id)){
			$r = array('success' => false);
			die(json_encode($r));
		}
		
		//get all current item				
		$except_product_id = array();
		$this->db->where("id", $package_id);
		$curr_product = $this->db->get($this->table);
		if($curr_product->num_rows() > 0){
			foreach($curr_product->result() as $dt){
				if(!in_array($dt->product_id, $except_product_id)){
					$except_product_id[] = $dt->product_id;
				}
			}
		}
		
		// Default Parameter
		$params = array(
			'fields'		=> '*',
			'primary_key'	=> 'id',
			'table'			=> $this->table2,
			'where'			=> array('is_deleted' => 0, 'product_type' => 'item'),
			'order'			=> array('id' => 'DESC'),
			'single'		=> false,
			'output'		=> 'array' //array, object, json
		);
		
		//DROPDOWN & SEARCHING
		$is_dropdown = $this->input->post('is_dropdown');
		$searching = $this->input->post('query');
		$show_all_text = $this->input->post('show_all_text');
		$show_choose_text = $this->input->post('show_choose_text');
		
		if(!empty($is_dropdown)){
			$params['order'] = array('product_desc' => 'ASC');
		}
		if(!empty($searching)){
			$params['where'][] = "(product_name LIKE '%".$searching."%' OR product_desc LIKE '%".$searching."%')";
		}
		
		//except		
		if(!empty($except_product_id)){
			$except_product_id_txt = implode(",", $except_product_id);
			$params['where'][] = "(id NOT IN ('".$except_product_id_txt."'))";
		}
		
		//get data -> data, totalCount
		$get_data = $this->m->find_all($params);
		  		
  		$newData = array();		
		
		if(!empty($show_all_text)){
			$dt = array('id' => '-1', 'product_name' => 'Choose All Product');
			array_push($newData, $dt);
		}else{
			if(!empty($show_choose_text)){
				$dt = array('id' => '', 'product_name' => 'Choose Product');
				array_push($newData, $dt);
			}
		}
		
		if(!empty($get_data['data'])){
			
			foreach ($get_data['data'] as $s){
				if(empty($s['product_image'])){
					$s['product_image'] = 'no-image.jpg';
				}
				$s['product_image_show'] = '<img src="'.$this->product_img_url.$s['product_image'].'" style="max-width:80px; max-height:60px;"/>';
				$s['product_image_src'] = $this->product_img_url.$s['product_image'];
				$s['is_active_text'] = ($s['is_active'] == '1') ? '<span style="color:green;">Active</span>':'<span style="color:red;">Inactive</span>';
				
				array_push($newData, $s);
			}
		}
		
		$get_data['data'] = $newData;
		
      	die(json_encode($get_data));
	}
	
	/*SERVICES*/
	public function save()
	{
		$this->table = $this->prefix.'product_package';				
		$this->table_product = $this->prefix.'product';				
		$session_user = $this->session->userdata('user_username');
		
		
		$package_id = $this->input->post('package_id');
		$product_id = $this->input->post('product_id');
		$product_price = $this->input->post('product_price');
		$product_hpp = $this->input->post('product_hpp');		
		
		if(empty($product_id) OR empty($package_id)){
			$r = array('success' => false);
			die(json_encode($r));
		}	
			
		$r = '';
		if($this->input->post('form_type_masterProductPackage', true) == 'add')
		{
			$var = array(
				'fields'	=>	array(
				    'package_id'  => 	$package_id,
				    'product_id'  => 	$product_id,
					'product_price'	=>	$product_price,
					'product_hpp'	=>	$product_hpp,
					'created'		=>	date('Y-m-d H:i:s'),
					'createdby'		=>	$session_user,
					'updated'		=>	date('Y-m-d H:i:s'),
					'updatedby'		=>	$session_user
				),
				'table'		=>  $this->table
			);	
			
			//SAVE
			$insert_id = false;
			$this->lib_trans->begin();
				$q = $this->m->add($var);
				$insert_id = $this->m->get_insert_id();
			$this->lib_trans->commit();			
			if($q)
			{ 
				$r = array('success' => true, 'id' => $insert_id); 		
				
				//update HPP product
				$product_hpp = $this->m->product_hpp($package_id);
				$r['product_hpp'] = $product_hpp['product_hpp'];
				$r['normal_price'] = $product_hpp['normal_price'];
				
			}  
			else
			{  				
				$r = array('success' => false);
			}
      		
		}else
		if($this->input->post('form_type_masterProductPackage', true) == 'edit'){
			$var = array('fields'	=>	array(
				    'package_id'  => 	$package_id,
				    'product_id'  => 	$product_id,
					'product_price'	=>	$product_price,
					'product_hpp'	=>	$product_hpp,
					'updated'		=>	date('Y-m-d H:i:s'),
					'updatedby'		=>	$session_user
				),
				'table'			=>  $this->table,
				'primary_key'	=>  'id'
			);
								
			//UPDATE
			$id = $this->input->post('id', true);
			$this->lib_trans->begin();
				$update = $this->m->save($var, $id);
			$this->lib_trans->commit();
			
			if($update)
			{  
				$r = array('success' => true, 'id' => $id);
				
				//update HPP product
				$product_hpp = $this->m->product_hpp($package_id);
				$r['product_hpp'] = $product_hpp['product_hpp'];
				$r['normal_price'] = $product_hpp['normal_price'];
				
			}  
			else
			{ 				
				$r = array('success' => false);
			}
		}
		
		die(json_encode(($r==null or $r=='')? array('success'=>false) : $r));
	}
	
	public function delete()
	{
		$this->table = $this->prefix.'product_package';
		
		$get_id = $this->input->post('id', true);		
		$id = json_decode($get_id, true);
		//old data id
		$sql_Id = $id;
		if(is_array($id)){
			$sql_Id = implode(',', $id);
		}
		
		
		$this->db->from($this->table);
		$this->db->where("id IN (".$sql_Id.")");
		$get_product_package = $this->db->get();
		
				
		//Delete
		//$data_update = array(
		//	"is_deleted" => 1
		//);
		//$q = $this->db->update($this->table, $data_update, "id IN (".$sql_Id.")");
		
		$q = $this->db->delete($this->table, "id IN (".$sql_Id.")");
		
		$r = '';
		if($q)  
        {  
            $r = array('success' => true); 
			
			$package_id = 0;
			if($get_product_package->num_rows() > 0){
				$dt_product_package = $get_product_package->row();
				$package_id = $dt_product_package->package_id;
			}
			
			//update HPP product
			$product_hpp = $this->m->product_hpp($package_id);
			$r['product_hpp'] = $product_hpp['product_hpp'];
			$r['normal_price'] = $product_hpp['normal_price'];
        }  
        else
        {  
            $r = array('success' => false, 'info' => 'Delete Product Package Failed!'); 
        }
		die(json_encode($r));
	}
	
}