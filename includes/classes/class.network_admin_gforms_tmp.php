<?php

// Network Settings

class Network_Admin_GForms_TMP extends Admin_GForms_TMP {

    function __construct() {

        // Load Assets
        add_action('network_admin_menu', array(&$this, 'load_assets_Network_Admin_GForms_TMP'));

        // Single Edit Handler
        add_action('admin_action_site_update_network_admin_gforms_tmp', array($this, 'site_update_Network_Admin_GForms_TMP'));

        // Bulk Edit Handler
        add_action('admin_action_bulk_update_network_admin_gforms_tmp', array($this, 'bulk_update_Network_Admin_GForms_TMP'));

        // Edit Settings Handler
        add_action('admin_action_settings_update_network_admin_gforms_tmp', array($this, 'settings_update_Network_Admin_GForms_TMP'));
    }

    /**
     * Runs when the plugin is initialized
     * 
     * @return void
     */
    function setup_page_Network_Admin_GForms_TMP() {

        $screen = get_current_screen();

        if (in_array($screen->id, array('toplevel_page_gforms-tmp-network'))) {

            // Filter Network Sites Table Actions
            add_filter('manage_sites_action_links', array($this, 'table_actions_Network_Admin_GForms_TMP'), 10, 3);

            // Filter Network Sites Table Columns
            add_filter('wpmu_blogs_columns', array($this, 'table_columns_Network_Admin_GForms_TMP'), 10, 1);

            // Hook Network Sites Tracking and Updated Row Data
            add_action('manage_sites_custom_column', array($this, 'table_rows_Network_Admin_GForms_TMP'), 10, 2);

            // Filter Bulk Actions
            add_filter("bulk_actions-sites-network", array($this, 'bulk_actions_Network_Admin_GForms_TMP'), 10, 1);
        }
    }

    /**
     * Loads Assets.
     * 
     * @return void
     */
    function load_assets_Network_Admin_GForms_TMP() {

        if (is_network_admin()) {

            $this->page_hook = add_menu_page('Leads Network Settings', 'Leads', 'manage_network_options', $this::slug, array(&$this, 'display_settings_Network_Admin_GForms_TMP'));

            add_submenu_page($this::slug, 'TMP Network Settings', 'Settings', 'manage_network_options', 'admin.php?page=' . $this::slug . '&t=settings');

            add_action('load-' . $this->page_hook, array($this, 'setup_page_Network_Admin_GForms_TMP'));
        }
    }

    /**
     * Handle Single Edit Site Update.
     * 
     * @return void
     */
    function site_update_Network_Admin_GForms_TMP() {

        $id = isset($_POST['id']) ? $_POST['id'] : 0;

        $updated = false;

        if ($id) {

            check_admin_referer('gforms-tmp-edit-site');

            $details = get_blog_details($id);

            $attributes = array(
                'archived' => $details->archived,
                'spam' => $details->spam,
                'deleted' => $details->deleted
            );

            if (!in_array(1, $attributes)) {

                $gforms_tmp_admin_client_id = isset($_POST['gforms_tmp_admin_client_id']) ? $_POST['gforms_tmp_admin_client_id'] : false;

                $gforms_tmp_admin_client_name = isset($_POST['gforms_tmp_admin_client_name']) ? $_POST['gforms_tmp_admin_client_name'] : false;

                $gforms_tmp_active = isset($_POST['gforms_tmp_active']) ? 1 : 0;

                if ($gforms_tmp_admin_client_id || $gforms_tmp_admin_client_name || !$gforms_tmp_active) {

                    $updated = true;

                    switch_to_blog($id);

                    update_option('gforms_tmp_admin_client_id', $gforms_tmp_admin_client_id);

                    update_option('gforms_tmp_admin_client_name', $gforms_tmp_admin_client_name);

                    update_option('gforms_tmp_active', $gforms_tmp_active);

                    update_option('gforms_tmp_last_update', time());

                    restore_current_blog();
                }
            }
        }

        wp_redirect(add_query_arg(array('update' => $updated ? 'updated' : 'failed', 'id' => $id), network_admin_url('admin.php?page=' . $this::slug . '&action=edit')));

        exit;
    }

