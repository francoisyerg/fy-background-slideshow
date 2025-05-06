<?php
/*
Plugin Name: FY Background Slideshow
Description: Apply a slideshow to every element with a specific class.
Version: 1.0
Author: François Yerg
Author URI: https://francoisyerg.net
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: fybs
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load plugin text domain for translations
add_action('plugins_loaded', function() {
    load_plugin_textdomain('fybs', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// CPT pour les diaporamas
require_once plugin_dir_path(__FILE__) . 'includes/metaboxes.php';
require_once plugin_dir_path(__FILE__) . 'includes/output.php';

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('fybs-frontend', plugin_dir_url(__FILE__) . 'assets/fybs-frontend.js', [], '1.0', true);
});

// Register Custom Post Type for Slideshows
add_action('init', function() {
    $labels = array(
        'name' => esc_html__('Slideshows', 'fybs'),
        'singular_name' => esc_html__('Slideshow', 'fybs'),
        'menu_name' => esc_html__('Background slideshows', 'fybs'),
    );
    $args = array(
        'label' => esc_html__('Slideshow', 'fybs'),
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-images-alt2',
        'supports' => array('title'),
    );
    register_post_type('fybs_slideshow', $args);
});

add_action('wp_footer', function() {
    $slideshows = get_posts([
        'post_type' => 'fybs_slideshow',
        'post_status' => 'publish',
        'numberposts' => -1
    ]);

    if (empty($slideshows)) return;

    echo '<script>document.addEventListener("DOMContentLoaded",function(){';
    
    foreach ($slideshows as $s) {
        $class = get_post_meta($s->ID, '_fybs_class', true);
        $image_ids = explode(',', get_post_meta($s->ID, '_fybs_images', true));
        $transition = get_post_meta($s->ID, '_fybs_transition', true) ?: 'fade';
        $duration = get_post_meta($s->ID, '_fybs_duration', true) ?: 5000;
        $urls = array_map(function($id) {
            return wp_get_attachment_image_url($id, 'full');
        }, $image_ids);
        $urls = array_filter($urls);

        if ($class && $urls) {
            $selector = esc_js($class);
            $imgList = esc_js(implode(',', $urls));
            echo <<<JS
                document.querySelectorAll('.{$selector}').forEach(el=>{
                    el.setAttribute('data-fybs', '1');
                    el.setAttribute('data-images', '{$imgList}');
                    el.setAttribute('data-effect', '{$transition}');
                    el.setAttribute('data-duration', '{$duration}');
                });
            JS;
        }
    }

    echo '});</script>';
});