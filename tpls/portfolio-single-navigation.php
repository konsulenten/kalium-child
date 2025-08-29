<?php
/**
 * Child override: portfolio single navigation with context-aware back/prev/next
 * Path: kalium-child/tpls/portfolio-single-navigation.php
 */

$ctx  = function_exists('hrtb_portfolio_context_slug') ? hrtb_portfolio_context_slug( get_the_ID() ) : '';
$prev = function_exists('hrtb_get_adjacent_portfolio') ? hrtb_get_adjacent_portfolio( true ) : null;
$next = function_exists('hrtb_get_adjacent_portfolio') ? hrtb_get_adjacent_portfolio( false ) : null;
$back = function_exists('hrtb_portfolio_back_link_url') ? hrtb_portfolio_back_link_url( get_the_ID() ) : home_url( '/arkitektur/' );

$prev_url = $prev ? get_permalink( $prev ) : '';
$next_url = $next ? get_permalink( $next ) : '';
if ( $ctx ) {
	if ( $prev_url ) $prev_url = add_query_arg( 'ctx', $ctx, $prev_url );
	if ( $next_url ) $next_url = add_query_arg( 'ctx', $ctx, $next_url );
}
?>
<div class="single-portfolio__navigation container">
	<nav class="post-navigation post-navigation--type-3 post-navigation--reverse post-navigation--has-archive-link post-navigation--animate post-navigation--align-start" aria-label="Post Navigation" data-config="animate-icon animate-archive">
		<ul class="post-navigation__list">
			<li class="post-navigation__item post-navigation__item--prev">
				<?php if ( $prev_url ) : ?>
					<a href="<?php echo esc_url( $prev_url ); ?>" class="post-navigation__link post-navigation__link--prev post-navigation__link--icon-type-1">
						<span class="post-navigation__link-icon"></span>
					</a>
				<?php endif; ?>
			</li>

			<li class="post-navigation__item post-navigation__item--back-to-archive">
				<a href="<?php echo esc_url( $back ); ?>" class="back-to-archive back-to-archive--boxes-2" aria-label="Back to Archive">
					<span class="back-to-archive__boxes" aria-hidden="true"><span></span><span></span><span></span><span></span></span>
				</a>
			</li>

			<li class="post-navigation__item post-navigation__item--next">
				<?php if ( $next_url ) : ?>
					<a href="<?php echo esc_url( $next_url ); ?>" class="post-navigation__link post-navigation__link--next post-navigation__link--icon-type-1">
						<span class="post-navigation__link-icon"></span>
					</a>
				<?php endif; ?>
			</li>
		</ul>
	</nav>
</div>