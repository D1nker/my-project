<?php

add_action('add_meta_boxes', '_my_init_metaboxes');
function _my_init_metaboxes()
{
    add_meta_box('id_ma_meta', __('My metabox', 'my-textdomain'), '_my_meta_function', 'post', 'normal', 'high');
}

function _my_meta_function($post)
{
    $val = get_post_meta($post->ID, '_my_field', true);
    echo '<label for="my_field">'.__(' My field', 'my-textdomain ').'</label>';
    echo '<input id="my_field" type="text" name="_my_field" value="'.esc_attr($val).'"/>';
    wp_nonce_field('_my_meta_box_save', '_meta_box_nonce'); // un seul à la fin, pour la sécurité
}

add_action('save_post', '_my_save_post');
function _my_save_post($post_ID)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return false;
    }
    if (!empty($_POST['_my_field']) && check_admin_referer('_my_meta_box_save', '_meta_box_nonce')) {
        update_post_meta($post_ID, '_my_field', esc_attr($_POST['_my_field']));
    }
}
