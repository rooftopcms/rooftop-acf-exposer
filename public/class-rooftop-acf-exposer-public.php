<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://errorstudio.co.uk
 * @since      1.0.0
 *
 * @package    Rooftop_Acf_Exposer
 * @subpackage Rooftop_Acf_Exposer/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Rooftop_Acf_Exposer
 * @subpackage Rooftop_Acf_Exposer/public
 * @author     Error <info@errorstudio.co.uk>
 */
class Rooftop_Acf_Exposer_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rooftop_Acf_Exposer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rooftop_Acf_Exposer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rooftop-acf-exposer-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Rooftop_Acf_Exposer_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Rooftop_Acf_Exposer_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rooftop-acf-exposer-public.js', array( 'jquery' ), $this->version, false );

	}

    public function get_acf_fields($response, $post, $request) {

        global $wpdb;

        $custom_fields = get_fields($post->ID);
        $response->data['fields'] = $custom_fields;

//        $field_group_ids = array_unique(array_values(array_map(function($acf_group){
//            return $acf_group['field_group'];
//        }, get_field_objects($post->ID))));
//
//        $acf_groups = array();
//        foreach($field_group_ids as $field_group_id){
//            $field = array();
//            $acf_field_group = apply_filters('acf/field_group/get_fields', array(), $field_group_id);
//            $field['key'] = $acf_field_group[0]['key'];
//            $field['name'] = $acf_field_group[0]['name'];
//            $field['label'] = $acf_field_group[0]['label'];
//            $field['fields'] = array_map(function($field){
//                $f = array();
//                $f['class'] = $field['class'];
//                return $f;
//            }, $acf_field_group);
//            $acf_groups[] = $field;
//
//        }

        return $response;
    }

}
