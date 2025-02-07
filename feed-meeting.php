<?php
header('Content-Type: application/rss+xml; charset=' . get_option('blog_charset'), true);
echo '<?xml version="1.0" encoding="' . get_option('blog_charset') . '"?' . '>';
?>
<rss version="2.0">
    <channel>
        <title><?php bloginfo_rss('name'); ?> - Meetings</title>
        <link><?php echo esc_url(home_url('/proudcity-meetings/')); ?></link>
        <description><?php bloginfo_rss('description'); ?></description>
        <language><?php bloginfo_rss('language'); ?></language>

        <?php
        $args = array(
            'post_type'      => 'meeting',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'meta_key' => 'datetime',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'meta_type' => 'DATETIME',
            'meta_query' => array(
                array(
                    'key' => 'datetime',
                    'value' => date('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATETIME'
                )
            ),
        );

$meetings = new WP_Query($args);

while ($meetings->have_posts()) : $meetings->the_post();
    ?>
            <item>
                <title><?php the_title_rss(); ?></title>
                <link><?php the_permalink_rss(); ?></link>
                <guid><?php the_guid(); ?></guid>
                <pubDate><?php echo get_the_date(DATE_RSS); ?></pubDate>
                <description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
            </item>
        <?php endwhile;
wp_reset_postdata(); ?>
    </channel>
</rss>
