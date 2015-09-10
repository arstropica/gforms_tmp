<?php
    /*
    Plugin Name: TenStreet GravityForms Addon
    Plugin URI: https://arstropica.com
    Description: This plugin adds web service functionality to Gravity Forms used to submit applications to the TenStreet API.
    Version: 1.1
    Author: Akin Williams
    Author Email: aowilliams@arstropica.com
    License: GNU General Public License v2 or later
    License URI: http://www.gnu.org/licenses/gpl-2.0.html
    Text Domain: gforms-tmp
    Domain Path: /lang

    */

    /**
    * The core plugin class that is used to define internationalization,
    * dashboard-specific hooks, and public-facing site hooks.
    */
    require plugin_dir_path( __FILE__ ) . 'includes/classes/class.gforms_tmp.php';

    global $gforms_tmp;

    /**
    * Begins execution of the plugin.
    *
    * Since everything within the plugin is registered via hooks,
    * then kicking off the plugin from this point in the file does
    * not affect the page life cycle.
    *
    * @since    1.0.0
    */
    function init_gforms_tmp() {

        global $gforms_tmp;

        $gforms_tmp = new GForms_TMP();

    }

    init_gforms_tmp();

?>