<?php

class ztt_progress_widget {

	//variables
	private $ztt_user_meta;
	private $placeholder_image_url = "http://res.cloudinary.com/ztt/image/upload/v1478824929/ph_tfc5fc.png";
	private $placeholder_image_id = "ph_tfc5fc.png";

	//Constructor
	public function __construct(){
		include( plugin_dir_path( __FILE__ ) . 'includes/Cloudinary.php');
		include( plugin_dir_path( __FILE__ ) . 'includes/Uploader.php');
		include( plugin_dir_path( __FILE__ ) . 'includes/Api.php');

		if (file_exists( plugin_dir_path( __FILE__ ) . 'settings.php')) {
  			include( plugin_dir_path( __FILE__ ) . 'settings.php');
		}

		//Adding Actions
		add_action( 'wp_head', array($this, 'ztt_enqueue_scripts'));
		add_action('admin_menu', array($this, 'ztt_menu_pages'));
		add_action( 'init', array($this, 'ztt_base_pic_register_shortcode'));
		add_action( 'init', array($this, 'ztt_progress_pic_register_shortcode'));
		add_action( 'init', array($this, 'ztt_upload_image_register_shortcode'));
		add_action( 'init', array($this, 'ztt_gallery_register_shortcode'));
		add_action('wp_ajax_ztt_upload_pic', array($this, 'ztt_upload_pic_request'));
		add_action('wp_ajax_ztt_delete_pic', array($this, 'ztt_delete_pic_request'));
		add_action('wp_ajax_ztt_change_date', array($this, 'ztt_change_date_request'));

		//Admin Actions
		if (is_admin() ) {
			add_action('admin_init', array($this, 'ztt_admin_init'));
		}
		
		//Need to implement better system for this config
		\Cloudinary::config(array(
		    "cloud_name" => "NAME",
		    "api_key" => "KEY",
		    "api_secret" => "SECRET"
		));

	}

	private function ztt_build_save_location($url = "")
	{
		global $current_user;
		$api = new \Cloudinary\Api();
		$index = 0;
		$next_cursor = "";

		do {
			$data = $api->resources(array("type" => "upload", "prefix" => $current_user->user_login, "max_results" => 20, "next_cursor" => $next_cursor ));

			foreach ($data['resources'] as $key => $value) {
				if ($url == "") {
					$temp_id = str_split($value['public_id']);
					$s1 = 0;
					$s2 = sizeof($temp_id);
					$num_slash = 0;
					for ($i=0; $i < sizeof($temp_id); $i++) { 
						if($temp_id[$i] == "/") {
							$num_slash++;
							if($s1 == 0) {
								$s1 = $i+1;
							} else {
								$s2 = $i-$s1;
								break;
							}
						}
					}

					$string = (int)substr(implode($temp_id), $s1, $s2);

					if($string > $index) {
						$index = $string;
					}

				}
				else {

					$temp_id = $value['public_id'];
					$string = "";

					for ($i=strlen($temp_id); $i >= 0; $i--) { 
						$char = substr( $temp_id, $i, 1);
						if($char == "/") { break; }
						$string .= $char;
					}
					$string = strrev($string);

					if ($string == $url) {
						return $temp_id;						
					}
				}
			}

			if ( isset( $data['next_cursor'] ) && ( $data['next_cursor'] != null ) ) {
				$next_cursor = $data['next_cursor'];
			}	
			else {
				$next_cursor = "";
			}

		} while( isset( $next_cursor ) && ( $next_cursor != null ) );

		$index++; //make sure that the index is one more than the previous file number

		return $current_user->user_login . "/" . $index . "/front";
	}

	public function ztt_enqueue_scripts() {
		
		$ztt_current_user = wp_get_current_user();
		wp_enqueue_style('ztt-style', plugin_dir_url(__FILE__) . '/css/ztt.css');
		wp_enqueue_style('ztt-css-lightbox', plugin_dir_url(__FILE__) . '/css/ztt-lightbox.css');
		wp_enqueue_script('cloudinary-upload-widget', '//widget.cloudinary.com/global/all.js');
		wp_enqueue_script('ztt-cl-widget', plugin_dir_url(__FILE__) . '/js/ztt-cl-widget.js');
		wp_enqueue_script('ztt-js-lightbox', plugin_dir_url(__FILE__) . '/js/ztt-lightbox.js');
		wp_localize_script('ztt-cl-widget', 'ztt_vars', array( 
			'ajax_url' => admin_url( 'admin-ajax.php' ), 
			//'user_id' => $ztt_current_user->ID,
			'user_name' => $this->ztt_build_save_location()
			) );
	}

