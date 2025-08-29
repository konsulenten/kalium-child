<?php
/**
 * Kalium WordPress Theme - Child setup
 *
 * @author Laborator
 * @link   https://kaliumtheme.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Direct access not allowed.
}

// === 1) Setup: språk + style.css ===
function kalium_child_after_setup_theme() {
    load_child_theme_textdomain( 'kalium-child', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'kalium_child_after_setup_theme' );

function kalium_child_wp_enqueue_scripts() {
    wp_enqueue_style( 'kalium-child', get_stylesheet_directory_uri() . '/style.css' );
        // Child theme JS
        wp_enqueue_script(
            'kalium-child-scripts',                                // Handle
            get_stylesheet_directory_uri() . '/js/scripts.js',        // File URL
            array(),                                               // Dependencies (add ['jquery'] if needed)
            null,                                                  // Version (null = no version query)
            true                                                   // Load in footer
        );
}
add_action( 'wp_enqueue_scripts', 'kalium_child_wp_enqueue_scripts', 110 );

// === 2) Arkitektur: move-alle-filter (om du bruker den) ===
add_action( 'wp_enqueue_scripts', function() {
    if ( is_page('arkitektur') ) {
        wp_enqueue_script(
            'move-alle-filter',
            get_stylesheet_directory_uri() . '/js/move-alle-filter.js',
            [],
            '1.0.0',
            true
        );
    }
});

function custom_enqueue_ansatt_portfolio_scripts() {
    if (is_post_type_archive('ansatt') || is_singular('ansatt')) {
        // Isotope core
        wp_enqueue_script(
            'isotope',
            get_template_directory_uri() . '/assets/vendors/metafizzy/isotope.pkgd.min.js',
            ['jquery'],
            '3.0.6',
            true
        );
        // ImagesLoaded library (helps with Isotope layout)
wp_enqueue_script(
    'imagesloaded',
    get_template_directory_uri() . '/assets/vendors/desandro/imagesloaded.pkgd.min.js',
    ['jquery'],
    '4.1.4',
    true
);
               

        // Din custom JS for ansatt grid
        wp_enqueue_script(
            'ansatt-portfolio',
            get_stylesheet_directory_uri() . '/js/ansatt-portfolio.js',
            ['jquery', 'isotope'],
            null,
            true
        );
        // AJAX til popup
        wp_localize_script('ansatt-portfolio', 'ansattAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'custom_enqueue_ansatt_portfolio_scripts');



// === 4) Ansatt popup AJAX ===
add_action('wp_ajax_load_ansatt_popup', 'load_ansatt_popup');
add_action('wp_ajax_nopriv_load_ansatt_popup', 'load_ansatt_popup');

function load_ansatt_popup() {
    $post_id = intval($_POST['post_id']);
    if (!$post_id) wp_send_json_error('No post ID');
    $post = get_post($post_id);
    if (!$post) wp_send_json_error('No post');

    ob_start();
    $stillingstittel = get_field('stillingstittel', $post_id);
    $rolle = get_field('rolle', $post_id);
    $telefon = get_field('telefon', $post_id);
    $epost = get_field('epost-adresse', $post_id);
    ?>
    <div class="ansatt-popup-content">
        <div class="ansatt-inner">
            <div class="ansatt-left">
                <div class="ansatt-image">
                    <?php echo get_the_post_thumbnail($post_id, 'large'); ?>
                </div>
            </div>
            <div class="ansatt-right">
                <div class="ansatt-name"><h3><?php echo esc_html(get_the_title($post_id)); ?></h3></div>
                <div class="ansatt-meta">
                    <?php if ($stillingstittel): ?><p class="role"><?php echo esc_html($stillingstittel); ?></p><?php endif; ?>
                    <?php if ($rolle): ?><p class="role"><?php echo esc_html($rolle); ?></p><?php endif; ?>
                    <?php if ($telefon): ?><p class="tel"><?php echo esc_html($telefon); ?></p><?php endif; ?>
                    <?php if ($epost): ?><p class="mail"><?php echo esc_html($epost); ?></p><?php endif; ?>
                </div>
                <div class="ansatt-divider-with-buttons">
                    <hr>
                    <div class="ansatt-buttons">
                        <?php if ($telefon): ?><a href="tel:<?php echo esc_attr($telefon); ?>">Ring</a><?php endif; ?>
                        <?php if ($epost): ?><a href="mailto:<?php echo esc_attr($epost); ?>">Send e-post</a><?php endif; ?>
                    </div>
                </div>
                <div class="ansatt-text">
                    <?php echo wpautop(apply_filters('the_content', $post->post_content)); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    wp_send_json_success(ob_get_clean());
}

// === 5) Elementor Portfolio utvidet ===
add_action('elementor/widgets/register', function($widgets_manager) {
    class New_Portfolio extends \ElementorPro\Modules\Posts\Widgets\Portfolio {
        protected function render_post_header() {
            global $post;
            $tags_classes = array_map(function($tag) {
                return 'elementor-filter-' . $tag->term_id;
            }, $post->tags);

            $classes = [
                'elementor-portfolio-item',
                'elementor-post',
                implode(' ', $tags_classes),
            ];

            echo '<article ' . get_post_class(implode(' ', $classes)) . '>';
        }

        protected function render_post_footer() {
            echo '</article>';
        }

        protected function render_post_link_header() {
            echo '<a class="new_portfolio_post_link" href="' . esc_url(get_permalink()) . '">';
        }

        protected function render_post_link_footer() {
            echo '</a>';
        }

        protected function render_post() {
            $this->render_post_header();
            $this->render_post_link_header();
            $this->render_thumbnail();
            $this->render_post_link_footer();

            $this->render_overlay_header();
            $this->render_post_link_header();
            echo '<h3 class="elementor-portfolio-item__title">' . get_the_title() . '</h3>';
            $this->render_post_link_footer();
            $this->render_overlay_footer();

            echo '<div class="ansatt-card-meta">';
            echo '<p class="stillingstittel">' . esc_html(get_field('stillingstittel')) . '</p>';
            echo '<p class="rolle">' . esc_html(get_field('rolle')) . '</p>';
            echo '<p class="telefon">' . esc_html(get_field('telefon')) . '</p>';
            echo '<p class="epost">' . esc_html(get_field('epost-adresse')) . '</p>';
            echo '</div>';

            echo '<p class="les-mer"><a href="#" onclick="return false;">Les mer <span aria-hidden="true">→</span></a></p>';

            $this->render_post_footer();
        }
    }

    $widgets_manager->register(new \New_Portfolio());
}, 250);


/**
 * Schema for meta-tags
 */