    /**
     * Handle Bulk Edit Site Update.
     * 
     * @return void
     */
    function bulk_update_Network_Admin_GForms_TMP() {

        $blogs = isset($_POST['allblogs']) ? $_POST['allblogs'] : array();

        $action = isset($_POST['action2']) ? $_POST['action2'] : false;

        $updated = false;

        if ($blogs && $action && $action != '-1') {

            check_admin_referer('bulk-sites');

            $updated = true;

            $gforms_tmp_active = $action == 'activate' ? 1 : 0;

            foreach ($blogs as $id) {

                $details = get_blog_details($id);

                $attributes = array(
                    'archived' => $details->archived,
                    'spam' => $details->spam,
                    'deleted' => $details->deleted
                );

                if (!in_array(1, $attributes)) {

                    switch_to_blog($id);

                    update_option('gforms_tmp_active', $gforms_tmp_active);

                    update_option('gforms_tmp_last_update', time());

                    restore_current_blog();
                }
            }
        }

        wp_redirect(add_query_arg(array('update' => $updated ? 'updated' : 'failed', 'id' => $id), network_admin_url('admin.php?page=' . $this::slug)));

        exit;
    }

    /**
     * Handle Settings Tab Update.
     * 
     * @return void
     */
    function settings_update_Network_Admin_GForms_TMP() {

        check_admin_referer('gforms-tmp-edit-settings');

        $action = isset($_POST['action2']) ? $_POST['action2'] : false;

        $updated = false;

        if ($action) {

            $updated = true;

            switch ($action) {

                case 'authorize' :

                    $gforms_tmp_admin_restapi_url = isset($_POST['gforms_tmp_admin_restapi_url']) ? esc_url($_POST['gforms_tmp_admin_restapi_url']) : false;

                    $gforms_tmp_admin_api_password = isset($_POST['gforms_tmp_admin_api_password']) ? $_POST['gforms_tmp_admin_api_password'] : false;

                    $gforms_tmp_admin_api_username = isset($_POST['gforms_tmp_admin_api_username']) ? $_POST['gforms_tmp_admin_api_username'] : false;

                    update_site_option('gforms_tmp_admin_api_password', $gforms_tmp_admin_api_password);

                    update_site_option('gforms_tmp_admin_api_username', $gforms_tmp_admin_api_username);

                    update_site_option('gforms_tmp_admin_restapi_url', $gforms_tmp_admin_restapi_url);

                    if ($gforms_tmp_admin_restapi_url && $gforms_tmp_admin_api_username && $gforms_tmp_admin_api_password) {
                        try {
                            if ($this->is_valid_url($gforms_tmp_admin_restapi_url)) {
                                $oauth_provider = new Admin_GForms_TMP_OAuth($gforms_tmp_admin_restapi_url, $gforms_tmp_admin_api_username, $gforms_tmp_admin_api_password);
                                $accessToken = $oauth_provider->get_client_credentials_grant();
                                if (!$accessToken) {
                                    $updated = false;
                                }
                            } else {
                                $updated = false;
                            }
                        } catch (\Exception $e) {
                            $updated = false;
                        }
                    }
                    break;

                case 'deauthorize' :

                    delete_site_option('gforms_tmp_admin_api_password');

                    delete_site_option('gforms_tmp_admin_api_username');

                    delete_site_option('gforms_tmp_admin_restapi_url');

                    delete_site_option('gforms_tmp_access_token');

                    break;
            }
        }

        \wp_redirect(\add_query_arg(['update' => $updated ? 'updated' : 'failed'], \network_admin_url('admin.php?page=' . $this::slug . '&t=settings')));

        exit;
    }