	public function ztt_menu_pages(){
	    add_menu_page('ZTT', 'ZTT Options', 'manage_options', 'ztt-menu', array($this, 'ztt_menu_output' ));
	    add_submenu_page('ztt-menu', 'ZTT Options', 'Whatever You Want', 'manage_options', 'ztt-menu' );
	}

	public function ztt_menu_output(){
		// check user capabilities
	    if (!current_user_can('manage_options')) {
	        return;
	    }
	    ?>
	    <div class="wrap">
	        <h1><?= esc_html(get_admin_page_title()); ?></h1>
	        <form action="options.php" method="post">
	            <?php
	            // output security fields for the registered setting "wporg_options"
	            settings_fields('ztt_options');
	            // output setting sections and their fields
	            // (sections are registered for "wporg", each field is registered to a specific section)
	            do_settings_sections('ztt_options');
	            // output save settings button
	            ?>

	            <?php

	            submit_button();
	            ?>
	        </form>
	    </div>
	    <?php
	}

	public function ztt_admin_init(){
		register_setting( 'ztt_options', 'ztt_options', 'ztt_options_validate' );
		add_settings_section('ztt_main', 'Settings', array($this, 'ztt_section_text'), 'ztt_options');
		add_settings_field('ztt_cloud_name', 'cloud_name', array($this, 'ztt_cloud_name_string'), 'ztt_options', 'ztt_main');
		add_settings_field('ztt_api_key', 'api_key', array($this, 'ztt_api_key_string'), 'ztt_options', 'ztt_main');
		add_settings_field('ztt_api_secret', 'api_secret', array($this, 'ztt_api_secret_string'), 'ztt_options', 'ztt_main');
	}

	public function ztt_section_text() {
		echo '<p>Enter in the info from cloudinary</p>';
	}

	public function ztt_cloud_name_string() {
		$options = get_option('ztt_options');

		if( ! isset($options['cloud_name']) ) { 
			$options['cloud_name'] = "Enter Cloud Name..."; 
		}	

		echo "<input id='ztt_cloud_name' name='ztt_options[cloud_name]' size='40' type='text' value='{$options['cloud_name']}'/>";
	}

	public function ztt_api_key_string() {
		$options = get_option('ztt_options');

		if( ! isset($options['api_key']) ) { 
			$options['api_key'] = "Enter API Key..."; 
		}

		echo "<input id='ztt_api_key_name' name='ztt_options[api_key]' size='40' type='text' placeholder='********'/>";
	}

	public function ztt_api_secret_string() {
		$options = get_option('ztt_options');

		if( ! isset($options['api_secret']) ) { 
			$options['api_secret'] = "Enter API Secret..."; 
		}

		echo "<input id='ztt_api_secret_name' name='ztt_options[api_secret]' size='40' type='text'  placeholder='********'/>";
	}

	public function ztt_options_validate($input) {
		//afutre
		return $input;
	}

	/*

	BASE PICTURE

	*/

	public function ztt_base_pic_shortcode() {
		global $current_user;
		$ztt_current_user_id = (string) get_current_user_id();
		$api = new \Cloudinary\Api();
		$next_cursor = "";
		$ztt_html = "";
		$imageDate = "";
		$imageId = $this->placeholder_image_id;
		$imageUrl = $this->placeholder_image_url;

		if(!is_user_logged_in())
			return "<span>You must login!</span>";

		do {
			$data = $api->resources(array("type" => "upload", "prefix" => $current_user->user_login, "max_results" => 20, "next_cursor" => $next_cursor ));

			foreach ($data['resources'] as $key => $value) {
				if ($imageDate == "") {
					$imageDate = $value['created_at'];	
					$imageId = substr($value['public_id'], 2);
					$imageUrl = $value['url'];
				}
				
				if ($imageDate > $value['created_at']) {
					$imageDate = $value['created_at'];
					$imageId = substr($value['public_id'], 2);
					$imageUrl = $value['url'];
				}			
			}

			if ( isset( $data['next_cursor'] ) && ( $data['next_cursor'] != null ) ) {
				$next_cursor = $data['next_cursor'];
			}	
			else {
				$next_cursor = "";
			}

		} while( isset( $next_cursor ) && ( $next_cursor != null ) );

		$ztt_html .= "<span id='" . $imageId . "'>";
		$ztt_html .= "<a class = 'ztt-base-pic-wrapper' href='" . $imageUrl . "' data-lightbox='" . $imageId . "'>"; 
		$ztt_html .= "<img id='ztt-base-pic' src='" . $imageUrl . "'>";
		$ztt_html .= "</a>";
		$ztt_html .= "</span>";
		

		return $ztt_html;
	}

