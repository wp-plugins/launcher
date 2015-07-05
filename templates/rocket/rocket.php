<?php
/*
Launcher template: Rocket
Template URI: http://mythemeshop.com/plugins/launcher/
Description: Default Rocket template that comes with Launcher plugin.
Author: MyThemeShop
Version: 1.0
Author URI: http://mythemeshop.com/
Supports: countdown timer, social icons, subscribe form, twitter feed
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
		<link rel="stylesheet" href="<?php wplauncher_template_directory_uri(); ?>/shake.css">
		<link href='http://fonts.googleapis.com/css?family=Exo+2:400,600,700' rel='stylesheet' type='text/css'>
	</head>
	<body>
		<div id="launcher" <?php wplauncher_background_attr('meta=class:bg&default='.wplauncher_get_template_directory_uri().'/bgpattern.png'); ?>>
			<div class="page">
				<div id="social"<?php wplauncher_color_attr('id=social_icons_color'); ?>>
					<p class="followus">Follow Us:</p> 
					<?php wplauncher_social('hideable=1'); ?>
				</div>
				<div id="block">
	      			<div id="block-text">
	      				<h1><?php wplauncher_text('default=Launcher Plugin Demo&hideable=1'); ?></h1>
						<p>
	          				<span class="text"><?php wplauncher_text('default=We are working on our website design. We are sure this new website will completely blow your mind! Plugin designed by MyThemeShop&hideable=1'); ?></span>
	          			</p>
						<?php wplauncher_subscribe(); ?>
						<?php if (wplauncher_get_field('subscribe_color')): ?>
							<style type="text/css">input[type="text"], input[type="email"], input[type="submit"] { border-color: <?php echo wplauncher_get_field('subscribe_color'); ?>; } input[type="submit"] { background-color: <?php echo wplauncher_get_field('subscribe_color'); ?>; }</style>
						<?php endif; ?>
						<div class="countdown">
							<?php wplauncher_countdown(array('format' => '
							<span class="counting wplauncher-days"><span class="count_num">%D</span><span class="count_text">Days</span></span>
							<span class="counting wplauncher-hours"><span class="count_num">%H</span><span class="count_text">Hours</span></span>
							<span class="counting wplauncher-minutes"><span class="count_num">%M</span><span class="count_text">Minutes</span></span>
							<span class="counting wplauncher-seconds"><span class="count_num">%S</span><span class="count_text">Seconds</span></span>
						', 'edit_color' => 1)); ?>
							<?php if (wplauncher_get_field('countdown_color')): ?>
								<style type="text/css">.count_num { color: <?php echo wplauncher_get_field('countdown_color'); ?>; }</style>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="rocket">
					<img class="floating-rocket shake shake-little shake-constant" src="<?php wplauncher_template_directory_uri(); ?>/the_rocket.png" alt="Launching" >
				</div>
				<div class="comeback">
					<img src="<?php wplauncher_template_directory_uri(); ?>/comeback.png" alt="comeback" >
				</div>
				<div class="launchpad">
					<img src="<?php wplauncher_template_directory_uri(); ?>/launchpad.png" alt="launchpad" >
				</div>
			</div>
			<div id="footer_area" class="footer1">
				<div class="page">
					<div id="my_tweets">
						<div id="tweet">
							<?php wplauncher_twitter('number=1&edit_color=1'); ?>
						</div>
					</div>
				</div>
			</div>
			<div id="footer_area" class="footer2">
				<div class="page">
					<p class="copyrights">
						<?php wplauncher_text( array( 'default' => '2015 &copy; Launcher Plugin by <a href="https://mythemeshop.com/" rel="nofollow">MyThemeShop</a>' ) ); ?>
					</p>
				</div>
			</div>
		</div>
		<?php wplauncher_footer(); // includes editor scripts ?>
	</body>
</html>