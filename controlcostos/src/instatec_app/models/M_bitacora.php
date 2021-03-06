<?php defined('BASEPATH') OR exit('No direct script access allowed');

class M_Bitacora extends CI_Model {
	
	private $t_bitacora = 'usuario_bitacora_cambios',
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
	}

	function registrarEdicionBicatora($tabla, $row){
		$this->db->set('usuario_id', $this->usuario_id);
		$this->db->set('fecha_cambio', date('Y-m-d H:i:s'));
		$this->db->set('tipo', 'edicion');
		$this->db->set('id_fila', $row);
		$this->db->set('tabla',$tabla);
		$this->db->insert($this->t_bitacora);
	}
}