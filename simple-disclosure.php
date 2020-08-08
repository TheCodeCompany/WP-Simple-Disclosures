<?php
/**
 * Plugin Name: Simple Affiliate Disclosures
 * Description: Programmatically add a block of text at the top of certain posts, based on taxonomy. 
 * Version:     1.0
 * Author:      The Code Company
 * License:     GPLv2 or later.
*/

namespace TheCodeCompany;

class Simple_Disclosures {

	public function __construct() {

		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_settings'  ) );
		add_filter( 'the_content', array( $this, 'content_filter' ) );

	}

	public function add_admin_menu() {

		add_options_page(
			'Manage Disclosures',
			'Disclosures',
			'manage_options',
			'manage-disclosures',
			array( $this, 'page_layout' )
		);

	}

	public function init_settings() {

		register_setting(
			'simple_disclosures',
			'simple_disclosures'
		);

		add_settings_section(
			'simple_disclosures_section',
			'',
			false,
			'simple_disclosures'
		);

		add_settings_field(
			'disclosure_html',
			'Disclosure',
			array( $this, 'render_disclosure_html_field' ),
			'simple_disclosures',
			'simple_disclosures_section'
		);
		add_settings_field(
			'show_control',
			'Show on Categories',
			array( $this, 'render_show_control_field' ),
			'simple_disclosures',
			'simple_disclosures_section'
		);

	}

	public function page_layout() {

		// Check required user capability
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		}

		// Admin Page Layout
		echo '<div class="wrap">' . "\n";
		echo '	<h1>' . get_admin_page_title() . '</h1>' . "\n";
		echo '	<form action="options.php" method="post">' . "\n";

		settings_fields( 'simple_disclosures' );
		do_settings_sections( 'simple_disclosures' );
		submit_button();

		echo '	</form>' . "\n";
		echo '</div>' . "\n";

	}

	function render_disclosure_html_field() {

		// Retrieve data from the database.
		$options = get_option( 'simple_disclosures' );

		// Set default value.
		$value = isset( $options['disclosure_html'] ) ? $options['disclosure_html'] : '';

		// Field output.
		echo '<textarea rows="10" style="width:500px;" name="simple_disclosures[disclosure_html]" class="regular-text disclosure_html_field" placeholder="' . '' . '">' . $value . '</textarea>';
		echo '<p class="description">' . 'Disclosure can include HTML.' . '</p>';

	}

	function render_show_control_field() {

		// Retrieve data from the database.
		$options = get_option( 'simple_disclosures' );

		// Set default value.
        $value = isset( $options['show_control'] ) ? $options['show_control'] : array();

		// Field output.

        $terms = get_terms( array(
		    'taxonomy' => 'category',
		    'hide_empty' => false,
		) );

    	foreach( $terms as $term ) {
 
		echo '<input type="checkbox" name="simple_disclosures[show_control][]" class="show_control_field" value="' . esc_attr( $term->term_taxonomy_id ) . '" ' . ( in_array( $term->term_taxonomy_id, $value )? 'checked="checked"' : '' ) . '> ' .  $term->name . '<br>';
		
		}

		echo '<p class="description">' . 'Leave blank to show on all posts.' . '</p>';

	}

	function content_filter( $content ) {

		// Only show on single posts for now. Could extend by CPTs
		if( ! is_singular( 'post' ) )
			return $content;

		// Get disclosure settings
		$disclosure = get_option( 'simple_disclosures' );

		// Don't show by default.
		$show_disclosure = false;

		// If a disclosure is set, and there's no controls, show it.
		if( $disclosure['disclosure_html'] && ! isset( $disclosure['show_control'] ) )
			$show_disclosure = true;


		if( $disclosure['disclosure_html'] && isset( $disclosure['show_control'] ) ) {

			global $post;

			if( in_category( $disclosure['show_control'], $post ) ){

				$show_disclosure = true; 

			}
		}

		if ( $show_disclosure ) {

			return $disclosure['disclosure_html'] . $content;

		} else {

			return $content;

		}
	}
}

new Simple_Disclosures;