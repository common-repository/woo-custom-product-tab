<?php

/**
 * Plugin Name:       Woo Custom Product Tab
 * Plugin URI:        http://www.wpcodelibrary.com
 * Description:       This plugin allows site admins to add custom tabs to products.
 * Version:           1.0.2
 * Author:            WPCodelibrary
 * Author URI:        http://www.wpcodelibrary.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-custom-product-tab
 * Domain Path:       /languages
 */
if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('Woo_Custom_Product_Tab')) :

    /**
     * Plugin main class.
     *
     * @package WC_Digital_Goods_Checkout
     */
    class Woo_Custom_Product_Tab {

        /**
         * Plugin version.
         *
         * @var string
         */
        const VERSION = '1.0.1';
        
        /**
         * Instance of this class.
         *
         * @var object
         */
        protected static $instance = null;
        public $post_type = 'wcpt_tab';
       
        /**
         * $id
         * holds settings tab id
         * @var string
         */
        public $id = 'wcpt_custom_tabs';

        /**
         * Initialize the plugin public actions.
         */
        private function __construct() {
            add_action('init', array($this, 'load_plugin_textdomain'));
            if (is_admin() && (!defined('DOING_AJAX') || !DOING_AJAX )) {
                $this->admin_includes();
            }
            add_action('init', array($this, 'wcpt_custom_product_tabs_post_type'), 0);
            //add tabs to product page
            add_filter('woocommerce_product_tabs', array($this, 'wcpt_woocommerce_product_tabs'));
        }

        /**
         * Return an instance of this class.
         *
         * @return object A single instance of this class.
         */
        public static function get_instance() {
            // If the single instance hasn't been set, set it now.
            if (null == self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * Load the plugin text domain for translation.
         */
        public function load_plugin_textdomain() {
            load_plugin_textdomain('woo-custom-product-tab', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        }

        /**
         * Admin includes.
         *
         * @return void
         */
        protected function admin_includes() {
            include_once( 'admin/class-woo-custom-product-tab-admin.php' );
        }

        /**
         * custom_product_tabs_post_type
         * Register custom tabs Post Type
         * @return void
         */
        function wcpt_custom_product_tabs_post_type() {
            $labels = array(
                'name' => _x('Product Tabs', 'Post Type General Name', 'woo-custom-product-tab'),
                'singular_name' => _x('Product Tab', 'Post Type Singular Name', 'woo-custom-product-tab'),
                'menu_name' => __('product Tabs', 'woo-custom-product-tab'),
                'parent_item_colon' => __('', 'woo-custom-product-tab'),
                'all_items' => __('Product Tabs', 'woo-custom-product-tab'),
                'view_item' => __('', 'woo-custom-product-tab'),
                'add_new_item' => __('Add Product Tab', 'woo-custom-product-tab'),
                'add_new' => __('Add New', 'woo-custom-product-tab'),
                'edit_item' => __('Edit Product Tab', 'woo-custom-product-tab'),
                'update_item' => __('Update Product Tab', 'woo-custom-product-tab'),
                'search_items' => __('Search Product Tab', 'woo-custom-product-tab'),
                'not_found' => __('Not found', 'woo-custom-product-tab'),
                'not_found_in_trash' => __('Not found in Trash', 'woo-custom-product-tab'),
            );
            $args = array(
                'label' => __('Product Tabs', 'woo-custom-product-tab'),
                'description' => __('Custom Product Tabs', 'woo-custom-product-tab'),
                'labels' => $labels,
                'supports' => array('title', 'editor', 'custom-fields'),
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_in_menu' => true,
                'show_in_nav_menus' => false,
                'show_in_admin_bar' => true,
                'menu_position' => 5,
                'menu_icon' => 'dashicons-feedback',
                'can_export' => true,
                'has_archive' => false,
                'exclude_from_search' => true,
                'publicly_queryable' => false,
                'capability_type' => 'post',
            );
            register_post_type('wcpt_tab', $args);
        }

       
        public function wcpt_woocommerce_product_tabs($tabs) {
            global $post;
            //get global tabs
            $global_tabs = get_option('wc_' . $this->id . '_globals');
            $global_tabs_ids = !empty($global_tabs) ? array_map('absint', $global_tabs) : array();

            //get tabs to include with current product
            $product_tabs = get_post_meta($post->ID, 'custom_tabs_ids', true);
            $ids_arr = !empty($product_tabs) ? array_map('absint', $product_tabs) : null;
          
            if ($ids_arr) {
                $ids_arr = array_reverse($ids_arr);
                //loop over tabs and add them
                foreach ($ids_arr as $id) {
                    if ($this->wcpt_post_exists($id)) {
                        $display_title = get_post_meta($id, 'tab_display_title', true);
                        $priority = get_post_meta($id, 'tab_priority', true);
                        $tabs['customtab_' . $id] = array(
                            'title' => (!empty($display_title) ? $display_title : get_the_title($id) ),
                            'priority' => (!empty($priority) ? $priority : 50 ),
                            'callback' => array($this, 'wcpt_render_tab'),
                            'content' => apply_filters('the_content', get_post_field('post_content', $id)) //this allows shortcodes in custom tabs
                        );
                    }
                }
            }
            return $tabs;
        }

        public function wcpt_post_exists($post_id) {
            return is_string(get_post_status($post_id));
        }

       
       public function wcpt_render_tab($key, $tab) {
            global $post;
            echo '<h2>' . apply_filters('wcpt_custom_tab_title', $tab['title'], $tab, $key) . '</h2>';
            echo apply_filters('wcpt_custom_tab_content', $tab['content'], $tab, $key);
        }

    }

    add_action('plugins_loaded', array('Woo_Custom_Product_Tab', 'get_instance'));

endif;