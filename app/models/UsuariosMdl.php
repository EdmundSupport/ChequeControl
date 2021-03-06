<?php 
	class UsuariosMdl{
		private $db;

		public function __construct($config){
			$this->db = new Database($config);
		}

		public function obtenerUsuarios(){
			$this->db->query("SELECT a.id_usuario, a.nombre_usuario, TO_CHAR(TO_DATE(a.fecha_creacion, 'DD-MM-RRRR'),'RRRR-MM-DD') AS fecha_creacion, b.primer_nombre, b.primer_apellido, a.id_grupo FROM usuarios a
			INNER JOIN personas b ON b.id_persona = a.id_persona
			ORDER BY a.id_usuario");
			return $this->db->resultSet();
		}

		public function obtenerUsuario($id){
			$datos = array();
			$this->db->query("SELECT a.id_usuario, a.nombre_usuario, TO_CHAR(TO_DATE(a.fecha_creacion, 'DD-MM-RRRR'),'RRRR-MM-DD') AS fecha_creacion, a.max_monto_cheque AS monto, b.cui, b.primer_nombre, b.segundo_nombre, b.otro_nombre, b.primer_apellido, b.segundo_apellido, TO_CHAR(TO_DATE(b.fecha_nac, 'DD-MM-RRRR'),'RRRR-MM-DD') AS fecha_nac, a.id_grupo FROM usuarios a
			INNER JOIN personas b ON b.id_persona = a.id_persona WHERE a.id_usuario = :id");
			$this->db->bind(':id', $id);
			$datos['informacion']  = $this->db->single();

			$this->db->query("SELECT a.id_modulo, a.nombre, a.descripcion, nvl(b.id_modulo,0) AS permiso FROM modulos a
			LEFT JOIN 
				((SELECT id_modulo FROM usuarios_modulos WHERE id_usuario = :id)
				UNION
				(SELECT id_modulo FROM grupos_modulos WHERE id_grupo = (SELECT id_grupo FROM usuarios WHERE id_usuario = :id))) b
			ON a.id_modulo = b.id_modulo
			WHERE a.asignable = 1");
			$this->db->bind(':id', $id);
			$datos['modulos']  = $this->db->resultSet();

			$this->db->query("SELECT a.id_privilegio, a.nombre, a.descripcion, nvl(b.id_privilegio,0) AS permiso FROM privilegios a
			LEFT JOIN 
				((SELECT id_privilegio FROM usuarios_privilegios WHERE id_usuario = :id)
				UNION
				(SELECT id_privilegio FROM grupos_privilegios WHERE id_grupo = (SELECT id_grupo FROM usuarios WHERE id_usuario = :id))) b
			ON a.id_privilegio = b.id_privilegio 
			WHERE a.asignable = 1 ORDER BY a.id_privilegio");
			$this->db->bind(':id', $id);
			$datos['privilegios']  = $this->db->resultSet();
			return $datos;
		}

		public function obtenerModulos($id){
			$this->db->query("SELECT a.id_modulo, a.nombre, a.descripcion, nvl(b.id_modulo,0) AS permiso, a.ruta, a.padre, a.asignable FROM modulos a
			INNER JOIN 
				((SELECT id_modulo FROM usuarios_modulos WHERE id_usuario = :id)
				UNION
				(SELECT id_modulo FROM grupos_modulos WHERE id_grupo = (SELECT id_grupo FROM usuarios WHERE id_usuario = :id))) b
			ON a.id_modulo = b.id_modulo
            ORDER BY a.id_modulo");
			$this->db->bind(':id', $id);
			return $this->db->resultSet();
		}

		public function crearUsuarios($nombre, $contra, $cui, $nombre1, $nombre2, $nombre3, $apellido1, $apellido2, $fechanac, $grupo, $monto){
			$this->db->query("SELECT usuario_crear(:nombre, :contra, :cui, :nombre1, :nombre2, :nombre3, :apellido1, :apellido2, TO_CHAR(TO_DATE(:fechanac, 'RRRR-MM-DD'),'DD-MON-RRRR'), :grupo, :monto) AS result FROM dual");
			$this->db->bind(':nombre', $nombre);
			$this->db->bind(':contra', $contra);
			$this->db->bind(':cui', $cui);
			$this->db->bind(':nombre1', $nombre1);
			$this->db->bind(':nombre2', $nombre2);
			$this->db->bind(':nombre3', $nombre3);
			$this->db->bind(':apellido1', $apellido1);
			$this->db->bind(':apellido2', $apellido2);
			$this->db->bind(':fechanac', $fechanac);
			$this->db->bind(':grupo', $grupo);
			$this->db->bind(':monto', $monto);
			return $this->db->resultSet();
		}

		public function guardarUsuario($nombre, $id, $contra, $nombre1, $nombre2, $nombre3, $apellido1, $apellido2, $fechanac, $grupo, $monto){
			$this->db->query("SELECT usuario_guardar(:nombre, :id, :contra, :nombre1, :nombre2, :nombre3, :apellido1, :apellido2, TO_CHAR(TO_DATE(:fechanac, 'RRRR-MM-DD'),'DD-MON-RRRR'), :grupo, :monto) AS result FROM dual");
			$this->db->bind(':nombre', $nombre, PDO::PARAM_STR);
			$this->db->bind(':id', $id);
			$this->db->bind(':contra', $contra, PDO::PARAM_STR);
			$this->db->bind(':nombre1', $nombre1, PDO::PARAM_STR);
			$this->db->bind(':nombre2', $nombre2, PDO::PARAM_STR);
			$this->db->bind(':nombre3', $nombre3, PDO::PARAM_STR);
			$this->db->bind(':apellido1', $apellido1, PDO::PARAM_STR);
			$this->db->bind(':apellido2', $apellido2, PDO::PARAM_STR);
			$this->db->bind(':fechanac', $fechanac);
			$this->db->bind(':grupo', $grupo);
			$this->db->bind(':monto', $monto);
			return $this->db->resultSet();
		}

		public function guardarModulos($usuario, $modulo){
			$this->db->query("INSERT INTO usuarios_modulos SELECT :usuario, id_modulo FROM modulos WHERE asignable = 1 AND id_modulo = :modulo");
			$this->db->bind(':usuario', $usuario);
			$this->db->bind(':modulo', $modulo);
			return $this->db->execute();
		}

		public function reiniciarModulos($usuario){
			$this->db->query("DELETE FROM usuarios_modulos WHERE id_usuario=:usuario");
			$this->db->bind(':usuario', $usuario);
			return $this->db->execute();
		}

		public function guardarPrivilegios($usuario, $privilegio){
			$this->db->query("INSERT INTO usuarios_privilegios SELECT id_privilegio, :usuario  FROM privilegios WHERE asignable = 1 AND id_privilegio = :privilegio");
			$this->db->bind(':usuario', $usuario);
			$this->db->bind(':privilegio', $privilegio);
			return $this->db->execute();
		}

		public function reiniciarPrivilegios($usuario){
			$this->db->query("DELETE FROM usuarios_privilegios WHERE id_usuario=:usuario");
			$this->db->bind(':usuario', $usuario);
			return $this->db->execute();
		}

		public function eliminarUsuario($id){
			$this->db->query("SELECT usuario_eliminar(:id) AS result FROM dual");
			$this->db->bind(':id', $id);
			return $this->db->resultSet();
		}

		public function obtenerGrupos(){
			$this->db->query("SELECT id_grupo, nombre, descripcion FROM grupos");
			return $this->db->resultSet();
		}

		public function obtenerPrivilegio($usuario, $privilegio){
			$this->db->query("SELECT obtenerprivilegio(:usuario, :privilegio).codigo AS PRIV FROM dual");
			$this->db->bind(':usuario', $usuario);
			$this->db->bind(':privilegio', $privilegio);
			return $this->db->single();
		}

		public function obtenerModulo($usuario, $modulo){
			$this->db->query("SELECT obtenerModulo(:usuario, :modulo).codigo AS MOD FROM dual");
			$this->db->bind(':usuario', $usuario);
			$this->db->bind(':modulo', $modulo);
			return $this->db->single();
		}

		public function validarLogin($nombre){
			$this->db->query("SELECT id_usuario, contra FROM usuarios WHERE nombre_usuario = :nombre");
			$this->db->bind(':nombre', $nombre);
			return $this->db->single();
		}
	}
 ?>
