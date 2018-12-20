<?php

namespace uncanny_learndash_toolkit;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class learndashBreadcrumbs
 * @package uncanny_custom_toolkit
 */
class Blocks {

	/*
	 * Plugin prefix
	 * @var string
	 */
	public $prefix = '';

	/*
	 * Plugin version
	 * @var string
	 */
	public $version = '';

	/*
	 * Active Classes
	 * @var string
	 */
	public $active_classes = '';

	/**
	 * Blocks constructor.
	 *
	 * @param string $prefix
	 * @param string $version
	 * @param array  $active_classes
	 */
	public function __construct( $prefix = '', $version = '', $active_classes = [] ) {

		$this->prefix         = $prefix;
		$this->version        = $version;
		$this->active_classes = $active_classes;

		$add_block_scripts = false;

		// Check if Gutenberg exists
		if ( function_exists( 'register_block_type' ) ) {

			if (
				isset( $active_classes[ 'uncanny_learndash_toolkit\Breadcrumbs' ] ) ||
				isset( $active_classes[ 'uncanny_learndash_toolkit\LearnDashResume' ] ) || 
				isset( $active_classes[ 'uncanny_learndash_toolkit\FrontendLoginPlus' ] )
			) {
				$add_block_scripts = true;
			}
			// Register Blocks
			add_action( 'init', function () {
				if ( isset( $this->active_classes[ 'uncanny_learndash_toolkit\Breadcrumbs' ] ) ) {
					require_once( dirname( __FILE__ ) . '/src/toolkit-breadcrumbs/block.php' );
				}

				if ( isset( $this->active_classes[ 'uncanny_learndash_toolkit\LearnDashResume' ] ) ) {
					require_once( dirname( __FILE__ ) . '/src/toolkit-resume-button/block.php' );
				}

				if ( isset( $this->active_classes[ 'uncanny_learndash_toolkit\FrontendLoginPlus' ] ) ) {
					require_once( dirname( __FILE__ ) . '/src/toolkit-login-uncanny/block.php' );
				}

				if ( isset( $this->active_classes[ 'uncanny_learndash_toolkit\FrontendLoginPlus' ] ) ) {
					require_once( dirname( __FILE__ ) . '/src/toolkit-login-uncanny/block.php' );
					require_once( dirname( __FILE__ ) . '/src/toolkit-login-wordpress/block.php' );
				}
			});

			if ( $add_block_scripts ) {


				// Enqueue Gutenberg block assets for both frontend + backend
				add_action( 'enqueue_block_assets', function () {
					wp_enqueue_style(
						$this->prefix . '-gutenberg-blocks',
						plugins_url( 'blocks/dist/blocks.style.build.css', dirname( __FILE__ ) ),
						[ 'wp-blocks' ],
						UNCANNY_TOOLKIT_VERSION
					);
				} );

				// Enqueue Gutenberg block assets for backend editor
				add_action( 'enqueue_block_editor_assets', function () {
					wp_enqueue_script(
						$this->prefix . '-gutenberg-editor',
						plugins_url( 'blocks/dist/blocks.build.js', dirname( __FILE__ ) ),
						[ 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ],
						UNCANNY_TOOLKIT_VERSION,
						true
					);

					wp_localize_script($this->prefix . '-gutenberg-editor', $this->prefix . 'Modules', array(
						'active' => $this->active_classes,
					));

					wp_enqueue_style(
						$this->prefix . '-gutenberg-editor',
						plugins_url( 'blocks/dist/blocks.editor.build.css', dirname( __FILE__ ) ),
						[ 'wp-edit-blocks' ],
						UNCANNY_TOOLKIT_VERSION
					);
				} );

				// Create custom block category
				add_filter( 'block_categories', function ( $categories, $post ) {
					return array_merge(
						$categories,
						array(
							array(
								'slug'  => 'uncanny-learndash-toolkit',
								'title' => __( 'Uncanny LearnDash Toolkit', 'uncanny-learndash-toolkit' ),
							),
						)
					);
				}, 10, 2 );
			}

		}
	}
}
