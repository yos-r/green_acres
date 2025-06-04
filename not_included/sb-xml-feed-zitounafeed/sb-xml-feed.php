<?php  

/*
Plugin Name: XML Feed Generator (Zitouna Feed) 
Version: 0.2.4
Description: This plugin is required to generate kyero or any xml feed. This plugin will not generate automatically any feed, it contains only helper functions. 
Author: Shashank Mishra

*/

defined( 'ABSPATH' ) or die( 'Access Denied.' );

register_activation_hook( __FILE__, 'sb_xml_feed_activate' );

function sb_xml_feed_activate()
{
    
    flush_rewrite_rules();
}


register_deactivation_hook(__FILE__, 'sb_xml_feed_deactivate');
function sb_xml_feed_deactivate()
{
    flush_rewrite_rules();
}

function sb_xml_feed_wp_init()
{
    
    add_rewrite_rule('^property-feed/mubawab/([a-zA-Z0-9]*)', 'index.php?xml_feed_action=property-feed&xml_feed_property_id=$matches[1]');
    
}
add_action('init', 'sb_xml_feed_wp_init');

add_action('query_vars','sb_xml_feed_query_vars');
function sb_xml_feed_query_vars($query_vars)
{
    $query_vars[]='xml_feed_action';
    $query_vars[]='xml_feed_property_id';
   
    return $query_vars;
}

add_action('template_include','sb_xml_feed_template_include',1,1);
function sb_xml_feed_template_include($template)
{
    global $wp_query;
    
    if(isset($wp_query->query_vars['xml_feed_action']) && $wp_query->query_vars['xml_feed_action'] == 'property-feed')
    {
        
        
        if(file_exists(get_stylesheet_directory().'/xml-feed.php'))
        {
            
            return get_stylesheet_directory().'/xml-feed.php';
        }
        else if(file_exists(get_template_directory().'/xml-feed.php'))
        {
            return get_stylesheet_directory().'/xml-feed.php';
        }
        else
        {
             
            return plugin_dir_path( __FILE__ ).'/xml-feed.php';
        }
        
    }
    return $template;
}

function xmlEscape($string) {
    return str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $string);
}

function sb_property_title()
{
    global $post;
    $content = strip_tags($post->post_title);
    echo xmlEscape($content);
}

function sb_property_description()
{
    global $post;
    $content = strip_tags($post->post_content);
    echo xmlEscape($content);
}

function sb_get_details($name, $tag='', $only_one = false)
{
    global $post;
    $terms = wp_get_post_terms($post->ID, $name);
    $term_arr = array();
    $count = 1;   
    foreach($terms as $term)
    {
        if($tag != '')
            echo "<{$tag}>{$term->name}</{$tag}>";
        else
            $term_arr[] = $term->name;
        if($only_one)
        {
            $count ++;
            if($count > 1)
                break;
        }
    }
    if($tag == '')
        return $term_arr;   
}

function sb_check_exist($name, $search = '')
{
    global $post;
    $terms = wp_get_post_terms( $post->ID, $name);
  
    foreach($terms as $term)
    {
        if(is_array($search)) {
            foreach($search as $t) {
                
                if (stripos($term->name, $t) !== false) {
                    
                    return true;
                }
            }
        } 
        else {
            if (stripos($term->name, $search) !== false) {
                return true;
            }
        }
        
        
    }
    return false;
}

function sb_get_detail($name, $tag='', $intval=false, $replace = null)
{
    global $post;
    $post_meta = get_post_meta($post->ID, $name, true);
    if($intval)
        $post_meta = intval($post_meta);
    if(!empty($replace))
    {
       $post_meta =  str_replace($replace[0], $replace[1], $post_meta);
    }
    
    if($tag != '')
        echo "<{$tag}>{$post_meta}</{$tag}>";
    else
        return $post_meta;
}


function sb_get_attach_images($imgnum = 10, $include_title = false, $include_featured = true)
{
    global $post;
    $attachments = get_post_meta($post->ID, 'fave_property_images', false);
    
     
     
    if ($attachments) {  ?>
    <?php            
        $imgid = 1;
        foreach ( $attachments as $img  ) {
        
        $img= wp_get_attachment_image_src($img, 'full');
        if (!empty($img[0])) {
            ?>
        <PHOTO<?php echo $imgid; ?>><?php
                        echo $img[0]; ?></PHOTO<?php echo $imgid; ?>>
        <?php $imgid++;
        }
        }
            ?>

    
    <?php
    } 
}

/** Custom Field to Exclude Properties */
/*
* Custom Meta Fields for properties
* Reference and Pool Field
*/