    /**
     * Displays the Network Admin Page.
     * 
     * @return void
     */
    function display_settings_Network_Admin_GForms_TMP() {

        $action = isset($_GET['action']) ? $_GET['action'] : 'view';

        $tab = isset($_GET['t']) ? $_GET['t'] : 'network';

        switch ($action) :

            case 'edit' :

                $this->edit_site_Network_Admin_GForms_TMP();

                break;

            case 'view' :

            default :
                ?>

                <div class="wrap">

                    <div id="icon-options-general" class="icon32"><br></div>

                    <h2>

                        <?php \_e('Lead Network Settings', 'gforms-tmp') ?>

                        <?php
                        if (isset($_REQUEST['s']) && $_REQUEST['s']) {
                            printf('<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html($_REQUEST['s']));
                        }
                        ?>

                    </h2>

                    <br />

                    <?php
                    if (isset($_GET['update'])) {

                        $messages = array();

                        if ('updated' == $_GET['update'])
                            $messages[1] = __('Site(s) have been updated.');
                        else
                            $messages[0] = __('One or more Sites could not be updated.');
                    }

                    if (!empty($messages)) {

                        foreach ($messages as $status => $msg)
                            echo '<div id="message" class="' . ($status ? 'updated' : 'error') . '"><p>' . $msg . '</p></div>';

                        echo '<br />';
                    }
                    ?>

                    <?php $this->tabs_Network_Admin_GForms_TMP($tab); ?>

                    <br />

                    <?php
                    switch ($tab) :

                        case 'settings' :

                            $this->plugin_settings_Network_Admin_GForms_TMP();

                            break;

                        case 'network' :

                        default :

                            $this->network_settings_Network_Admin_GForms_TMP();

                            break;

                    endswitch;
                    ?>

                </div>

                <?php
                break;

        endswitch;
    }

    /**
     * Display Network Section of Settings Page
     * 
     */
    function network_settings_Network_Admin_GForms_TMP() {

        $wp_list_table = _get_list_table('WP_MS_Sites_List_Table', array('screen' => 'sites-network', 'plural' => 'sites', 'singular' => 'site'));

        $pagenum = $wp_list_table->get_pagenum();

        $wp_list_table->_actions = $this->bulk_actions_Network_Admin_GForms_TMP();

        $wp_list_table->prepare_items();

        $is_token_authorized = $this->is_api_authorized_GForms_TMP(true);

        if ($is_token_authorized) {
            $messages[1] = __('You are authorized to use the Target Media Partners Leads API.');
        } else {
            $messages[0] = __('You are not authorized to use the Target Media Partners Leads API. Authorize using the form below.');
        }

        if (!empty($messages)) {

            foreach ($messages as $status => $msg)
                echo '<div id="message" class="' . ($status ? 'updated' : 'error') . '"><p>' . $msg . '</p></div>';
        }
        ?>

        <form action="<?php echo network_admin_url('admin.php?page=' . $this::slug); ?>" method="post" id="ms-search">

            <?php $wp_list_table->search_box(__('Search Sites'), 'site'); ?>

            <input type="hidden" name="action" value="blogs" />

        </form>

        <form action="<?php echo network_admin_url('admin.php'); ?>" class="network_admin_gforms_tmp-settings" method="post">

            <input type="hidden" name="action" value="bulk_update_network_admin_gforms_tmp" />

            <?php $wp_list_table->display(); ?>

        </form>

        <script type="text/javascript">

            jQuery(document).ready(function ($) {

                $('SELECT[name=action2]').on('change', function (e) {

                    $('SELECT[name=action2]').val($(this).val());

                });

            });

        </script>

        <?php
    }

    /**
     * Display Edit Site Section of Settings Page
     * 
     */
    function edit_site_Network_Admin_GForms_TMP() {

        $unauthorized = false;

        if (!is_multisite())
            $unauthorized = __('Multisite support is not enabled.');

        if (!current_user_can('manage_sites'))
            $unauthorized = __('You do not have sufficient permissions to edit this site.');

        $id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        if (!$id)
            $unauthorized = __('Invalid site ID.');

        $details = get_blog_details($id);
        if (!can_edit_network($details->site_id))
            $unauthorized = __('You do not have permission to access this page.');

        if ($unauthorized) {

            echo "<p>{$unauthorized}</p>\n";

            return;
        }

        $site_url_no_http = preg_replace('#^http(s)?://#', '', get_blogaddress_by_id($id));

        $title_site_url_linked = sprintf(__('API Enabled: <a href="%1$s">%2$s</a>'), get_blogaddress_by_id($id), $site_url_no_http);

        $gforms_tmp_admin_client_id = \get_blog_option($id, 'gforms_tmp_admin_client_id', false);

        $gforms_tmp_admin_client_name = \get_blog_option($id, 'gforms_tmp_admin_client_name', false);

        $gforms_tmp_active = \get_blog_option($id, 'gforms_tmp_active', false);

        $gforms_tmp_last_update = \get_blog_option($id, 'gforms_tmp_last_update', false);

        $date = 'Y/m/d g:i:s a';

        $is_main_site = \is_main_site($id);

        if (isset($_GET['update'])) {

            $messages = array();

            if ('updated' == $_GET['update'])
                $messages[1] = __('Site updated.');
            else
                $messages[0] = __('Site could not be updated.');
        }

        if (!empty($messages)) {

            foreach ($messages as $status => $msg)
                echo '<div id="message" class="' . ($status ? 'updated' : 'error') . '"><p>' . $msg . '</p></div>';
        }
        ?>

        <div class="wrap">

            <div id="icon-options-general" class="icon32"><br></div>

            <h2 id="edit-site"><?php echo $title_site_url_linked ?></h2>

            <br />

            <form method="post" action="<?php echo network_admin_url('admin.php'); ?>">

                <?php wp_nonce_field('gforms-tmp-edit-site'); ?>

                <input type="hidden" name="id" value="<?php echo esc_attr($id) ?>" />

                <input type="hidden" name="action" value="site_update_network_admin_gforms_tmp" />

                <table class="form-table">

                    <tr class="form-field form-required">

                        <th scope="row"><?php _e('Domain') ?></th>

                        <?php
                        $protocol = is_ssl() ? 'https://' : 'http://';

                        if ($is_main_site) {
                            ?>

                            <td><code><?php
                                    echo $protocol;
                                    echo esc_attr($details->domain)
                                    ?></code></td>

                        <?php } else { ?>

                            <td><?php echo $protocol; ?><input type="text" id="domain" value="<?php echo esc_attr($details->domain) ?>" size="33" readonly="readonly" /></td>

                        <?php } ?>
                    </tr>

                    <tr class="form-field">

                        <th scope="row"><?php _e('Ten Street Client Name') ?></th>

                        <td><input type="text" name="gforms_tmp_admin_client_name" id="gforms_tmp_admin_client_name" value="<?php echo $gforms_tmp_admin_client_name; ?>" /></td>

                    </tr>

                    <tr class="form-field">

                        <th scope="row"><?php _e('Ten Street Client ID') ?></th>

                        <td><input type="text" name="gforms_tmp_admin_client_id" id="gforms_tmp_admin_client_id" value="<?php echo $gforms_tmp_admin_client_id; ?>" size="4" style="width: 50px;" /></td>

                    </tr>

                    <?php
                    $attributes = array();

                    $attributes['archived'] = $details->archived;

                    $attributes['spam'] = $details->spam;

                    $attributes['deleted'] = $details->deleted;
                    ?>

                    <tr>
                        <th scope="row"><?php _e('Enable API'); ?></th>

                        <td>

                            <label><input type="checkbox" name="gforms_tmp_active" value="1" <?php
                                checked((bool) $gforms_tmp_active, true);
                                disabled(in_array(1, $attributes));
                                ?> />

                            </label><br/>


                        </td>

                    </tr>

                    <tr class="form-field">

                        <th scope="row"><?php _e('Last Updated'); ?></th>

                        <td>

                            <label><?php echo (!$gforms_tmp_last_update ) ? __('Never') : mysql2date($date, date('Y-m-d h:i:s', $gforms_tmp_last_update)); ?></label>

                            <input name="gforms_tmp_last_update" type="hidden" id="gforms_tmp_last_update" value="<?php echo $gforms_tmp_last_update ?>" />

                        </td>

                    </tr>

                </table>

                <?php submit_button(); ?>

            </form>

        </div>

        <?php
    }

    /**
     * Plugin Settings Section of the Settings Page
     * 
     */
    function plugin_settings_Network_Admin_GForms_TMP() {

        $unauthorized = false;

        if (!is_multisite())
            $unauthorized = __('Multisite support is not enabled.');

        if (!current_user_can('manage_sites'))
            $unauthorized = __('You do not have sufficient permissions to edit this site.');

        if ($unauthorized) {

            echo "<p>{$unauthorized}</p>\n";

            return;
        }

        $gforms_tmp_admin_restapi_url = \get_site_option('gforms_tmp_admin_restapi_url', false);

        $gforms_tmp_admin_api_password = \get_site_option('gforms_tmp_admin_api_password', false);

        $gforms_tmp_admin_api_username = \get_site_option('gforms_tmp_admin_api_username', false);

        $is_token_authorized = $this->is_api_authorized_GForms_TMP(true);



        if (isset($_GET['update'])) {

            $messages = array();

            if ('updated' == $_GET['update']) {
                $messages[] = __('Settings updated.');
            } else {
                $messages[] = __('Settings could not be updated.');
            }
        }

        if ($is_token_authorized) {
            $messages[] = __('You are authorized to use the Target Media Partners Leads API.');
        } else {
            $messages[] = __('You are not authorized to use the Target Media Partners Leads API. Authorize using the form below.');
        }

        if (!empty($messages)) {

            foreach ($messages as $status => $msg)
                echo '<div id="message" class="' . ($status ? 'updated' : 'error') . '"><p>' . $msg . '</p></div>';
        }
        ?>

        <?php if (!$is_token_authorized) : ?>          

            <form method="post" action="<?php echo network_admin_url('admin.php'); ?>">

                <?php wp_nonce_field('gforms-tmp-edit-settings'); ?>

                <input type="hidden" name="action" value="settings_update_network_admin_gforms_tmp" />

                <input type="hidden" name="action2" value="authorize" />

            <?php endif; ?>

            <table class="form-table">

                <tr class="form-required">

                    <th scope="row"><?php _e('Target Media Partners REST API URL') ?></th>

                    <td>

                        <input type="text" name="gforms_tmp_admin_restapi_url" id="gforms_tmp_admin_restapi_url" value="<?php echo esc_attr($gforms_tmp_admin_restapi_url); ?>" class="regular-text" placeholder="http(s)://" <?php disabled($is_token_authorized); ?> />

                        <p class="description">Target Media Partners REST API URL. <br /><strong>Do not change this unless you know what you are doing!</strong></p>

                    </td>

                </tr>

                <tr class="form-required">
                    <th scope="row"><?php _e('API Username'); ?></th>

                    <td>

                        <input type="text" id="gforms_tmp_admin_api_username" name="gforms_tmp_admin_api_username" value="<?php echo $gforms_tmp_admin_api_username; ?>" class="regular-text" <?php disabled($is_token_authorized); ?> />

                        <p class="description">Enter Target Media Partners API Username.</p>

                    </td>

                <tr class="form-required">
                    <th scope="row"><?php _e('API Password'); ?></th>

                    <td>

                        <input type="password" id="gforms_tmp_admin_api_password" name="gforms_tmp_admin_api_password" value="<?php echo $gforms_tmp_admin_api_password; ?>" class="regular-text" <?php disabled($is_token_authorized); ?> />

                        <p class="description">Enter Target Media Partners API Key.</p>

                    </td>

                </tr>

            </table>

            <?php if (!$is_token_authorized) : ?>          

                <?php submit_button("Authorize API"); ?>

            </form>

        <?php else : ?>

            <form method="post" action="<?php echo network_admin_url('admin.php'); ?>">

                <?php wp_nonce_field('gforms-tmp-edit-settings'); ?>

                <input type="hidden" name="action" value="settings_update_network_admin_gforms_tmp" />

                <input type="hidden" name="action2" value="deauthorize" />

                <p><?php submit_button('Revoke Authorization', 'secondary'); ?></p>

            </form>

        <?php endif; ?>

        <?php
    }

    /**
     * Add Tabbed Headings
     */
    function tabs_Network_Admin_GForms_TMP($current = 'network') {

        $tabs = array('network' => 'Sites', 'settings' => 'Settings');

        echo '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $tab => $name) {
            $class = ( $tab == $current ) ? ' nav-tab-active' : '';
            echo "<a class='nav-tab$class' href='" . network_admin_url('admin.php?page=' . $this::slug) . "&t=$tab'>$name</a>";
        }
        echo '</h2>';
    }

