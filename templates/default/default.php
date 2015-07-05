<?php
/*
Launcher template: Launcher
Template URI: http://mythemeshop.com/plugins/launcher/
Description: Default template that comes with Launcher plugin.
Author: MyThemeShop
Version: 1.0
Author URI: http://mythemeshop.com/
Supports: countdown timer, subscribe form, contact form, twitter feed, social icons
*/

if (!defined('ABSPATH')) die(); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta charset="utf-8">
		<?php wplauncher_head(); // title, favicon, meta description, noindex, jquery ?>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<link href='http://fonts.googleapis.com/css?family=Roboto:400,300,700' rel='stylesheet' type='text/css'>
		<link rel="stylesheet" href="<?php wplauncher_template_directory_uri(); ?>/style.css">
	</head>
	<body id ="blog" class="home blog main" itemscope itemtype="http://schema.org/WebPage">  
		<div class="main-container">  			        	
	    	<?php wplauncher_section_start(array('default_bg' => wplauncher_get_template_directory_uri().'/start.jpg', 'class' => 'L_first')); ?>
			<div class="L_left">
				<div class="container">
					<h1 id="logo" class="text-logo" itemprop="headline">
						<?php wplauncher_image('hideable=1&default='.wplauncher_get_template_directory_uri().'/logo.png'); ?>
					</h1>
					<div class="front-view-content">
						<?php wplauncher_text('type=textarea&edit_color=1&default=Sorry, we are working on something that will completely blow your mind away. Please come back soon!'); ?>
					</div>
					<div class="counter">
						<?php wplauncher_countdown(array('format' => '
							<span class="counting wplauncher-days"><span class="count_num">%D</span><span>Days</span></span>
							<span class="counting wplauncher-hours"><span class="count_num">%H</span><span>Hours</span></span>
							<span class="counting wplauncher-minutes"><span class="count_num">%M</span><span>Minutes</span></span>
							<span class="counting wplauncher-seconds"><span class="count_num">%S</span><span>Seconds</span></span>
						', 'edit_color' => 1)); ?>
						<?php if (wplauncher_get_field('countdown_color')): ?>
							<style type="text/css">#wplauncher-countdown > span { border-color: <?php echo wplauncher_get_field('countdown_color'); ?>; }</style>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="L_right">
				<div class="container"> 
	                <h3 class="front-view-title">
						<?php wplauncher_text('Stay Updated'); ?>
					</h3>
					<div class="front-view-content">
						<?php wplauncher_text('Subscribe for latest updates on our websites from our dream team'); ?>
					</div>
					<div id="mts_subscribe_widget-2" class="widget mts_subscribe_widget">        	
						<?php wplauncher_subscribe(); ?>
						<?php if (wplauncher_get_field('subscribe_color')): ?>
							<style type="text/css">.L_right .wplauncher-subscribe input[type="text"], .L_right .wplauncher-subscribe input[type="email"], .L_right .wplauncher-subscribe input[type="submit"] { border-color: <?php echo wplauncher_get_field('subscribe_color'); ?>; } .L_right .wplauncher-subscribe input[type="submit"] { background-color: <?php echo wplauncher_get_field('subscribe_color'); ?>; }</style>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php wplauncher_section_end(); ?>

		<?php wplauncher_section_start(array('default_bg' => wplauncher_get_template_directory_uri().'/about.jpg', 'class' => 'L_second')); ?>
			<div class="L_left">
				<div class="container">
					<h3 class="front-view-title">
						<?php wplauncher_text('About Us'); ?>
					</h3>
					<p class="front-view-content">
						<?php wplauncher_text(array(
							'type' => 'textarea',
							'default' => 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenan nec augue eget lacus iaculis condimentum. Maecenas consequat fermentum leo, eu efficitur nunc iaculis quis.

								Morbi imperdiet tristique ligula, in cursus massa . Cras eu congue libero. Sed inturdum enim lacus, sit amet consectetur libero lacinia sed. Mauris ut velit magna. Nunc vitae nisl eu erat consectur aliquet. Nulla facilisi'
						)); ?>
					</p>
				</div>
			</div>
			<div class="L_right">
				<div class="container">
			  		<div class="L_icon">
						<div <?php wplauncher_color_attr('id=twittericon_color&class=twittericon'); ?>><i class="wplauncher-icon-twitter"></i></div>
					</div>
					<div class="front-view-content">
						<?php wplauncher_twitter('number=1&edit_color=1'); ?>
						<?php if (wplauncher_get_field('twittericon_color')): ?>
							<style type="text/css">.L_icon .twittericon { border-color: <?php echo wplauncher_get_field('twittericon_color'); ?>; }</style>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php wplauncher_section_end(); ?>

		<?php wplauncher_section_start(array('default_bg' => wplauncher_get_template_directory_uri().'/contact.jpg', 'class' => 'L_third')); ?>
			<div class="L_left">
		    	<div class="container">
					<h3 class="front-view-title">
						<?php wplauncher_text('Contact Us'); ?>
					</h3>
					<div class="contact-form">
						<?php wplauncher_contact('edit_color=1'); ?>
						<?php if (wplauncher_get_field('contact_color')): ?>
							<style type="text/css">#wplauncher_contact_form input, #wplauncher_contact_form textarea { border-color: <?php echo wplauncher_get_field('contact_color'); ?>; } .L_third #wplauncher_contact_submit { background-color: <?php echo wplauncher_get_field('contact_color'); ?>; }</style>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="L_right">
				<div class="container"> 
            		<div class="L_contact">
            			<ul class="left">
							<li<?php wplauncher_hideable_attr(); ?>><i class="wplauncher-icon wplauncher-icon-phone"></i> <?php wplauncher_text('default=012-345-6787&hideable=0&edit_color=0') ?></li>
							<li<?php wplauncher_hideable_attr(); ?>><i class="wplauncher-icon wplauncher-icon-fax"></i> <?php wplauncher_text('default=012-345-6788&hideable=0&edit_color=0') ?></li>
							<li<?php wplauncher_hideable_attr(); ?>><i class="wplauncher-icon wplauncher-icon-mail"></i> <?php wplauncher_text('default=contact@example.com&hideable=0&edit_color=0') ?></li>
						</ul>
						<ul class="right">
							<li<?php wplauncher_hideable_attr(); ?>><?php wplauncher_text('default=Launcher&hideable=0&edit_color=0') ?></li>
							<li<?php wplauncher_hideable_attr(); ?>><?php wplauncher_text('default=1147, Libertyville&hideable=0&edit_color=0') ?></li>
							<li<?php wplauncher_hideable_attr(); ?>><?php wplauncher_text('default=Illinois, USA&hideable=0&edit_color=0') ?></li>
						</ul>
					</div>
					<div class="L_social">
						<?php wplauncher_social('hideable=1&edit_color=1'); ?>
						<?php if (wplauncher_get_field('social_color')): ?>
							<style type="text/css">.L_social .wplauncher-social a { background-color: <?php echo wplauncher_get_field('social_color'); ?>; color: #fff; }</style>
						<?php endif; ?>
					</div>
					<div class="L_copyrights">
						<?php wplauncher_text( array( 'default' => '2015 &copy; Launcher Plugin by <a href="https://mythemeshop.com/" rel="nofollow">MyThemeShop</a>' ) ); ?>
					</div>
				</div>
			</div>
		<?php wplauncher_section_end(); ?>

		<?php wplauncher_footer(); // includes editor scripts ?>
	</body>
</html>