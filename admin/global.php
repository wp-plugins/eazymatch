<?php 
	function eazymatch_plugin_options() {

		//must check that the user has the required capability 
		if (!current_user_can('manage_options'))
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}

		// variables for the field and option names 
		$hidden_field_name = 'mt_submit_hidden';

		/**
		* ALSO ADJUST EMOL-INSTALL WHEN EDITTING THIS!!
		* 
		*/
		$eazymatchOptions = array(
           
			'emol_instance' 				=> get_option('emol_instance'),
			'emol_key' 						=> get_option('emol_key'),
			'emol_secret'					=> get_option('emol_secret'),
			'emol_url'						=> get_option('emol_url'),
            'emol_account_url'              => get_option('emol_account_url'),
            'emol_jquery_ui_skin'           => get_option('emol_jquery_ui_skin'),
			'emol_company_account_url'		=> get_option('emol_company_account_url')
		);



		// See if the user has posted us some information
		// If they did, this hidden field will be set to 'Y'
		if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {

			foreach($_POST as $option => $value){
				if( ! get_option( $option ) ) {
					add_option($option);
				} 
				update_option($option, $value);
				
				$eazymatchOptions[$option] = $value;
			}
            //always reset the session hash
           
            
		?>
		<div class="updated"><p><strong><?php _e(EMOL_ADMIN_SAVEMSG, 'Emol-3.0-identifier' ); ?></strong></p></div>
		<?php
		}

		echo '<div class="wrap">';
		echo "<h2>" . __( 'EazyMatch > '.EMOL_ADMIN_SETTINGS.' > '.EMOL_ADMIN_LICENCE.' ', 'Emol-3.0-identifier' ) . "</h2>";

		// settings form

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
	<form name="form1" method="post" action="">
		<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
		<div align="right"><a href="http://www.eazymatch.nl" target="_new"><img src="http://www.eazymatch.nl/wordpress_logo.png" /></a></div>
		<div id="emol-admin-table">
			<table cellpadding="4">
				<tr>
					<td colspan="2" class="cTdh"><br><h2>Uw EazyMatch account</h2></td>
				</tr>
				<tr><td width="200"><?php _e("Instance naam:", 'Emol-3.0-identifier' ); ?> </td>
					<td><input type="text" name="emol_instance" value="<?php echo $eazymatchOptions['emol_instance']; ?>" size="40"></td>
					<td>
					</td>
				</tr>
				<tr>
					<td><?php _e("Key:", 'Emol-3.0-identifier' ); ?> </td>
					<td><input type="text" name="emol_key" value="<?php echo $eazymatchOptions['emol_key']; ?>" size="40"></td><td>
					</td>
				</tr>
				<tr>
					<td><?php _e("Secret:", 'Emol-3.0-identifier' ); ?> </td>
					<td><input type="text" name="emol_secret" value="<?php echo $eazymatchOptions['emol_secret']; ?>"  size="40"></td><td>
					</td>
				</tr>
                <tr>
                    <td colspan="3" class="cTdh"><br><h2>Accounts</h2></td>
                </tr>
                <tr>
                    <td><?php _e("Account url kandidaat: ", 'Emol-3.0-identifier' ); ?> </td>
                    <td><input type="text" name="emol_account_url" value="<?php echo $eazymatchOptions['emol_account_url']; ?>" size="40"></td><td> (ex: <?=bloginfo('url')?>/<b>account</b>/)
                    </td>
                </tr>
                <tr>
                    <td><?php _e("Account url bedrijf: ", 'Emol-3.0-identifier' ); ?> </td>
                    <td><input type="text" name="emol_company_account_url" value="<?php echo $eazymatchOptions['emol_company_account_url']; ?>" size="40"></td><td> (ex: <?=bloginfo('url')?>/<b>account-werkgever</b>/)
                    </td>
                </tr>
                
				<tr>
					<td colspan="3" class="cTdh"><br><h2>Other</h2></td>
				</tr>
				<tr>
					<td><?php _e("JQuery UI skin: ", 'Emol-3.0-identifier' ); ?> </td>
					<td><input type="text" name="emol_jquery_ui_skin" value="<?php echo $eazymatchOptions['emol_jquery_ui_skin']; ?>" size="40"></td><td>
					</td>
				</tr>
				
			</table>
		</div>
		<hr />

		<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Opslaan') ?>" />
		</p>

	</form>
	</div>

	<?php


}