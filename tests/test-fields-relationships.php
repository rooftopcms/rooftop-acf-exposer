<?php
/**
 * Class SampleTest
 *
 * @package Rooftop_Acf_Exposer
 */


/**
 * Sample test case.
 */
class TestRelationships extends WP_UnitTestCase {
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
                'type' => 'relationship',
                'return_format' => 'object',
                'post_type' => array (
                    0 => 'all',
                ),
                'taxonomy' => array (
                    0 => 'all',
                ),
                'filters' => array (
                    0 => 'search',
                ),
                'result_elements' => array (
                    0 => 'post_type',
                    1 => 'post_title',
                ),
                'max' => ''
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

    /**
     *
     */
    public function setUp() {
        parent::setUp();

        wp_set_current_user( 1 ); // run requests as the admin user

        global $wp_rest_server;
        $this->server = $wp_rest_server = new \WP_REST_Server;
        do_action( 'rest_api_init' );

        $this->post1 = $this->factory->post->create_and_get( array( 'post_title' => 'the post' ) );
        $this->post2 = $this->factory->post->create_and_get( array( 'post_title' => 'first page' ) );

        global $acf;
        require_once $acf->settings['path'].'/acf.php';

        // register our save_post hook
        $this->plugin = new Rooftop_Acf_Exposer_Public( "rooftop-acf-exposer", 1 );
        add_filter( 'save_post', array( $this->plugin, 'store_acf_fields'), 1, 1 );
    }

	function test_acf_field_added() {
        $data = $this->add_acf_field_to_post();

        $fields = $data['content']['advanced'][0]['fields'];

        $this->assertEquals( count( $fields ), 1 ); // test we've added the field
        $this->assertTrue( is_array( $fields[0]['value'] ) ) ; // we're returning an object (as defined in our register_field_group call)
        $this->assertEquals( $fields[0]['value'][0]['ID'], $this->post2->ID ); // we've set the related post value
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
                            'value' => (string)$this->post2->ID
                        )
                    )
                )
            )
        );

        $_POST['advanced'] = $fields['advanced'];
        $request = new WP_REST_Request('POST', '/wp/v2/posts/'.$this->post1->ID );
        $response = $this->server->dispatch( $request );

        return $response->data;
    }
}
