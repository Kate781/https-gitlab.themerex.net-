<?php
/**
 * Plugin support: Gutenberg
 *
 * @package WordPress
 * @subpackage ThemeREX Addons
 * @since v1.0.49
 */

// Don't load directly
if ( ! defined( 'TRX_ADDONS_VERSION' ) ) {
	die( '-1' );
}

// Check if plugin 'Gutenberg' is installed and activated
// Attention! This function is used in many files and was moved to the api.php
/*
if ( !function_exists( 'trx_addons_exists_gutenberg' ) ) {
	function trx_addons_exists_gutenberg() {
		return function_exists( 'register_block_type' );
	}
}
*/
	
// Merge specific styles into single stylesheet
if ( !function_exists( 'trx_addons_gutenberg_merge_styles' ) ) {
	add_filter("trx_addons_filter_merge_styles", 'trx_addons_gutenberg_merge_styles');
	function trx_addons_gutenberg_merge_styles($list) {
		if (trx_addons_exists_gutenberg()) {
			//$list[] = TRX_ADDONS_PLUGIN_API . 'gutenberg/_gutenberg.scss';
		}
		return $list;
	}
}


// Merge shortcode's specific styles to the single stylesheet (responsive)
if ( !function_exists( 'trx_addons_gutenberg_merge_styles_responsive' ) ) {
	add_filter("trx_addons_filter_merge_styles_responsive", 'trx_addons_gutenberg_merge_styles_responsive');
	function trx_addons_gutenberg_merge_styles_responsive($list) {
		if (trx_addons_exists_gutenberg()) {
			//$list[] = TRX_ADDONS_PLUGIN_API . 'gutenberg/_gutenberg.responsive.scss';
		}
		return $list;
	}
}


// Load required styles and scripts for Backend Editor mode
if ( !function_exists( 'trx_addons_gutenberg_editor_load_scripts' ) ) {
	add_action("enqueue_block_editor_assets", 'trx_addons_gutenberg_editor_load_scripts');
	function trx_addons_gutenberg_editor_load_scripts() {
		trx_addons_load_scripts_admin(true);
		trx_addons_localize_scripts_admin();
		wp_enqueue_style( 'trx_addons', trx_addons_get_file_url(TRX_ADDONS_PLUGIN_API . 'gutenberg/gutenberg-preview.css'), array(), null );
		if (trx_addons_get_setting('allow_gutenberg_blocks')) {
			wp_enqueue_style( 'trx_addons-gutenberg-blocks-editor', trx_addons_get_file_url(TRX_ADDONS_PLUGIN_API . 'gutenberg/blocks/dist/blocks.editor.build.css'), array(), null );
			wp_enqueue_script( 'trx_addons-gutenberg-blocks', trx_addons_get_file_url(TRX_ADDONS_PLUGIN_API . 'gutenberg/blocks/dist/blocks.build.js'), array('jquery'), null, true );

			// Load Swiper slider script and styles
			trx_addons_enqueue_slider();

			// Load Popup script and styles
			trx_addons_enqueue_popup();

			// Load merged scripts
			wp_enqueue_script( 'trx_addons', trx_addons_get_file_url( 'js/trx_addons.js' ), array( 'jquery' ), null, true );
		}
		do_action('trx_addons_action_pagebuilder_admin_scripts');
	}
}

// Load required scripts for both: Backend + Frontend mode
if ( !function_exists( 'trx_addons_gutenberg_preview_load_scripts' ) ) {
	add_action("enqueue_block_assets", 'trx_addons_gutenberg_preview_load_scripts');
	function trx_addons_gutenberg_preview_load_scripts() {
		if (trx_addons_get_setting('allow_gutenberg_blocks')) {
			wp_enqueue_style(  'trx_addons-gutenberg-blocks', trx_addons_get_file_url(TRX_ADDONS_PLUGIN_API . 'gutenberg/blocks/dist/blocks.style.build.css'), array(), null );
		}
		do_action('trx_addons_action_pagebuilder_preview_scripts');
	}
}

// Add shortcode's specific vars to the JS storage
if ( !function_exists( 'trx_addons_gutenberg_localize_script' ) ) {
	add_filter("trx_addons_filter_localize_script", 'trx_addons_gutenberg_localize_script');
	function trx_addons_gutenberg_localize_script($vars) {
		return $vars;
	}
}


