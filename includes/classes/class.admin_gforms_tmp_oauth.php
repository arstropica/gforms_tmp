<?php

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;

class Admin_GForms_TMP_OAuth extends GForms_TMP {

    protected $params;
    protected $provider;
    protected $options;

    public function __construct($api = null, $user = null, $pass = null) {
        $oauth_domain = parse_url($api, PHP_URL_HOST);
        $oauth_scheme = parse_url($api, PHP_URL_SCHEME) ? : "http";
        $oauth_url = $oauth_scheme . "://" . $oauth_domain;
        $params = array(
            'clientId' => $user, // The client ID assigned to you by the provider
            'clientSecret' => $pass, // The client password assigned to you by the provider
            'redirectUri' => $oauth_url . '/oauth/receivecode',
            'urlAuthorize' => $oauth_url . '/oauth/authorize',
            'urlAccessToken' => $oauth_url . '/oauth',
            'urlResourceOwnerDetails' => $oauth_url . '/oauth'
        );
        $options = array(
            'timeout' => 5
        );

        $this->params = $params;
        $this->options = $options;

        return $this->provider = new GenericProvider($params, $options);
    }

    public function get_code_grant() {
        // If we don't have an authorization code then get one
        if (!isset($_GET['code'])) {

            // Fetch the authorization URL from the provider; this returns the
            // urlAuthorize option and generates and applies any necessary parameters
            // (e.g. state).
            $authorizationUrl = $this->provider->getAuthorizationUrl();

            // Get the state generated for you and store it to the session.
            $_SESSION['oauth2state'] = $this->provider->getState();

            // Redirect the user to the authorization URL.
            header('Location: ' . $authorizationUrl);
            exit;

            // Check given state against previously stored one to mitigate CSRF attack
        } elseif (empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state'])) {

            unset($_SESSION['oauth2state']);
            exit('Invalid state');
        } else {

            try {

                // Try to get an access token using the authorization code grant.
                $accessToken = $this->provider->getAccessToken('authorization_code', [
                    'code' => $_GET['code']
                ]);

                // We have an access token, which we may use in authenticated
                // requests against the service provider's API.
                echo $accessToken->getToken() . "\n";
                echo $accessToken->getRefreshToken() . "\n";
                echo $accessToken->getExpires() . "\n";
                echo ($accessToken->hasExpired() ? 'expired' : 'not expired') . "\n";

                $this->set_token($accessToken);

                // Using the access token, we may look up details about the
                // resource owner.
                $resourceOwner = $this->provider->getResourceOwner($accessToken);

                var_export($resourceOwner->toArray());

                // The provider provides a way to get an authenticated API request for
                // the service, using the access token; it returns an object conforming
                // to Psr\Http\Message\RequestInterface.
                $request = $this->provider->getAuthenticatedRequest(
                        'GET', $this->params['urlResourceOwnerDetails'], $accessToken
                );
            } catch (IdentityProviderException $e) {

                // Failed to get the access token or user details.
                exit($e->getMessage());
            }
        }
    }

    public function refresh_token() {
        $existingAccessToken = $this->get_token();

        if ($existingAccessToken->hasExpired()) {
            $newAccessToken = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $existingAccessToken->getRefreshToken()
            ]);

            $this->set_token($newAccessToken);
            // Purge old access token and store new access token to your data store.
        }
    }

    public function get_client_credentials_grant() {
        try {
            // Try to get an access token using the client credentials grant.
            $accessToken = $this->provider->getAccessToken('client_credentials');
            if ($accessToken->getToken()) {
                $this->set_token($accessToken);
                return $accessToken;
            } else {
                exit("Failed to generate Token.");
            }
        } catch (IdentityProviderException $e) {

            // Failed to get the access token
            exit($e->getMessage());
        }
    }

    public static function is_authorized($accessToken = null) {

        $is_authorized = false;

        try {
            if ($accessToken && $accessToken instanceof AccessToken) {

                if (!$accessToken->hasExpired()) {

                    $is_authorized = true;
                }
            }
        } catch (\Exception $e) {
            $is_authorized = false;
        }

        return $is_authorized;
    }

    protected function get_token() {
        $is_multisite = is_multisite();

        return $is_multisite ? get_site_option('gforms_tmp_access_token', null, false) : get_option('gforms_tmp_access_token', null);
    }

    protected function set_token($token) {
        $is_multisite = is_multisite();

        return $is_multisite ? update_site_option('gforms_tmp_access_token', $token) : update_option('gforms_tmp_access_token', $token);
    }

}
