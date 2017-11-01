<?php
/**
 * Class SampleTest
 *
 * @package Rooftop_Acf_Exposer
 */


/**
 * Sample test case.
 */
class SampleTest extends WP_UnitTestCase {
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
    }

    public function setUp() {
        parent::setUp();

        wp_set_current_user( 1 ); // run requests as the admin user

        global $wp_rest_server;
        $this->server = $wp_rest_server = new \WP_REST_Server;
        do_action( 'rest_api_init' );


        $this->post = $this->factory->post->create_and_get( array( 'post_title' => 'the post', 'post_meta' => array( 'some_key' => 'the value') ) );

        global $acf;
        require_once $acf->settings['path'].'/acf.php';

        // register our save_post hook
        $this->plugin = new Rooftop_Acf_Exposer_Public( "rooftop-acf-exposer", 1 );
        add_filter( 'save_post', array( $this->plugin, 'store_acf_fields'), 1, 1 );
    }

	function test_acf_field_added() {
        $data = $this->add_acf_field_to_post();

        $this->assertTrue( isset( $data['content']['advanced'][0] ) );
        $this->assertEquals( count( $data['content']['advanced'][0]['fields'] ), 1 );
    }

    function test_acf_field_updated() {
        $data = $this->add_acf_field_to_post();

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

        $_POST['advanced'] = $fields['advanced'];
        $request = new WP_REST_Request('POST', '/wp/v2/posts/'.$this->post->ID);
        $response = $this->server->dispatch( $request );
        $updated_data = $response->data;

        $updated_fields = $updated_data['content']['advanced'][0]['fields'];

        $this->assertNotEquals( $data['content']['advanced'][0]['fields'][0]['value'], $updated_fields[0]['value'] ); // field was changed
        $this->assertEquals( 1, count( $updated_fields ) ); // we still have 1 field
        $this->assertEquals( $updated_field_text_value, $updated_fields[0]['value'] ); // it was updated to the proper value
    }

    /**
     * Use the API to add a value to an existing ACF field associated with Posts
     *
     * @return array
     */
    private function add_acf_field_to_post( $key = 1508952972 ) {
        $fields = array(
            'advanced' => array(
                0 => array(
                    'fields' => array(
                        $key => array(
                            'key' => 'field_1',
                            'value' => "field value"
                        )
                    )
                )
            )
        );

        $_POST['advanced'] = $fields['advanced'];
        $request = new WP_REST_Request('POST', '/wp/v2/posts/'.$this->post->ID);
        $response = $this->server->dispatch( $request );

        return $response->data;
    }
}