	public function ztt_base_pic_register_shortcode(){
		add_shortcode( 'ztt-base-pic', array($this, 'ztt_base_pic_shortcode') );
	}

	/*

	PROGRESS PICTURE

	*/

	public function ztt_progress_pic_shortcode() {
		global $current_user;
		$ztt_current_user_id = (string) get_current_user_id();
		$api = new \Cloudinary\Api();
		$next_cursor = "";
		$ztt_html = "";
		$imageDate = "";
		$imageId = $this->placeholder_image_id;
		$imageUrl = $this->placeholder_image_url;
		$count = 0;

		if(!is_user_logged_in())
			return "<span>You must login!</span>";


		do {
			$data = $api->resources(array("type" => "upload", "prefix" => $current_user->user_login, "max_results" => 20, "next_cursor" => $next_cursor ));

			foreach ($data['resources'] as $key => $value) {
				if ($imageDate == "") {
					$imageDate = $value['created_at'];	
					$imageId = substr($value['public_id'], 2);
					$imageUrl = $value['url'];
				}
				
				if ($imageDate < $value['created_at']) {
					$imageDate = $value['created_at'];
					$imageId = substr($value['public_id'], 2);
					$imageUrl = $value['url'];
				}			

				$count++;
			}

			if ( isset( $data['next_cursor'] ) && ( $data['next_cursor'] != null ) ) {
				$next_cursor = $data['next_cursor'];
			}	
			else {
				$next_cursor = "";
			}

		} while( isset( $next_cursor ) && ( $next_cursor != null ) );

		if($count < 2)
		{
			$ztt_html .= "<span id='" . "ztt-null-placeholder" . "'>";
			$ztt_html .= "<a class = 'ztt-progress-pic-wrapper' href='#' data-lightbox='" . $this->placeholder_image_id . "'>"; 
			$ztt_html .= "<img id='ztt-progress-pic' src='" . $this->placeholder_image_url . "' hidden='true'>";
			$ztt_html .= "</a>";
			$ztt_html .= "</span>";
			return $ztt_html;
		}

		$ztt_html .= "<span id='" . $imageId . "'>";
		$ztt_html .= "<a class = 'ztt-progress-pic-wrapper' href='" . $imageUrl . "' data-lightbox='" . $imageId . "'>"; 
		$ztt_html .= "<img id='ztt-progress-pic' src='" . $imageUrl . "'>";
		$ztt_html .= "</a>";
		$ztt_html .= "</span>";

		return $ztt_html;
	}


	public function ztt_progress_pic_register_shortcode() {
		add_shortcode( 'ztt-progress-pic', array($this, 'ztt_progress_pic_shortcode') );
	}

	/*

	UPLOAD IMAGE

	*/

	public function ztt_upload_image_shortcode() {

		if (is_user_logged_in()) {
			$output = '<button id="upload_widget_opener">Upload images</button>';
		}
		else {
			$output = '<div> You need to Sign in to upload images </div>';
		}
		
	    return $output;
	}

	public function ztt_upload_image_register_shortcode(){
		add_shortcode( 'ztt-upload', array($this, 'ztt_upload_image_shortcode' ));
	}

	/*

	GALLERY

	*/

	function ztt_date_compare($a, $b) {
		$t1 = strtotime($a['created_at']);
		$t2 = strtotime($b['created_at']);

		return $t1 - $t2;
	}

	public function ztt_gallery_shortcode() {
		global $current_user;
		// $ztt_current_user_id = (string) get_current_user_id();
		$api = new \Cloudinary\Api();
		$next_cursor = "";
		$block = "<div id='ztt-gallery-wrapper'>";

		if(!is_user_logged_in())
			return "<div>You must login to see this!<div>";

		do {
			$data = $api->resources(array("type" => "upload", "prefix" => $current_user->user_login, "max_results" => 20, "next_cursor" => $next_cursor ));


			if (sizeof($data['resources']) != 0) {

				usort($data['resources'], array($this, 'ztt_date_compare'));

				foreach ($data['resources'] as $key => $value) {
					$block .= "<span id='" . substr($value['public_id'], 2) . "' class='ztt-show-image'>";
					$block .= "<a href='" . $value['url'] . "' data-lightbox='" . substr($value['public_id'], 2) . "'>"; 
					$block .= "<img class='ztt-gallery' src='" . $value['url'] . "'>";
					$block .= "</a>";
					$block .= "</span>";
				}

				if ( isset( $data['next_cursor'] ) && ( $data['next_cursor'] != null ) ) {
					$next_cursor = $data['next_cursor'];
				}	
				else {
					$next_cursor = "";
				}

			} else {
				$block .= "<div class='ztt-gallery-error'>You do not have any images</div>";
			}

		} while( isset( $next_cursor ) && ( $next_cursor != null ) );

		$block .= "</div>";

		return $block;
	}
	
