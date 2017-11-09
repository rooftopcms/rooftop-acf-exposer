<?php
/**
 * Class SampleTest
 *
 * @package Rooftop_Acf_Exposer
 */


/**
 * Sample test case.
 */
class TestFieldsReturned extends WP_UnitTestCase {
    static function setUpBeforeClass() {
        activate_plugin('advanced-custom-fields/acf.php');

        register_field_group(array(
            'key' => 'group_1',
            'title' => 'group 1',
            'id' => 'group_1',
            'field_group' => 'group_1',
            'fields' => array(array(
                'group' => 'group_1',
                'field_group' => 'group_1',
                'key' => 'field_1',
                'label' => 'Sub field',
                'name' => 'sub_field',
                'type' => 'text',
                'prefix' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'css' => '',
                    'id' => ''
                ),
                'default_value' => '',
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'maxlength' => '',
                'readonly' => 0,
                'disabled' => 0
            )),
            'location' => array (
                array (
                    array (
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ),
                ),
            ),
            'menu_order' => 0,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'hide_on_screen' => ''
        ));

        // manually add our callback actions (these aren't automatically called in a test environment)
        $plugin = new Rooftop_Acf_Exposer_Public( "rooftop-acf-exposer", 1 );
        add_action( 'rest_api_init', array( $plugin, 'add_rooftop_acf_schema' ), 1 );
        add_filter( 'save_post', array( $plugin, 'store_acf_fields' ), 1, 1 );
    }

    public function setUp() {
        parent::setUp();

        wp_set_current_user( 1 ); // run requests as the admin user

        global $wp_rest_server;
        $this->server = $wp_rest_server = new \WP_REST_Server;
        do_action( 'rest_api_init' ); // initialize our $server

        // add a default post
        $this->post = $this->factory->post->create_and_get( array( 'post_title' => 'the post', 'post_meta' => array( 'some_key' => 'the value') ) );

        global $acf;
        require_once $acf->settings['path'].'/acf.php';
    }

    function test_schema_added() {
        $request = new WP_REST_Request('OPTIONS', '/wp/v2/posts' );
        $response = $this->server->dispatch( $request );

        $schema = $response->data['schema']['properties']['advanced_fields_schema'];

        $this->assertEquals( 1, count( array_keys( $schema ) ) ); // advanced fields fieldgroup was added
        $this->assertTrue( array_key_exists( 'fields', $schema[0] ) ); // fieldset exists
        $this->assertTrue( array_key_exists( 'name', $schema[0]['fields'][0] ) ); // sub-fields exists
        $this->assertEquals( 'sub_field', $schema[0]['fields'][0]['name'] ); // sub-field 1 has a name attribute
    }

	function test_acf_field_added() {
        $data = $this->add_acf_field_to_post( );

        $this->assertTrue( isset( $data['content']['advanced'][0] ) ); // advanced fields exists in the post
        $this->assertEquals( count( $data['content']['advanced'][0]['fields'] ), 1 ); // we have added 1 field
    }

    function test_acf_field_updated() {
        $data = $this->add_acf_field_to_post( );

        $updated_field_text_value = "the updated value";
        $fields = array(
            'advanced' => array(
                0 => array(
                    'fields' => array(
                        0 => array(
                            'key' => 'field_1',
                            'value' => $updated_field_text_value
                        )
                    )
                )
            )
        );

        $_POST['content'] = $fields;
        $request = new WP_REST_Request('POST', '/wp/v2/posts/'.$this->post->ID);
        $response = $this->server->dispatch( $request );
        $updated_data = $response->data;

        $updated_fields = $updated_data['content']['advanced'][0]['fields'];

        $this->assertNotEquals( $data['content']['advanced'][0]['fields'][0]['value'], $updated_fields[0]['value'] ); // field was changed
        $this->assertEquals( 1, count( $updated_fields ) ); // we still have 1 field
        $this->assertEquals( $updated_field_text_value, $updated_fields[0]['value'] ); // it was updated to the proper value
    }

    function test_acf_not_written_unless_header_set() {
        $data = $this->add_acf_field_to_post( array( 'HTTP_ACF_WRITE_ENABLED' => false ) );
        $this->assertFalse( isset( $data['content']['advanced'][0] ) ); // advanced fields weren't added
    }

    /**
     * @param array $headers - the $_SERVER headers to include in the request. defaults to headers that allow writing to ACF
     * @return mixed - data attribute from request response
     */
    private function add_acf_field_to_post( $headers = array( 'HTTP_ACF_WRITE_ENABLED' => true ) ) {
        // we have to manually set _POST and _SERVER data...

        foreach( $headers as $header => $value ) {
            $_SERVER[$header] = $value;
        }

        $fields = array(
            'advanced' => array(
                0 => array(
                    'fields' => array(
                        0 => array(
                            'key' => 'field_1',
                            'value' => "field value"
                        )
                    )
                )
            )
        );

        $_POST['content'] = $fields;
        $request = new WP_REST_Request('POST', '/wp/v2/posts/'.$this->post->ID);
        $request->set_header( 'acf-write-enabled', true );
        $response = $this->server->dispatch( $request );

        return $response->data;
    }
}
