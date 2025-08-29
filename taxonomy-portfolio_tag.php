<?php
get_header();
?>
<div class="container">
    <?php
    // Output the tag title/description
    echo '<h2 style="font-weight: 200;text-align: center;">Stikkord: ' . single_term_title('', false) . '</h2>';
    // Output your shortcode
    echo do_shortcode('[dynamic_portfolio_tag]');
    ?>
</div>
<?php get_footer(); ?>