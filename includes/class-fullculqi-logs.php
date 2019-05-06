<?php
class FullCulqi_Logs {

	protected $post_id;
	protected $permission;
	protected $slug = 'culqi_log';

	public function __construct() {

	}

	public function set_settings_payment( $post_id = null) {
		
		if( is_numeric($post_id) && $post_id > 0  )
			$this->post_id = $post_id;
	}

	public function set_msg_payment( $type = 'notice', $message = '' ) {
		if( !$this->post_id )
			return false;

		$array_msg = get_post_meta($this->post_id, $this->slug, true );

		$array_set = isset($array_msg) && !empty($array_msg) ? $array_msg : array();

		$array_set[] = array('dateh' => date('Y-m-d H:i:s'), 'type' => $type, 'message' => $message );

		update_post_meta($this->post_id, $this->slug, $array_set );
	}
}
?>