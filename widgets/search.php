<?php
    /**
    * Widget Class
    */
    class EazyMatchSearchWidget extends WP_Widget {

        /** constructor */
        function EazyMatchSearchWidget() {
            parent::WP_Widget(false, $name = 'EazyMatch - '.EMOL_WIDGET_SEARCH);    
        }

        /** @see WP_Widget::widget */
        function widget($args, $instance) {
            global $emol_side;

            extract( $args );
            $title = '';
            if(isset($instance['title']))
                $title = apply_filters('widget_title', $instance['title']);
                
            $reset = '';
            if(isset($instance['reset']))
                $reset = apply_filters('widget_reset', $instance['reset']);

            $searchLabel = '';
            if(isset($instance['searchLabel']))
                $searchLabel = apply_filters('widget_searchLabel', $instance['searchLabel']);

            echo $before_widget;

            if ( $title )
                echo $before_title . $title . $after_title;

            try {
                $api            = eazymatch_connect();
                $cpt            = $api->get('competence');
                $competenceList = $cpt->getPublishedTree();
            } catch(SoapFault $e){
                eazymatch_trow_error('EazyMatch fout.');
                if( $emol_isDebug ){
                    var_dump( $e );
                }
            }    
            
            $lists = array();
            
            if($emol_side == 'company'){
                $setUrl = get_option( 'emol_cv_search_url' );
            } else {
                $setUrl = get_option( 'emol_job_search_url' );
            }
            
        
            if(count($competenceList)>0){
                $lists = new emol_Level2Listboxes($competenceList,$setUrl);
            } else {
                $lists = array();
            }
            
            echo '<div class="emol_widget" id="emol_search_widget">';
            
            echo '<div class="emol-free-search">
            <label for="emol-free-search-input">'.EMOL_WIDGET_FREE_SEARCH.'</label>
            <input type="text" value="'. urldecode(emol_session::get('freeSearch'))  .'" class="emol-text-input" name="emol-free-search" id="emol-free-search-input" /> 
            </div>';
            
            //checked values
            $val5='';
            $val10='';
            $val15='';
            $val25='';
            $val50='';
            
            //range
            switch( urldecode(emol_session::get('locationSearchRange')) ){
                case '5': $val5='selected="selected"'; break;
                case '10': $val10='selected="selected"'; break;
                case '15': $val15='selected="selected"'; break;
                case '25': $val25='selected="selected"'; break;
                case '50': $val50='selected="selected"'; break;
            }
            
            //selectbox for range
            $rangeBox = '<select class="emol-text-input" name="emol-range-search" id="emol-range-search-input"> 
            <option value="5" '.$val5.'>5 '.EMOL_KM.'</option>
            <option value="10" '.$val10.'>10 '.EMOL_KM.'</option>
            <option value="15" '.$val15.'>15 '.EMOL_KM.'</option>
            <option value="25" '.$val25.'>25 '.EMOL_KM.'</option>
            <option value="50" '.$val50.'>50 '.EMOL_KM.'</option>
            </select>';
            
            echo '<div class="emol-location-search">
            	<label for="emol-zipcode-search-input">'.EMOL_WIDGET_LOCATION_SEARCH.'</label>
	            <input type="text" value="'. urldecode(emol_session::get('locationSearchZipcode'))  .'" class="emol-text-input" name="emol-zipcode-search" id="emol-zipcode-search-input" /> 
	            '.$rangeBox.'
            </div>';
            
            if( isset( $lists->lists ) )
                echo $lists->lists;
                
            echo '
            <div class="emol-submit-wrapper">
            	<span class="emol-reset-button"><a href="/'.get_option( 'emol_job_search_url' ).'/all/">'.$reset.'</a></span>
            	<button onclick="emolSearch(\'/'.$setUrl.'/\');">'.$searchLabel.'</button>
            </div>';

            echo "</div>";
            
            echo $after_widget;

        }

        /** @see WP_Widget::update */
        function update($new_instance, $old_instance) {                
            $instance = $old_instance;
            $instance['title'] = strip_tags($new_instance['title']);
                
            $instance['reset'] = strip_tags($new_instance['reset']);
            $instance['searchLabel'] = strip_tags($new_instance['searchLabel']);
            return $instance;
        }

        /** @see WP_Widget::form */
        function form($instance) {                
            if( isset($instance['title']) ) {
                $title = esc_attr($instance['title']);
            }
            if( isset($instance['reset']) ) {
                $reset = esc_attr($instance['reset']);
            }
            if( isset($instance['searchLabel']) ) {
                $searchLabel = esc_attr($instance['searchLabel']);
            }
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> 
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('searchLabel'); ?>"><?php _e('Label:'); ?> 
                <input class="widefat" id="<?php echo $this->get_field_id('searchLabel'); ?>" name="<?php echo $this->get_field_name('searchLabel'); ?>" type="text" value="<?php echo $searchLabel; ?>" />
            </label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('reset'); ?>"><?php _e('Reset:'); ?> 
                <input class="widefat" id="<?php echo $this->get_field_id('reset'); ?>" name="<?php echo $this->get_field_name('reset'); ?>" type="text" value="<?php echo $reset; ?>" />
            </label>
        </p>
        <?php 
    }

} // class EazyMatchSearcgWidget


add_action('widgets_init', create_function('', 'return register_widget("EazyMatchSearchWidget");'));
