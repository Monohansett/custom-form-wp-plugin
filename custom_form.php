<?php
/*
Plugin Name: Contact Form Plugin
Description: Simple WordPress Contact Form
Version: 1.0
*/

define('CUSTOM_FORM_DIR', plugin_dir_path(__FILE__));

function custom_form_builder() {
    echo '
    <form action="' . esc_url( $_SERVER['REQUEST_URI'] ) . '" method="post" id="cf">
    <p>
	 First Name<br/>
	 <input type="text" name="cf-first_name" pattern="[a-zA-Z0-9 ]+" value="' . ( isset( $_POST["cf-first_name"] ) ? esc_attr( $_POST["cf-first_name"] ) : '' ) . '" size="40" />
	 </p>
	 <p>
	 Last Name<br/>
	 <input type="text" name="cf-last_name" pattern="[a-zA-Z0-9 ]+" value="' . ( isset( $_POST["cf-last_name"] ) ? esc_attr( $_POST["cf-last_name"] ) : '' ) . '" size="40" />
	 </p>
	 <p>
	 Subject * <br/>
	 <input type="text" name="cf-subject" pattern="[a-zA-Z ]+" value="' . ( isset( $_POST["cf-subject"] ) ? esc_attr( $_POST["cf-subject"] ) : '' ) . '" size="40" />
	 </p>
	 <p>
	 Message * <br/>
	 <textarea rows="10" cols="35" name="cf-message">' . ( isset( $_POST["cf-message"] ) ? esc_attr( $_POST["cf-message"] ) : '' ) . '</textarea>
     </p>
     <p>
	 E-mail * <br/>
	 <input type="email" name="cf-email" pattern="^([a-zA-Z0-9_\-\.]+)@([a-zA-Z0-9_\-\.]+)\.([a-zA-Z]{2,5})$" value="' . ( isset( $_POST["cf-email"] ) ? esc_attr( $_POST["cf-email"] ) : '' ) . '" size="40" />
	 </p>
	<p><input type="submit" name="cf-submitted" value="Send" id="send-contact"></p>
    </form>
    ';
}

function logging_valid_mail() {
	$fd = fopen(CUSTOM_FORM_DIR . 'valid_mail.log', 'a+') or die('Can\'t open file');
	$e = $_POST["cf-email"];
	$valid_mail = 'This email is valid: ' . $e . "\r\n";
    fwrite($fd, $valid_mail);
    fclose($fd);
}

function send_post_data() {
	$user_data = array(
		'properties' => array(
			array(
				'property' => 'email',
				'value' => $_POST['cf-email']
			),
			array(
				'property' => 'firstname',
				'value' => $_POST['cf-first_name']
			),
			array(
				'property' => 'lastname',
				'value' => $_POST['cf-last_name']
			)
		)
	);
	
	$post_json = json_encode($user_data);
	$endpoint = 'https://api.hubapi.com/contacts/v1/contact?hapikey=e0479953-d674-45fd-a0b3-d8edf526770b';

	$ch = @curl_init();

	$curl_options = array(
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $post_json,
		CURLOPT_URL => $endpoint,
		CURLOPT_HTTPHEADER => array('Content-Type: application/json'),
		CURLOPT_RETURNTRANSFER => true
	);

	curl_setopt_array($ch, $curl_options);

	$response = @curl_exec($ch);

	if ($response) {
		
		$name    = sanitize_text_field( $_POST["cf-first_name"] );
        $email   = sanitize_email( $_POST["cf-email"] );
        $subject = sanitize_text_field( $_POST["cf-subject"] );
		$message = esc_textarea( $_POST["cf-message"] );
		
		$to = get_option( 'admin_email' );
		$headers = "From: $name <$email>" . "\r\n";

		wp_mail($to, $subject, $message, $headers);

		logging_valid_mail();
		
		echo '
		<div>
		<p>Thanks for contacting me, expect a response soon.</p>
		</div>
		';
	} else {
		echo 'Please fill the form correctly and try again!';
	}

	@curl_close($ch);
}


function to_send_email() {
	if ( isset( $_POST['cf-submitted'] ) ) {
		send_post_data();
	} 
}

function cf_shortcode() {
	ob_start();
	to_send_email();
	custom_form_builder();

	return ob_get_clean();
}

add_action('wp_enqueue_scripts','clear_form_init');

function clear_form_init() {
    wp_enqueue_script( 'clear-form-js', plugins_url( '/js/clear-form.js', __FILE__ ));
}

add_shortcode( 'contact_form', 'cf_shortcode' );

?>