// === 7) Use Folk page for Ansatte Archive settings ===
define('ANSATT_SETTINGS_PAGE_ID', 8689); // Folk page ID

// Customize Archive Title from Folk page
function custom_ansatte_archive_title($title) {
    if (is_post_type_archive('ansatt')) {
        // First try ACF field
        $custom_title = get_field('ansatte_archive_title', ANSATT_SETTINGS_PAGE_ID);
        if ($custom_title) {
            return $custom_title;
        }
        // Fallback to page title
        return get_the_title(ANSATT_SETTINGS_PAGE_ID);
    }
    return $title;
}
add_filter('get_the_archive_title', 'custom_ansatte_archive_title');

// Browser tab title
function custom_ansatte_archive_document_title($title_parts) {
    if (is_post_type_archive('ansatt')) {
        // Check if Yoast is active and has a title
        if (function_exists('YoastSEO') && class_exists('WPSEO_Meta')) {
            $yoast_title = WPSEO_Meta::get_value('title', ANSATT_SETTINGS_PAGE_ID);
            if ($yoast_title) {
                $title_parts['title'] = $yoast_title;
                return $title_parts;
            }
        }
        
        // Otherwise use ACF or page title
        $custom_title = get_field('ansatte_archive_title', ANSATT_SETTINGS_PAGE_ID);
        $title_parts['title'] = $custom_title ?: get_the_title(ANSATT_SETTINGS_PAGE_ID);
    }
    return $title_parts;
}
add_filter('document_title_parts', 'custom_ansatte_archive_document_title', 20);

