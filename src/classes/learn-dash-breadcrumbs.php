<?php
	namespace uncanny_learndash_toolkit;

	/**
	 * Class learndashBreadcrumbs
	 * @package uncanny_custom_toolkit
	 */
	class learnDashBreadcrumbs extends Config implements RequiredFunctions {

		/**
		 * Class constructor
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( __CLASS__, 'run_frontend_hooks' ) );
		}

		/*
		 * Initialize frontend actions and filters
		 */
		public static function run_frontend_hooks() {

			if ( true === self::dependants_exist() ) {

				/* ADD FILTERS ACTIONS FUNCTION */
				add_shortcode( 'learndash-breadcrumbs', array( __CLASS__, 'learndash_breadcrumbs' ) );
				//Disable WP SEO breadcrumbs
				add_filter( 'wpseo_breadcrumb_output', array( __CLASS__, 'wpseo_learndash_breadcrumbs' ) );
			}

		}

		/**
		 * Does the plugin rely on another function or plugin
		 *
		 * @static
		 * @return mixed
		 */
		static function dependants_exist() {
			/* Checks for LearnDash */
			global $learndash_post_types;

			if ( ! isset( $learndash_post_types ) ) {
				return 'Plugin: LearnDash';
			}

			return true;
		}

		/**
		 * Description of class in Admin View
		 *
		 * @static
		 * @return mixed
		 */
		static function get_details() {
			$class_title = __( 'LearnDash Breadcrumbs', self::get_text_domain() );

			$kb_link = null;

			/* Sample Simple Description with shortcode */
			$class_description = __( 'Implement Breadcrumbs that supports courses, lessons, topics and quizzes. Also supports woocommerce, custom post types with or without taxonomies & tags, pages and blog posts. Use shortcode [learndash-breadcrumbs] or add &lt;? learndash_breadcrumbs() ?&gt; in the template.', self::get_text_domain() );

			/* Icon as font awesome icon */
			$class_icon = '<i class="uo_icon_fa fa fa-link"></i>';

			return array(
				'title'            => $class_title,
				'kb_link'          => $kb_link, // OR set as null not to display
				'description'      => $class_description,
				'dependants_exist' => self::dependants_exist(),
				'settings'         => self::get_class_settings( $class_title ),
				//'settings'         => false,
				'icon'             => $class_icon,
			);
		}


		/**
		 * HTML for modal to create settings
		 * 
		 * @param $class_title
		 *
		 * @return array (html)
		 */
		public static function get_class_settings( $class_title ) {
			$pages[]   = array( 'value' => 0, 'text' => '-- Select Page --' );
			$get_pages = get_pages(
				array(
					'sort_order'  => 'asc',
					'sort_column' => 'post_title',
				) );
			foreach ( $get_pages as $page ) {
				$pages[] = array( 'value' => $page->ID, 'text' => get_the_title( $page->ID ) );
			}
			// Create options
			$options = array(

				array(
					'type'        => 'text',
					'label'       => 'Dashboard Text',
					'option_name' => 'learn-dash-breadcrumbs-dashboard-text',
				),
				array(
					'type'        => 'select',
					'label'       => 'Dashboard Link',
					'select_name' => 'learn-dash-breadcrumbs-dashboard-link',
					'options'     => $pages,
				),
				array(
					'type'        => 'text',
					'label'       => 'Dashboard Link Separator',
					'option_name' => 'learn-dash-breadcrumbs-dashboard-separator',
				),
			);

			// Build html
			$html = self::settings_output(
				array(
					'class'   => __CLASS__,
					'title'   => $class_title,
					'options' => $options,
				) );

			return $html;
		}

		/**
		 * @return mixed
		 */
		static function wpseo_learndash_breadcrumbs() {
			return self::learndash_breadcrumbs( false );
		}

		/**
		 * @param bool $echo
		 *
		 * @return string
		 */
		public static function learndash_breadcrumbs( $echo = true ) {
			global $wp_query;
			//$wp_query = new WP_Query();

			// Define main variables
			$trail               = array();
			$trail[]             = self::lms_build_anchor_links( get_bloginfo( 'url' ), __( 'Home', self::get_text_domain() ) );
			$dashboard_link      = get_permalink( get_page_by_path( '/dashboard' ) );
			$dashboard_text      = 'Dashboard';
			$dashboard_separator = '&raquo;';

			$get_dashboard_text      = self::get_settings_value( 'learn-dash-breadcrumbs-dashboard-text', __CLASS__ );
			$get_dashboard_link      = self::get_settings_value( 'learn-dash-breadcrumbs-dashboard-link', __CLASS__ );
			$get_dashboard_separator = self::get_settings_value( 'learn-dash-breadcrumbs-dashboard-separator', __CLASS__ );

			if ( strlen( trim( $get_dashboard_text ) ) ) {
				$dashboard_text = $get_dashboard_text;
			}

			if ( strlen( trim( $get_dashboard_link ) ) ) {
				$dashboard_link = get_permalink( $get_dashboard_link );
			}

			if ( strlen( trim( $get_dashboard_separator ) ) ) {
				$dashboard_separator = $get_dashboard_separator;
			}

			$dashboard_link = self::lms_build_anchor_links( $dashboard_link, $dashboard_text );

			$lesson_id = false;


			// If it's on home page
			if ( is_front_page() ) {
				$trail = array(); //Removing Single Home link from Homepage.
			} elseif ( is_singular() ) {
				// Get singular vars (page, post, attachments)
				$post      = $wp_query->get_queried_object();
				$post_id   = absint( $wp_query->get_queried_object_id() );
				$post_type = $post->post_type;

				if ( 'post' === $post_type ) {
					$maybe_tax = self::lms_post_taxonomy( $post_id );

					if ( false !== $maybe_tax ) {
						$trail[] = $maybe_tax;
					}
					$trail[] = get_the_title( $post_id );

				} elseif ( 'page' === $post_type ) {
					// If Woocommerce is installed and being viewed, add shop page to cart, checkout pages
					if ( class_exists( 'Woocommerce' ) ) {

						if ( is_cart() || is_checkout() ) {
							// Get shop page
							if ( function_exists( 'wc_get_page_id' ) ) {
								$shop_id    = wc_get_page_id( 'shop' );
								$shop_title = get_the_title( $shop_id );
								if ( function_exists( 'wpml_object_id' ) ) {
									$shop_title = get_the_title( wpml_object_id( $shop_id, 'page' ) );
								}
								// Shop page
								if ( $shop_id && $shop_title ) {
									$trail[] = self::lms_build_anchor_links( get_permalink( $shop_id ), $shop_title );
								}
							}
						}
						$trail[] = get_the_title( $post_id );
					} else {
						// Regular pages. See if the page has any ancestors. Add in the trail if ancestors are found
						$ancestors = get_ancestors( $post_id, 'page' );
						if ( ! empty ( $ancestors ) ) {
							$ancestors = array_reverse( $ancestors );
							foreach ( $ancestors as $page ) {
								$trail[] = self::lms_build_anchor_links( get_permalink( $page ), get_the_title( $page ) );
							}
						}
						$trail[] = get_the_title( $post_id );
					}
				} elseif ( 'sfwd-courses' === $post_type ) {
					// See if Single Course is being displayed.
					$trail[] = $dashboard_link;
					$trail[] = self::lms_build_anchor_links( get_post_type_archive_link( 'sfwd-courses' ), __( 'Courses', self::get_text_domain() ) );
					$trail[] = get_the_title( $post_id );
				} elseif ( 'sfwd-lessons' === $post_type ) {
					// See if Single Lesson is being displayed.
					$course_id = get_post_meta( $post_id, 'course_id', true ); // Getting Parent Course ID
					$trail[]   = $dashboard_link;
					$trail[]   = self::lms_build_anchor_links( get_post_type_archive_link( 'sfwd-courses' ), __( 'Courses', self::get_text_domain() ) ); // Getting Main Course Page Link
					$trail[]   = self::lms_build_anchor_links( get_permalink( $course_id ), get_the_title( $course_id ) ); // Getting Lesson's Course Link
					$trail[]   = get_the_title( $post_id );
				} elseif ( 'sfwd-topic' === $post_type ) {
					// See if single Topic is being displayed
					$course_id = get_post_meta( $post_id, 'course_id', true ); // Getting Parent Course ID
					$lesson_id = get_post_meta( $post_id, 'lesson_id', true ); // Getting Parent Lesson ID
					$trail[]   = $dashboard_link;
					$trail[]   = self::lms_build_anchor_links( get_post_type_archive_link( 'sfwd-courses' ), __( 'Courses', self::get_text_domain() ) );  // Getting Main Course Page Link
					$trail[]   = self::lms_build_anchor_links( get_permalink( $course_id ), get_the_title( $course_id ) ); // Getting Lesson's Course Link
					$trail[]   = self::lms_build_anchor_links( get_permalink( $lesson_id ), get_the_title( $lesson_id ) ); // Getting Topics's Lesson Link
					$trail[]   = get_the_title( $post_id );
				} elseif ( 'sfwd-quiz' === $post_type ) {
					// See if quiz is being displayed
					$course_id = get_post_meta( $post_id, 'course_id', true ); // Getting Parent Course ID
					$trail[]   = $dashboard_link;
					$topic_id  = get_post_meta( $post_id, 'lesson_id', true ); // Getting Parent Topic/Lesson ID
					if ( 'sfwd-topic' === get_post_type( $topic_id ) ) {
						$lesson_id = get_post_meta( $topic_id, 'lesson_id', true ); // Getting Parent Lesson ID
					}
					$trail[] = self::lms_build_anchor_links( get_post_type_archive_link( 'sfwd-courses' ), __( 'Courses', self::get_text_domain() ) );  // Getting Main Course Page Link
					$trail[] = self::lms_build_anchor_links( get_permalink( $course_id ), get_the_title( $course_id ) ); // Getting Lesson's Course Link
					//If $lesson_id is false, the quiz is associated with a lesson and course but not a topic.
					if ( $lesson_id ) {
						$trail[] = self::lms_build_anchor_links( get_permalink( $lesson_id ), get_the_title( $lesson_id ) ); // Getting Topics's Lesson Link
					}
					//If $topic_id is false, the quiz is associated with a course but not associated with any lessons or topics.
					if ( $topic_id ) {
						$trail[] = self::lms_build_anchor_links( get_permalink( $topic_id ), get_the_title( $topic_id ) );
					}
					$trail[] = get_the_title( $post_id );

				} else {
					// Add shop page to single product
					if ( 'product' === $post_type ) {
						// Get shop page
						if ( class_exists( 'Woocommerce' ) && function_exists( 'wc_get_page_id' ) ) {
							$shop_id    = wc_get_page_id( 'shop' );
							$shop_title = get_the_title( $shop_id );
							if ( function_exists( 'wpml_object_id' ) ) {
								$shop_title = get_the_title( wpml_object_id( $shop_id, 'page' ) );
							}

							// Shop page
							if ( $shop_id && $shop_title ) {
								$trail[] = self::lms_build_anchor_links( get_permalink( $shop_id ), $shop_title );
							}
						}
					}

					// Getting terms of the post.
					if ( self::lms_get_taxonomy( $post_id, $post_type ) ) {
						$trail[] = self::lms_get_taxonomy( $post_id, $post_type );
					}
					$trail[] = get_the_title( $post_id );
				}
			}
			// If it's an Archive
			if ( is_archive() ) {
				//Ignore if Courses & Products
				if ( ! is_post_type_archive( 'sfwd-courses' ) && ! is_post_type_archive( 'product' ) ) {
					if ( is_category() || is_tax() ) {
						$trail[] = single_cat_title( '', false ); // If its Blog Category
					}
					if ( is_day() ) {
						$trail[] = get_the_date(); // If its Single Day Archive
					}
					if ( is_month() ) {
						$trail[] = get_the_date( __( 'F Y', self::get_text_domain() ) ) . __( ' Archives', self::get_text_domain() ); // If Mothly Archives
					}
					if ( is_year() ) {
						$trail[] = get_the_date( __( 'Y', self::get_text_domain() ) ) . __( ' Archives', self::get_text_domain() ); // If its Yearly Archives
					}
					if ( is_author() ) {
						$trail[] = get_the_author(); // If its Author's Archives
					}
				} elseif ( is_post_type_archive( 'sfwd-courses' ) ) {
					$trail[] = __( 'Courses', self::get_text_domain() );
				} elseif ( is_post_type_archive( 'product' ) ) {
					$trail[] = __( 'Shop', self::get_text_domain() );
				}
			}

			if ( is_search() ) {
				$trail[] = __( 'Search', self::get_text_domain() );
				$trail[] = get_search_query();
			}

			// Build breadcrumbs
			$classes = 'sfwd-breadcrumbs clr';

			if ( array_key_exists( 'the_content', $GLOBALS[ 'wp_filter' ] ) ) {
				$classes .= ' lms-breadcrumbs ';
			}

			// Open breadcrumbs
			$breadcrumb = '<nav class="' . esc_attr( $classes ) . '"><div class="breadcrumb-trail">';

			// Separator HTML
			$separator = '<span class="sep"> ' . $dashboard_separator . ' </span>';

			// Join all trail items into a string
			$breadcrumb .= implode( $separator, $trail );

			// Close breadcrumbs
			$breadcrumb .= '</div></nav>';

			if ( false === $echo ) {
				return $breadcrumb;
			}else{
				// Display breadcrumbs
				echo $breadcrumb;
			}
		}

		/**
		 * @param $permalink
		 * @param $title
		 *
		 * @return mixed
		 */
		public static function lms_build_anchor_links( $permalink, $title ) {

			return sprintf(
				'<span itemscope="" itemtype="http://schema.org/Breadcrumb"><a href="%1$s" title="%2$s" rel="%3$s" class="trail-begin"><span itemprop="%2$s">%4$s</span></a></span>',
				esc_url( $permalink ),
				esc_attr( $title ),
				sanitize_title( $title ),
				esc_html( $title )
			);

		}

		/**
		 * @param        $post_id
		 * @param string $taxonomy
		 *
		 * @return bool
		 */
		public static function lms_post_taxonomy( $post_id, $taxonomy = 'category' ) {
			$terms = get_the_terms( $post_id, $taxonomy );
			$t     = array();
			if ( $terms ) {
				foreach ( $terms as $term ) {
					$t[] = self::lms_build_anchor_links( get_term_link( $term->slug, $taxonomy ), $term->name );
				}

				return implode( ' / ', $t );
			} else {
				return false;
			}
		}

		/**
		 * @param $post_id
		 * @param $post_type
		 *
		 * @return bool
		 */
		public static function lms_get_taxonomy( $post_id, $post_type ) {
			$taxonomies = get_taxonomies( array( 'object_type' => array( $post_type ) ), 'objects' );
			$tax        = array();
			if ( $taxonomies ) {
				foreach ( $taxonomies as $taxonomy ) {
					// Pass the $taxonomy name to lms_post_taxonomy to return with proper terms and links
					$tax[] = self::lms_post_taxonomy( $post_id, $taxonomy->query_var );
				}

				return implode( ' / ', $tax );
			} else {
				return false;
			}
		}


		/**
		 * @return null
		 */
		public static function lms_get_the_id() {
			// If singular get_the_ID
			if ( is_singular() ) {
				return get_the_ID();
			} // Get ID of WooCommerce product archive
			elseif ( is_post_type_archive( 'product' ) && class_exists( 'Woocommerce' ) && function_exists( 'wc_get_page_id' ) ) {
				$shop_id = wc_get_page_id( 'shop' );
				if ( isset( $shop_id ) ) {
					return wc_get_page_id( 'shop' );
				}
			} // Posts page
			elseif ( is_home() && $page_for_posts = get_option( 'page_for_posts' ) ) {
				return $page_for_posts;
			} // Return nothing
			else {
				return null;
			}
			return null;
		}
	}