// Save CSS with custom colors and fonts to the gutenberg-editor-style.css
if ( ! function_exists( 'trx_addons_gutenberg_save_css' ) ) {
	add_action( 'trx_addons_action_save_options', 'trx_addons_gutenberg_save_css', 30 );
	add_action( 'trx_addons_action_save_options_theme', 'trx_addons_gutenberg_save_css', 30 );
	function trx_addons_gutenberg_save_css() {

		$msg = '/* ' . esc_html__( "ATTENTION! This file was generated automatically! Don't change it!!!", 'trx_addons' )
				. "\n----------------------------------------------------------------------- */\n";

		// Get main styles
		$css = trx_addons_fgc( trx_addons_get_file_dir( 'css/trx_addons.css' ) );

		// Add context class to each selector
		$css = trx_addons_css_add_context($css, '.edit-post-visual-editor');

		// Save styles to the file
		trx_addons_fpc( trx_addons_get_file_dir( TRX_ADDONS_PLUGIN_API . 'gutenberg/gutenberg-preview.css' ), $msg . $css );
	}
}


//------------------------------------------------------------
//-- Compatibility Gutenberg and other PageBuilders
//-------------------------------------------------------------

// Prevent simultaneous editing of posts for Gutenberg and other PageBuilders (VC, Elementor)
if ( ! function_exists( 'trx_addons_gutenberg_disable_cpt' ) ) {
	add_action( 'current_screen', 'trx_addons_gutenberg_disable_cpt' );
	function trx_addons_gutenberg_disable_cpt() {
		$safe_pb = trx_addons_get_setting( 'gutenberg_safe_mode' );
		if ( !empty($safe_pb) && trx_addons_exists_gutenberg() ) {
			$current_post_type = get_current_screen()->post_type;
			$disable = false;
			if ( !$disable && in_array('elementor', $safe_pb) && trx_addons_exists_elementor() ) {
				$post_types = get_post_types_by_support( 'elementor' );
				$disable = is_array($post_types) && in_array($current_post_type, $post_types);
			}
			if ( !$disable && in_array('vc', $safe_pb) && trx_addons_exists_vc() ) {
				$post_types = function_exists('vc_editor_post_types') ? vc_editor_post_types() : array();
				$disable = is_array($post_types) && in_array($current_post_type, $post_types);
			}
			if ( $disable ) {
				remove_filter( 'replace_editor', 'gutenberg_init' );
				remove_action( 'load-post.php', 'gutenberg_intercept_edit_post' );
				remove_action( 'load-post-new.php', 'gutenberg_intercept_post_new' );
				remove_action( 'admin_init', 'gutenberg_add_edit_link_filters' );
				remove_filter( 'admin_url', 'gutenberg_modify_add_new_button_url' );
				remove_action( 'admin_print_scripts-edit.php', 'gutenberg_replace_default_add_new_button' );
				remove_action( 'admin_enqueue_scripts', 'gutenberg_editor_scripts_and_styles' );
				remove_filter( 'screen_options_show_screen', '__return_false' );
			}
		}
	}
}


// Add shortcode's specific vars to the JS storage
if ( ! function_exists( 'trx_addons_gutenberg_localize_scripts_admin' ) ) {
	add_filter( 'trx_addons_filter_localize_script_admin', 'trx_addons_gutenberg_localize_scripts_admin' );
	function trx_addons_gutenberg_localize_scripts_admin( $vars = array() ) {
		if ( trx_addons_exists_gutenberg() && trx_addons_get_setting( 'allow_gutenberg_blocks' ) ) {
			$vars['gutenberg_allowed_blocks'] = trx_addons_gutenberg_get_list_allowed_blocks();
			$vars['gutenberg_sc_params']      = apply_filters( 'trx_addons_filter_gutenberg_sc_params', array() );
		}
		return $vars;
	}
}


// Get list of blocks, allowed inside block-container (i.e. "Content area")
if ( ! function_exists( 'trx_addons_gutenberg_get_list_allowed_blocks' ) ) {
	function trx_addons_gutenberg_get_list_allowed_blocks( $exclude = '' ) {
		if ( !is_array($exclude) ) {
			$exclude = !empty($exclude) ? explode(',', $exclude) : array();
		}
		// This way not include many 'core/xxx' blocks
		//$list = trx_addons_gutenberg_get_list_registered_blocks();
		// Manual way
		global $TRX_ADDONS_STORAGE;
		$list = array( 'core/archives', 'core/block', 'core/categories',
						'core/latest-comments', 'core/latest-posts', 'core/shortcode',
						'core/heading', 'core/subheading', 'core/paragraph', 'core/quote', 'core/list',
						'core/image', 'core/gallery', 'core/audio', 'core/video', 'core/code',
						'core/classic', 'core/custom-html', 'core/table', 'core/columns',
						'core/spacer', 'core/separator', 'core/button', 'core/more',
						'core/preformatted' );
		$registry = WP_Block_Type_Registry::get_instance();
		foreach ( $TRX_ADDONS_STORAGE['sc_list'] as $key => $value ) {
			if ( $registry->is_registered( 'trx-addons/' . $key ) ) {
				$list[] = 'trx-addons/' . $key;
			}
		}
		foreach ( $TRX_ADDONS_STORAGE['widgets_list'] as $key => $value ) {
			if ( $registry->is_registered( 'trx-addons/' . $key ) ) {
				$list[] = 'trx-addons/' . $key;
			}
		}
		foreach ( $TRX_ADDONS_STORAGE['cpt_list'] as $key => $value ) {
			if ( $registry->is_registered( 'trx-addons/' . $key ) ) {
				$list[] = 'trx-addons/' . $key;
			}
		}
		return apply_filters('trx_addons_filter_gutenberg_allowed_blocks', $list);
	}
}