// Custom Meta Tags from Folk page
function custom_ansatte_archive_meta() {
    if (is_post_type_archive('ansatt')) {
        // Get featured image from Folk page
        $featured_image_id = get_post_thumbnail_id(ANSATT_SETTINGS_PAGE_ID);
        $featured_image_url = $featured_image_id ? wp_get_attachment_image_url($featured_image_id, 'large') : '';
        
        // Get description - try ACF first, then Yoast, then excerpt
        $description = get_field('ansatte_archive_description', ANSATT_SETTINGS_PAGE_ID);
        if (!$description && function_exists('YoastSEO') && class_exists('WPSEO_Meta')) {
            $description = WPSEO_Meta::get_value('metadesc', ANSATT_SETTINGS_PAGE_ID);
        }
        if (!$description) {
            $description = get_the_excerpt(ANSATT_SETTINGS_PAGE_ID);
        }
        
        // Get title for OG
        $og_title = get_field('ansatte_archive_title', ANSATT_SETTINGS_PAGE_ID) ?: get_the_title(ANSATT_SETTINGS_PAGE_ID);
        
        // Output meta tags
        if ($description) : ?>
            <meta name="description" content="<?php echo esc_attr($description); ?>">
        <?php endif; ?>
        
        <meta property="og:title" content="<?php echo esc_attr($og_title); ?>">
        <?php if ($description) : ?>
            <meta property="og:description" content="<?php echo esc_attr($description); ?>">
        <?php endif; ?>
        <?php if ($featured_image_url) : ?>
            <meta property="og:image" content="<?php echo esc_url($featured_image_url); ?>">
        <?php endif; ?>
        <meta property="og:type" content="website">
        <meta property="og:url" content="<?php echo esc_url(get_post_type_archive_link('ansatt')); ?>">
        
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo esc_attr($og_title); ?>">
        <?php if ($description) : ?>
            <meta name="twitter:description" content="<?php echo esc_attr($description); ?>">
        <?php endif; ?>
        <?php if ($featured_image_url) : ?>
            <meta name="twitter:image" content="<?php echo esc_url($featured_image_url); ?>">
        <?php endif;
    }
}
add_action('wp_head', 'custom_ansatte_archive_meta', 5);

// === 8) Yoast SEO Integration for Archive ===
if (defined('WPSEO_VERSION')) {
    
    // Override Yoast title for archive
    function custom_ansatte_archive_yoast_title($title) {
        if (is_post_type_archive('ansatt')) {
            // Try Yoast title from Folk page first
            $yoast_title = WPSEO_Meta::get_value('title', ANSATT_SETTINGS_PAGE_ID);
            if ($yoast_title) {
                return wpseo_replace_vars($yoast_title, get_post(ANSATT_SETTINGS_PAGE_ID));
            }
            // Fallback to ACF/page title
            $custom_title = get_field('ansatte_archive_title', ANSATT_SETTINGS_PAGE_ID);
            return $custom_title ?: get_the_title(ANSATT_SETTINGS_PAGE_ID);
        }
        return $title;
    }
    add_filter('wpseo_title', 'custom_ansatte_archive_yoast_title', 20);
    
    // Override Yoast description
    function custom_ansatte_archive_yoast_desc($description) {
        if (is_post_type_archive('ansatt')) {
            // Try Yoast description from Folk page first
            $yoast_desc = WPSEO_Meta::get_value('metadesc', ANSATT_SETTINGS_PAGE_ID);
            if ($yoast_desc) {
                return wpseo_replace_vars($yoast_desc, get_post(ANSATT_SETTINGS_PAGE_ID));
            }
            // Fallback to ACF field
            $custom_desc = get_field('ansatte_archive_description', ANSATT_SETTINGS_PAGE_ID);
            if ($custom_desc) {
                return $custom_desc;
            }
        }
        return $description;
    }
    add_filter('wpseo_metadesc', 'custom_ansatte_archive_yoast_desc', 20);
    
    // Override Yoast OG image
    function custom_ansatte_archive_yoast_image($image) {
        if (is_post_type_archive('ansatt')) {
            $featured_image_id = get_post_thumbnail_id(ANSATT_SETTINGS_PAGE_ID);
            if ($featured_image_id) {
                return wp_get_attachment_image_url($featured_image_id, 'large');
            }
        }
        return $image;
    }
    add_filter('wpseo_opengraph_image', 'custom_ansatte_archive_yoast_image', 20);
    add_filter('wpseo_twitter_image', 'custom_ansatte_archive_yoast_image', 20);
    
    // Override canonical URL to point to archive
    function custom_ansatte_archive_canonical($canonical) {
        if (is_post_type_archive('ansatt')) {
            return get_post_type_archive_link('ansatt');
        }
        return $canonical;
    }
    add_filter('wpseo_canonical', 'custom_ansatte_archive_canonical', 20);
    
    // Prevent Yoast from adding archive to sitemap if Folk page is in sitemap
    function custom_ansatte_archive_sitemap($exclude, $post_type) {
        if ($post_type === 'ansatt') {
            // You might want to exclude the archive from sitemap
            // since you have the Folk page representing it
            // return true; // Uncomment to exclude
        }
        return $exclude;
    }
    add_filter('wpseo_sitemap_exclude_post_type', 'custom_ansatte_archive_sitemap', 10, 2);
}