function sb_layout_of_custom_field()
{
    global $post;
    $exclude_from_feed = get_post_meta($post->ID, 'exclude_from_feed', true);
    $exclude_from_a_place_in_sun_feed = get_post_meta($post->ID, 'exclude_from_a_place_in_sun_feed', true);
    ?>
        <table class="form-table cmb_metabox" width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
                <td>
                    <label style="margin-bottom: 10px; display: block; font-weight: bold;"> <input name="exclude_from_feed" type="checkbox" id="exclude_from_feed" <?php echo $exclude_from_feed ? "checked='checked'" : ""; ?> /> Exclude this property from Feed</label>
                   
                </td>
            </tr>
        </table><?php
}

function sb_register_custom_field()
{
    add_meta_box("sb_layout_of_custom_field", "Zitouna Feed Fields", "sb_layout_of_custom_field", "property", "side", "low");
}

//add_action('add_meta_boxes', 'sb_register_custom_field');

//add_action('save_post', 'sb_save_custom_field');
function sb_save_custom_field()
{
	global $post;
    if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX)) return;
		if ( 'page' == isset($_POST['post_type']) ) { if ( !current_user_can( 'edit_page', $post_id ) ) return;
		} else { if ( !current_user_can( 'edit_post', $post_id ) ) return; }
		
	
        
        $included = isset($_POST["exclude_from_feed"])?true:false;
		
        update_post_meta($post->ID, "exclude_from_feed", $included);
        
}

function get_exclude_list() {
    $posts = get_posts(array(
        'post_type'         => 'property',
        'post_status'       => 'publish',
        'posts_per_page'    => '-1',
        'meta_query' => array(
            array(
                'key'     => 'exclude_from_feed',
                'value'   => true,
                
            ),
        ),
    ));

    $list_id = [];
    foreach($posts as $p) {
        $list_id[] = $p->ID;
    }
    return $list_id;
}


// add_filter('manage_property_posts_columns', 'sb_xml_feed_custom_columns');
// add_filter('manage_edit-property_columns', 'sb_xml_feed_custom_columns');

function sb_xml_feed_custom_columns($columns) {
    $columns['include_in_feed'] = "Include in Kyero Feed";
    $columns['exclude_from_a_place_in_sun_feed'] = "Include in A Place In The Sun Feed";
    return $columns;
}


// add_action( 'manage_property_posts_custom_column' , 'sb_xml_feed_custom_column', 10, 2 );
function sb_xml_feed_custom_column($column_name, $property_id) {
    if($column_name == "include_in_feed") {
        $exclude_from_feed = get_post_meta($property_id, 'exclude_from_feed', true);
        if($exclude_from_feed) {
            echo "<span style='color: green;'>Yes</span>";
        }
        else {
            echo "<span style='color: red;'>No</span>";
        }
    }
    if($column_name == "exclude_from_a_place_in_sun_feed") {
        $exclude_from_feed = get_post_meta($property_id, 'exclude_from_a_place_in_sun_feed', true);
        if($exclude_from_feed) {
            echo "<span style='color: green;'>Yes</span>";
        }
        else {
            echo "<span style='color: red;'>No</span>";
        }
    }
}


//Sortable Column
// add_filter('manage_edit-property_sortable_columns', function($columns) {
// 	$columns['include_in_feed'] = 'include_in_feed';
// 	$columns['exclude_from_a_place_in_sun_feed'] = 'exclude_from_a_place_in_sun_feed';
// 	return $columns;
// });

add_action('pre_get_posts', function($query) {
    if (!is_admin()) {
        return;
    }
 
    $orderby = $query->get('orderby');
    if ($orderby == 'include_in_feed') {
        $query->set('meta_key', 'exclude_from_feed');
        $query->set('orderby', 'meta_value_num');
    }
    if ($orderby == 'exclude_from_a_place_in_sun_feed') {
        $query->set('meta_key', 'exclude_from_a_place_in_sun_feed');
        $query->set('orderby', 'meta_value_num');
    }
});

// add_action('admin_init', 'set_property_meta');
function set_property_meta() {
    // $includedPosts = get_posts(array('post_type' => 'property', 'posts_per_page'    => '-1',  'meta_query' => array(
    //     array(
    //         'key'     => 'exclude_from_feed',
    //         'value'   => '1',
    //         'compare' => '!=',
    //     ),
    // ), ));

    // $excludedPosts = get_posts(array('post_type' => 'property', 'posts_per_page'    => '-1',  'meta_query' => array(
    //     array(
    //         'key'     => 'exclude_from_feed',
    //         'value'   => '1',
    //         'compare' => '=',
    //     ),
    // ), ));
    
    // foreach ($includedPosts as $p) {
    //     // $postIds[] = $p->ID;
    //     update_post_meta($p->ID, 'exclude_from_feed', true);
    // }

    // foreach ($excludedPosts as $p) {
    //     // $postIds[] = $p->ID;
    //     update_post_meta($p->ID, 'exclude_from_feed', false);
    // }

    $properties = get_posts(array('post_type' => 'property', 'posts_per_page' => -1));
    foreach($properties as $p) {
        update_post_meta($p->ID, 'exclude_from_a_place_in_sun_feed', false);
    }
}

