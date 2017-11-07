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

    private static $MAX_DEPTH = 3;

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

        $terms = get_terms( );
        foreach( $terms as $term ) {
            register_rest_field($term->taxonomy, 
                'advanced',
                array(
                    'get_callback' => array($this, 'add_fields_to_taxonomy'),
                    'update_callback' => null,
                    'schema' => null
                )
            );
        }
    }

    public function add_fields_to_taxonomy( $response, $field, $request ) {
        $term_id = $response['taxonomy'] . "_" . $response['id'];
        $term_fields = get_fields( $term_id );

        return array('fields' => $term_fields);
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

            if( empty( $data ) ) {
                $data = apply_filters( 'rooftop_acf_data', $post, array() );

                if( !empty( $data ) ) {
                    update_metadata('post', $post->ID, 'rooftop_acf_data', $data, '');
                }
            }

            $response->data['content']['advanced'] = empty( $data ) ? [] : $data;
        }catch(Exception $e) {
            error_log("Failed to get ACF fields for post: " . $e->getMessage());
        }

        return $response;
    }

    /**
     * @param $post
     * @return array
     *
     * returns the ACF fields associated with a given post
     * called by the 'add_acf_fields_to_content' callback
     *
     */
    public function get_acf_data( $post, $depth = 0 ) {
        $data = $this->acf_fields_for_post_at_depth( $post, $depth );
        return $data;
    }

    /**
     * @param $fields
     * @return array
     *
     * given a set of fields, build up the ACF fields structure of rows, repeaters and fields
     */
    private function acf_field_structure( $fields ) {
        $structure = [];

        foreach( $fields as $field ) {
            if( 'repeater' == $field['type'] ) {
                $repeater_structure = array(
                    'key' => $field['key'],
                    'name' => $field['name'],
                    'type' => $field['type'],
                    'label' => $field['label'],
                    'fields' => $this->acf_field_structure( $field['sub_fields'] )
                );
                $structure[] = apply_filters( 'rooftop/advanced_fields_structure/repeater', $repeater_structure, $field );
            }else {
                $field_structure = array('key' => $field['key'], 'name' => $field['name'], 'type' => $field['type'], 'label' => $field['label'], 'required' => ( @$field['required'] ? true : false ) );

                if( @$field['conditional_logic']['status'] ) {
                    $field_structure['conditional_logic'] = @$field['conditional_logic'];
                }

                $field_structure = apply_filters( 'rooftop/advanced_fields_structure/'.$field['type'], $field_structure, $field );
                $structure[] = $field_structure;
            }
        }

        return $structure;
    }

    /**
     * @param $server
     *
     * callback that is registered on a rest_api_init (if serving an OPTIONS request).
     * fetch the ACF fieldsets and include their schema in the 'advanced_fields_schema' attribute
     */
    function add_rooftop_acf_schema( $server ) {
        $types = get_post_types(array('public' => true));

        foreach( $types as $key => $type ) {
            $schema = $this->get_acf_structure( $type );
            register_rest_field( $type, 'advanced_fields_schema', array(
                'get_callback' => null,
                'update_callback' => null,
                'schema' => $schema[0]
            ));
        }
    }

    /**
     * @param $post_type
     * @return array
     *
     * given a post type, get the ACF field groups that can be applied to the post type and build up a valid structure.
     * this is for new posts that dont have ACF data, so we can use this as a mapping for some sort of client-side form builder
     */
    public function get_acf_structure( $post_type ) {
        $acfs = array_filter( apply_filters('acf/get_field_groups', array() ) );

        $acf_structure = [];

        // build up an array of ACF metabox_ids and only include our fieldset if
        // the current post is a valid type for the given post_type
        $filter = array(
            'post_type'	=> $post_type
        );
        $metabox_ids = array();
        $metabox_ids = apply_filters( 'acf/location/match_field_groups', $metabox_ids, $filter );

        if( is_array($acfs) ) {
            $fieldsets = array_map( function( $a ) use ( $metabox_ids ) {
                $fields = apply_filters('acf/field_group/get_fields', array(), $a['id'] );

                if( in_array( $a['id'], $metabox_ids ) ) {
                    $structured_fields = $this->acf_field_structure( $fields );
                    return array('id' => $a['id'], 'title' => $a['title'], 'fields' => $structured_fields );
                }
            }, $acfs );

            $acf_structure[] = array_values( array_filter( $fieldsets ) );
        }

        return $acf_structure;
    }

    /**
     * @return bool
     *
     * filter to work out whether we have an acf-write-enabled header
     */
    public function acf_write_enabled( ) {
        $write_enabled = array_key_exists( 'HTTP_ACF_WRITE_ENABLED', $_SERVER ) && $_SERVER['HTTP_ACF_WRITE_ENABLED'] == "true";
        return $write_enabled;
    }

    /**
     * @param $post
     * @return array
     *
     * returns the ACF fields associated with a given post
     * called by the 'add_acf_fields_to_content' callback
     *
     */

    private function acf_fields_for_post_at_depth($post, $depth) {
        $acf_fields = get_fields($post->ID);

        if( !$acf_fields ) {
            return [];
        }

        // field groups that have been associated with this post
        $post_field_groups = array_filter(get_field_objects($post->ID), function($f) {
            return $f['value'];
        });

        $field_value = array_filter($acf_fields, function($f){
            return $f !== false;
        });

        // iterate over the acf groups
        $acf_data = array_map(function($group) use($field_value, $post, $post_field_groups, $depth) {
            // the response group is the container for the individual fields
            $response_group = array('title' => $group['title'], 'id' => @$group['id']);

            $acf_fields = $this->get_acf_fields_in_group($group);

            $post_has_group = array_filter($post_field_groups, function($field_group) use($group) {
                return @$field_group['field_group'] == $group['id'];
            });

            if ( !$post_has_group ) {
                return null;
            }

            // now we have a group and its fields - get the fields that correspond to this post (from the $custom_fields array)
            $response_group['fields'] = array_map(function($acf_field) use($field_value, $depth) {
                $acf_field = apply_filters('acf/load_field', $acf_field, $acf_field['key']);

                if(array_key_exists($acf_field['name'], $field_value)) {
                    $response_value = $this->process_field($acf_field, $field_value, $depth);
                }else {
                    // we still include the field in the response so we can test `if !somefield.empty?` rather than `if response.responds_to?(:somefield) && !somefield.empty?`
                    $response_value = array('name' => $acf_field['name'], 'label' => $acf_field['label'], 'value' => "");
                }

                return $response_value;
            }, $acf_fields);

            return $response_group;
        }, apply_filters('acf/get_field_groups', array()));

        $fieldsets = array_filter($acf_data);

        return array_values($fieldsets);
    }

    /**
     * @param $acf_field
     * @param $field_values
     * @return array
     *
     * return the attributes for a given field, recursively collect the nested fields if it is a repeater
     *
     */
    private function process_field($acf_field, $field_values, $depth) {
        $response_field = array('key' => $acf_field['key'], 'name' => $acf_field['name'], 'label' => $acf_field['label'], 'class' => $acf_field['class'], 'value' => "");

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
                $relationships      = $this->prepare_post_object($field_values[$acf_field['name']], $acf_field, $depth+1);
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
            $response_field['fields'] = $this->process_repeater_field($acf_field, $field_values, $depth);
            return $response_field;
        }else {
            $response_field['value'] = apply_filters( 'rooftop_acf_field_value', $acf_field, $field_values[$acf_field['name']] );
            return $response_field;
        }
    }

    /**
     * @param $acf_field
     * @param $field_values
     * @return array
     *
     * recursively process the fields in a repeater
     *
     */
    function process_repeater_field($acf_field, $field_values, $depth) {
        $repeater_field = array();

        if($acf_field['sub_fields']) {
            foreach($acf_field['sub_fields'] as $index => $acf_sub_field) {
                foreach($field_values[$acf_field['name']] as $index => $sub_field_value) {
                    $repeater_field[$index][] = $this->process_field($acf_sub_field, $sub_field_value, $depth);
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
    private function prepare_post_object($value, $field, $depth) {
        $post_response = function($p) use($depth, $field) {
            $new_field = array(
                'ID' => $p->ID,
                'title'=>$p->post_title,
                'post_type'=>$p->post_type,
                'slug' => $p->post_name,
                'excerpt' => apply_filters('rooftop_sanitise_html', $p->post_excerpt),
                'content' => apply_filters('rooftop_sanitise_html', $p->post_content),
                'status' => $p->post_status
            );

            /*
             * Ordinarily, depth will be 1 here - however, if the user has added an ACF relationship, we should
             * also add the ACF data to the related post, but only down to a certain depth.
             *
             * If POST-A has a relationship with POST-B, which in turn has a relationship with POST-A and POST-C,
             * the ACF structure should refer to data from A to B, and B to A as well as B to C.
             * If MAX_DEPTH is set to 3, all three levels, A -> B -> C, should have ACF data.
             */
            if( $depth <= Rooftop_Acf_Exposer_Public::$MAX_DEPTH ) {
                $new_field['advanced'] = $this->get_acf_data($p, $depth);
            }

            return $new_field;
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

    /**
     * @param $acf_field
     * @param $field_value
     * @return mixed|void
     *
     * return the value of the ACF field, call the rooftop_sanitiser if it's a html field
     */
    function get_acf_field_value($acf_field, $field_value) {
        if( "wysiwyg" === $acf_field['class']) {
            return apply_filters( 'rooftop_sanitise_html', $field_value );
        }
        return $field_value;
    }

    /**
     * @param $post_id
     *
     * store the advanced fields in the POST body against the given $post_id
     */
    function store_acf_fields( $post_id ) {
        $post = get_post( $post_id );

        $dont_write_acf = apply_filters( 'rooftop_acf_write_enabled', false ) == false;

        // if we dont have a post, or it's being auto saved or trashed, skip storing anything at this point
        if( ! $post || @$_POST['data']['wp_autosave'] || in_array( $post->post_status, array( 'auto-draft', 'trash' ) ) || $dont_write_acf ) {
            return;
        }

        if( is_array( @$_POST['content']['advanced'] ) ) {
            foreach( $_POST['content']['advanced'] as $fieldset ) {
                $posted_fields = $fieldset['fields'];
                $flattened_fields = $this->flattened_acf_fields( $posted_fields );

                foreach( $flattened_fields as $index => $field ) {
                    foreach( $field as $key => $value ) {
                        update_field( $key, $value, $post_id );
                    }
                }
            }
        }
    }

    /**
     * @param $fields
     * @param $key
     * @return array
     *
     * recursive method for collecting the key/value/fields of a given fieldset
     */
    function sub_fields( $fields, $key ) {
        $nested_fields = [];

        foreach( $fields as $field_index => $field ) {
            foreach( $field as $index => $sub_field ) {
                if( array_key_exists('fields', $sub_field ) ) {
                    $nested = $this->sub_fields( $sub_field['fields'], $sub_field['key'] );
                    $nested_sub_field = array_merge( $nested_fields[$key][$field_index], $nested );

                    $nested_fields[$key][$field_index] = $nested_sub_field;
                }else {
                    $nested_fields[$key][$field_index][$sub_field['key']] = $sub_field['value'];
                }
            }
        }

        return $nested_fields;
    }

    /**
     * @param $fields
     * @return array
     *
     * collect the fields and values depending on whether we have a key:value, key:repeater, or key:repeater:[key:repeater...] structure
     */
    function flattened_acf_fields( $fields ) {
        $flattened_fields = [];

        foreach( $fields as $key => $value ) {
            if( $this->is_a_repeater_with_nested_repeaters( $value ) ) {
                $flattened_fields[] = $this->sub_fields( $value['fields'], $value['key'] );
            }elseif( $this->is_a_repeater_with_values( $value ) ) {
                $flattened_fields[] = $this->sub_fields( $value['fields'], $value['key'] );
            }else {
                $flattened_fields[] = array($value['key'] => @$value['value']); // single non-repeating field values. todo: check for a field_* key name and build an acfcloneindex object as the value
            }
        }

        return $flattened_fields;
    }

    /**
     * @param $value
     * @return bool
     *
     * work out whther we have a repeater with other repeaters nested
     */
    function is_a_repeater_with_nested_repeaters( $value ) {
        $nested_value = @$value['fields'];

        $is_array = is_array( $nested_value );
        if( !$is_array ) return false;

        $fields_with_sub_fields = array_filter( $nested_value, function( $sub_field ) {
            $matching = [];
            foreach( $sub_field as $_field ) {
                if( array_key_exists( 'fields', $_field ) ) {
                    $matching[] = $_field;
                }
            }

            return $matching;
        } );

        return count( $fields_with_sub_fields ) ? true : false;
    }

    /**
     * @param $value
     * @return bool
     *
     * work out if this is a repeater with a set of values
     */
    function is_a_repeater_with_values( $value ) {
        $types = array_unique( array_map( function( $i) {return gettype( $i ); }, array_values( $value ) ) );
        $has_arrays = count( preg_grep( '/array/', $types ) ) > 0;
        $has_fields = array_key_exists( 'fields', $value );

        $fields = @$value['fields'];

        if( !$fields ) return false;

        $all_rows_have_values = false;
        foreach( $fields as $field ) {
            $values = array_values( $field );
            $field_values = array_map( function( $i ) {return @$i['value']; }, $values );

            $all_rows_have_values = count( $field ) == count( array_filter( $field_values ) );
        }

        return $has_arrays && $has_fields && $all_rows_have_values;
    }
}

/**
 * filters for building out our ACF schema attributes.
 */

add_filter( 'rooftop/advanced_fields_structure/select', function( $structure, $field ) {
    $structure['field_options'] = array(
        'choices' => $field['choices'],
        'default_value' => $field['default_value'],
        'allow_null' => $field['allow_null'],
        'multiple' => $field['multiple']
    );
    return $structure;
}, 2, 2 );

add_filter( 'rooftop/advanced_fields_structure/checkbox', function( $structure, $field ) {
    $structure['field_options'] = array(
        'choices' => $field['choices'],
        'default_value' => $field['default_value']
    );
    return $structure;
}, 2, 2 );

add_filter( 'rooftop/advanced_fields_structure/repeater', function( $structure, $field ) {
    $structure['repeater_options'] = array(
        'row_min' => @$field['row_min'] || null,
        'row_limit' => @$field['row_limit'] || null,
    );

    return $structure;
}, 2, 2 );

add_filter( 'rooftop/advanced_fields_structure/relationship', function( $structure, $field ) {
    $structure['field_options'] = array(
        'return_format' => $field['return_format'],
        'post_type' => $field['post_type'],
        'taxonomy' => $field['taxonomy'],
        'max' => @$field['max'] || null
    );

    return $structure;
}, 2, 2 );

add_filter( 'rooftop/advanced_fields_structure/taxonomy', function( $structure, $field ) {
    $structure['field_options'] = array(
        'field_type' => $field['field_type'],
        'taxonomy' => $field['taxonomy']
    );

    return $structure;
}, 2, 2 );