// === 9) Schema.org Structured Data ===
function custom_ansatte_archive_schema() {
    if (is_post_type_archive('ansatt')) {
        $title = get_field('ansatte_archive_title', ANSATT_SETTINGS_PAGE_ID) ?: get_the_title(ANSATT_SETTINGS_PAGE_ID);
        $description = get_field('ansatte_archive_description', ANSATT_SETTINGS_PAGE_ID);
        ?>
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "ItemList",
            "name": "<?php echo esc_js($title); ?>",
            <?php if ($description) : ?>
            "description": "<?php echo esc_js($description); ?>",
            <?php endif; ?>
            "url": "<?php echo esc_url(get_post_type_archive_link('ansatt')); ?>",
            "numberOfItems": <?php echo wp_count_posts('ansatt')->publish; ?>
        }
        </script>
        <?php
    }
}
add_action('wp_head', 'custom_ansatte_archive_schema');

// === 10) Helper function to check if we're using the Folk page settings ===
function ansatt_archive_has_custom_settings() {
    return get_post_status(ANSATT_SETTINGS_PAGE_ID) === 'private';
}

// === 11) FIXED: Redirect single ansatt posts to archive with parameter ===
function redirect_single_ansatt_to_archive() {
    if (!is_admin() && !wp_doing_ajax()) {
        
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Only match single post URLs like /ansatt/bendik-aursand/
        // NOT archive URLs like /ansatt/ or /ansatt/?param=value
        if (preg_match('#^/ansatt/([a-zA-Z0-9\-_]+)/?$#', $request_uri, $matches)) {
            $post_slug = $matches[1];
            
            error_log('Found post slug: ' . $post_slug);
            
            // Verify this ansatt post exists
            $post = get_page_by_path($post_slug, OBJECT, 'ansatt');
            if ($post) {
                error_log('Post exists, redirecting...');
                
                // Force redirect to archive page with parameter
                $archive_url = get_post_type_archive_link('ansatt');
                $redirect_url = add_query_arg('ansatt', $post_slug, $archive_url);
                
                wp_safe_redirect($redirect_url, 301);
                exit;
            } else {
                error_log('Post not found for slug: ' . $post_slug);
            }
        }
    }
}
add_action('template_redirect', 'redirect_single_ansatt_to_archive', 1);

// === FORCE ARCHIVE QUERY FOR ELEMENTOR ===
// Ensure that /ansatt/?ansatt=slug is treated as archive, not single post
function force_ansatt_archive_query($query) {
    if (!is_admin() && $query->is_main_query()) {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // If we're on /ansatt/?ansatt=something, force it to be treated as archive
        if (strpos($request_uri, '/ansatt/?ansatt=') !== false || 
            (is_post_type_archive('ansatt') && isset($_GET['ansatt']))) {
            
            error_log('Forcing archive query for: ' . $request_uri);
            
            // Force this to be an archive query, not singular
            $query->is_singular = false;
            $query->is_single = false;
            $query->is_page = false;
            $query->is_archive = true;
            $query->is_post_type_archive = true;
            
            // Set the correct post type
            $query->set('post_type', 'ansatt');
        }
    }
}
add_action('pre_get_posts', 'force_ansatt_archive_query', 1);

