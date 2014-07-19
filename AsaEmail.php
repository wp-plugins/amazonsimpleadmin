<?php
/**
 * Handles the ASA email notification feature
 *
 * A non-public custom post type is used for updating error information on a post
 * This way Post Status Notifier (another plugin by me) with all its powerful features
 * can handle the email sending by setting up one or more notification rules listening
 * on Post Type "ASA Errors".
 *
 * Post Status Notifier homepage: http://www.ifeelweb.de/wp-plugins/post-status-notifier/
 *
 * @author    Timo Reith <timo@ifeelweb.de>
 * @copyright Copyright (c) 2014 ifeelweb.de
 * @version   $Id$
 * @package
 */

class AsaEmail 
{
    /**
     * @var AsaEmail
     */
    protected static $_instance;

    /**
     * @var string
     */
    protected $_psnBridgePostType = 'asa-errors';


    /**
     * @return AsaEmail
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    protected function __construct()
    {
        $this->_init();
    }

    protected function _init()
    {
        add_action('init', array($this, 'initPsnBridgePostType'));
    }

    /**
     * Updates the PSN bridge post
     *
     * @param array $error
     * @param string $content
     * @return int|WP_Error
     */
    public function updatePsnBridgePost(array $error, $content = '')
    {
        $bridgePageId = $this->getPsnBridgePostId();

        update_post_meta($bridgePageId, 'error-code', $error['Code']);
        update_post_meta($bridgePageId, 'error-message', $error['Message']);
        update_post_meta($bridgePageId, 'error-asin', $error['ASIN']);
        update_post_meta($bridgePageId, 'error-location', $error['Location']);

        $postData = array(
            'ID'           => $bridgePageId,
            'post_content' => $content
        );

        return wp_update_post($postData);
    }

    /**
     * @return bool
     */
    public function hasPsnBridgePost()
    {
        return count($this->getPsnBridgePost()) > 0;
    }

    /**
     * @return array
     */
    public function getPsnBridgePost()
    {
        $result = get_posts(array(
            'post_type' => $this->_psnBridgePostType,
            'post_status' => 'draft'
        ));

        return $result;
    }

    /**
     * @return int|WP_Error
     */
    public function getPsnBridgePostId()
    {
        if ($this->hasPsnBridgePost()) {
            $post = array_shift($this->getPsnBridgePost());
            $id = $post->ID;
        } else {
            $id = $this->_createPsnBridgePost();
        }

        return $id;
    }

    /**
     * Creates the PSN bridge post
     *
     * @return int|WP_Error
     */
    protected function _createPsnBridgePost()
    {
        $post = array(
            'post_title' => 'ASA Error',
            'post_status' => 'draft',
            'post_type' => $this->_psnBridgePostType
        );

        $id = wp_insert_post($post);

        return $id;
    }

    /**
     *
     */
    public function initPsnBridgePostType()
    {
        $labels = array(
            'name'               => _x( 'ASA Errors', 'post type general name', 'asa1' ),
            'singular_name'      => _x( 'ASA Error', 'post type singular name', 'asa1' ),
            'menu_name'          => _x( 'ASA Errors', 'admin menu', 'asa1' ),
            'name_admin_bar'     => _x( 'ASA Error', 'add new on admin bar', 'asa1' ),
            'add_new'            => _x( 'Add New', 'asa-error', 'asa1' ),
            'add_new_item'       => __( 'Add New', 'asa1' ),
            'new_item'           => __( 'New', 'asa1' ),
            'edit_item'          => __( 'Edit', 'asa1' ),
            'view_item'          => __( 'View', 'asa1' ),
            'all_items'          => __( 'All', 'asa1' ),
            'search_items'       => __( 'Search', 'asa1' ),
            'parent_item_colon'  => __( 'Parent:', 'asa1' ),
            'not_found'          => __( 'Noting found.', 'asa1' ),
            'not_found_in_trash' => __( 'Nothing found in Trash.', 'asa1' )
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'query_var'          => false,
            //'rewrite'            => array( 'slug' => 'asa-error' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'custom-fields')
        );

        register_post_type($this->_psnBridgePostType, $args );
    }
}
 