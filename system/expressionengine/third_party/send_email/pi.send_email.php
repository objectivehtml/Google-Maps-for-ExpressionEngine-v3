<?php 

$plugin_info = array(
						'pi_name'			=> 'Send Email',
						'pi_version'		=> '1.4',
						'pi_author'			=> 'Engaging.net',
						'pi_author_url'		=> 'http://engaging.net',
						'pi_description'	=> 'Send an email without using a form from within an EE template. Compatible with both EE v1.x and v2.x.',
						'pi_usage'			=> Send_email::usage()
					);

/**
 * Send_email class
 *
 * @package			ExpressionEngine
 * @category		Plugin
 * @author			Engaging.net
 * @copyright		Copyright (c) 2010 Engaging.net
 * @link			http://engaging.net/products/send-email/
 */

/*
 * EE Syntax changes v1 -> v2
 *
 * $TMPL -> $this->EE->TMPL
 * $DB -> $this->EE->db
 * query->result -> query->result_array()
*/

class Send_email {
		
	function Send_email($str = '')
	{
		// Are we running EE 1.x or 2.x?
		global $TMPL; // EEv1 syntax
		global $DB; // EEv1 syntax
		global $PREFS; // EEv1 syntax
		$version = "";
		if ( $TMPL )
		{
			$version = "1";
		}
		else
		{
			$version = "2";
		}
		if ($version == "2")
		{
			$this->EE =& get_instance(); // EEv2 syntax
			$TMPL = $this->EE->TMPL;
			$DB = $this->EE->db;
			$SESS = $this->EE->session;
		}

		// Get the only required parameter
		$to = $TMPL->fetch_param('to');

		// If there's no to, then bail
		if ( $to == "" )
		{
			$error_message .= '<p>The "to" parameter is required.</p>';
			$this->return_data = $error_message;
		}
		else
		{
			// Get the other parameters, if any
			$from_email = $TMPL->fetch_param('from_email');
			$from_name = $TMPL->fetch_param('from_name');
			$subject = $TMPL->fetch_param('subject');
			$message = $TMPL->tagdata;
			$message = str_replace("{username}", $SESS->userdata('username'), $message);
			$message = str_replace("{screen_name}", $SESS->userdata('screen_name'), $message);
			$message = str_replace("{member_id}", $SESS->userdata('member_id'), $message);
			$message = str_replace("{email}", $SESS->userdata('email'), $message);
			$message = str_replace("{ip_address}", $SESS->userdata('ip_address'), $message);
			$message = str_replace("&#47;", "/", $message);

			// Send email
			$this->EE->load->library('email');
			$this->EE->email->initialize();
			if ($from_email && $from_name)
			{
				$this->EE->email->from($from_email, $from_name);
			}
			else
			{
				$this->EE->email->from($PREFS->ini('webmaster_email'), $PREFS->ini('webmaster_name'));
			}
			$this->EE->email->to($to);
			if ($subject)
			{
				$this->EE->email->subject($subject);
			}
			else
			{
				$this->EE->email->subject( "Email from " . $PREFS->ini('site_name') );
			}
			$this->EE->email->message($message);
			$this->EE->email->Send();
		}
		// Clear variables to allow repeat use of plugin on EEv2
		if ($version == "2")
		{
			$TMPL="";
			$DB="";
			$PREFS="";
		}
	}

	function usage()
	{
		ob_start(); 
		?>
			See the documentation at http://www.engaging.net/docs/send-email
		<?php
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
}
// END CLASS