// === 12) Keep ansatt searchable ===
// The CPT is already set to be searchable based on your JSON config
// But we can ensure it stays in search results:
function ensure_ansatt_in_search($query) {
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        $post_types = get_post_types(['public' => true, 'exclude_from_search' => false]);
        // Make sure ansatt is included
        $post_types['ansatt'] = 'ansatt';
        $query->set('post_type', array_values($post_types));
    }
}
add_action('pre_get_posts', 'ensure_ansatt_in_search');

// === 13) UPDATED: Auto-open script with better parameter handling ===
function output_ansatt_auto_open_script() {
    if (is_post_type_archive('ansatt') && isset($_GET['ansatt'])) {
        $ansatt_slug = sanitize_text_field($_GET['ansatt']);
        
        // Verify the ansatt exists
        $ansatt_post = get_page_by_path($ansatt_slug, OBJECT, 'ansatt');
        if ($ansatt_post) {
            ?>
            <script>
            // Set auto-open variables
            window.ansattAutoOpen = '<?php echo esc_js($ansatt_slug); ?>';
            window.ansattAutoOpenId = <?php echo intval($ansatt_post->ID); ?>;
            
            // Clean URL after auto-open
            if (window.history && window.history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('ansatt');
                window.history.replaceState({}, document.title, url.pathname);
            }
            </script>
            <?php
        }
    }
}
add_action('wp_head', 'output_ansatt_auto_open_script', 999);

// === 14) Update the shortcode to include slug as data attribute ===
function ansatt_masonry_grid_shortcode_with_slug($atts) {
    ob_start();
    $atts = shortcode_atts(['posts_per_page' => 40], $atts, 'ansatt_masonry');

    $args = [
        'post_type' => 'ansatt',
        'posts_per_page' => $atts['posts_per_page'],
        'post_status' => 'publish',
        'orderby' => 'menu_order',
        'order' => 'ASC'
    ];
    $query = new WP_Query($args);
    $uniq_id = 'ansatt-items-' . uniqid();
    $terms = get_terms(['taxonomy' => 'avdeling', 'hide_empty' => true]);
    ?>
    <ul class="elementor-portfolio__filters">
        <li class="elementor-portfolio__filter elementor-active" tabindex="0" data-filter="__all">Alle</li>
        <?php foreach ($terms as $term): ?>
            <li class="elementor-portfolio__filter" tabindex="0" data-filter="<?php echo esc_attr($term->term_id); ?>">
                <?php echo esc_html($term->name); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="elementor-portfolio <?php echo esc_attr($uniq_id); ?> grid columns-1 columns-md-2 columns-xl-4 masonry-container" data-layout="masonry">
        <?php while($query->have_posts()): $query->the_post(); 
            $tax_classes = '';
            $departments = get_the_terms(get_the_ID(), 'avdeling');
            if ($departments && !is_wp_error($departments)) {
                foreach ($departments as $term) {
                    $tax_classes .= ' elementor-filter-' . $term->term_id . ' avdeling-' . esc_attr($term->slug);
                }
            }
            // Get post slug
            $post_slug = get_post_field('post_name', get_the_ID());
            ?>
            <article class="elementor-portfolio-item<?php echo $tax_classes; ?>" 
                     data-post-id="<?php the_ID(); ?>" 
                     data-post-slug="<?php echo esc_attr($post_slug); ?>">
                <a class="new_portfolio_post_link" style="pointer-events: none;">
                    <div class="elementor-portfolio-item__img elementor-post__thumbnail">
                        <?php the_post_thumbnail('medium_large', ['alt' => get_the_title()]); ?>
                    </div>
                </a>
                <div class="elementor-portfolio-item__overlay">
                    <a class="new_portfolio_post_link" style="pointer-events: none;">
                        <h3 class="elementor-portfolio-item__title"><?php the_title(); ?></h3>
                    </a>
                </div>
                <div class="ansatt-card-meta">
                    <?php if ($stilling = get_field('stillingstittel')) : ?><p class="stillingstittel"><?php echo esc_html($stilling); ?></p><?php endif; ?>
                    <?php if ($rolle = get_field('rolle')) : ?><p class="rolle"><?php echo esc_html($rolle); ?></p><?php endif; ?>
                    <?php if ($telefon = get_field('telefon')) : ?><p class="telefon"><?php echo esc_html($telefon); ?></p><?php endif; ?>
                    <?php if ($epost = get_field('epost-adresse')) : ?><p class="epost"><?php echo esc_html($epost); ?></p><?php endif; ?>
                </div>
                <p class="les-mer"><a onclick="return false;">Les mer <span aria-hidden="true">→</span></a></p>
            </article>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('ansatt_masonry', 'ansatt_masonry_grid_shortcode_with_slug');



// === 15) Add data attribute to help JavaScript find the right ansatt ===
function add_ansatt_slug_to_portfolio_item($classes, $class, $post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_type === 'ansatt') {
        $classes[] = 'ansatt-slug-' . $post->post_name;
    }
    return $classes;
}
add_filter('post_class', 'add_ansatt_slug_to_portfolio_item', 10, 3);


