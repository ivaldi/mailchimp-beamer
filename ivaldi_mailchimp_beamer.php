<?php
/*
Plugin Name: Ivaldi MailChimp Beamer
Plugin URI: http://ivaldi.nl
Description: Send your news-items to a MailChimp newsletter.
Version: 0.1
Author: Martijn Reijerse
Author URI: http://ivaldi.nl
License: MIT License
*/


function imb_menu() {
	add_menu_page('Ivaldi MailChimp Beamer', 'newsletter', 'publish_pages', 'imb_admin', 'imb_admin');
}

add_action('admin_menu', 'imb_menu');


function hasErrors(array $data){	
	if(count($data) == 0 || (isValidEmail($data['email']) &&
			isValidEmail($data['sender']) &&
			$data['subject'] != '' &&
			$data['name']  != '')){
		
		return false;		
	} 
	
	return true;
	
}

function isValidEmail($email){
    return (preg_match("/^[_a-z0-9-=]+(\.[_a-z0-9+-=]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i", $email) == 1);
}

function imb_admin() {
	
	$rows = get_posts(array('posts_per_page' => 50));
	
	$errors = hasErrors($_POST);
	
	if(isset($_POST['imb_admin']) && !$errors){
		$html = file_get_contents(plugin_dir_path( __FILE__ ).'/template.html');
		$posts = '';

		$postarr = array();
		asort($_POST['order']);

		foreach($_POST['order'] as $id => $order){
			if($order != ''){
				$postarr[] = $_POST['post'][array_search($id, $_POST['post'])];
			}
		}

		foreach ($postarr as $id) {
			$post = get_post($id);
			setup_postdata($post);
			
			$template = file_get_contents('../wp-content/plugins/ivaldi_mailchimp_beamer/post.html');		
			
			$template = str_replace('<%%title%%>',get_the_title($id), $template);
			$template = str_replace('<%%content%%>',get_the_excerpt($id), $template);
			$template = str_replace('<%%permalink%%>',get_permalink($id), $template);
			
			if(has_post_thumbnail( $id)){
				$thumb = wp_get_attachment_image_src( get_post_thumbnail_id($id), 'thumbnail' );
				$template = str_replace('<%%postimg%%>', '<td><img src="'.$thumb[0].'" /></td>', $template);
			}else{
				$template = str_replace('<%%postimg%%>', '', $template);
			}
				
			$posts .= $template;
		}

		$html = str_replace('<%%posts%%>', $posts, $html);		
		$html = str_replace('<%%date%%>', date_i18n(get_option('date_format'), time()), $html);
		$html = str_replace('<%%subject%%>', $_POST['subject'], $html);
				
		add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
		$headers = 'From: '.$_POST['name'].' <'.$_POST['sender'].'>' . "\r\n";

		wp_mail($_POST['email'], $_POST['subject'], $html, $headers);
		$send = true;
	}
	
	?>
	
	<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div><h2>Send a newsletter with MailChimp Beamer</h2>

	<?php if(isset($send) && $send): ?>
		<div id="setting-error-settings_updated" class="updated settings-error">
			<p>Newsletter has been send to MailChimp!</p>
		</div>
	<?php endif; ?>
	<?php if($errors): ?>
		<div id="setting-error-settings_updated" class="updated settings-error">
			<p>Please correct the errors in this form.</p>
		</div>
	<?php endif; ?>

	
	<form method="post" action="admin.php?page=imb_admin" id="posts-filter">
	        <input type="hidden" value="imb_admin" name="imb_admin" />
	        
	        <table class="form-table">
		        <tr valign="top">
		        	<th scope="row">
		        		<label>Subject:</label>
		        	</th>
			        <td>
			        	<input type="text" name="subject" value="Newsletter - <?php echo date_i18n(get_option('date_format'), time()); ?>" class="regular-text" />
			        	<p class="description">Subject of the newsletter. This subject will be shown in the inboxes of your readers</p>
			        </td>
		        </tr>
	        	<tr valign="top">
	        		<th scope="row">
	        			<label>Name of sender:</label>
	        		</th>
	        	    <td>
	        	    	<input type="text" name="name" value="<?php bloginfo('name'); ?>" class="regular-text" />
	        	    	<p class="description">The name of the sender of this newsletter.</p>
	        	    </td>
	        	</tr>
	        	<tr valign="top">
	        		<th scope="row"><label>Email of sender:</label></th>
	        	    <td>
	        	    	<input type="text" name="sender" class="regular-text" value="<?php bloginfo('admin_email') ?>" />
	        	    	<p class="description">The email of the sender of the newsletter. Make sure you enter an email address on which you can recieve the confirmation email from MailChimp.</p>
	        	    </td>
	        	</tr>
	        	<tr valign="top">
	        		<th scope="row">
	        			<label>MailChimp-list e-mailaddress:</label>
	        		</th>
	        	    <td>
	        	    	<input type="text" name="email" class="regular-text" />
	        	    	<p class="description">The e-mailaddress of the MailChimp-list. Example: [unique code]@campaigns.mailchimp.com</p>
	        	    </td>
	        	</tr>
	        </table>
	        
	        <h3>Select the messages you want to include in your newsletter</h3>
	        <table class="widefat">
	                <thead>
	                        <tr>
	                                <th></th>
	                                <th>Title</th>
   	                                <th>Date</th>
   	                                <th>Order</th>
	                        </tr>
	                </thead>
	                <tfoot>
	                        <tr>
	                                <th></th>
	                                <th>Title</th>
   	                                <th>Date</th>
	                                <th>Order</th>
	                        </tr>
	                </tfoot>
	                <tbody>
					<?php
					        foreach($rows as $row) {
					        		//var_dump($row);
					                echo '<tr>
					                <td>
					                <input type="checkbox" name="post[]" value="'.$row->ID.'" /></td>
					                <td><a href="'.$row->guid.'" target="_blank">' . $row->post_title . '</a></td>
					                <td>'.date_i18n(get_option('date_format'), strtotime($row->post_date)). ' ' . date_i18n(get_option('time_format'), strtotime($row->post_date)) .'</td>
					                <td><input type="text" name="order['.$row->ID.']" /></td>
					                </tr>', PHP_EOL;
					        }
					?>
	                </tbody>
	        </table>
	        
	        <p class="submit">
		        <input type="submit" name="submit" value="Send to MailChimp" class="button button-primary" />
	        </p>        
	        
	</form>
	</div>
	<?php
}

?>