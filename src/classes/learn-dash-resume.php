<?php

namespace uncanny_learndash_toolkit;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class LearnDashResume extends Config implements RequiredFunctions {

	static $topic_type = 'sfwd-topic';


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
			add_action( 'wp_head', array( __CLASS__, 'find_last_known_learndash_page' ) );
			add_shortcode( 'uo-learndash-resume', array( __CLASS__, 'learndash_resume' ) );
			add_shortcode( 'uo_learndash_resume', array( __CLASS__, 'learndash_resume' ) );
		}

	}

	/**
	 * Does the plugin rely on another function or plugin
	 *
	 * return boolean || string Return either true or name of function or plugin
	 */
	public static function dependants_exist() {
		global $learndash_post_types;
		if ( ! isset( $learndash_post_types ) ) {
			return 'Plugin: LearnDash';
		}

		return true;
	}

	/**
	 * Description of class in Admin View
	 *
	 * @return array
	 */
	public static function get_details() {

		$class_title       = esc_html__( 'LearnDash Resume Button', 'uncanny-learndash-toolkit' );
		$kb_link           = 'https://www.uncannyowl.com/knowledge-base/learndash-resume/';
		$class_description = esc_html__( 'Inserts a button that allows learners to return to the course, lesson or topic they last visited.', 'uncanny-learndash-toolkit' );
		$class_icon        = '<i class="uo_icon_fa fa fa-refresh"></i>';
		$tags              = 'learndash';
		$type              = 'free';

		return array(
			'title'            => $class_title,
			'type'             => $type,
			'tags'             => $tags,
			'kb_link'          => $kb_link,
			'description'      => $class_description,
			'dependants_exist' => self::dependants_exist(),
			'settings'         => self::get_class_settings( $class_title ),
			'icon'             => $class_icon,
		);

	}

	/**
	 * HTML for modal to create settings
	 *
	 * @static
	 *
	 * @param $class_title
	 *
	 * @return string
	 */
	public static function get_class_settings( $class_title ) {

		// Create options
		$options = array(

			array(
				'type'        => 'text',
				'label'       => esc_html__( 'Resume Button Text', 'uncanny-learndash-toolkit' ),
				'option_name' => 'learn-dash-resume-button-text',
			)

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
	 *Adding wp_head action so that we capture the type of post / page user is on and add that to wordpress options table.
	 *
	 * @static
	 */
	public static function find_last_known_learndash_page() {

		$user = wp_get_current_user();

		if ( is_user_logged_in() ) {

			/* declare $post as global so we get the post->ID of the current page / post */
			global $post;
			/* Limit the plugin to LearnDash specific post types */
			$learn_dash_post_types = apply_filters(
				'last_known_learndash_post_types',
				array(
					'sfwd-courses',
					'sfwd-lessons',
					'sfwd-topic',
					'sfwd-quiz',
					'sfwd-certificates',
					'sfwd-assignment',
				)
			);

			$step_id = $post->ID;
			$step_course_id = learndash_get_course_id($step_id);

			if( empty( $step_course_id ) ){
				$step_course_id = 0;
			}

			if ( is_singular( $learn_dash_post_types ) ) {
				update_user_meta( $user->ID, 'learndash_last_known_page', $step_id .','. $step_course_id );
			}

		}
	}


	/**
	 *Adding [uo-learndash-resume] shortcode functionality which can be used anywhere on the website to take user back to last known page of LearnDash.
	 *
	 * @static
	 * @return string
	 */
	public static function learndash_resume() {

		$user = wp_get_current_user();

		if ( is_user_logged_in() ) {

			$last_know_step     = get_user_meta( $user->ID, 'learndash_last_known_page', true );

			// User has not hit a LD module yet
			if( empty($last_know_step)){

				return 'testing: not hit';
			}

			$step_course_id = 0;

			if( false !== strpos($last_know_step, ',') ){
				$last_know_step = explode(',', $last_know_step);
				$step_id = $last_know_step[0];
				$step_course_id = $last_know_step[1];
			}else{

				// Sanity Check
				if( absint($last_know_step)){
					$step_id = $last_know_step;
				}else{
					return 'testing: sanity check';
				}

			}

			$last_know_post_object = get_post( $step_id );

			// Make sure the post exists and that the user hit a page that was a post
			// if $last_know_page_id returns '' then get post will return current pages post object
			// so we need to make sure first that the $last_know_page_id is returning something and
			// that the something is a valid post
			if ( null !== $last_know_post_object ) {

				$post_type        = $last_know_post_object->post_type; // getting post_type of last page.
				$label            = get_post_type_object( $post_type ); // getting Labels of the post type.
				$title            = $last_know_post_object->post_title;
				$resume_link_text = 'RESUME';

				// Resume Link Text
				$link_text = self::get_settings_value( 'learn-dash-resume-button-text', __CLASS__ );

				if ( strlen( trim( $link_text ) ) ) {
					$resume_link_text = $link_text;
				}

				$resume_link_text = apply_filters( 'learndash_resume_link_text', $resume_link_text );

				$css_classes = apply_filters( 'learndash_resume_css_classes', 'learndash-resume-button' );

				ob_start();

				printf(
					'<a href="%s" title="%s" class="%s"><input type="submit" value="%s" class=""></a>',
					learndash_get_step_permalink( $step_id, $step_course_id ),
					esc_attr(
						sprintf(
							esc_html_x( 'Resume %s: %s', 'LMS shortcode Resume link title "Resume post_type_name: Post_title ', 'uncanny-learndash-toolkit' ),
							$label->labels->singular_name,
							$title
						)
					),
					esc_attr( $css_classes ),
					esc_attr( $resume_link_text )
				);

				$resume_link = ob_get_contents();
				ob_end_clean();

				return $resume_link;
			}

		}

		return 'testing: not logged in';
	}
}