// === 17) Handle search result clicks ===
// Add a filter to modify search result permalinks for ansatt posts
function modify_ansatt_permalink_in_search($permalink, $post) {
    if ($post->post_type === 'ansatt' && is_search()) {
        // Keep the original permalink in search results
        // The redirect will handle it when clicked
    }
    return $permalink;
}
add_filter('post_type_link', 'modify_ansatt_permalink_in_search', 10, 2);

// === Redirect category URLs to archive with hash ===
function redirect_portfolio_category_to_archive() {
    if (!is_admin() && !wp_doing_ajax()) {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Match /kategori/[slug]/ pattern
        if (preg_match('#^/kategori/([a-zA-Z0-9\-_]+)/?$#', $request_uri, $matches)) {
            $category_slug = $matches[1];
            
            // Verify this is a valid portfolio category
            $term = get_term_by('slug', $category_slug, 'portfolio_category'); // Adjust taxonomy name
            if ($term) {
                // Redirect to archive page with hash
                $archive_url = home_url('/arkitektur/#' . $category_slug);
                wp_safe_redirect($archive_url, 301);
                exit;
            }
        }
    }
}
add_action('template_redirect', 'redirect_portfolio_category_to_archive', 1);

// === Set canonical URL for category pages ===
function set_category_canonical_url() {
    if (is_tax('portfolio_category')) { // Adjust taxonomy name
        $term = get_queried_object();
        if ($term) {
            $canonical_url = home_url('/arkitektur/#' . $term->slug);
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
            
            // Also set og:url for social sharing
            echo '<meta property="og:url" content="' . esc_url($canonical_url) . '" />' . "\n";
        }
    }
}
add_action('wp_head', 'set_category_canonical_url', 1);

/**
 * Portfolio Navigation Fix - Working with Hash-based Filtering
 * Replace the navigation-related functions in your functions.php with this code
 */

// === 1) Get primary category using Yoast ===
function get_portfolio_primary_category($post_id = null) {
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Check if Yoast is active and get primary category
    if (class_exists('WPSEO_Primary_Term')) {
        $primary_term = new WPSEO_Primary_Term('portfolio_category', $post_id);
        $primary_category_id = $primary_term->get_primary_term();
        
        if ($primary_category_id) {
            return get_term($primary_category_id, 'portfolio_category');
        }
    }
    
    // Fallback: Get the first category if no primary is set
    $categories = get_the_terms($post_id, 'portfolio_category');
    if ($categories && !is_wp_error($categories)) {
        return $categories[0];
    }
    
    return false;
}

// === 2) Capture the referrer hash via JavaScript ===
add_action('wp_footer', 'capture_portfolio_referrer_hash');
function capture_portfolio_referrer_hash() {
    if (is_singular('portfolio')) {
        ?>
        <script>
        (function() {
            // Get the hash from the referrer
            let filterCategory = '';
            if (document.referrer && document.referrer.includes('/arkitektur/')) {
                const referrerUrl = new URL(document.referrer);
                filterCategory = referrerUrl.hash.substring(1);
            }
            
            // Store in sessionStorage
            if (filterCategory) {
                sessionStorage.setItem('portfolio_filter_category', filterCategory);
            }
            
            // Pass to PHP via AJAX if needed
            window.portfolioFilterCategory = filterCategory || sessionStorage.getItem('portfolio_filter_category') || '';
        })();
        </script>
        <?php
    }
}

