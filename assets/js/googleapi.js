// We'll declare the google_access_token variable outside of our jQuery to make it easier to pass the value around.
var access_token = '';
var client_id;
var api_endpoint;
var nonce;
var state;

client_id = tmp_api.gapi_client_id;
api_endpoint = tmp_api.gapi_redirect_uri;
access_token = tmp_api.gapi_access_token;
jQuery(document).ready(function($) {
	nonce = $('INPUT#gforms_tmp_google_oauth').val();
	state = {
		origin : 'wptest.tmpadmin-tenstreet.com',
		referrer : window.location.href,
		id : nonce
	};
	// we don't need the client secret for
	// this, and should not
	// expose it to the web.

	function request_auth_code() {
		var oauth_url = 'https://accounts.google.com/o/oauth2/auth';
		var oauth_scope = 'profile email';
		var win_url = oauth_url + '?scope=' + oauth_scope + '&client_id=' + client_id + '&redirect_uri=' + api_endpoint + '&state=' + btoa(JSON.stringify(state)) + '&access_type=offline&response_type=code&prompt=select_account consent';

		var win = window.open(win_url, "googleauthwindow", 'width=800, height=600');
		var pollTimer = window.setInterval(function() {
			try {
				if (win.closed === true) {
					window.clearInterval(pollTimer);
					// Get token from server using nonce
					var data = {
						action : 'gforms_tmp_handle_code_exchange',
						id : nonce,
						_wpnonce : nonce
					};
					$.post(ajaxurl, data, ajax_handler_cb, 'json');
				}
			} catch (e) {
			}
		}, 500);
	}

	function logout_from_google() {
		var data = {
			action : 'gforms_tmp_handle_token_revocation',
			id : nonce,
			_wpnonce : nonce
		};
		$.post(ajaxurl, data, ajax_handler_cb, 'json');
	}

	function ajax_handler_cb(response) {
		if (response && typeof response == 'object') {
			if (typeof response.message != 'undefined') {
				$('#gforms_tmp_google_oauth_message').val(response.message);
			}
			if (typeof response.error != 'undefined') {
				$('#gforms_tmp_google_oauth_error').val(response.message);
			}
		}
		$('#gforms_tmp_auth_settings_form').submit();
	}

	$(function() {
		$('#google-login-block').click(request_auth_code);
		$('#google-logout-block').click(logout_from_google);
	});
});
