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

    /**
     * @param $response
     * @param $post
     * @param $request
     * @return mixed
     *
     * Rather than return a flat array of afc's, we want to return them in the groups specified by the site admin.
     *
     */
    public function get_acf_fields($response, $post, $request) {
        // our field values
        $custom_fields = get_fields($post->ID);

        // iterate over the acf groups
        $response->data['fields'] = array_map(function($group) use($custom_fields, $post) {
            // the response group is the container for the individual fields
            $response_group = array('title' => $group['title']);

            // get the fields that are available in this group
            $acf_fields = apply_filters('acf/field_group/get_fields', array(), $group['id']);

            // now we have a group and its fields - get the fields that correspond to this post (from the $custom_fields array)
            $response_group['fields'] = array_map(function($field_group) use($custom_fields){
                $acf_field = apply_filters('acf/load_field', $field_group, $field_group['key']);

                $response_field = array();

                $response_field['name']  = $field_group['name'];

                if(array_key_exists($field_group['name'], $custom_fields)){
                    $response_field['value'] = $custom_fields[$field_group['name']];
                }else {
                    $response_field['value'] = null;
                }

                // some fields are multi-choice, like select boxes and radiobuttons - return them too
                if(array_key_exists('choices', $acf_field)){
                    $response_field['choices'] = $acf_field['choices'];
                }

                // for fields that are 'relationships' we should return the relationship type
                $is_relationship_type = preg_match('/^(page_link|post_object|relationship|taxonomy|user)$/', $field_group['class']);
                if($is_relationship_type) {
                    if(array_key_exists('post_type', $field_group)){
                        $relationship_type  = 'post';
                        $relationship_class = is_array($field_group['post_type']) ? $field_group['post_type'][0] : $field_group['post_type'];
                    }elseif(array_key_exists('taxonomy', $field_group)) {
                        $relationship_type  = 'taxonomy';
                        $relationship_class = $field_group['taxonomy'];
                    }else {
                        $relationship_type  = $field_group['type'];
                        $relationship_class = $field_group['class'];
                    }

                    $response_field['relationship'] = array(
                        'type' => $relationship_type,
                        'class' => $relationship_class
                    );
                }

                return $response_field;
            }, $acf_fields);

            return $response_group;
        }, apply_filters('acf/get_field_groups', array()));

        return $response;
    }

}