// === 3) Modify portfolio navigation dynamically ===
add_action('wp_footer', 'modify_portfolio_navigation_links');
function modify_portfolio_navigation_links() {
    if (is_singular('portfolio')) {
        global $post;
        $primary_category = get_portfolio_primary_category($post->ID);
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the current filter from sessionStorage or referrer
            const filterCategory = window.portfolioFilterCategory || '';
            
            // Find the back to archive link
            const archiveLink = document.querySelector('.back-to-archive');
            if (archiveLink) {
                let archiveUrl = '/arkitektur/';
                
                if (filterCategory) {
                    // User came from filtered view
                    archiveUrl += '#' + filterCategory;
                } else {
                    <?php if ($primary_category): ?>
                    // No filter but post has primary category
                    const categories = <?php 
                        $all_categories = get_the_terms($post->ID, 'portfolio_category');
                        echo json_encode(wp_list_pluck($all_categories, 'slug'));
                    ?>;
                    
                    if (categories.length > 1) {
                        // Multiple categories - use primary
                        archiveUrl += '#<?php echo esc_js($primary_category->slug); ?>';
                    }
                    <?php endif; ?>
                }
                
                // Update the link
                archiveLink.href = archiveUrl;
            }
        });
        </script>
        <?php
    }
}

// === 4) Fix the prev/next navigation query ===
add_filter('get_previous_post_where', 'filter_portfolio_nav_by_category', 10, 5);
add_filter('get_next_post_where', 'filter_portfolio_nav_by_category', 10, 5);

function filter_portfolio_nav_by_category($where, $in_same_term, $excluded_terms, $taxonomy, $post) {
    if ($post->post_type !== 'portfolio') {
        return $where;
    }
    
    global $wpdb;
    
    // Check if we should filter by category (via JavaScript set cookie)
    $filter_category = $_COOKIE['portfolio_nav_category'] ?? '';
    
    if ($filter_category) {
        // User navigated from a filtered view
        $term = get_term_by('slug', $filter_category, 'portfolio_category');
    } else {
        // Use primary category if post has multiple categories
        $categories = get_the_terms($post->ID, 'portfolio_category');
        if ($categories && count($categories) > 1) {
            $primary_category = get_portfolio_primary_category($post->ID);
            $term = $primary_category;
        } elseif ($categories && count($categories) === 1) {
            // Single category - use it
            $term = $categories[0];
        } else {
            // No categories - show all
            return $where;
        }
    }
    
    if ($term) {
        $where .= $wpdb->prepare(" AND p.ID IN (
            SELECT object_id 
            FROM {$wpdb->term_relationships} tr
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.term_id = %d AND tt.taxonomy = %s
        )", $term->term_id, 'portfolio_category');
    }
    
    return $where;
}

// === 5) Set navigation category cookie ===
add_action('wp_footer', 'set_portfolio_nav_category_cookie');
function set_portfolio_nav_category_cookie() {
    if (is_singular('portfolio')) {
        ?>
        <script>
        (function() {
            const filterCategory = window.portfolioFilterCategory || '';
            
            // Set cookie for PHP to read
            if (filterCategory) {
                document.cookie = 'portfolio_nav_category=' + filterCategory + ';path=/;max-age=3600';
            } else {
                // Check for primary category scenario
                const archiveLink = document.querySelector('.back-to-archive');
                if (archiveLink && archiveLink.href.includes('#')) {
                    const hash = archiveLink.href.split('#')[1];
                    if (hash) {
                        document.cookie = 'portfolio_nav_category=' + hash + ';path=/;max-age=3600';
                    }
                } else {
                    // Clear the cookie
                    document.cookie = 'portfolio_nav_category=;path=/;max-age=0';
                }
            }
        })();
        </script>
        <?php
    }
}

// === 6) Clean up archive page behavior ===
add_action('wp_footer', 'fix_portfolio_archive_behavior');
function fix_portfolio_archive_behavior() {
    if (is_post_type_archive('portfolio')) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clear navigation category when on archive
            sessionStorage.removeItem('portfolio_filter_category');
            document.cookie = 'portfolio_nav_category=;path=/;max-age=0';
            
            // Store hash when clicking portfolio items
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[href*="/portfolio/"]');
                if (link) {
                    const currentHash = window.location.hash.substring(1);
                    if (currentHash) {
                        sessionStorage.setItem('portfolio_filter_category', currentHash);
                    }
                }
            });
        });
        </script>
        <?php
    }
}

