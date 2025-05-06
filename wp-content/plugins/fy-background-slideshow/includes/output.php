<?php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('fybs-script', plugin_dir_url(__FILE__) . 'script.js', array(), null, true);
    wp_enqueue_style('fybs-style', plugin_dir_url(__FILE__) . 'style.css');
    
    $slideshows = get_posts(array('post_type' => 'bg_slideshow', 'posts_per_page' => -1));
    $data = [];
    foreach ($slideshows as $slideshow) {
        $data[] = [
            'class' => get_post_meta($slideshow->ID, '_fybs_class', true),
            'images' => explode(',', get_post_meta($slideshow->ID, '_fybs_images', true)),
            'duration' => intval(get_post_meta($slideshow->ID, '_fybs_duration', true)) ?: 5000
        ];
    }
    wp_localize_script('fybs-script', 'fybs_data', $data);
});
