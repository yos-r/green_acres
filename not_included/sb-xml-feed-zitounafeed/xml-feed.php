<?php
defined('ABSPATH') or die('Access Denied.');
global $wp_query;
$property_id = 'all';
if (intval($wp_query->query_vars['xml_feed_property_id'])) {
    $property_id = (int) $wp_query->query_vars['xml_feed_property_id'];
}
header('Content-Type: ' . feed_content_type('rss-http') . '; charset=' . get_option('blog_charset'), true);
$more = 1;
$numposts = '15';

remove_filter('the_content', 'wptexturize');
remove_filter('the_excerpt', 'wptexturize');
echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?' . '>';?>
<Adverts>
    <?php
if ($property_id == 'all'):
    query_posts(array(
        'post_type' => 'property',
        'post_status' => 'publish',
        'posts_per_page' => '-1',
        'tax_query' => array(
            array(
                'taxonomy' => 'property_status',
                'field'    => 'slug',
                'terms'    => array('a-louer', 'a-vendre'),
                'operator' => 'IN',
            ),
        ),
        // 'meta_query' => array(
        //     array(
        //         'key'     => 'exclude_from_feed',
        //         'value'   => '1',
        //         'compare' => '!=',
        //     ),
        // ),
    ));
else:
    query_posts(array(
        'post_type' => 'property',
        'post_status' => 'publish',
        'posts_per_page' => '1',
        'post__in' => array($property_id),
        'tax_query' => array(
            array(
                'taxonomy' => 'property_status',
                'field'    => 'slug',
                'terms'    => array('a-louer', 'a-vendre'),
                'operator' => 'IN',
            ),
        ),
        // 'meta_query' => array(
        //     array(
        //         'key'     => 'exclude_from_feed',
        //         'value'   => '1',
        //         'compare' => '!=',
        //     ),
        // ),
    ));
endif;

