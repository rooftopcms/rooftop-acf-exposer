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

    function prepare_acf_hooks() {
        // register hooks for specific post types
        $types = get_post_types(array('public' => true));

        foreach($types as $key => $type) {
            add_action( "rest_prepare_$type", array( $this, 'add_acf_fields_to_content' ), 20, 3 );
        }
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
    public function add_acf_fields_to_content($response, $post, $request) {
        try {
            $data = get_post_meta( $post->ID, 'rooftop_acf_data', true );
            $response->data['content']['advanced'] = empty( $data ) ? [] : $data;
        }catch(Exception $e) {
            error_log("Failed to get ACF fields for post: " . $e->getMessage());
        }

        return $response;
    }

}
