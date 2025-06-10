<?php

/*
Plugin Name: XML Feed Generator (Green Acres Feed) 
Version: 0.2.4
Description: This plugin is required to generate kyero or any xml feed. This plugin will not generate automatically any feed, it contains only helper functions. 
Author: Shashank Mishra
*/

defined('ABSPATH') or die('Access Denied.');

register_activation_hook(__FILE__, 'sb_ga_xml_feed_activate');

function sb_ga_xml_feed_activate()
{
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'sb_ga_xml_feed_deactivate');
function sb_ga_xml_feed_deactivate()
{
    flush_rewrite_rules();
}

function sb_ga_xml_feed_wp_init()
{
    add_rewrite_rule('^property-feed/green-acres/([a-zA-Z0-9]*)', 'index.php?xml_feed_action=property-feed-green-acres&xml_feed_property_id=$matches[1]', 'top');
}
add_action('init', 'sb_ga_xml_feed_wp_init');

add_action('query_vars', 'sb_ga_xml_feed_query_vars');
function sb_ga_xml_feed_query_vars($query_vars)
{
    $query_vars[] = 'xml_feed_action';
    $query_vars[] = 'xml_feed_property_id';

    return $query_vars;
}

add_action('template_include', 'sb_ga_xml_feed_template_include', 1, 1);
function sb_ga_xml_feed_template_include($template)
{
    global $wp_query;

    if (isset($wp_query->query_vars['xml_feed_action']) && $wp_query->query_vars['xml_feed_action'] == 'property-feed-green-acres') {
        if (file_exists(get_stylesheet_directory() . '/xml-feed-green-acres.php')) {
            return get_stylesheet_directory() . '/xml-feed-green-acres.php';
        } else if (file_exists(get_template_directory() . '/xml-feed-green-acres.php')) {
            return get_template_directory() . '/xml-feed-green-acres.php';
        } else {
            return plugin_dir_path(__FILE__) . '/xml-feed-green-acres.php';
        }
    }
    return $template;
}

function sb_ga_xmlEscape($string)
{
    return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
}

function sb_ga_property_title()
{
    global $post;
    $content = strip_tags($post->post_title);
    echo sb_ga_xmlEscape($content);
}

function sb_ga_property_description()
{
    global $post;
    $content = strip_tags($post->post_content);
    echo sb_ga_xmlEscape($content);
}

function sb_ga_get_details($name, $tag = '', $only_one = false)
{
    global $post;
    $terms = wp_get_post_terms($post->ID, $name);
    $term_arr = array();
    $count = 0;
    foreach ($terms as $term) {
        if ($tag != '') {
            echo "<{$tag}>{$term->name}</{$tag}>";
        } else {
            $term_arr[] = $term->name;
        }

        if ($only_one) {
            $count++;
            if ($count >= 1) {
                break;
            }
        }
    }
    if ($tag == '') {
        return $term_arr;
    }
}

function sb_ga_check_exist($name, $search = '')
{
    global $post;
    $terms = wp_get_post_terms($post->ID, $name);

    foreach ($terms as $term) {
        if (is_array($search)) {
            foreach ($search as $t) {
                if (stripos($term->name, $t) !== false) {
                    return true;
                }
            }
        } else {
            if (stripos($term->name, $search) !== false) {
                return true;
            }
        }
    }
    return false;
}

function sb_ga_get_detail($name, $tag = '', $intval = false, $replace = null)
{
    global $post;
    $post_meta = get_post_meta($post->ID, $name, true);
    if ($intval) {
        $post_meta = intval($post_meta);
    }
    if (!empty($replace)) {
        $post_meta = str_replace($replace[0], $replace[1], $post_meta);
    }

    if ($tag != '') {
        echo "<{$tag}>{$post_meta}</{$tag}>";
    } else {
        return $post_meta;
    }
}

function sb_ga_get_attach_images($imgnum = 10, $include_title = false, $include_featured = true)
{
    global $post;
    $attachments = get_post_meta($post->ID, 'fave_property_images', false);
    
    if ($attachments) {
        $imgid = 1;
        foreach ($attachments as $img) {
            $img_src = wp_get_attachment_image_src($img, 'full');
            if (!empty($img_src[0])) {
                echo '<PHOTO' . $imgid . '>' . $img_src[0] . '</PHOTO' . $imgid . '>' . "\n";
                $imgid++;
                if ($imgid > $imgnum) {
                    break;
                }
            }
        }
    }
}

// Custom Meta Fields for properties
function sb_ga_layout_of_custom_field()
{
    global $post;
    $exclude_from_feed = get_post_meta($post->ID, 'exclude_from_feed', true);
    ?>
    <table class="form-table cmb_metabox" width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <label style="margin-bottom: 10px; display: block; font-weight: bold;">
                    <input name="exclude_from_feed" type="checkbox" id="exclude_from_feed" <?php echo $exclude_from_feed ? "checked='checked'" : ""; ?> />
                    Exclude this property from Feed
                </label>
            </td>
        </tr>
    </table>
    <?php
}

function sb_ga_register_custom_field()
{
    add_meta_box("sb_ga_layout_of_custom_field", "Green Acres Feed Fields", "sb_ga_layout_of_custom_field", "property", "side", "low");
}

add_action('add_meta_boxes', 'sb_ga_register_custom_field');
add_action('save_post', 'sb_ga_save_custom_field');

function sb_ga_save_custom_field($post_id)
{
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX)) {
        return;
    }

    if (isset($_POST['post_type'])) {
        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }
    }

    $included = isset($_POST["exclude_from_feed"]) ? true : false;
    update_post_meta($post_id, "exclude_from_feed", $included);
}

function ga_get_exclude_list()
{
    $posts = get_posts(array(
        'post_type' => 'property',
        'post_status' => 'publish',
        'posts_per_page' => '-1',
        'meta_query' => array(
            array(
                'key' => 'exclude_from_feed',
                'value' => true,
            ),
        ),
    ));

    $list_id = [];
    foreach ($posts as $p) {
        $list_id[] = $p->ID;
    }
    return $list_id;
}

add_filter('manage_property_posts_columns', 'sb_ga_xml_feed_custom_columns');
function sb_ga_xml_feed_custom_columns($columns)
{
    $columns['include_in_feed'] = "Include in Kyero Feed";
    return $columns;
}

add_action('manage_property_posts_custom_column', 'sb_ga_xml_feed_custom_column', 10, 2);
function sb_ga_xml_feed_custom_column($column_name, $property_id)
{
    if ($column_name == "include_in_feed") {
        $exclude_from_feed = get_post_meta($property_id, 'exclude_from_feed', true);
        if (!$exclude_from_feed) {
            echo "<span style='color: green;'>Yes</span>";
        } else {
            echo "<span style='color: red;'>No</span>";
        }
    }
}

add_action('pre_get_posts', function ($query) {
    if (!is_admin()) {
        return;
    }

    $orderby = $query->get('orderby');
    if ($orderby == 'include_in_feed') {
        $query->set('meta_key', 'exclude_from_feed');
        $query->set('orderby', 'meta_value_num');
    }
});

function sb_ga_set_property_meta()
{
    $properties = get_posts(array('post_type' => 'property', 'posts_per_page' => -1));
    foreach ($properties as $p) {
        update_post_meta($p->ID, 'exclude_from_a_place_in_sun_feed', false);
    }
}

?>