// === 7) REMOVE the default filter that's causing redirects ===
add_filter('kalium_portfolio_loop_options', 'remove_default_portfolio_filter', 100);
function remove_default_portfolio_filter($options) {
    if (is_post_type_archive('portfolio') && !isset($_GET['filter'])) {
        // Remove the default "utvalgte" filter unless explicitly requested
        if (isset($options['filtering']['current']['portfolio_category'])) {
            $hash = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER'], PHP_URL_FRAGMENT) : '';
            if (!$hash) {
                unset($options['filtering']['current']['portfolio_category']);
            }
        }
    }
    return $options;
}

/**
 * Primary term helper (Yoast → first term → null)
 */
function hrtb_get_primary_portfolio_category( $post_id = 0 ) {
    $post_id  = $post_id ?: get_the_ID();
    $taxonomy = 'portfolio_category';

    if ( class_exists( 'WPSEO_Primary_Term' ) ) {
        $primary = new WPSEO_Primary_Term( $taxonomy, $post_id );
        $term_id = $primary->get_primary_term();
        if ( $term_id && ! is_wp_error( $term_id ) ) {
            $term = get_term( (int) $term_id, $taxonomy );
            if ( $term && ! is_wp_error( $term ) ) {
                return $term;
            }
        }
    }

    $terms = get_the_terms( $post_id, $taxonomy );
    return ( is_array( $terms ) && ! empty( $terms ) ) ? array_shift( $terms ) : null;
}

/**
 * Persist/read the last archive filter (from cookie) to build back link.
 * If user came from a filtered archive (/arkitektur/#plan), return that.
 * Else fall back to primary category hash, else plain /arkitektur/.
 */
function hrtb_portfolio_back_link_url( $post_id = 0 ) {
    $base = home_url( '/arkitektur/' );

    // 1) last used filter from archive (set by JS below)
    $slug = isset( $_COOKIE['hrtb_portfolio_hash'] ) ? sanitize_title( $_COOKIE['hrtb_portfolio_hash'] ) : '';

    if ( $slug ) {
        return trailingslashit( $base ) . '#' . $slug;
    }

    // 2) Yoast primary (or first) term
    $term = hrtb_get_primary_portfolio_category( $post_id );
    if ( $term ) {
        return trailingslashit( $base ) . '#' . $term->slug;
    }

    // 3) default
    return $base;
}
/**
 * Adjacent navigation within selected archive filter term (hash/cookie).
 * - If user came from /arkitektur/#<slug>, constrain prev/next to that <slug>.
 * - If no filter in cookie, traverse all items (default behaviour).
 */
function hrtb_get_adjacent_portfolio( $previous = true ) {
	$taxonomy    = 'portfolio_category';
	$current_id  = get_the_ID();
	$filter_slug = hrtb_get_archive_filter_slug(); // from earlier helper

	// No filter context → traverse all (keep your old behaviour)
	if ( ! $filter_slug ) {
		return get_adjacent_post( false, '', $previous, $taxonomy );
	}

	// Constrain strictly to the selected term (not "any shared term")
	$term = get_term_by( 'slug', $filter_slug, $taxonomy );
	if ( ! $term || is_wp_error( $term ) ) {
		return get_adjacent_post( false, '', $previous, $taxonomy );
	}

	// Build ordered list of posts in that exact term.
	// Order: menu_order ASC, then date DESC (mirrors common portfolio grids).
	$q = new WP_Query( array(
		'post_type'           => 'portfolio',
		'post_status'         => 'publish',
		'posts_per_page'      => -1,
		'fields'              => 'ids',
		'orderby'             => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'tax_query'           => array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => (int) $term->term_id,
			),
		),
	) );

	if ( empty( $q->posts ) ) {
		return null;
	}

	$list = $q->posts; // array of IDs in display order
	$idx  = array_search( $current_id, $list, true );

	if ( $idx === false ) {
		// If current post isn't in the filtered list, fall back to default.
		return get_adjacent_post( false, '', $previous, $taxonomy );
	}

	$target_idx = $previous ? $idx - 1 : $idx + 1;

	if ( $target_idx < 0 || $target_idx >= count( $list ) ) {
		return null; // no prev/next in this term
	}

	return get_post( $list[ $target_idx ] );
}