// Get list of registered blocks
// 'type' = 'all | dynamic | static'
if ( ! function_exists( 'trx_addons_gutenberg_get_list_registered_blocks' ) ) {
	function trx_addons_gutenberg_get_list_registered_blocks( $type='all' ) {
		$list = array();
		if ( trx_addons_exists_gutenberg() ) {
			$blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
			if (is_array($blocks)) {
				foreach($blocks as $block) {
					if ($type == 'all' || ($type=='dynamic' && $block->is_dynamic()) || ($type=='static' && !$block->is_dynamic()) ) {
						$list[] = $block->name;
					}
				}
			}
		}
		return apply_filters('trx_addons_filter_gutenberg_registered_blocks', $list);
	}
}


// Add new category to block categories
if ( ! function_exists( 'trx_addons_gutenberg_block_categories' ) ) {
	add_filter( 'block_categories', 'trx_addons_gutenberg_block_categories', 10, 2 );
	function trx_addons_gutenberg_block_categories( $default_categories = array(), $post ) {
		if ( trx_addons_exists_gutenberg() && trx_addons_get_setting( 'allow_gutenberg_blocks' ) ) {
			$default_categories[] = array(
				'slug'  => 'trx-addons-blocks',
				'title' => __( 'TRX Addons Blocks', 'trx-addons' ),
			);
			$default_categories[] = array(
				'slug'  => 'trx-addons-widgets',
				'title' => __( 'TRX Addons Widgets', 'trx-addons' ),
			);
			$default_categories[] = array(
				'slug'  => 'trx-addons-cpt',
				'title' => __( 'TRX Addons Custom Post Types', 'trx-addons' ),
			);
		}
		return $default_categories;
	}
}

