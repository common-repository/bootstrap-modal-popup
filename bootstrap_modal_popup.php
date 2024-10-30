<?php

/*
  Plugin Name: Bootstrap modal popup
  Plugin Script: bootstrap_modal_popup.php
  Plugin URI: http://uanet.se/bootstrap_modal_popup
  Description: (...)
  Version: 0.4
  Author: Rikard
  Author URI: http://www.uanet.se

  === RELEASE NOTES ===
  2014-07-25 - v0.1 - first version
  2014-08-04 - v0.2 - Solved bug when logged in as admin
  2014-08-05 - v0.3 - Added screenshots
  2014-09-16 - v0.4 - Tested with WP 4.0
 */




/**
 * On activation, add default options
 */
function bmp_activate() {

    $optionArray = array('title' => 'Look at me',
                         'timeout' => 3,
                         'scroll' => 300,
                         'bmp_message' => 'Behöver ni hjälp med er hemsida? Kontakta www.uanet.se',
                         'cookie_timeout' => 86400);
    
    
    if( !get_option( 'bmp_options' ) ) {
        add_option( 'bmp_options', maybe_serialize($optionArray) );
    }
}
register_activation_hook( __FILE__, 'bmp_activate' );

/**
 * Do the cookie stuff
 */
function set_bmp_cookie() {
    
    $options = get_option( 'bmp_options' );
    
    if ( $options['cookie_timeout'] > 0) {
        setcookie('bmp_cookie', 1, time()+($options['cookie_timeout']));
    }
}
add_action( 'init', 'set_bmp_cookie');


function bmp_footer_html() {
    
    $options = get_option( 'bmp_options' );
    
    //-- Check on cookie first
    if (!isset($_COOKIE['bmp_cookie']) || $options['cookie_timeout'] == 0) {
        ?>
            <!-- Modal -->
            <div class="modal fade" id="bootstrap_modal_popup" tabindex="-1" role="dialog" aria-labelledby="bootstrap_modal_popupLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"><?php echo (isset( $options['close_text'] ) ? esc_attr( $options['close_text']) : 'Close'); ?></span></button>
                    <h4 class="modal-title" id="bootstrap_modal_popupLabel"><?php echo $options['title']; ?></h4>
                  </div>
                  <div class="modal-body">
                    <?php echo $options['bmp_message']; ?>
                  </div>
                  <div class="modal-footer">
                    <?php if(!empty($options['readmore_link'])): ?>
                    <a class="btn btn-success" href="<?php echo $options['readmore_link']; ?>"><?php echo (isset( $options['readmore_text'] ) ? esc_attr( $options['readmore_text']) : 'Read more'); ?></a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-primary" data-dismiss="modal"><?php echo (isset( $options['close_text'] ) ? esc_attr( $options['close_text']) : 'Close'); ?></button>
                  </div>
                </div>
              </div>
            </div>

            <script>
                jQuery(window).load(function(){


                    <?php if($options['timeout'] > 0): ?>
                        setTimeout(function(){
                            if(!jQuery('#bootstrap_modal_popup').hasClass('donotopen')) {
                                jQuery('#bootstrap_modal_popup').modal('show');
                                jQuery('#bootstrap_modal_popup').addClass('donotopen');
                            }
                        }, <?php echo $options['timeout'] * 1000; ?>);
                    <?php endif; ?>


                    <?php if($options['scroll'] > 0): ?>
                        jQuery(window).scroll(function() {
                            if ($(window).scrollTop() > <?php echo $options['scroll']; ?>) {
                                if(!jQuery('#bootstrap_modal_popup').hasClass('donotopen')) {
                                    jQuery('#bootstrap_modal_popup').modal('show');
                                    jQuery('#bootstrap_modal_popup').addClass('donotopen');
                                }
                            }
                        });                    
                    <?php endif; ?>


               });
           </script>
        <?php
    }
}
add_action('wp_footer', 'bmp_footer_html');




