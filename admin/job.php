<?php
    function eazymatch_plugin_job() {
        //must check that the user has the required capability 
        if (!current_user_can('manage_options'))
        {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }

        if( ! get_option('emol_instance') ) {
            echo "<div style=\"background-color:#ffebeb;border:1px solid red;margin-top:10px;text-align:center;font-weight:bold;padding:15px;\">FIRST SETUP YOUR CREDENTIALS UNDER GLOBAL</div>";
        } else {
            
            //we actually use eazymatch here too... 
            $api            = eazymatch_connect();
            try {
                $wsJob      = $api->get('tree');
                $jobStatus  = $wsJob->tree('Jobstatus');
            } catch( SoapFault $e){
               echo "<div style=\"background-color:#ffebeb;border:1px solid red;margin-top:10px;text-align:center;font-weight:bold;padding:15px;\">SOAP ERROR.</div>";
                exit();
            }
            //jobstatus
            if( is_null( $jobStatus ) ){
                echo "<div style=\"background-color:#ffebeb;border:1px solid red;margin-top:10px;text-align:center;font-weight:bold;padding:15px;\">ERROR IN CONNECTION, NULL RESPONSE</div>";
                exit();
            }
            
            // variables for the field and option names 
            $hidden_field_name = 'mt_submit_hidden';

            $eazymatchOptions = array(
                'emol_job_header'                => get_option('emol_job_header'),
                'emol_job_url'                    => get_option('emol_job_url'),
                'emol_job_amount_pp'            => get_option('emol_job_amount_pp'),
                'emol_job_search_url'            => get_option('emol_job_search_url'),
                'emol_job_search_logo'          => get_option('emol_job_search_logo'),
                'emol_job_search_desc'          => get_option('emol_job_search_desc'),
                'emol_job_search_region'        => get_option('emol_job_search_region'),
                'emol_job_no_result'            => get_option('emol_job_no_result'),
                'emol_apply_url'                => get_option('emol_apply_url'),
                'emol_apply_url_free'            => get_option('emol_apply_url_free'),
                'emol_filter_options'           => get_option('emol_filter_options'),
                'emol_apply_success'            => get_option('emol_apply_success')
            );

            //check the filter options, it will generate an error if not existing
            if( ! get_option( 'emol_filter_options' )) {
                add_option( 'emol_filter_options' );
                update_option('emol_filter_options', serialize( array() ));
            }
            
            // See if the user has posted us some information
            // If they did, this hidden field will be set to 'Y'
            if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {

                if( ! is_numeric($_POST['emol_job_amount_pp'])){
                    $_POST['emol_job_amount_pp'] = 5;
                }

                //first our arrays
                if(isset($_POST['filter'])){
                    $types = $_POST['filter'];
                    unset($_POST['filter']);
                    if( ! empty($types) ){
                        $_POST['emol_filter_options'] = serialize($types);
                    } else {
                        $_POST['emol_filter_options'] = serialize(array());
                    }
                } else {
                    $_POST['emol_filter_options'] = serialize(array());
                }

                foreach($_POST as $option => $value){

                    if( ! get_option( $option ) ) {
                        add_option($option);
                    } 
                    update_option($option, $value);

                    $eazymatchOptions[$option] = $value;
                }

            ?>
            <div class="updated"><p><strong><?php _e(EMOL_ADMIN_SAVEMSG, 'Emol-3.0-identifier' ); ?></strong></p></div>
            <?php
            }

            // $filterOptions = get_option('emol_filter_options');
            $filterOptions = unserialize($eazymatchOptions['emol_filter_options']);// Wordpress does not do this for us

            //create filterlist
            if(is_array($jobStatus) && count($jobStatus)>0){
                $list = '<ul id="emol-admin-filter-list">';
                foreach($jobStatus as $status){
                    foreach($status['children'] as $firstChildren){
                        $checked = '';
                        if(is_bool($filterOptions)){
                           continue;
                        }
                        if(in_array($firstChildren['id'],$filterOptions)) $checked = 'checked="checked"';
                        $list .=  "<li><input type=\"checkbox\" name=\"filter[]\" ".$checked." value=\"".$firstChildren['id']."\"> ".$firstChildren['name'].'';
                        if(isset($firstChildren['children']) && count($firstChildren['children']) > 0){
                            $list .= '<ul>';
                            foreach($firstChildren['children'] as $secondChildren){
                                $checked2 = '';
                                if(in_array($secondChildren['id'],$filterOptions)) $checked2 = 'checked="checked"';
                                $list .= "<li><input type=\"checkbox\" name=\"filter[]\" ".$checked2." value=\"".$secondChildren['id']."\"> ".$secondChildren['name'].'</li>';
                            }
                            $list .= '</ul>';
                        }
                        $list .= '</li>';
                    }
                } 
                $list .= '</ul>';
            } else {
                $list='--';
            }

            //checkbox for picture on/off
            $sel2 = 'checked="checked"';
            $sel1 = '';

            if( get_option('emol_job_search_logo') == 1 ){
                $sel1 = 'checked="checked"';
                $sel2 = '';
            }
            $checkboxPicture = '<input type="radio" name="emol_job_search_logo" value="1" '.$sel1.' /> '.EMOL_ON.' &nbsp;';
            $checkboxPicture .= '<input type="radio" name="emol_job_search_logo" value="0" '.$sel2.' /> '.EMOL_OFF.'';

            $sel2 = 'checked="checked"';
            $sel1 = '';

            //description
            if( get_option('emol_job_search_desc') == 1 ){
                $sel1 = 'checked="checked"';
                $sel2 = '';
            }
            $checkboxDescr = '<input type="radio" name="emol_job_search_desc" value="1" '.$sel1.' /> '.EMOL_ON.' &nbsp;';
            $checkboxDescr.= '<input type="radio" name="emol_job_search_desc" value="0" '.$sel2.' /> '.EMOL_OFF.'';


            //emol_job_search_region
            if( get_option('emol_job_search_region') == 1 ){
                $sel1 = 'checked="checked"';
                $sel2 = '';
            }
            $checkboxRegio = '<input type="radio" name="emol_job_search_region" value="1" '.$sel1.' /> '.EMOL_ON.' &nbsp;';
            $checkboxRegio.= '<input type="radio" name="emol_job_search_region" value="0" '.$sel2.' /> '.EMOL_OFF.'';


            echo '<div class="wrap">';
            echo "<h2>" . __( 'EazyMatch > '.EMOL_ADMIN_SETTINGS.' > '.EMOL_ADMIN_JOB.' ', 'Emol-3.0-identifier' ) . "</h2>";
        ?>

        <style type="">

            #emol-admin-table tr td {
                background-color:#f6f6f6;
                padding:5px;
            }    
            #emol-admin-table .cTdh {
                background-color:#f9f9f9;
                padding:0px;
            }

            #emol-admin-filter-list  li{
                margin-left:20px !important;
                line-height:2em;
            }

        </style>
        <strong><?=get_option('emol_instance')?></strong>
        <form name="form1" method="post" action="">
            <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
            <div align="right"><a href="http://www.eazymatch.nl" target="_new"><img src="http://www.eazymatch.nl/wordpress_logo.png" /></a></div>
            <div id="emol-admin-table">
                <table cellpadding="4">
                    <tr>
                        <td colspan="3" class="cTdh"><br><h2><?php echo EMOL_ADMIN_SETTINGS.' - '.EMOL_ADMIN_JOB?></h2></td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_JOBDISPLAY_URL, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_job_url" value="<?php echo $eazymatchOptions['emol_job_url']; ?>"   size="40"></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_JOBSEARCH_URL, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_job_search_url" value="<?php echo $eazymatchOptions['emol_job_search_url']; ?>" size="40"></td><td> 
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_JOBSEARCH_LOGOS, 'Emol-3.0-identifier' ); ?> </td>
                        <td><?php echo $checkboxPicture; ?></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_JOBSEARCH_DESCR, 'Emol-3.0-identifier' ); ?> </td>
                        <td><?php echo $checkboxDescr; ?></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_JOBSEARCH_REGIO, 'Emol-3.0-identifier' ); ?> </td>
                        <td><?php echo $checkboxRegio; ?></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_RESULTSPERPAGE, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_job_amount_pp" value="<?php echo $eazymatchOptions['emol_job_amount_pp']; ?>" size="40"></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_JOBHEADER, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_job_header" value="<?php echo $eazymatchOptions['emol_job_header']; ?>" size="40"></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="cTdh"><br><h2><?php echo EMOL_ADMIN_APPLYING?></h2></td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_APPLY_URL, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_apply_url" value="<?php echo $eazymatchOptions['emol_apply_url']; ?>" size="40"></td><td>
                        </td>
                    </tr>

                    <tr>
                        <td><?php _e(EMOL_ADMIN_APPLY_URL_FREE, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_apply_url_free" value="<?php echo $eazymatchOptions['emol_apply_url_free']; ?>" size="40"></td><td>
                        </td>
                    </tr>

                    <tr>
                        <td valign="top"><?php _e(EMOL_ADMIN_MSGNORESULT, 'Emol-3.0-identifier' ); ?> </td>
                        <td><textarea name="emol_job_no_result" cols="62" rows="7" ><?php echo $eazymatchOptions['emol_job_no_result']; ?></textarea></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top"><?php _e(EMOL_ADMIN_MSGAFTERAPPLY, 'Emol-3.0-identifier' ); ?> </td>
                        <td><textarea name="emol_apply_success" cols="62" rows="7" ><?php echo $eazymatchOptions['emol_apply_success']; ?></textarea></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="cTdh"><br><h2><?php echo EMOL_ADMIN_FILTERS?></h2></td>
                    </tr>                
                    <tr>
                        <td valign="top">Filters</td>
                        <td colspan="2">
                            <?php
                                echo $list;
                            ?>
                        </td>
                    </tr>
                </table>
            </div>
            <hr />

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e(EMOL_ACCOUNT_SAVE) ?>" />
            </p>

        </form>
        </div>

        <?php
    }
}
