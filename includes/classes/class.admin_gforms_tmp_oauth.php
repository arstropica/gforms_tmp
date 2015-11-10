<?php
/**
 * OAuth2 Provider Class
 */
class Admin_GForms_TMP_OAuth extends GForms_TMP {
	public function __construct($token = null) {
		if ($token) {
			$this->set_token( $token );
		}
	}
	
	/**
	 * Handle Auth Code Exchange
	 *
	 * @return boolean
	 */
	public function refresh_token($token = null) {
		$result = false;
		$token = $token ?  : $this->get_token();
		
		$url = self::$oauth_host . "/gapi/token/refresh";
		
		$post = array (
				'token' => json_encode( $token ) 
		);
		$args = array (
				'body' => $post 
		);
		$response = wp_remote_post( $url, $args );
		if (! is_wp_error( $response )) {
			if (isset( $response ['response'] ) && $response ['response'] ['code'] == 200) {
				if (isset( $response ['body'] )) {
					$body = $response ['body'];
					$message = @json_decode( $body, true );
					if (isset( $message ['outcome'] ) && $message ['outcome']) {
						$token = $message ['token'] ?  : false;
						if ($token) {
							$this->set_token( $token );
							$result = $token;
						}
					}
				}
			}
		}
		
		return $result;
	}
	public function is_token_expired($token = null) {
		$result = false;
		$token = $token ?  : $this->get_token();
		$token = $this->is_json($token) ? json_decode($token) : $token;
		if ($token) {
			$expires_in = $token->expires_in;
			$created = $token->created;
			if ((time() - $created) > $expires_in) {
				$result = true;
			}
		}
		return $result;
	}
	public function is_token_authorized($token = null) {
		$result = false;
		$token = $token ?  : $this->get_token();
		
		if ($token) {
			if ($this->is_token_expired( $token )) {
				$token = $this->refresh_token( $token );
			}
			$url = self::$oauth_host . "/gapi/token/valid";
			
			$post = array (
					'token' => json_encode( $token ) 
			);
			$args = array (
					'body' => $post 
			);
			$response = wp_remote_post( $url, $args );
			if (! is_wp_error( $response )) {
				if (isset( $response ['response'] ) && $response ['response'] ['code'] == 200) {
					if (isset( $response ['body'] )) {
						$body = $response ['body'];
						$message = @json_decode( $body, true );
						if (isset( $message ['outcome'] ) && $message ['outcome']) {
							$result = true;
						}
					}
				}
			}
		}
		return $result;
	}
	public function token() {
		$token = false;
		if ($this->is_token_authorized()) {
			$token = $this->get_token();
		}
		return $token;
	}
	protected function get_token() {
		$is_multisite = is_multisite();
		
		return $is_multisite ? get_site_option( 'gforms_tmp_access_token', null, false ) : get_option( 'gforms_tmp_access_token', null );
	}
	protected function set_token($token) {
		$is_multisite = is_multisite();
		
		return $is_multisite ? update_site_option( 'gforms_tmp_access_token', $token ) : update_option( 'gforms_tmp_access_token', $token );
	}
	private function is_json($string) {
		@json_decode( $string );
		return (json_last_error() == JSON_ERROR_NONE);
	}
}