	public function ztt_gallery_register_shortcode(){
		add_shortcode( 'ztt-gallery', array($this, 'ztt_gallery_shortcode' ) );
	}




	/*

	UPLOADING OF IMAGE

	*/

	public function ztt_upload_pic_request(){
		global $current_user;
		$ztt_current_user_id = get_current_user_id();
		$ztt_plugin_data = '_ztt_plugin_data';
		$api = new \Cloudinary\Api();

		if( isset($_POST["ztt_pic_url"] ) ) {

			$ztturl= $_POST["ztt_pic_url"];
			$ztt_user_meta = unserialize( get_user_meta($ztt_current_user_id, $ztt_plugin_data, true) );
			$data = $api->resources(array("type" => "upload", "prefix" => $current_user->user_login, "max_results" => 20,"next_cursor" => $next_cursor ));//$current_user->user_login

			if(sizeof($data["resources"]) == 1) {
				$ztt_user_meta["base"] = $ztturl;
				$ztt_user_meta["hasFirst"] = false;
				$response = "base";
			}
			else {
				$ztt_user_meta["progress"] = $ztturl;
				update_user_meta($ztt_current_user_id, $ztt_plugin_data, serialize($ztt_user_meta) );
				$ztt_user_meta["hasFirst"] = true;
				$response = "progress";
				// var_dump($response);
			}

			echo $response;
			wp_die();
		}

	}

	private function ztt_change_date_request() {
		global $current_user;
		if(is_user_logged_in() && isset($_POST["ztt_date"])) {

		$ztt_current_user_id = get_current_user_id();
		$ztt_plugin_data = '_ztt_plugin_data';

		$zttdata = $_POST["ztt_date"];

		$timestamp = $zttdata["year"] . "-" . ($zttdata["month"] > 9 ? $zttdata["month"] : "0" . $zttdata["month"]) . "-" . ($zttdata["day"] > 9 ? $zttdata["day"] : "0" . $zttdata["day"]) . "T11:54:21Z";

		$ztt_user_meta = unserialize( get_user_meta($ztt_current_user_id, $ztt_plugin_data, true) );

		//$ztt_user_meta_init[] = array();

		foreach ($ztt_user_meta as $key => $value) {
			if(is_array($value)) {
				if(empty($value)) {
					$ztt_user_meta[$key] = array('public_id' => ( $current_user->user_login . "/" . $zttdata["string"]), 'created_at' => $timestamp);
					break;
				} else if(array_key_exists("public_id", $value)) {
					if($value["public_id"] == $zttdata["string"]) {
						$ztt_user_meta[$key]["created_at"] = $timestamp;
						break;
					} else if($key == count($ztt_user_meta) - 1) {
						$ztt_user_meta[] = array('public_id' => ( $current_user->user_login . "/" . $zttdata["string"]), 'created_at' => $timestamp);
					}
				}
			}
		}

		//update_user_meta($ztt_current_user_id, $ztt_plugin_data, serialize($ztt_user_meta_init));
		update_user_meta($ztt_current_user_id, $ztt_plugin_data, serialize($ztt_user_meta));

		$response = "Clear!";

		} else {
			$response = "You cannot do this";
		}

		echo $response;
		wp_die();

	}

	public function ztt_delete_pic_request() {
		global $current_user;
		if (isset($_POST["ztt_delete_url"])) {

			$api = new \Cloudinary\Api();

			$ztt_current_user_id = get_current_user_id();
			$ztturl = $_POST["ztt_delete_url"];

			$ztt_pic_url = $this->ztt_build_save_location($ztturl);

			if (is_user_logged_in() && $ztt_pic_url != $this->placeholder_image_id) {
				$api->delete_resources($ztt_pic_url);
				$response = true;
			} else {
				$response = false;
			}
			

			echo $response;
			wp_die();

		}
	}

} // END OF ClASS

?>

//To do
//Implement better security
//Make it all more modular
