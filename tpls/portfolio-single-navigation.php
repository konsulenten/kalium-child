<?php
/**
 * Child override of Kalium nav — context-aware back link + prev/next
 * Path: kalium-child/tpls/portfolio-single-navigation.php
 */

if ( ! function_exists( 'hrtb_get_primary_portfolio_category' ) ) {
	// --- helpers (keep once in functions.php ideally) ---
	function hrtb_get_primary_portfolio_category( $post_id = 0 ) {
		$post_id  = $post_id ?: get_the_ID();
		$taxonomy = 'portfolio_category';

		if ( class_exists( 'WPSEO_Primary_Term' ) ) {
			$primary = new WPSEO_Primary_Term( $taxonomy, $post_id );
			$term_id = (int) $primary->get_primary_term();
			if ( $term_id && ! is_wp_error( $term_id ) ) {
				$term = get_term( $term_id, $taxonomy );
				if ( $term && ! is_wp_error( $term ) ) {
					return $term;
				}
			}
		}

		$terms = get_the_terms( $post_id, $taxonomy );
		return ( is_array( $terms ) && $terms ) ? array_values( $terms )[0] : null;
	}
	function hrtb_get_archive_filter_slug() {
		if ( empty( $_COOKIE['hrtb_portfolio_hash'] ) ) return '';
		return sanitize_title( wp_unslash( $_COOKIE['hrtb_portfolio_hash'] ) );
	}
	function hrtb_portfolio_back_link_url( $post_id = 0 ) {
		$base = home_url( '/arkitektur/' );
		$slug = hrtb_get_archive_filter_slug();
		if ( $slug ) return $base . '#' . $slug;
		$term = hrtb_get_primary_portfolio_category( $post_id );
		return $term ? $base . '#' . $term->slug : $base;
	}
	function hrtb_get_adjacent_portfolio( $previous = true ) {
		$taxonomy    = 'portfolio_category';
		$filter_slug = hrtb_get_archive_filter_slug();
		// If user filtered on archive: constrain to that term; else traverse all
		return get_adjacent_post( (bool) $filter_slug, '', $previous, $taxonomy );
	}
	// --- end helpers ---
}

$prev = hrtb_get_adjacent_portfolio( true );
$next = hrtb_get_adjacent_portfolio( false );
$back = hrtb_portfolio_back_link_url( get_the_ID() );
?>
<div class="single-portfolio__navigation container">
	<nav class="post-navigation post-navigation--type-3 post-navigation--reverse post-navigation--has-archive-link post-navigation--animate post-navigation--align-start" aria-label="Post Navigation" data-config="animate-icon animate-archive">
		<ul class="post-navigation__list">
			<li class="post-navigation__item post-navigation__item--prev">
				<?php if ( $prev ) : ?>
					<a href="<?php echo esc_url( get_permalink( $prev ) ); ?>" class="post-navigation__link post-navigation__link--prev post-navigation__link--icon-type-1"><span class="post-navigation__link-icon"></span></a>
				<?php endif; ?>
			</li>
			<li class="post-navigation__item post-navigation__item--back-to-archive">
				<a href="<?php echo esc_url( $back ); ?>" class="back-to-archive back-to-archive--boxes-2" aria-label="Back to Archive">
					<span class="back-to-archive__boxes" aria-hidden="true"><span></span><span></span><span></span><span></span></span>
				</a>
			</li>
			<li class="post-navigation__item post-navigation__item--next">
				<?php if ( $next ) : ?>
					<a href="<?php echo esc_url( get_permalink( $next ) ); ?>" class="post-navigation__link post-navigation__link--next post-navigation__link--icon-type-1"><span class="post-navigation__link-icon"></span></a>
				<?php endif; ?>
			</li>
		</ul>
	</nav>
</div>