    /**
     * Filter Network Table Actions
     */
    function table_actions_Network_Admin_GForms_TMP($actions, $blog_id, $blogname) {

        $new_actions = array();

        $new_actions['backend'] = "<span class='backend'><a href='" . esc_url(get_admin_url($blog_id)) . "' class='edit'>" . __('Dashboard') . '</a></span>';

        if (
                get_blog_status($blog_id, 'public') == true &&
                get_blog_status($blog_id, 'archived') == false &&
                get_blog_status($blog_id, 'spam') == false &&
                get_blog_status($blog_id, 'deleted') == false
        ) {

            $new_actions['edit'] = '<span class="edit"><a href="' . esc_url(network_admin_url('admin.php?page=' . $this::slug . '&action=edit&id=' . $blog_id)) . '">' . __('Edit') . '</a></span>';
        }

        return $new_actions;
    }

    /**
     * Filter Network Table Columns
     */
    function table_columns_Network_Admin_GForms_TMP($sites_columns) {

        $blogname_columns = ( is_subdomain_install() ) ? __('Domain') : __('Path');

        $sites_columns = array(
            'cb' => '<input type="checkbox" />',
            'blogname' => $blogname_columns,
            'tenstreet_client_id' => __('Target Media Partners Client ID'),
            'tenstreet_client_name' => __('Target Media Partners Client Name'),
            'enable_api' => __('Plugin Activated'),
            'tmp_updated' => __('Last Updated')
        );

        if (has_filter('wpmublogsaction'))
            $sites_columns['plugins'] = __('Actions');

        return $sites_columns;
    }