class bmpSettings
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'Settings for Bootstrap modal popup', 
            'Bootstrap modal popup', 
            'manage_options', 
            'bmp-setting-admin', 
            array( $this, 'create_admin_page' )
        );
    }

    /**
     * Options page callback
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option( 'bmp_options' );
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Settings for Bootstrap modal popup</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'my_option_group' );   
                do_settings_sections( 'bmp-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'my_option_group', // Option group
            'bmp_options', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'setting_section_id', // ID
            'Behavior settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'bmp-setting-admin' // Page
        );  

        add_settings_field(
            'timeout', // ID
            'Timeout in sec (0 = inactive)', // Title 
            array( $this, 'timeout_callback' ), // Callback
            'bmp-setting-admin', // Page
            'setting_section_id' // Section           
        );    

        add_settings_field(
            'scroll', // ID
            'scroll in px (0 = inactive)', // Title 
            array( $this, 'scroll_callback' ), // Callback
            'bmp-setting-admin', // Page
            'setting_section_id' // Section           
        );      

        add_settings_field(
            'close_text', 
            'Close text', 
            array( $this, 'close_text_callback' ), 
            'bmp-setting-admin', 
            'setting_section_id'
        );    

        add_settings_field(
            'readmore_text', 
            'Readmore text', 
            array( $this, 'readmore_text_callback' ), 
            'bmp-setting-admin', 
            'setting_section_id'
        );    

        add_settings_field(
            'readmore_link', 
            'Readmore link (Empty = inactive)', 
            array( $this, 'readmore_link_callback' ), 
            'bmp-setting-admin', 
            'setting_section_id'
        );       

        add_settings_field(
            'cookie_timeout', 
            'Cookie timeout in sec. (0 = inactive)', 
            array( $this, 'cookie_timeout_callback' ), 
            'bmp-setting-admin', 
            'setting_section_id'
        );      
        

        add_settings_section(
            'messade_settings', // ID
            'Message settings', // Title
            array( $this, 'print_message_section_info' ), // Callback
            'bmp-setting-admin' // Page,
        );         

        add_settings_field(
            'title', 
            'Title', 
            array( $this, 'title_callback' ), 
            'bmp-setting-admin', 
            'messade_settings'
        );  
        

        add_settings_field(
            'bmp_message', // ID
            'Message', // Title
            array( $this, 'bmp_message_callback' ), // Callback
            'bmp-setting-admin',
            'messade_settings'
        ); 
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['timeout'] ) )
            $new_input['timeout'] = absint( $input['timeout'] );
        
        if( isset( $input['scroll'] ) )
            $new_input['scroll'] = absint( $input['scroll'] );

        if( isset( $input['title'] ) )
            $new_input['title'] = sanitize_text_field( $input['title'] );

        if( isset( $input['close_text'] ) )
            $new_input['close_text'] = sanitize_text_field( $input['close_text'] );
        
        if( isset( $input['readmore_text'] ) )
            $new_input['readmore_text'] = sanitize_text_field( $input['readmore_text'] );
        
        if( isset( $input['readmore_link'] ) )
            $new_input['readmore_link'] = sanitize_text_field( $input['readmore_link'] );
        
        if( isset( $input['cookie_timeout'] ) )
            $new_input['cookie_timeout'] = sanitize_text_field( $input['cookie_timeout'] );

        if( isset( $_POST['bmp_message'] ) )
            $new_input['bmp_message'] = wp_kses( $_POST['bmp_message'] , true );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info()
    {
        print 'How the plugin will work';
    }

    /** 
     * Print the print_message_section_info
     */
    public function print_message_section_info()
    {
       
    }

    /** 
     * bmp_message_callback
     */
    public function bmp_message_callback()
    {
        $content = isset( $this->options['bmp_message'] ) ? ( $this->options['bmp_message']) : '';
        wp_editor( $content, 'bmp_message' );
    }

    /** 
     * timeout_callback
     */
    public function timeout_callback()
    {
        printf(
            '<input type="text" id="timeout" name="bmp_options[timeout]" value="%s" />',
            isset( $this->options['timeout'] ) ? esc_attr( $this->options['timeout']) : ''
        );
    }

    /** 
     * scroll_callback
     */
    public function scroll_callback()
    {
        printf(
            '<input type="text" id="scroll" name="bmp_options[scroll]" value="%s" />px',
            isset( $this->options['scroll'] ) ? esc_attr( $this->options['scroll']) : ''
        );
    }

    /** 
     * title_callback
     */
    public function title_callback()
    {
        printf(
            '<input type="text" id="title" name="bmp_options[title]" value="%s" />',
            isset( $this->options['title'] ) ? esc_attr( $this->options['title']) : ''
        );
    }

    /** 
     * close_text_callback
     */
    public function close_text_callback()
    {
        printf(
            '<input type="text" id="close_text" name="bmp_options[close_text]" value="%s" />',
            isset( $this->options['close_text'] ) ? esc_attr( $this->options['close_text']) : ''
        );
    }

    /** 
     * readmore_text_callback
     */
    public function readmore_text_callback()
    {
        printf(
            '<input type="text" id="readmore_text" name="bmp_options[readmore_text]" value="%s" />',
            isset( $this->options['readmore_text'] ) ? esc_attr( $this->options['readmore_text']) : ''
        );
    }

    /** 
     * readmore_link_callback
     */
    public function readmore_link_callback()
    {
        printf(
            '<input type="text" id="readmore_link" name="bmp_options[readmore_link]" value="%s" />',
            isset( $this->options['readmore_link'] ) ? esc_attr( $this->options['readmore_link']) : ''
        );
    }

    /** 
     * cookie_timeout_callback
     */
    public function cookie_timeout_callback()
    {
        printf(
            '<input type="text" id="cookie_timeout" name="bmp_options[cookie_timeout]" value="%s" />',
            isset( $this->options['cookie_timeout'] ) ? esc_attr( $this->options['cookie_timeout']) : ''
        );
    }
}

if( is_admin() )
    $my_settings_page = new bmpSettings();