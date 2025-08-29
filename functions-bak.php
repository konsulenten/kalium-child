<?php
/**
 * Kalium WordPress Theme
 *
 * @author Laborator
 * @link   https://kaliumtheme.com
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Direct access not allowed.
}

/**
 * After theme setup hooks.
 */
function kalium_child_after_setup_theme() {

	// Load translations for child theme
	load_child_theme_textdomain( 'kalium-child', get_stylesheet_directory() . '/languages' );
}

add_action( 'after_setup_theme', 'kalium_child_after_setup_theme' );

/**
 * This will enqueue style.css of child theme.
 */
function kalium_child_wp_enqueue_scripts() {

	// Remove if you are not going to use style.css
	wp_enqueue_style( 'kalium-child', get_stylesheet_directory_uri() . '/style.css' );
}

add_action( 'wp_enqueue_scripts', 'kalium_child_wp_enqueue_scripts', 110 );



add_action( 'wp_enqueue_scripts', function() {
    // Sjekk at vi er på /arkitektur/-siden
    if ( is_page('arkitektur') ) {
        wp_enqueue_script(
            'move-alle-filter',
            get_stylesheet_directory_uri() . '/js/move-alle-filter.js',
            [],
            '1.0.0',
            true // last i footer
        );
    }
});


//extends default portfolio widget
add_action( 'elementor/widgets/register', function( $widgets_manager ) {
	class New_Portfolio extends \ElementorPro\Modules\Posts\Widgets\Portfolio {
		
		protected function render_post_header() {
			global $post;
	
			$tags_classes = array_map( function( $tag ) {
				return 'elementor-filter-' . $tag->term_id;
			}, $post->tags );
	
			$classes = [
				'elementor-portfolio-item',
				'elementor-post',
				implode( ' ', $tags_classes ),
			];
	
			?>
			<article <?php post_class( $classes ); ?>>
			<?php
		}

		protected function render_post_footer() {
			?>
			</article>
			<?php
		}

		protected function render_post_link_header() { ?>
            <a class="new_portfolio_post_link" href="<?php echo esc_url( get_permalink() ); ?>">
            <?php 
        }

        protected function render_post_link_footer() { ?>
                </a>
            <?php
        }

protected function render_post() {
	$this->render_post_header();

	// Link wrapper (click is handled by JS)
	$this->render_post_link_header();
	$this->render_thumbnail();
	$this->render_post_link_footer();

	$this->render_overlay_header();
	$this->render_post_link_header();

	// Title
	echo '<h3 class="elementor-portfolio-item__title">' . get_the_title() . '</h3>';

	$this->render_post_link_footer();
	$this->render_overlay_footer();

	// Custom fields below image
	echo '<div class="ansatt-card-meta">';
	echo '<p class="stillingstittel">' . esc_html(get_field('stillingstittel')) . '</p>';
	echo '<p class="rolle">' . esc_html(get_field('rolle')) . '</p>';
	echo '<p class="telefon">' . esc_html(get_field('telefon')) . '</p>';
	echo '<p class="epost">' . esc_html(get_field('epost-adresse')) . '</p>';
	echo '</div>';

	// Les mer → link
	echo '<p class="les-mer"><a href="#" onclick="return false;">Les mer <span aria-hidden="true">→</span></a></p>';

	$this->render_post_footer();
}
	}

	$widgets_manager->register( new \New_Portfolio() );
}, 250 );



