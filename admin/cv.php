<?php
    function eazymatch_plugin_cv() {
        //must check that the user has the required capability 
        if (!current_user_can('manage_options'))
        {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }

        if( ! get_option('emol_instance') ) {
            echo "<div style=\"background-color:#ffebeb;border:1px solid red;margin-top:10px;text-align:center;font-weight:bold;padding:15px;\">FIRST SETUP YOUR CREDENTIALS UNDER GLOBAL</div>";
        } else {

            // variables for the field and option names 
            $hidden_field_name = 'mt_submit_hidden';

            $eazymatchOptions = array(
            'emol_cv_header'				=> get_option('emol_cv_header'),
            'emol_cv_url'					=> get_option('emol_cv_url'),
            'emol_cv_amount_pp'				=> get_option('emol_cv_amount_pp'),
            'emol_cv_search_url'			=> get_option('emol_cv_search_url'),
            'emol_cv_search_picture'		=> get_option('emol_cv_search_picture'),
            'emol_react_url_cv'             => get_option('emol_react_url_cv'),
            'emol_cv_no_result'				=> get_option('emol_cv_no_result'),
            'emol_react_success'			=> get_option('emol_react_success')
            );

            // See if the user has posted us some information
            // If they did, this hidden field will be set to 'Y'
            if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {

                if( ! is_numeric($_POST['emol_cv_amount_pp'])){
                    $_POST['emol_cv_amount_pp'] = 5;
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

            //checkbox for picture on/off
            $sel2 = 'checked="checked"';
            $sel1 = '';
            if( get_option('emol_cv_search_picture') == 1 ){
                $sel1 = 'checked="checked"';
                $sel2 = '';
            }
            $checkboxPicture = '<input type="radio" name="emol_cv_search_picture" value="1" '.$sel1.' /> '.EMOL_ON.' &nbsp;';
            $checkboxPicture .= '<input type="radio" name="emol_cv_search_picture" value="0" '.$sel2.' /> '.EMOL_OFF.' &nbsp;';

            echo '<div class="wrap">';
            echo "<h2>" . __( 'EazyMatch > '.EMOL_ADMIN_SETTINGS.' > '.EMOL_ADMIN_CV.' ', 'Emol-3.0-identifier' ) . "</h2>";
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

        </style>
        <strong><?=get_option('emol_instance')?></strong>
        <form name="form1" method="post" action="">
            <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
            <div align="right"><a href="http://www.eazymatch.nl" target="_new"><img src="http://www.eazymatch.nl/wordpress_logo.png" /></a></div>
            <div id="emol-admin-table">
                <table cellpadding="4">
                    <tr>
                        <td colspan="3" class="cTdh"><br><h2><?php echo EMOL_ADMIN_SETTINGS.' - '.EMOL_ADMIN_CV?></h2></td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_CVDISPLAY_URL, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_cv_url" value="<?php echo $eazymatchOptions['emol_cv_url']; ?>"   size="40"></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_CVSEARCH_URL, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_cv_search_url" value="<?php echo $eazymatchOptions['emol_cv_search_url']; ?>" size="40"></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_CV_PICTURE, 'Emol-3.0-identifier' ); ?> </td>
                        <td><?php echo $checkboxPicture; ?></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_RESULTSPERPAGE, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_cv_amount_pp" value="<?php echo $eazymatchOptions['emol_cv_amount_pp']; ?>" size="40"></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_CV_HEADER, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_cv_header" value="<?php echo $eazymatchOptions['emol_cv_header']; ?>"   size="40"></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" class="cTdh"><br><h2><?php echo EMOL_ADMIN_REACTING ?></h2></td>
                    </tr>
                    <tr>
                        <td><?php _e(EMOL_ADMIN_REACT_URL, 'Emol-3.0-identifier' ); ?> </td>
                        <td><input type="text" name="emol_react_url_cv" value="<?php echo $eazymatchOptions['emol_react_url_cv']; ?>" size="40"></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top"><?php _e(EMOL_ADMIN_MSGNORESULT, 'Emol-3.0-identifier' ); ?> </td>
                        <td><textarea name="emol_cv_no_result" cols="62" rows="7" ><?php echo $eazymatchOptions['emol_cv_no_result']; ?></textarea></td><td>
                        </td>
                    </tr>
                    <tr>
                        <td valign="top"><?php _e(EMOL_ADMIN_MSGAFTERREACT, 'Emol-3.0-identifier' ); ?> </td>
                        <td><textarea name="emol_react_success" cols="62" rows="7" ><?php echo $eazymatchOptions['emol_react_success']; ?></textarea></td><td>
                        </td>
                    </tr>
                </table>
            </div>

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e(EMOL_ACCOUNT_SAVE) ?>" />
            </p>

        </form>
        </div>

        <?php
    }
}
