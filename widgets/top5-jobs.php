<?php
    /**
    * EazyMatchLoginWidget Class
    */
    class EazyMatchTop5JobsWidget extends WP_Widget {
        /** constructor */
        function EazyMatchTop5JobsWidget() {
            parent::WP_Widget(false, $name = 'EazyMatch - '.EMOL_WIDGET_TOP5);    
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

            echo '<div class="emol_widget" id="emol_top5jobs_widget">';

            try {
                $filterOptions = unserialize(get_option('emol_filter_options'));
                
                $wsJob      = $api->get('job');
                $jobs       = $wsJob->getPublished(5,$filterOptions);
               // var_dump($jobs);
            } catch (SoapFault $e){
                eazymatch_trow_error('Fout in SOAP request EazyMatch -> jobs');
                echo "<pre>";
                print_r($e);
            }
            
            
            //navigation
            $total = count($jobs);

            //check if the description may be visbile
            $descVisible    = get_option('emol_job_search_desc');
            $regioVisible   = get_option('emol_job_search_region');

            $i=0;
            if($total > 0){
                $text  ='<ul>';
                foreach($jobs as $job){
					$i++;
                    $job_url     = '/'.get_option( 'emol_job_url' ).'/'.$job['id'].'/'.eazymatch_friendly_seo_string($job['name']).'/';
                    $apply_url     = '/'.get_option( 'emol_apply_url' ).'/'.$job['id'].'/'.eazymatch_friendly_seo_string($job['name']).'/';
                    
                    $text .= '<li class="' . ( $i % 2 == 0 ? 'even':'odd' ) . '"><a href="'.$job_url.'">'.$job['name'].'</a>';
                    
                    if( $descVisible == 1 && !empty($job['description']) ) $text .= '<div class="emol_top5jobs_description">'.substr($job['description'],0,64).'...</div>';
                    if( $regioVisible == 1 && isset($job['Address']['Region'])) $text .= '<div class="emol_top5jobs_region">'.$job['Address']['Region']['name'].'</div>';
                    
                    $text .= '<div class="emol_top5jobs_apply"><a href="'.$apply_url.'">'.EMOL_JOBSEARCH_APPLY.'</a></div></li>';
                }
                echo '</ul>';
                
                
                $text .= '
                <div id="emol_top5jobs_findmore">
                	<div class="emol-submit-wrapper">
            			<a href="/'.get_option( 'emol_job_search_url' ).'/all/">'.EMOL_JOBSEARCH_MORE.'</a>
			        </div>
                </div>';

            } else {
                $text = '<ul><li><span>'.get_option( 'emol_job_no_result' ).'</span></li></ul>';
            }

            echo $text;
                
            echo '</div>';
            
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
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label>
        </p>
        <?php 
    }

} // class EazyMatchLoginWidget

add_action('widgets_init', create_function('', 'return register_widget("EazyMatchTop5JobsWidget");'));