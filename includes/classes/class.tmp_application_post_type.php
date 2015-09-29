<?php

// Register Custom Post Type
class TMP_Application {

    /**
     * The key used by the Target Media Partners post type.
     *
     * @var string
     */
    const POST_TYPE = 'tmpleads';

    public function __construct() {
        $this->register();
    }

    /**
     * Register Target Media Partners Custom Post Type
     * 
     * @return void
     */
    protected function register() {
        $labels = array(
            'name' => _x('Lead', 'Post Type General Name', 'gforms-tmp'),
            'singular_name' => _x('Lead', 'Post Type Singular Name', 'gforms-tmp'),
            'menu_name' => __('Leads', 'gforms-tmp'),
            'name_admin_bar' => __('Leads', 'gforms-tmp'),
            'parent_item_colon' => __('Parent Lead:', 'gforms-tmp'),
            'all_items' => __('All Leads', 'gforms-tmp'),
            'add_new_item' => __('Add New Lead', 'gforms-tmp'),
            'add_new' => __('Add New', 'gforms-tmp'),
            'new_item' => __('New Lead', 'gforms-tmp'),
            'edit_item' => __('Edit Lead', 'gforms-tmp'),
            'update_item' => __('Update Lead', 'gforms-tmp'),
            'view_item' => __('View Lead', 'gforms-tmp'),
            'search_items' => __('Search Leads', 'gforms-tmp'),
            'not_found' => __('Not found', 'gforms-tmp'),
            'not_found_in_trash' => __('Not found in Trash', 'gforms-tmp'),
        );
        $args = array(
            'label' => __('TMP Leads', 'gforms-tmp'),
            'description' => __('Target Media Partners Leads', 'gforms-tmp'),
            'labels' => $labels,
            'supports' => array('title', 'content', 'custom-fields',),
            'taxonomies' => array(),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 90,
            'show_in_admin_bar' => false,
            'show_in_nav_menus' => false,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => true,
            'publicly_queryable' => true,
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => false, 
            ),
            'map_meta_cap' => false, // Set to false, if users are not allowed to edit/delete existing posts
        );
        register_post_type(TMP_Application::POST_TYPE, $args);
        $this->remove_custom_post_comment();
    }

    /**
     * Disable Comment Functionality
     * 
     * @return void
     */
    function remove_custom_post_comment() {
        remove_post_type_support(TMP_Application::POST_TYPE, 'comments');
    }

}
