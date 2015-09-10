<?php

use League\OAuth2\Client\Token\AccessToken;

class GForms_TMP {

    /**
     * Admin Page Hook
     *
     * Generated by 'add_menu_page' function.
     *
     * @var string
     */
    private $page_hook;

    /**
     * Admin Class
     *
     * Generated by 'init_GForms_TMP' method.
     *
     * @var object
     */
    private $admin_gforms_tmp;

    /**
     * GravityForm Addon Class
     *
     * Generated by 'init_GForms_TMP' method.
     *
     * @var object
     */
    private $addon_gforms_tmp;

    /**
     * TenStreet Custom Post Type Class
     *
     * Generated by 'init_GForms_TMP' method.
     *
     * @var object
     */
    private $post_type;

    /**
     * Plugin Dir Absolute Path
     *
     * @var string
     */
    static protected $plugin_path;

    const slug = 'gforms-tmp';
    const name = 'GForms_TMP';

    /**
     * Constructor
     * 
     * @return void
     */
    function __construct() {

        self::$plugin_path = plugin_dir_path(dirname(dirname(__FILE__)));

// Load Dependencies
        $this->load_dependencies_GForms_TMP();

// Hook up to the init action
        add_action('init', array(&$this, 'init_GForms_TMP'));

// Add class instance to Addon Filter
        add_filter('gforms_tmp_class_instance', array($this, 'get_instance'), 10, 1);
    }

    public function get_instance($class) {
        return $this;
    }

    /**
     * Load the required dependencies for this class.
     *
     * Include the following files that make up the plugin:
     *
     * - GForms_TMP_EndPoint. EndPoint Handler for API Functionality.
     * - Admin_GForms_TMP. Admin Page Functionality.
     * - Network_Admin_GForms_TMP. Network Admin Page Functionality.
     *
     */
    private function load_dependencies_GForms_TMP() {
        /**
         * The class responsible for defining admin functionality.
         */
        require_once self::$plugin_path . 'includes/classes/class.admin_gforms_tmp.php';
        /**
         * The class responsible for defining network admin functionality.
         */
        require_once self::$plugin_path . 'includes/classes/class.network_admin_gforms_tmp.php';
        /**
         * The class responsible for defining helper functionality.
         */
        require_once self::$plugin_path . 'includes/helpers/functions.php';
        /**
         * The vendor class responsible for defining OAuth2 functionality.
         */
        require_once self::$plugin_path . 'vendor/autoload.php';
        /**
         * The class responsible for defining OAuth functionality.
         */
        require_once self::$plugin_path . 'includes/classes/class.admin_gforms_tmp_oauth.php';
        /**
         * The class responsible for defining Reporting functionality.
         */
        require_once self::$plugin_path . 'includes/classes/class.tenstreet_application_post_type.php';

        if ($this->is_plugin_activated(false)) {
            /**
             * The class responsible for defining GravityForms functionality.
             */
            require_once self::$plugin_path . 'includes/classes/class.gforms_tmp_addon.php';
        }
    }

    /**
     * Runs when the plugin is initialized
     * 
     * @return void
     */
    function init_GForms_TMP() {
// Setup localization
        load_plugin_textdomain(self::slug, false, dirname(plugin_basename(__FILE__)) . '/lang');

        $this->post_type = new TenStreet_Application();

// Load Assets
        if (is_admin()) {

// Init Admin_GForms_TMP
            $this->admin_gforms_tmp = new Admin_GForms_TMP();
        } else {
            add_action('wp_loaded', array(&$this, 'load_assets_GForms_TMP'), 100);
        }
    }

    /**
     * Loads Assets.
     * 
     * @return void
     */
    function load_assets_GForms_TMP() {

//this will run when on the frontend
        add_action('wp_print_scripts', array(&$this, 'load_scripts_GForms_TMP'));
    }

    /**
     * Load Front End CSS/JS
     * 
     * @return void
     */
    function load_scripts_GForms_TMP() {
        
    }

    /**
     * Check API Authorization
     * 
     * @param boolean $refresh Refresh authorization token if expired
     * @return boolean $is_authorized 
     */
    protected function is_api_authorized_GForms_TMP($refresh = true) {
        $is_multisite = is_multisite();

        $is_authorized = false;

        $accessToken = $is_multisite ? get_site_option('gforms_tmp_access_token', null, false) : get_option('gforms_tmp_access_token', null);

        if ($accessToken && $accessToken instanceof AccessToken) {

            $is_authorized = Admin_GForms_TMP_OAuth::is_authorized($accessToken);

            if (!$is_authorized && $refresh) {
                $accessToken = $this->refresh_api_authorization_GForms_TMP();

                $is_authorized = Admin_GForms_TMP_OAuth::is_authorized($accessToken);
            }
        }

        return $is_authorized;
    }

    /**
     * Refresh API Token
     * 
     * @return void
     */
    protected function refresh_api_authorization_GForms_TMP() {
        $is_multisite = is_multisite();

        $accessToken = false;

        $gforms_tmp_admin_api_password = $is_multisite ? get_site_option('gforms_tmp_admin_api_password', false, false) : get_option('gforms_tmp_admin_api_password', false);

        $gforms_tmp_admin_api_username = $is_multisite ? get_site_option('gforms_tmp_admin_api_username', false, false) : get_option('gforms_tmp_admin_api_username', false);

        $gforms_tmp_admin_restapi_url = $is_multisite ? get_site_option('gforms_tmp_admin_restapi_url', false, false) : get_option('gforms_tmp_admin_restapi_url', false);

        if (!$gforms_tmp_admin_restapi_url || !$this->is_valid_url($gforms_tmp_admin_restapi_url) || !$gforms_tmp_admin_api_username || !$gforms_tmp_admin_api_password) {

            // update_option('gforms_tmp_active', 0);
        } else {
            try {
                if ($this->is_valid_url($gforms_tmp_admin_restapi_url)) {
                    $oauth_provider = new Admin_GForms_TMP_OAuth($gforms_tmp_admin_restapi_url, $gforms_tmp_admin_api_username, $gforms_tmp_admin_api_password);
                }
            } catch (\Exception $e) {
                $accessToken = false;
            }

            $accessToken = $oauth_provider->get_client_credentials_grant();
        }

        return $accessToken;
    }

    /**
     * Check if URL is active
     * 
     * @param string $url URL
     * @param array $reject Inactive HTTP Response Codes
     * @return boolean Returns whether response code is in $reject array
     */
    protected function is_valid_url($url, $reject = array(404, 500)) {
        $result = false;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($curl, CURLOPT_TIMEOUT, 15);
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD'); // HTTP request is 'HEAD'
        curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (!curl_errno($curl)) {
            $result = !in_array($status, $reject);
        }
        curl_close($curl);
        return $result;
    }

    /**
     * Check if Plugin is active (and authorized)
     * 
     * @param boolean $check_auth Check Authorization as well
     * @return boolean Returns whether plugin is active (and authorized)
     */
    public function is_plugin_activated($check_auth = true) {
        $is_authorized = get_option('gforms_tmp_active', false);

        if ($check_auth && $is_authorized) {
            $is_authorized = $this->is_api_authorized_GForms_TMP(true);
        }

        return $is_authorized;
    }

}

// end class
?>
