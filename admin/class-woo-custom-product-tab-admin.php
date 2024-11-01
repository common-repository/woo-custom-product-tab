<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Woo_Custom_Product_Tab_Admin {

    public $post_type = 'wcpt_tab';
    public $id = 'wcpt_custom_tabs';

    public function __construct() {
        if (is_admin()) {
            add_filter('woocommerce_settings_tabs_array', array($this, 'wcpt_woocommerce_settings_tabs_array'), 50);
            add_action('woocommerce_settings_tabs_' . $this->id, array($this, 'wcpt_show_settings_tab'));
            add_action('woocommerce_update_options_' . $this->id, array($this, 'wcpt_update_settings_tab'));
            add_action('woocommerce_admin_field_' . $this->post_type, array($this, 'show_' . $this->post_type . '_field'), 10);
            add_action('woocommerce_update_option_' . $this->post_type, array($this, 'save_' . $this->post_type . '_field'), 10);
            add_action('woocommerce_product_write_panel_tabs', array($this, 'wcpt_woocommerce_product_write_panel_tabs'));
            add_action('woocommerce_product_write_panels', array($this, 'wcpt_woocommerce_product_write_panels'));
            //save product selected tabs
            add_action('woocommerce_process_product_meta', array($this, 'wcpt_woocommerce_process_product_meta'), 10, 2);
            add_action('admin_footer', array($this, 'wcpt_ajax_footer_js'));
        }
        //ajax search handler
        
        add_action('wp_ajax_woocommerce_json_custom_tabs', array($this, 'wcpt_woocommerce_json_custom_tabs'));
    }

    public function wcpt_woocommerce_settings_tabs_array($settings_tabs) {
        $settings_tabs[$this->id] = __('Custom Product Tabs', 'woo-custom-product-tab');
        return $settings_tabs;
    }

    public function wcpt_show_settings_tab() {
        woocommerce_admin_fields($this->get_settings());
    }

    public function wcpt_update_settings_tab() {
        woocommerce_update_options($this->get_settings());
    }

    public function get_settings() {
        $settings = array(
            'section_title' => array(
                'name' => __('Custom Tabs', 'woo-custom-product-tab'),
                'type' => 'title',
                'desc' => '',
                'id' => 'wc_' . $this->id . '_section_title'
            ),
            'title' => array(
                'name' => __('Global Custom Tabs', 'woo-custom-product-tab'),
                'type' => $this->post_type,
                'desc' => __('Start typing the Custom Tab name, Used for including custom tabs on all products.', 'woo-custom-product-tab'),
                'desc_tip' => true,
                'default' => '',
                'id' => 'wc_' . $this->id . '_globals'
            ),
            'section_end' => array(
                'type' => 'sectionend',
                'id' => 'wc_' . $this->id . '_section_end'
            )
        );
        return apply_filters('wc_' . $this->id . '_settings', $settings);
    }

    public function wcpt_ajax_footer_js() {
        $screen = array();
        $screen = get_current_screen();
        if ($screen->post_type == 'product') {
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                // Ajax Chosen Product Selectors
              jQuery("select.ajax_chosen_select_tabs").select2({});
            });
        </script>
        <?php
        }
    }

    public function wcpt_woocommerce_product_write_panel_tabs() {
        ?>
        <li class="custom_tab">
            <a href="#wcpt_custom_tab">
        <?php _e('Custom Product Tab', 'woo-custom-product-tab'); ?>
            </a>
        </li>
        <?php
    }

    public function wcpt_woocommerce_product_write_panels() {
        global $post, $woocommerce;
        $fields = array(
            array(
                'key' => 'custom_tabs_ids',
                'label' => __('Select Custom Tabs', 'woo-custom-product-tab'),
                'desc' => __('Start typing the Custom Tab name, Used for including custom tabs.', 'woo-custom-product-tab')
            ),
        );
            
        ?>
        <div id="wcpt_custom_tab" class="panel woocommerce_options_panel">
       
            <?php
       
            foreach ($fields as $key) {
            $tabs_ids = get_post_meta($post->ID, $key['key'], true);
            $ids_arr = !empty($tabs_ids) ? array_map('absint', $tabs_ids) : array();
            ?>
                <div class="options_group">
                    <p class="form-field custom_product_tabs">
                        <label for="custom_product_tabs"><?php echo $key['label']; ?></label>
                        <select style="width: 50%;" id="<?php echo $key['key']; ?>" name="<?php echo $key['key']; ?>[]" class="ajax_chosen_select_tabs" multiple="multiple" data-placeholder="<?php _e('Search for a custom tab&hellip;', 'woo-custom-product-tab'); ?>">
            <?php
            foreach ($this->wcpt_get_custom_tabs_list() as $id => $label) {
                $selected = in_array($id, $ids_arr) ? 'selected="selected"' : '';
                echo '<option value="' . esc_attr($id) . '"' . $selected . '>' . esc_html($label) . '</option>';
            }
            ?>
                        </select> <img class="help_tip" data-tip="<?php echo esc_attr($key['desc']); ?>" src="<?php echo $woocommerce->plugin_url(); ?>/assets/images/help.png" height="16" width="16" />
                    </p>
                </div>
            <?php
        }
        ?>
        </div>
          <?php  
        }

       public function wcpt_woocommerce_process_product_meta($post_id) {
            foreach (array('exclude_custom_tabs_ids', 'custom_tabs_ids') as $key) {
                if (isset($_POST[$key]))
                    update_post_meta($post_id, $key, $_POST[$key]);
                else
                    delete_post_meta($post_id, $key);
            }
        }

       public function wcpt_woocommerce_json_custom_tabs() {
            check_ajax_referer('search-products-tabs', 'security');
            header('Content-Type: application/json; charset=utf-8');
            $term = (string) urldecode(stripslashes(strip_tags($_GET['term'])));
            if (empty($term))
                die();
            $post_types = array($this->post_type);
            if (is_numeric($term)) {
                //by tab id
                $args = array(
                    'post_type' => $post_types,
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'post__in' => array(0, $term),
                    'fields' => 'ids'
                );

                $args2 = array(
                    'post_type' => $post_types,
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'post_parent' => $term,
                    'fields' => 'ids'
                );

                $posts = array_unique(array_merge(get_posts($args), get_posts($args2)));
            } else {
                //by name
                $args = array(
                    'post_type' => $post_types,
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    's' => $term,
                    'fields' => 'ids'
                );
                $posts = array_unique(get_posts($args));
            }

            $found_tabs = array();

            if ($posts)
                foreach ($posts as $post_id) {

                    $found_tabs[$post_id] = get_the_title($post_id);
                }

            $found_tabs = apply_filters('woocommerce_json_search_found_tabs', $found_tabs);
            echo json_encode($found_tabs);

            die();
        }


        public function wcpt_get_custom_tabs_list() {
            $args = array(
                'post_type' => array($this->post_type),
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids'
            );
            $found_tabs = array();
            $posts = get_posts($args);
            if ($posts)
                foreach ($posts as $post_id) {
                    $found_tabs[$post_id] = get_the_title($post_id);
                }
            return $found_tabs;
        }

    }

    new Woo_Custom_Product_Tab_Admin();