    /**
     * Filter Network Table Rows
     */
    function table_rows_Network_Admin_GForms_TMP($column_name, $blog_id) {

        global $mode;

        $is_token_authorized = $this->is_api_authorized_GForms_TMP(true);

        $blogname = ( is_subdomain_install() ) ? str_replace('.' . get_current_site()->domain, '', $blog['domain']) : $blog['path'];

        $output = "";

        switch ($column_name) {

            case 'cb' : {

                    $output .= '<label class="screen-reader-text" for="blog_' . $blog_id . '">' . sprintf(__('Select %s'), $blogname) . '</label>';

                    $output .= '<input type="checkbox" id="blog_' . $blog_id . '" name="allblogs[]" value="' . esc_attr($blog_id) . '" ' . disabled($is_token_authorized, true, false) . '/>';

                    break;
                }
            case 'tmp_updated' : {

                    $gforms_tmp_last_updated = get_blog_option($blog_id, "gforms_tmp_last_update", false);

                    if ('list' == $mode)
                        $date = 'Y/m/d';
                    else
                        $date = 'Y/m/d \<\b\r \/\> g:i:s a';

                    $output .= (!$gforms_tmp_last_updated ) ? __('Never') : mysql2date($date, date('Y-m-d', $gforms_tmp_last_updated));

                    break;
                }
            case 'tenstreet_client_name' : {

                    $tenstreet_client_name = get_blog_option($blog_id, "gforms_tmp_admin_client_name", false);

                    $output .= (!$tenstreet_client_name ) ? __(' - ') : __($tenstreet_client_name);

                    break;
                }
            case 'tenstreet_client_id' : {

                    $tenstreet_client_id = get_blog_option($blog_id, "gforms_tmp_admin_client_id", false);

                    $output .= (!$tenstreet_client_id ) ? __(' - ') : __($tenstreet_client_id);

                    break;
                }
            case 'enable_api' : {

                    $enable_api = get_blog_option($blog_id, "gforms_tmp_active", false);

                    $output .= (!$enable_api ) ? __('Inactive') : __('Active');

                    break;
                }
        }

        if ($output)
            echo $output;
    }

    /**
     * Bulk Actions
     */
    function bulk_actions_Network_Admin_GForms_TMP() {

        $new_bulk_actions = array();

        if (current_user_can('delete_sites')) {
            $new_bulk_actions['activate'] = __('Activate', 'gforms-tmp');
            $new_bulk_actions['deactivate'] = __('Deactivate', 'gforms-tmp');
        }

        return $new_bulk_actions;
    }

}
