<?php

    /**
    * EazyMatchLoginWidget Class
    */
    class EazyMatchLoginWidget extends WP_Widget {
        /** constructor */
        function EazyMatchLoginWidget() {
            parent::WP_Widget(false, $name = 'EazyMatch - '.EMOL_WIDGET_LOGIN);	
        }

        /** @see WP_Widget::widget */
        function widget($args, $instance) {		

            //get arguments
            extract( $args );

            //set title var
            $title = '';

            //get emol
            $api = eazymatch_connect();

            //get instance
            if(isset($instance['title']))
                $title = apply_filters('widget_title', $instance['title']);

            echo $before_widget; 

            if ( $title )
                echo $before_title . $title . $after_title;

        
            echo "<div class=\"emol_widget\" id=\"eazymatch_login_widget\">";
                
            /**
            * logged on part
            */
            if( emol_session::isValidId( 'company_id' ) ){
                //get the logged in person

                
                $wsUser   = $api->get('person');
                try{
                    $userInfo = $wsUser->getCurrent();
                } catch ( SoapFault $e ) {
                    eazymatch_trow_error('EazyMatch Connection error.');
                }

                echo EMOL_LOGGEDINAS." <strong>".$userInfo['fullname']."</strong>";
                echo "<hr class=\"emol_hr\" /><ul>";
                echo "<li><a href=\"/".get_option( 'emol_company_account_url' )."/naw/\">".EMOL_MENU_COMP_NAW."</a></li>";
                //echo "<li><a href=\"/".get_option( 'emol_company_account_url' )."/jobs/\">".EMOL_MENU_COMP_JOBS."</a></li>";
                //echo "<li><a href=\"/".get_option( 'emol_company_account_url' )."/applications/\">".EMOL_MENU_COMP_APLIC."</a></li>";
                echo "<li><a href=\"/".get_option( 'emol_company_account_url' )."/logout/\">".EMOL_MENU_LOGOUT."</a></li>";
                echo "</ul>";
                //print_r($userInfo);  

            } elseif( emol_session::isValidId( 'applicant_id' ) ){

                $wsUser   = $api->get('person');
                try{
                    $userInfo = $wsUser->getCurrent();
                } catch ( SoapFault $e ) {
                    eazymatch_trow_error('EazyMatch Connection error.');
                }

                //get the picture
                $pic = $wsUser->getPicture($userInfo['id']);
                $img = '';
                if($pic > ''){
                    $img = '<div id="emol-account-pic-widget"><img src="data:image/png;base64,'.$pic.'" /></div>';
                }
                echo EMOL_LOGGEDINAS." <strong>".$userInfo['fullname']."</strong>";
                echo $img;
                echo "<hr class=\"emol_hr\" /><ul>";
                echo "<li><a href=\"/".get_option( 'emol_account_url' )."/naw/\">".EMOL_MENU_APP_NAW."</a></li>";
                //echo "<li><a href=\"/".get_option( 'emol_account_url' )."/cv/\">".EMOL_MENU_APP_CV."</a></li>";
                //echo "<li><a href=\"/".get_option( 'emol_account_url' )."/match/\">".EMOL_MENU_APP_MATCH."</a></li>";	
                //echo "<li><a href=\"/".get_option( 'emol_account_url' )."/applications/\">".EMOL_MENU_APP_APLIC."</a></li>";
                echo "<li><a href=\"/".get_option( 'emol_account_url' )."/logout/\">".EMOL_MENU_LOGOUT."</a></li>";
                echo "</ul>";
                //print_r($userInfo);  
            } else {

                echo "<form method=\"post\" action=\"/".get_option( 'emol_account_url' )."/login/\">";
                echo "<input type=hidden value=\"EMOL_LOGIN\" name=\"EMOL_LOGIN\">";
                echo "<input type=\"text\" class=\"emol-text-input\" value=\"".EMOL_LOGIN_USER."\" onfocus=\"if(this.value == '".EMOL_LOGIN_USER."'){this.value='';}\" name=\"username\"><br>";
                echo "<input type=\"password\" class=\"emol-text-input\" value=\"".EMOL_LOGIN_PASS."\" onfocus=\"if(this.value == '".EMOL_LOGIN_PASS."'){this.value='';}\" name=\"password\">";
                echo "<hr class=\"emol-hr\"><div class=\"emol-button-pos-search\"><button type=\"submit\">".EMOL_MENU_LOGIN."</button>";
                echo "</form></div>";

            }
            
            echo "</div>";
            echo $after_widget;

        }

        /** @see WP_Widget::update */
        function update($new_instance, $old_instance) {				
            $instance = $old_instance;
            $instance['title'] = strip_tags($new_instance['title']);
            return $instance;
        }

        /** @see WP_Widget::form */
        function form($instance) {				
            if(isset( $instance['title'] ))
                $title = esc_attr($instance['title']);
            else $title = '';
        ?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
        <?php 
    }

} // class EazyMatchLoginWidget

add_action('widgets_init', create_function('', 'return register_widget("EazyMatchLoginWidget");'));