function ansatt_portfolio_scripts() {
    if (is_post_type_archive('ansatt') || is_singular('ansatt')) {
        wp_enqueue_script('ansatt-portfolio', get_stylesheet_directory_uri() . '/js/ansatt-portfolio.js', ['jquery'], null, true);

        wp_localize_script('ansatt-portfolio', 'ansattAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'ansatt_portfolio_scripts');

add_action('wp_ajax_load_ansatt_popup', 'load_ansatt_popup');
add_action('wp_ajax_nopriv_load_ansatt_popup', 'load_ansatt_popup');

function load_ansatt_popup() {
    $post_id = intval($_POST['post_id']);
    if (!$post_id) wp_send_json_error('No post ID');
    $post = get_post($post_id);
    if (!$post) wp_send_json_error('No post');
    ob_start();

    // FETCH ACF fields!
    $stillingstittel = get_field('stillingstittel', $post_id);
    $rolle          = get_field('rolle', $post_id);
    $telefon        = get_field('telefon', $post_id);
    $epost          = get_field('epost-adresse', $post_id);
    ?>
    <div class="ansatt-popup-content">
        <div class="ansatt-inner">
            <!-- Bilde (venstre side) -->
            <div class="ansatt-left">
                <div class="ansatt-image">
                    <?php echo get_the_post_thumbnail($post_id, 'large'); ?>
                </div>
            </div>

            <!-- Informasjon (høyre side) -->
            <div class="ansatt-right">
                <div class="ansatt-name">
                    <h3><?php echo esc_html(get_the_title($post_id)); ?></h3>
                </div>

                <div class="ansatt-meta">
                    <?php if ($stillingstittel): ?><p class="role"><?php echo esc_html($stillingstittel); ?></p><?php endif; ?>
                    <?php if ($rolle): ?><p class="role"><?php echo esc_html($rolle); ?></p><?php endif; ?>
                    <?php if ($telefon): ?><p class="tel"><?php echo esc_html($telefon); ?></p><?php endif; ?>
                    <?php if ($epost): ?><p class="mail"><?php echo esc_html($epost); ?></p><?php endif; ?>
                </div>

                <div class="ansatt-divider-with-buttons">
                    <hr>
                    <div class="ansatt-buttons">
                        <?php if ($telefon): ?>
                            <a href="tel:<?php echo esc_attr($telefon); ?>">Ring</a>
                        <?php endif; ?>
                        <?php if ($epost): ?>
                            <a href="mailto:<?php echo esc_attr($epost); ?>">Send e-post</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ansatt-text">
                    <?php echo wpautop(apply_filters('the_content', $post->post_content)); ?>
                </div>
            </div>
        </div>
    </div>
    <?php

    wp_reset_postdata();
    wp_send_json_success(ob_get_clean());
}

add_action('wp_footer', function () {
    echo '<!-- Popups Loaded: ';
    if ( class_exists('\ElementorPro\Modules\Popup\Module') ) {
        echo 'YES -->';
        \ElementorPro\Modules\Popup\Module::instance()->print_popups();
    } else {
        echo 'NO -->';
    }
});


function dynamic_portfolio_tag_query($atts) {
    if (!is_tax('portfolio_tag')) {
        return 'This shortcode should be used on a portfolio tag archive page.';
    }

    $term = get_queried_object();
    if (!$term || !isset($term->term_id)) {
        return 'No tag found.';
    }

    $args = array(
        'post_type' => 'portfolio',
        'posts_per_page' => 50, // Adjust as needed
        'tax_query' => array(
            array(
                'taxonomy' => 'portfolio_tag',
                'field' => 'term_id',
                'terms' => $term->term_id,
            ),
        ),
    );

    $query = new WP_Query($args);
    ob_start();

$uid = uniqid();
$container_id = 'portfolio-items-' . $uid;

echo '<script type="text/javascript">
kalium("set", "portfolioContainers[' . $container_id . ']", {
    "url": "' . esc_url(get_term_link($term)) . '",
    "filtering": {"enabled":false},
    "pagination":{"enabled":false},
    "lightbox":{"browse_mode":"single","items":[]},
    "likes":{"enabled":false,"icon":"categories"}
});
</script>';

echo '<div class="masonry-container-loader" data-options=\'{"container":".' . $container_id . '","item":".portfolio-item-entry","layout_mode":"packery","init_animation":false,"stagger":null,"hidden_style":{"opacity":0,"transform":"scale(0.001)"},"visible_style":{"opacity":1,"transform":"scale(1)"},"loading_hide":0.2}\'></div>';

echo '<ul id="' . $container_id . '" class="portfolio-items ' . $container_id . ' grid columns-1 columns-md-2 columns-xl-3" data-layout="masonry">';
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_classes = join(' ', get_post_class('portfolio-item-entry', $post_id));
            // Get thumbnail & ratio for placeholder
            if (has_post_thumbnail()) {
                $thumb_id = get_post_thumbnail_id($post_id);
                $img_meta = wp_get_attachment_metadata($thumb_id);
            } else {
                $ratio = 1;
            }
            $overlay_icon_url = get_stylesheet_directory_uri() . '/assets/icons/open_in_full_24dp_5F6368_FILL0_wght400_GRAD0_opsz24_white.svg';
            ?>
            <li class="<?php echo esc_attr($post_classes); ?>">
                <article class="portfolio-item portfolio-item--type-1" data-aov="fade-in-up" data-aov-duration="3">
                    <div class="portfolio-item__thumbnail">
    <a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
        <span class="image-placeholder loaded">
            <?php the_post_thumbnail('medium'); ?>
        </span>
        <div class="portfolio-item__hover-overlay" data-url="<?php the_permalink(); ?>">
            <img decoding="async" width="24" height="24" src="<?php echo esc_url($overlay_icon_url); ?>" class="attachment-full size-full" alt="" />
        </div>
    </a>
</div>
                    <div class="portfolio-item__details">
                        <h3 class="portfolio-item__title link-plain">
                            <a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                                <?php the_title(); ?>
                            </a>
                        </h3>
                    </div>
                </article>
            </li>
            <?php
        }
    } else {
        echo '<li><p>No portfolio items found for this tag.</p></li>';
    }
    echo '</ul>';
    echo '<style data-inline-style>
    .portfolio-items-' . esc_attr($uid) . ' .portfolio-item__hover-overlay {--k-pi-overlay-icon-max-width: 20px;}
</style>';

    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode('dynamic_portfolio_tag', 'dynamic_portfolio_tag_query');





// [ansatt_masonry]
function ansatt_masonry_grid_shortcode($atts) {
    ob_start();
    $atts = shortcode_atts([
        'posts_per_page' => 24,
        'columns' => 3,
    ], $atts, 'ansatt_masonry');

    // Query Ansatt CPT
    $args = [
        'post_type' => 'ansatt',
        'posts_per_page' => $atts['posts_per_page'],
        'post_status' => 'publish',
    ];
    $query = new WP_Query($args);

    // Unique ID for grid container
    $uniq_id = 'ansatt-items-' . uniqid();

    // Get all 'avdeling' terms for filters
    $terms = get_terms([
        'taxonomy' => 'avdeling',
        'hide_empty' => true,
    ]);
    ?>
    <ul class="elementor-portfolio__filters">
        <li class="elementor-portfolio__filter elementor-active" tabindex="0" data-filter="__all">Alle</li>
        <?php foreach ($terms as $term): ?>
            <li class="elementor-portfolio__filter" tabindex="0" data-filter="<?php echo esc_attr($term->term_id); ?>">
                <?php echo esc_html($term->name); ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="elementor-portfolio <?php echo esc_attr($uniq_id); ?> grid columns-1 columns-md-2 columns-xl-3 masonry-container" data-layout="masonry">
            <?php 
    while($query->have_posts()): $query->the_post(); 
        $tax_classes = '';
        $departments = get_the_terms(get_the_ID(), 'avdeling');
        if ($departments && !is_wp_error($departments)) {
            foreach ($departments as $term) {
                $tax_classes .= ' elementor-filter-' . $term->term_id . ' avdeling-' . esc_attr($term->slug);
            }
        }
    ?>
        <article class="elementor-portfolio-item<?php echo $tax_classes; ?>" data-post-id="<?php the_ID(); ?>">
            <a class="new_portfolio_post_link" style="pointer-events: none;">
                <div class="elementor-portfolio-item__img elementor-post__thumbnail">
                    <?php the_post_thumbnail('medium_large', ['class' => 'wp-image-'.get_post_thumbnail_id().' wp-post-image', 'alt' => get_the_title()]); ?>
                </div>
            </a>
            <div class="elementor-portfolio-item__overlay">
                <a class="new_portfolio_post_link" style="pointer-events: none;">
                    <h3 class="elementor-portfolio-item__title"><?php the_title(); ?></h3>
                </a>
            </div>
            <div class="ansatt-card-meta">
                <?php if ($stilling = get_field('stillingstittel')) : ?>
                    <p class="stillingstittel"><?php echo esc_html($stilling); ?></p>
                <?php endif; ?>
                <?php if ($rolle = get_field('rolle')) : ?>
                    <p class="rolle"><?php echo esc_html($rolle); ?></p>
                <?php endif; ?>
                <?php if ($telefon = get_field('telefon')) : ?>
                    <p class="telefon"><?php echo esc_html($telefon); ?></p>
                <?php endif; ?>
                <?php if ($epost = get_field('epost-adresse')) : ?>
                    <p class="epost"><?php echo esc_html($epost); ?></p>
                <?php endif; ?>
            </div>
            <p class="les-mer"><a onclick="return false;" style="pointer-events: none;">Les mer <span aria-hidden="true">→</span></a></p>
        </article>
    <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('ansatt_masonry', 'ansatt_masonry_grid_shortcode');


// add_action('init', function() {
//     // Re-register the portfolio_tag taxonomy with correct rewrite base
//     register_taxonomy('portfolio_tag', 'portfolio', array(
//         'hierarchical' => false,
//         'label' => 'Portfolio Tags',
//         'query_var' => true,
//         'rewrite' => array(
//             'slug' => 'stikkord', // matches your desired URL
//             'with_front' => false,
//         ),
//         'show_admin_column' => true,
//         'show_ui' => true,
//         'show_in_rest' => true,
//     ));
// }, 11); // Run after theme/plugin registration