<?php defined('BASEPATH') OR exit('No direct script access allowed');

class M_Cliente extends CI_Model {
	
	private $t_cliente = 'cliente',
			$t_cliente_correo =  'cliente_correo',
			$t_cliente_telefono = 'cliente_telefono',
			$t_cliente_calificacion = 'cliente_calificacion',
			$rol_id,
			$usuario_id;

	function __construct()
	{
		parent::__construct();
		//comprueba si hay una sesion iniciada
		$loggedin = $this->m_general->conectado();			
		if($loggedin){
			if($this->session->has_userdata('rol_id')){
				$this->rol_id = $this->session->userdata('rol_id');
			}
			if($this->session->has_userdata('usuario_id')){
				$this->usuario_id = $this->session->userdata('usuario_id');
			}			
		}
		$this->load->model('m_bitacora');
	}

	function consultaAll($data = null){
		//Consulta primero los datos
		if(isset($data['filtros'])){
			foreach ($data['filtros'] as $key => $value) {
				if($value!='' && $value!='undefined' && $value!=null  &&  $value!='all'){
					if($key=='nombre_cliente' || $key=='cedula_cliente'){
						$this->db->like($this->t_cliente.'.'.$key, $value);
					}else{
						$this->db->where($this->t_cliente.'.'.$key, $value);
					}
				}
			}
		}
		$this->db->join($this->t_cliente_calificacion, $this->t_cliente_calificacion.'.cliente_calificacion_id = '.$this->t_cliente.'.cliente_calificacion_id', 'LEFT');
		/*$offset = 0;
		$cantidad_mostrar = 2;
		if(isset($data['cantidad_mostrar'])){
			$cantidad_mostrar = (int)$data['cantidad_mostrar'];
		}
		if(isset($data['pagina'])){
			$pagina = (int)$data['pagina'];
			if($pagina>1){
				$offset=$pagina*$cantidad_mostrar;
			}
		}

		$this->db->limit($cantidad_mostrar, $offset);*/
		$result_cliente = $this->db->get($this->t_cliente);

		//vuelve a hacer la consulta para obtener el total de rows 
		/*if(isset($data['filtros'])){
			foreach ($data['filtros'] as $key => $value) {
				if($key=='nombre_cliente' || $key=='cedula_cliente'){
					$this->db->like($key, $value);
				}else{
					$this->db->where($key, $value);
				}
			}
		}
		$total_rows = $this->db->count_all_results($this->t_cliente, FALSE);*/

		if($result_cliente->num_rows()>0){
			$result = array(
							'total_rows' => $result_cliente->num_rows(),
							'datos' => $this->security->xss_clean($result_cliente->result_array()),
							);

			return $result;

		}else{
			return false;
		}
		

	}

	function getAllActiveClientes(){
		$this->db->where('estado_cliente', 1);
		$cliente_result = $this->db->get($this->t_cliente);
		if($cliente_result->num_rows() > 0){
			return $this->security->xss_clean($cliente_result->result_array());
		}else{
			return false;
		}
	}
	


	function insertar($data){
		foreach ($data['cliente'] as $kfield => $vfield) {
			$this->db->set($kfield, $vfield);
		}
		$this->db->set('usuario_id', $this->usuario_id);
		$this->db->set('fecha_registro', date('Y-m-d H:i:s'));
		
		$this->db->insert($this->t_cliente);
		$cliente_id = $this->db->insert_id();

		if(isset($data['cliente_correo']) || isset($data['cliente_telefono'])){
			unset($data['cliente']);
			foreach ($data as $ktipo => $vtipo) {
				foreach ($vtipo as $krow => $vrow) {
					foreach ($vrow as $kfield => $vfield) {
						$this->db->set($kfield, $vfield);
					}
					$this->db->set('cliente_id', $cliente_id);				
					$this->db->insert($ktipo);	
				}			
			}
		}
		return $cliente_id;
	}

	function consultar($cliente_id){
		if($cliente_id!=null){
			$result = array();
			$this->db->where('cliente_id', $cliente_id);
			$this->db->join($this->t_cliente_calificacion, $this->t_cliente_calificacion.'.cliente_calificacion_id = '.$this->t_cliente.'.cliente_calificacion_id', 'LEFT');
			$result_cliente = $this->db->get($this->t_cliente);
			if($result_cliente->num_rows()> 0){
				$result['cliente'] = $this->security->xss_clean($result_cliente->row_array());
				$this->db->where('cliente_id', $cliente_id);
				$result_cliente_correo = $this->db->get($this->t_cliente_correo);
				if($result_cliente_correo->num_rows()>0){
					$result['cliente_correo'] = $this->security->xss_clean($result_cliente_correo->result_array());
				}
				$this->db->where('cliente_id', $cliente_id);
				$result_cliente_telefono = $this->db->get($this->t_cliente_telefono);
				if($result_cliente_telefono->num_rows()>0){
					$result['cliente_telefono'] = $this->security->xss_clean($result_cliente_telefono->result_array());
				}
				return $result;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	function actualizar($cliente_id, $data){
		//actualiza los campos
		foreach ($data['cliente'] as $kfield => $vfield) {
			$this->db->set($kfield, $vfield);
		}
		$this->db->where('cliente_id', $cliente_id);		
		$this->db->update($this->t_cliente);
		
		//borra telefonos y correos viejos
		$this->db->where('cliente_id', $cliente_id);
		$this->db->delete($this->t_cliente_correo);

		$this->db->where('cliente_id', $cliente_id);
		$this->db->delete($this->t_cliente_telefono);

		//inserta correos y telefonos nuevamente
		if(isset($data['cliente_correo']) || isset($data['cliente_telefono'])){
			unset($data['cliente']);
			foreach ($data as $ktipo => $vtipo) {
				foreach ($vtipo as $krow => $vrow) {
					foreach ($vrow as $kfield => $vfield) {
						$this->db->set($kfield, $vfield);
					}
					$this->db->set('cliente_id', $cliente_id);				
					$this->db->insert($ktipo);	
				}			
			}
		}
		$this->m_bitacora->registrarEdicionBicatora('cliente', $cliente_id);
	}

	function consultaAllCalificacionesCliente(){
		$result_calificaciones = $this->db->get($this->t_cliente_calificacion);
		if($result_calificaciones->num_rows()>0){
			$result =  $this->security->xss_clean($result_calificaciones->result_array());
			return $result;

		}else{
			return false;
		}
	}
}