// Add shortcode's specific vars to the JS storage
if ( ! function_exists( 'trx_addons_gutenberg_sc_params' ) ) {
	add_filter( 'trx_addons_filter_gutenberg_sc_params', 'trx_addons_gutenberg_sc_params' );
	function trx_addons_gutenberg_sc_params( $vars = array() ) {
		if ( trx_addons_exists_gutenberg() && trx_addons_get_setting( 'allow_gutenberg_blocks' ) ) {
			// Return iconed classes list
			$list_icons            = trx_addons_get_list_icons_classes();
			$vars['icons_classes'] = array();
			if ( ! empty( $list_icons ) ) {
				foreach ( $list_icons as $x => $y ) {
					$vars['icons_classes'][] = $y;
				}
			}

			// Return list of the element positions
			$vars['sc_positions'] = trx_addons_get_list_sc_positions();

			// Return list of the title tags
			$vars['sc_title_tags'] = trx_addons_get_list_sc_title_tags();

			// Return list of the title align
			$vars['sc_aligns'] = trx_addons_get_list_sc_aligns();

			// Return list of allowed layouts
			$vars['sc_layouts'] = apply_filters( 'trx_addons_filter_gutenberg_sc_layouts', array() );

			// Return list of the orderby options for CPT shortcodes
			$vars['sc_query_orderby'] = trx_addons_get_list_sc_query_orderby();

			// Return list of the order options
			$vars['sc_query_orders'] = trx_addons_get_list_sc_query_orders();

			// Return list of the slider pagination positions
			$vars['sc_paginations'] = trx_addons_get_list_sc_paginations();

			// Return list of post's types
			$vars['posts_types'] = trx_addons_get_list_posts_types();

			// Return list of taxonomies
			$vars['taxonomies'] = array();
			foreach ( $vars['posts_types'] as $key => $value ) {
				$vars['taxonomies'][ $key ] = trx_addons_get_list_taxonomies( false, $key );
			}

			// Return list of categories
			$vars['categories'] = array();
			$vars['categories']['category'] = trx_addons_get_list_categories();
			foreach ( $vars['posts_types'] as $key => $value ) {
				$taxonomies = trx_addons_get_list_taxonomies( false, $key );
				foreach ( $taxonomies as $x => $y ) {
					$vars['categories'][ $x ] = trx_addons_get_list_terms( false, $x );
				}
			}

			// Return list of categories
			$vars['list_categories'] = trx_addons_array_merge( array( 0 => esc_html__( '- Select category -', 'trx_addons' ) ), trx_addons_get_list_categories() );

			// Return list of the content's widths
			$vars['sc_content_widths'] = trx_addons_get_list_sc_content_widths();

			// Return list of the content's paddings and margins sizes
			$vars['sc_content_paddings_and_margins'] = trx_addons_get_list_sc_content_paddings_and_margins();

			// Return list of the content's push and pull sizes
			$vars['sc_content_push_and_pull'] = trx_addons_get_list_sc_content_push_and_pull();

			// Return list of the shift sizes to move content along X- and/or Y-axis
			$vars['sc_content_shift'] = trx_addons_get_list_sc_content_shift();

			// Return list of the bg sizes to oversize content area
			$vars['sc_content_extra_bg'] = trx_addons_get_list_sc_content_extra_bg();

			// Return list of the bg mask values to color tone of the bg image
			$vars['sc_content_extra_bg_mask'] = trx_addons_get_list_sc_content_extra_bg_mask();

			// Return list of the slider controls positions
			$vars['sc_slider_controls'] = trx_addons_get_list_sc_slider_controls();

			// Return list of the slider pagination positions
			$vars['sc_slider_paginations'] = trx_addons_get_list_sc_slider_paginations();

			// Prepare lists
			$vars['sliders_list'] = array(
				'swiper' => esc_html__( 'Posts slider (Swiper)', 'trx_addons' ),
			);
			if ( trx_addons_exists_revslider() ) {
				$vars['sliders_list']['revo'] = esc_html__( 'Layer slider (Revolution)', 'trx_addons' );
			}

			// Type of the slides content
			$vars['slides_type'] = array(
				'bg'     => esc_html__( 'Background', 'trx_addons' ),
				'images' => esc_html__( 'Image tag', 'trx_addons' ),
			);

			// Type of the slides content
			$vars['list_revsliders'] = trx_addons_get_list_revsliders();

			// Swiper effect
			$vars['sc_slider_effects'] = trx_addons_get_list_sc_slider_effects();

			// Direction to change slides
			$vars['sc_slider_directions'] = trx_addons_get_list_sc_slider_directions();

			// Direction to change slides
			$vars['sc_slider_paginations_types'] = trx_addons_get_list_sc_slider_paginations_types();

			// Titles in the Swiper
			$vars['sc_slider_titles'] = trx_addons_get_list_sc_slider_titles();

			// Size of the button
			$vars['sc_button_sizes'] = trx_addons_get_list_sc_button_sizes();

			// Icon position
			$vars['sc_icon_positions'] = trx_addons_get_list_sc_icon_positions();

			// Return list of the image positions
			$vars['sc_promo_positions'] = trx_addons_get_list_sc_promo_positions();

			// Return list of the promo's sizes
			$vars['sc_promo_sizes'] = trx_addons_get_list_sc_promo_sizes();

			// Return list of the promo text area's widths
			$vars['sc_promo_widths'] = trx_addons_get_list_sc_promo_widths();

			// Return input hover effects
			$vars['input_hover'] = trx_addons_get_list_input_hover( true );

			// Prepare list of pages
			$vars['list_pages'] = trx_addons_get_list_posts(
				false, array(
					'post_type'    => 'page',
					'not_selected' => false,
				)
			);

			// Prepare list of pages
			$vars['list_layouts'] = trx_addons_get_list_posts(
				false, array(
					'post_type'    => TRX_ADDONS_CPT_LAYOUTS_PT,
					'meta_key'     => 'trx_addons_layout_type',
					'meta_value'   => 'custom',
					'not_selected' => false,
				)
			);
			
			// Return list of positions of the featured element in services
			$vars['sc_googlemap_styles'] = trx_addons_get_list_sc_googlemap_styles();
			
			// Return list of the googlemap animations
			$vars['sc_googlemap_animations'] = trx_addons_get_list_sc_googlemap_animations();
			
			// Return list of the icon's sizes
			$vars['sc_icon_sizes'] = trx_addons_get_list_sc_icon_sizes();
			
			// Return list of the title types
			$vars['sc_supertitle_item_types'] = trx_addons_get_list_sc_supertitle_item_types();
			
			// Return list of the title tags
			$vars['sc_title_tags'] = trx_addons_get_list_sc_title_tags( '', true );
			
			// Return list of the instagram redirects
			$vars['sc_instagram_redirects'] = trx_addons_get_list_sc_instagram_redirects();
		}
		return $vars;
	}
}
