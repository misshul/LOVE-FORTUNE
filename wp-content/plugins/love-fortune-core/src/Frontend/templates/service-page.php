<?php
declare(strict_types=1);
defined('ABSPATH') || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="lf-skip-link" href="#love-fortune-main">입력으로 건너뛰기</a>
<main id="love-fortune-main" class="lf-service-main">
<h1 class="lf-service-title">LOVE FORTUNE</h1>
<?php while (have_posts()) : the_post(); the_content(); endwhile; ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