?>
    <?php while (have_posts()): the_post(); ?>

	    <Advert>
            <REFERENCE><?php echo sb_get_detail('fave_property_id'); ?></REFERENCE>
            <TITLE><?php the_title() ?></TITLE>
            <?php sb_get_details('property_type', 'AD_TYPE',true); ?> 
            <?php sb_get_details('property_status', 'TRANSACTION',true); ?> 
            <?php sb_get_detail('fave_property_map_address', 'PERIODE_LOCATION'); ?> 
            <?php sb_get_details('property_city', 'CITY',true); ?> 
            <?php sb_get_details('property_state', 'DISTRICT',true); ?> 
            <?php sb_get_detail('fave_property_price', 'PRICE'); ?> 
            <?php sb_get_detail('fave_property_size', 'SURFACE'); ?> 
            <?php $agent_agency = sb_get_detail('fave_agent_display_option'); 
            
            if($agent_agency == "agency_info") {
                $agency = get_the_title(sb_get_detail('fave_property_agency'));
                echo "<AGENCY>{$agency}</AGENCY>";
            }
            else if($agent_agency == "agent_info")
             {
                $agent = get_the_title(sb_get_detail('fave_agents'));
                echo "<AGENT>{$agent}</AGENT>";
             }
            $rooms = sb_get_detail('fave_property_bedrooms'); ?> 
            <NBRE_ROOMS><?php echo $rooms ?></NBRE_ROOMS>
            <NBRE_PIECES><?php echo (intval($rooms) + 1) ?></NBRE_PIECES>
            <?php sb_get_detail('fave_property_bathrooms', 'NBRE_BATHS'); ?> 
            <?php $floors = sb_get_detail('floor_plans'); ?>
            <NBRE_FLOORS><?php echo is_array($floors) ? count($floors) : 1 ?></NBRE_FLOORS>
            <Currency>TND</Currency>
            <AD_FLOOR><?php echo is_array($floors) ? count($floors) : 1 ?></AD_FLOOR>
            <FURNISHED><?php echo sb_check_exist('property_feature', ['Furnished', 'Meublé']) ? "YES" : "NO" ?></FURNISHED>
            <GARAGE><?php echo sb_check_exist('property_feature', ['Garage']) ? "YES" : "NO" ?></GARAGE>
            <TERRACE><?php echo sb_check_exist('property_feature', 'TERRACE') ? "YES" : "NO" ?></TERRACE>
            <POOL><?php echo sb_check_exist('property_feature', ['POOL', 'Piscine', 'Swimming']) ? "YES" : "NO" ?></POOL>
            <SECURITY><?php echo sb_check_exist('property_feature', ['CCTV', 'Surveillance', 'Security', 'Gardien']) ? "YES" : "NO" ?></SECURITY>
            <CONSERVATION></CONSERVATION>
            <FULLKITCHEN><?php echo sb_check_exist('property_feature', ['Kitchen', 'Cuisine équipée']) ? "YES" : "NO" ?></FULLKITCHEN>
            <FRIDGE><?php echo sb_check_exist('property_feature', ['Fridge', 'Refrigerator', 'Frigo']) ? "YES" : "NO" ?></FRIDGE>
            <MICROWAVE><?php echo sb_check_exist('property_feature', ['Microwave', 'Micro-onde']) ? "YES" : "NO" ?></MICROWAVE>
            <OVEN><?php echo sb_check_exist('property_feature', ['Oven']) ? "YES" : "NO" ?></OVEN>
            <WASHER><?php echo sb_check_exist('property_feature', ['Washer', 'Machine à laver']) ? "YES" : "NO" ?></WASHER>
            <FIREPLACE><?php echo sb_check_exist('property_feature', ['Fireplace', 'Cheminée']) ? "YES" : "NO" ?></FIREPLACE>
            <REINFORCEDDOOR><?php echo sb_check_exist('property_feature', ['Secure']) ? "YES" : "NO" ?></REINFORCEDDOOR>
            <SATELLITE><?php echo sb_check_exist('property_feature', ['SATELLITE', 'Parabole']) ? "YES" : "NO" ?></SATELLITE>
            <INTERNET><?php echo sb_check_exist('property_feature', ['Internet', 'Wifi', 'Internet']) ? "YES" : "NO" ?></INTERNET>
            <TV><?php echo sb_check_exist('property_feature', ['TV', 'Projector']) ? "YES" : "NO" ?></TV>
            <HEATING><?php echo sb_check_exist('property_feature', ['Heat', 'Chauffage central']) ? "YES" : "NO" ?></HEATING>
            <STORAGEROOM><?php echo sb_check_exist('property_feature', ['Storage', 'Dressing']) ? "YES" : "NO" ?></STORAGEROOM>
            <DOORMAN></DOORMAN>
            <SEAVIEWS><?php echo sb_check_exist('property_feature', ['sea', 'Vue Mer']) ? "YES" : "NO" ?></SEAVIEWS>
            <AIR><?php echo sb_check_exist('property_feature', ['Air conditioner', 'Aircon', 'AC', 'Climatisation']) ? "YES" : "NO" ?></AIR>
            <GARDEN><?php echo sb_check_exist('property_feature', ['Garden', 'Open Area', 'Jardin']) ? "YES" : "NO" ?></GARDEN>
            <ELEVATOR><?php echo sb_check_exist('property_feature', ['Elevator', 'Ascenseur']) ? "YES" : "NO" ?></ELEVATOR>
            <DOUBLEGLAZING><?php echo sb_check_exist('property_feature', ['Doubleglazing', 'Double Vitrage']) ? "YES" : "NO" ?></DOUBLEGLAZING>
            <MOUNTAINSVIEWS><?php echo sb_check_exist('property_feature', ['Mountain', 'Vue Montagne']) ? "YES" : "NO" ?></MOUNTAINSVIEWS>
            <EUROPEANLOUNGE></EUROPEANLOUNGE>
            <MOROCCANLOUNGE></MOROCCANLOUNGE>
            <AGE><?php $yearBuilt = sb_get_detail('fave_property_year', '', true); $currentYear = date('Y', time()); echo $currentYear - $yearBuilt; ?></AGE>
            <?php $location = sb_get_detail('fave_property_location');  $location = explode(',', $location) ?>
            <LATITUDE><?php echo $location[0]; ?></LATITUDE>
            <LONGITUDE><?php echo $location[1]; ?></LONGITUDE>
            <?php sb_get_attach_images(100); ?>
            <DESCRIPTION><?php sb_property_description(); ?></DESCRIPTION>

	    </Advert>
	<?php endwhile;
wp_reset_query();?>
</Adverts>
