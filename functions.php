<?php
$wp_launcher = '';
function wplauncher_init_plugin() {
	global $wp_launcher;
	$wp_launcher = new WP_Launcher;
}
add_action( 'plugins_loaded', 'wplauncher_init_plugin' );

// Only head() and footer() are required in a template

// Page Meta
function wplauncher_head() {
	global $wp_launcher;
	$wp_launcher->template_head();
}
// Editor Scripts
function wplauncher_footer() {
	global $wp_launcher;
	$wp_launcher->template_footer();
}

function wplauncher_is_editor() {
	global $wp_launcher;
	return $wp_launcher->is_editor();
}
function wplauncher_is_preview() {
	global $wp_launcher;
	return $wp_launcher->is_preview();
}

// Gets absolute path to current template directory
function wplauncher_template_directory() {
	global $wp_launcher;
	echo $wp_launcher->get_template_directory();
}
function wplauncher_get_template_directory() {
	global $wp_launcher;
	return $wp_launcher->get_template_directory();
}

// Gets public URI to current template directory
function wplauncher_template_directory_uri() {
	global $wp_launcher;
	echo $wp_launcher->get_template_directory_uri();
}
function wplauncher_get_template_directory_uri() {
	global $wp_launcher;
	return $wp_launcher->get_template_directory_uri();
}

// Editable fields
function wplauncher_text( $params = '' ) {
	global $wp_launcher;
	$wp_launcher->editable_text( $params );
}
function wplauncher_image( $params = '' ) {
	global $wp_launcher;
	$wp_launcher->editable_image( $params );
}

// These will output attributes
function wplauncher_background_attr( $params = '' ) {
	global $wp_launcher;
	$wp_launcher->editable_attr_background( $params );
}

function wplauncher_color_attr( $params = '' ) {
	global $wp_launcher;
	$wp_launcher->editable_attr_color( $params );
}
function wplauncher_hideable_attr( $params = '' ) {
	global $wp_launcher;
	$wp_launcher->editable_attr_hideable( $params );
}


// Additional elements

/* Dynamic Countdown
 * Variables for "format" parameter:
	%Y: "years",
	%m: "months",
	%w: "weeks",
	%d: "days",
	%D: "totalDays", // weeks * 7 + days
	%H: "hours",
	%I: "totalHours", // totalDays * 24 + hours
	%M: "minutes",
	%S: "seconds",
	%u: "milliseconds"
 * 
 * Other parameters "hideable", "edit_color"
 */
function wplauncher_countdown( $params = '' ) {
	global $wp_launcher;
	$wp_launcher->template_tag_countdown( $params );
}

// Subscribe Form
function wplauncher_subscribe( $params = '' ) {
	global $wp_launcher;
	return $wp_launcher->template_tag_subscribe( $params );
}
// Contact Form
function wplauncher_contact( $params = '' ) {
	global $wp_launcher;
	return $wp_launcher->template_tag_contact( $params );
}
// Twitter Feed
function wplauncher_twitter( $params = '' ) {
	global $wp_launcher;
	return $wp_launcher->template_tag_twitter( $params );
}
// Social Icons
function wplauncher_social( $params = '' ) {
	global $wp_launcher;
	return $wp_launcher->template_tag_social( $params );
}

function wplauncher_section_start( $params = '' ) {
	global $wp_launcher;
	return $wp_launcher->editable_section_start( $params );
}
function wplauncher_section_end( $id = '' ) {
	global $wp_launcher;
	return $wp_launcher->editable_section_end( $id );
}
function wplauncher_get_field( $field, $template = null ) {
	global $wp_launcher;
	return $wp_launcher->get_editable_value( $field, $template );
}