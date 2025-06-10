<?php
defined('ABSPATH') or die('Access Denied.');

// Get the excluded properties list
$excluded_properties = ga_get_exclude_list();

global $wp_query;
$property_id = 'all';
if (isset($wp_query->query_vars['xml_feed_property_id']) && intval($wp_query->query_vars['xml_feed_property_id'])) {
    $property_id = (int) $wp_query->query_vars['xml_feed_property_id'];
}

header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

// Set up the query
$query_args = array(
    'post_type' => 'property',
    'post_status' => 'publish',
    'posts_per_page' => -1,
    'post__not_in' => $excluded_properties,
);

if ($property_id !== 'all') {
    $query_args['p'] = $property_id;
}

$properties_query = new WP_Query($query_args);

// Remove filters that add HTML entities
remove_filter('the_content', 'wptexturize');
remove_filter('the_excerpt', 'wptexturize');
remove_filter('the_title', 'wptexturize');

// Helper function to clean text for XML
function clean_for_xml($text) {
    // First decode HTML entities
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // Remove any remaining HTML tags
    $text = strip_tags($text);
    // Then escape for XML (this handles &, <, >, ", ')
    return esc_xml($text);
}

echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>

<Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Body>
        <add_adverts>
            <?php while ($properties_query->have_posts()): $properties_query->the_post(); ?>
                <advert>
                    <account_id>3315306a</account_id>
                    <reference>3315306a-<?php echo esc_html(sb_ga_get_detail('fave_property_id')); ?></reference>

                    <account>
                        <?php 
                        $agency_id = sb_ga_get_detail('fave_property_agency');
                        $agency_name = '';
                        if (!empty($agency_id)) {
                            $agency_title = get_the_title($agency_id);
                            $agency_name = !empty($agency_title) ? $agency_title : 'Century 21 Tunisie';
                        } else {
                            $agency_name = 'Century 21 Tunisie';
                        }
                        ?>
                        <agency_name><?php echo clean_for_xml($agency_name); ?></agency_name>
                    </account>

                    <?php
                    $status_terms = sb_ga_get_details('property_status');
                    $status = '';
                    if (is_array($status_terms) && !empty($status_terms)) {
                        $status = implode(' ', $status_terms);
                    } elseif (!empty($status_terms)) {
                        $status = $status_terms;
                    }
                    $advert_type = (stripos($status, 'Location') !== false) ? 'rentals' : 'properties';
                    ?>
                    <advert_type><?php echo $advert_type; ?></advert_type>
                    <status>available</status>
                    
                    <?php 
                    $price = sb_ga_get_detail('fave_property_price');
                    $price_value = !empty($price) ? intval($price) : 0;
                    ?>
                    <price><?php echo $price_value; ?></price>
                    <currency>TND</currency>
                    <postal_code></postal_code>
                    
                    <city><?php 
                    $city_terms = sb_ga_get_details('property_city');
                    $city_name = '';
                    if (is_array($city_terms) && !empty($city_terms)) {
                        $city_name = implode(', ', $city_terms);
                    } elseif (!empty($city_terms)) {
                        $city_name = $city_terms;
                    }
                    echo clean_for_xml($city_name);
                    ?></city>
                    <country_code>TN</country_code>

                    <?php
                    $type_terms = sb_ga_get_details('property_type');
                    $type = '';
                    if (is_array($type_terms) && !empty($type_terms)) {
                        $type = trim(strtolower($type_terms[0]));
                    } elseif (!empty($type_terms)) {
                        $type = trim(strtolower($type_terms));
                    }
                    
                    $mapping = array(
                        'bureau' => 'business',
                        'local commercial' => 'business',
                        'fond de commerce' => 'business',
                        'depot' => 'business',
                        'appartement' => 'appartement',
                        'flat' => 'appartement',
                        'studio' => 'appartement',
                        'villa' => 'house',
                        'étage de villa' => 'house',
                        'rdc de villa' => 'house',
                        'immeuble' => 'building',
                        'duplex' => 'house',
                        'triplex' => 'house',
                        'terrain' => 'land',
                        'autre' => 'other',
                        'other' => 'other',
                    );

                    $property_type = isset($mapping[$type]) ? $mapping[$type] : 'other';
                    ?>
                    <property_type><?php echo $property_type; ?></property_type>

                    <structure>0</structure>
                    <mandate_number><?php echo esc_html(sb_ga_get_detail('fave_property_id')); ?></mandate_number>
                    <is_exclusive>1</is_exclusive>
                    <agency_rates><?php echo (stripos($status, 'Location') !== false) ? '100' : '3'; ?></agency_rates>
                    <agency_rates_type>2</agency_rates_type>
                    <has_included_fees>0</has_included_fees>

                    <fees>
                        <?php 
                        echo (stripos($status, 'Location') !== false) ? $price_value : ($price_value * 0.02);
                        ?>
                    </fees>

                    <?php 
                    $location = sb_ga_get_detail('fave_property_location');
                    $latitude = '';
                    $longitude = '';
                    if (!empty($location)) {
                        $location_coords = explode(',', $location);
                        $latitude = isset($location_coords[0]) ? trim($location_coords[0]) : '';
                        $longitude = isset($location_coords[1]) ? trim($location_coords[1]) : '';
                    }
                    ?>
                    <longitude><?php echo esc_html($longitude); ?></longitude>
                    <latitude><?php echo esc_html($latitude); ?></latitude>

                    <?php 
                    $area_terms = sb_ga_get_details('property_area');
                    if (!empty($area_terms)) {
                        $neighborhood = is_array($area_terms) ? $area_terms[0] : $area_terms;
                        echo "<neighbourhood>" . clean_for_xml($neighborhood) . "</neighbourhood>";
                    }
                    ?>
                    
                    <title_fr><?php echo clean_for_xml(get_the_title()); ?></title_fr>
                    
                    <summary_fr>
                        <?php 
                        ob_start();
                        sb_ga_property_description();
                        $description = ob_get_clean();
                        echo clean_for_xml($description);
                        ?>
                    </summary_fr>

                    <?php 
                    $size = sb_ga_get_detail('fave_property_size');
                    if (!empty($size)) {
                        echo "<habitable_surface>" . intval($size) . "</habitable_surface>";
                    }
                    ?>
                    
                    <?php 
                    $bedrooms = sb_ga_get_detail('fave_property_bedrooms');
                    $bedrooms_count = !empty($bedrooms) ? intval($bedrooms) : 0;
                    $rooms_count = $bedrooms_count + 1; // Adding 1 for living room
                    ?>
                    <rooms_number><?php echo $rooms_count; ?></rooms_number>
                    <bedrooms_number><?php echo $bedrooms_count; ?></bedrooms_number>
                    
                    <?php 
                    $bathrooms = sb_ga_get_detail('fave_property_bathrooms');
                    $bathrooms_count = !empty($bathrooms) ? intval($bathrooms) : 0;
                    ?>
                    <baths_number><?php echo $bathrooms_count; ?></baths_number>
                    
                    <?php 
                    $floors = sb_ga_get_detail('floor_plans');
                    $floor_count = 1; // Default to 1 floor
                    if (is_array($floors) && !empty($floors)) {
                        $floor_count = count($floors);
                    } elseif (!empty($floors)) {
                        $floor_count = 1;
                    }
                    ?>
                    <floor><?php echo $floor_count; ?></floor>
                    <floor_total><?php echo $floor_count; ?></floor_total>

                    <has_pool><?php echo sb_ga_check_exist('property_feature', array('Piscine')) ? "1" : "0"; ?></has_pool>
                    <has_terrace><?php echo sb_ga_check_exist('property_feature', array('Terrasse')) ? "1" : "0"; ?></has_terrace>
                    <has_elevator><?php echo sb_ga_check_exist('property_feature', array('Ascenseur')) ? "1" : "0"; ?></has_elevator>
                    <has_air_conditioning><?php echo sb_ga_check_exist('property_feature', array('Climatisation')) ? "1" : "0"; ?></has_air_conditioning>
                    <has_alarm><?php echo sb_ga_check_exist('property_feature', array('Alarme')) ? "1" : "0"; ?></has_alarm>
                    <kitchen_type><?php echo sb_ga_check_exist('property_feature', array('Cuisine Equipée')) ? "8" : "0"; ?></kitchen_type>

                    <precise_location>0</precise_location>
                    
                    <latitude_circle_center><?php echo esc_html($latitude); ?></latitude_circle_center>
                    <longitude_circle_center><?php echo esc_html($longitude); ?></longitude_circle_center>
                    <circle_radius>100</circle_radius>
                    
                    <publications>
                        <greenacres>1</greenacres>
                        <vizzit>1</vizzit>
                    </publications>

                    <?php sb_ga_get_attach_images(30); ?>

                </advert>
            <?php endwhile; ?>
        </add_adverts>
    </Body>
</Envelope>
<?php wp_reset_postdata(); ?>