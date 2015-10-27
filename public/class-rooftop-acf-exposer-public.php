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
    public function add_acf_fields_to_content() {
        register_api_field('post', 'fieldsets', array(
            'get_callback' => array($this, 'add_acf_to_post'),
            'update_callback' => null,
            'schema' => null
        ));
        register_api_field('page', 'fieldsets', array(
            'get_callback' => array($this, 'add_acf_to_post'),
            'update_callback' => null,
            'schema' => null
        ));

        $custom_types = get_post_types(array('public' => true, '_builtin' => false));
        foreach($custom_types as $key => $type) {
            register_api_field($type, 'fieldsets', array(
                'get_callback' => array($this, 'add_acf_to_post'),
                'update_callback' => null,
                'schema' => null
            ));
        }
    }

    public function add_acf_to_post($object, $fieldname, $request) {
        $acf_fields = get_fields($object['id']);

        if(!$acf_fields){
            return [];
        }

        $field_value = array_filter($acf_fields, function($f){
            return $f !== false;
        });

        // iterate over the acf groups
        $acf_data = array_map(function($group) use($field_value, $object) {
            // the response group is the container for the individual fields
            $response_group = array('title' => $group['title']);

            $acf_fields = $this->get_acf_fields_in_group($group);

            // now we have a group and its fields - get the fields that correspond to this post (from the $custom_fields array)
            $response_group['fields'] = array_map(function($acf_field) use($field_value){
                $acf_field = apply_filters('acf/load_field', $acf_field, $acf_field['key']);

                if(array_key_exists($acf_field['name'], $field_value)) {
                    $response_value = $this->process_field($acf_field, $field_value);
                }else {
                    // we still include the field in the response so we can test `if !somefield.empty?` rather than `if response.responds_to?(:somefield) && !somefield.empty?`
                    $response_value = array('name' => $acf_field['name'], 'label' => $acf_field['label'], 'value' => "");
                }

                return $response_value;
            }, $acf_fields);

            return $response_group;
        }, apply_filters('acf/get_field_groups', array()));

        return $acf_data;
    }

    function process_field($acf_field, $field_values) {
        $response_field = array('name' => $acf_field['name'], 'label' => $acf_field['label'], 'value' => "");

        // some fields are multi-choice, like select boxes and radiobuttons - return them too
        if(array_key_exists('choices', $acf_field)){
            $response_field['choices'] = $acf_field['choices'];
            $response_field['class']   = $acf_field['class'];
        }

        // for fields that are 'relationships' we should return the relationship type along with the value
        $is_relationship_type = preg_match('/^(page_link|post_object|relationship|taxonomy|user)$/', $acf_field['class']);
        if($is_relationship_type) {
            if(array_key_exists('post_type', $acf_field)) {
                $relationship_type  = 'post';
                $relationship_class = is_array($acf_field['post_type']) ? $acf_field['post_type'][0] : $acf_field['post_type'];
                $relationships      = $this->prepare_post_object($field_values[$acf_field['name']], $acf_field);
            }elseif(array_key_exists('taxonomy', $acf_field)) {
                $relationship_type  = 'taxonomy';
                $relationship_class = $acf_field['taxonomy'];
                $relationships      = $this->prepare_taxonomy_object($field_values[$acf_field['name']], $acf_field);
            }else {
                $relationship_type  = $acf_field['type'];
                $relationship_class = $acf_field['class'];
                $relationships      = $field_values[$acf_field['name']];
            }

            $response_field['relationship'] = array(
                'type' => $relationship_type,
                'class' => $relationship_class
            );
            $response_field['value'] = $relationships;

            return $response_field;
        }elseif('repeater' == $acf_field['class']) {
            unset($response_field['value']);
            $response_field['fields'] = $this->process_repeater_field($acf_field, $field_values);
            return $response_field;
        }else {
            $response_field['value'] = $field_values[$acf_field['name']];
            return $response_field;
        }
    }

    function process_repeater_field($acf_field, $field_values) {
        $repeater_field = array();

        if($acf_field['sub_fields']) {
            foreach($acf_field['sub_fields'] as $index => $acf_sub_field) {
                foreach($field_values[$acf_field['name']] as $index => $sub_field_value) {
                    $repeater_field[$index][] = $this->process_field($acf_sub_field, $sub_field_value);
                }
            }
            return $repeater_field;
        }else {
            $repeater_field['value'] = $field_values[$acf_field['name']];
            return $repeater_field;
        }
    }

    /**
     * @param $group
     * @return array|mixed|void
     *
     * return the fields that a user has added to a specific ACF group
     *
     */
    private function get_acf_fields_in_group($group){
        // get the fields that are available in this group
        $acf_fields = apply_filters('acf/field_group/get_fields', array(), $group['id']);

        // some fields aren't intended for front-end rendering, like tabs and messages
        $acf_fields = array_filter($acf_fields, function($f){
            return !in_array($f['class'], array('tab', 'message'));
        });

        return $acf_fields;
    }

    /**
     * @param $value
     * @param $field
     * @return array
     *
     * given a WP_Post or array of WP_Post objects, return a trimmed down version of the post as an array
     * If the value isn't an object, the user has specified their ACF field should return the object ID's
     *
     */
    private function prepare_post_object($value, $field) {
        $post_response = function($p){
            return array(
                'ID' => $p->ID,
                'title'=>$p->post_title,
                'post_type'=>$p->post_type,
                'slug' => $p->post_name,
                'excerpt' => $p->post_excerpt,
                'status' => $p->post_status
            );
        };

        if(is_array($value) && is_object(array_values($value)[0])){
            return array_map($post_response, $value);
        }elseif(is_object($value)){
            return $post_response($value);
        }else {
            return $value;
        }
    }

    /**
     * @param $value
     * @param $field
     * @return array
     *
     * given an array of taxonomy objects, return a trimmed down version of the object as an array
     * If the value isn't an object, the user has specified their ACF field should return the object ID's
     *
     */
    private function prepare_taxonomy_object($value, $field) {
        $taxonomy_response = function($t){
            return array(
                'name'=>$t->name,
                'taxonomy'=>$t->taxonomy,
                'term_id'=>$t->term_id,
                'term_taxonomy_id'=>$t->term_taxonomy_id,
                'name'=>$t->name,
                'description'=>$t->description,
                'parent'=>$t->parent);
        };

        if(is_array($value) && is_object(array_values($value)[0])){
            return array_map($taxonomy_response, $value);
        }elseif(is_object($value)){
            return $taxonomy_response($value);
        }else {
            return $value;
        }
    }
}
