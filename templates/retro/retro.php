<?php
/*
Launcher template: Retro
Template URI: http://mythemeshop.com/plugins/launcher/
Description: Default Retro template that comes with Launcher plugin.
Author: MyThemeShop
Version: 1.0
Author URI: http://mythemeshop.com/
Supports: countdown timer, social icons
*/

if (!defined('ABSPATH')) die();
?><!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta charset="utf-8">
		<?php wplauncher_head(); // title, favicon, meta description, noindex ?>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" href="<?php wplauncher_template_directory_uri(); ?>/style.css">
		<link href='http://fonts.googleapis.com/css?family=Bowlby+One|Open+Sans:700,400' rel='stylesheet' type='text/css'>
	</head>
	<body>
		<div<?php wplauncher_background_attr('meta=class:bg&default='.wplauncher_get_template_directory_uri().'/testpattern.jpg'); ?>></div>

		<div class="wrap">
			<h1><?php wplauncher_text('default=PLEASE STAND BY&hideable=1'); ?></h1>
			<p><?php wplauncher_text('default=Launching Soon&hideable=1'); ?></h1>
			<?php 
			$refresh_rate = 50; // in preview & live page
			if (wplauncher_is_editor()) $refresh_rate = 3000; // in editor, no need for fancy animation
			wplauncher_countdown(array(
				'format' => '
					<span class="wplauncher-hours">%I</span>
						<span class="wplauncher-time-sep">:</span>
					<span class="wplauncher-minutes">%M</span>
						<span class="wplauncher-time-sep">:</span>
					<span class="wplauncher-seconds">%S</span>
						<span class="wplauncher-time-sep">:</span>
					<span class="wplauncher-milliseconds">%u</span>',
				'refresh_rate' => $refresh_rate,
				'hideable' => true,
				'edit_color' => true
			)); ?>
			<div<?php wplauncher_color_attr('id=social_icons_color'); ?>>
				<?php wplauncher_social('hideable=1'); ?>
			</div>
			<?php if (wplauncher_get_field('social_icons_color')): ?>
				<style type="text/css">.wplauncher-social a:hover { background: <?php echo wplauncher_get_field('social_icons_color'); ?>; }</style>
			<?php endif; ?>
			<div class="copyrights">
				<?php wplauncher_text( array( 'default' => '2015 &copy; Launcher Plugin by <a href="https://mythemeshop.com/" rel="nofollow">MyThemeShop</a>' ) ); ?>
			</div>
		</div>
		<div class="noise-wrap">
			<div class="noise"></div>
		</div>
		<?php wplauncher_footer(); // includes editor scripts ?>
	</body>
</html>