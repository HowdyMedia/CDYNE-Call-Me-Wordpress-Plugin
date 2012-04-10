<?php
/*
Plugin Name: CDYNE Call Me Widget
Plugin URI: http://cdynecallme.howdymedia.com/
Description: Easily add a 'Call Me' button to your website using this widget and CDYNEs PhoneNotify!&reg;.
Author: H.O.W.D.Y. Media
Version: 1.0
Author URI: http://www.howdymedia.com/
License: GPLv2 or later
*/
/*
	Copyright 2012 H.O.W.D.Y. Media

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

session_start();
define('CDYNECALLME_CURL',(function_exists('curl_init') ? true:false));

function cdyneCallMe_cURL($url,$postvars = '') {
 if (CDYNECALLME_CURL) {
  $c = curl_init();
  curl_setopt($c, CURLOPT_USERAGENT, 'CDYNE Call Me Wordpress Widget Plugin');
  curl_setopt($c, CURLOPT_URL, $url);
  curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($c, CURLOPT_HEADER, false);
  curl_setopt($c, CURLOPT_FRESH_CONNECT, true);
  curl_setopt($c, CURLOPT_MAXREDIRS, 2);
  curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);
  if ($postvars) {
   curl_setopt($c, CURLOPT_POST, true);
   curl_setopt($c, CURLOPT_POSTFIELDS, $postvars);
  }
  if (strpos(PHP_OS,"WIN") !== false) {
   curl_setopt($c, CURLOPT_SSL_VERIFYPEER, 0);
  }
  $out = curl_exec($c);
  curl_close($c);
 } else {
  $out = file_get_contents($url.'?'.$postvars);
 }
 return $out;
}
function cdyneCallMe_optOut($array,$set='',$none='',$match_value=''){ // HM-optOut v2.3
 if (is_array($array)){
  if (is_array($set)) {} else $set = array($set);
  if ($none) $out = '<option value=""'.(count($set) ? '':' selected').'>'.$none.'</option>';
  foreach ($array as $id => $val){
   $match = ($match_value ? $val:$id);
   $out .= '<option value="'.$match.'"'.(count($set) && in_array($match,$set) ? ' selected':'').'>'.$val.'</option>';
  }
 }
 return $out;
}
function cdyneCallMe_getVoices() {
 $voices = array();
 $response = cdyneCallMe_cURL('http://ws.cdyne.com/NotifyWS/PhoneNotify.asmx/getVoices');
 $voicesXML = cdyneCallMe_parseXML($response,'voice',true);
 if (is_array($voicesXML) && count($voicesXML) > 0) {
  foreach($voicesXML as $voice) {
   $voiceID = cdyneCallMe_parseXML($voice,'voiceID');
   $voiceName = cdyneCallMe_parseXML($voice,'voiceName');
   $voiceGender = cdyneCallMe_parseXML($voice,'voiceGender');
   $voiceAge = cdyneCallMe_parseXML($voice,'voiceAge');
   $voiceLanguage = cdyneCallMe_parseXML($voice,'voiceLanguage');
   $voices[$voiceID] = "$voiceName ($voiceGender/$voiceAge) $voiceLanguage";
  }
 }
 return $voices;
}
function cdyneCallMe_parseXML($data, $key, $multiple = false) {
 if ($multiple === false) {
  preg_match("'<$key>(.*?)</$key>'si", $data, $out);
 } else {
  preg_match_all("'<$key>(.*?)</$key>'si", $data, $out);
 }
 return $out[1];
}

if(!class_exists('cdyneCallMeWidget') && class_exists('WP_Widget')) {
 class cdyneCallMe extends WP_Widget {
  public function cdyneCallMe() {
   $description = __('Easily add a \'Call Me\' button to your website using this widget.');
   $widget_options = array('classname' => strtolower(get_class($this)), 'description' => $description);
   $control_options = array('width' => 600, 'height' => 350);
   $this->WP_Widget('cdynecallme', __('CDYNE Call Me'), $widget_options, $control_options);
  }

  public function form($instance) {
   $voices = cdyneCallMe_getVoices();
?>
<p>
 <label for="<?php echo $this->get_field_id('WidgetTitle');?>"><?php _e('Title:');?></label>
 <input style="width:300px;" id="<?php echo $this->get_field_id('WidgetTitle');?>" name="<?php echo $this->get_field_name('WidgetTitle');?>" type="text" value="<?php echo $instance['WidgetTitle'];?>" /><br />
 <label for="<?php echo $this->get_field_id('WidgetHTML');?>"><?php echo 'Widget Custom HTML:';?></label>
 <textarea class="widefat" rows="6" cols="20" id="<?php echo $this->get_field_id('WidgetHTML');?>" name="<?php echo $this->get_field_name('WidgetHTML');?>"><?php echo $instance['WidgetHTML'];?></textarea>
  <label for="<?php echo $this->get_field_id('WidgetButton');?>"><?php _e('Submit Button Text:');?></label>
 <input style="width:300px;" id="<?php echo $this->get_field_id('WidgetButton');?>" name="<?php echo $this->get_field_name('WidgetButton');?>" type="text" value="<?php echo $instance['WidgetButton'];?>" />
</p>
<hr>
<p>
 <label for="<?php echo $this->get_field_id('LicenseKey');?>"><?php echo 'CDYNE Phone Notify!&reg; API Key:';?></label>
 <input style="width:300px;" id="<?php echo $this->get_field_id('LicenseKey');?>" name="<?php echo $this->get_field_name('LicenseKey');?>" type="text" value="<?php echo $instance['LicenseKey'];?>" /><br />
 <a href="http://www.cdyne.com/products/phone-notify.aspx">Don't have an API Key yet?</a>
</p>
<p>
 <label for="<?php echo $this->get_field_id('TextToSay');?>"><?php echo 'TextToSay'; _e(' Voice Message/Script:');?></label>
 <textarea class="widefat" rows="6" cols="20" id="<?php echo $this->get_field_id('TextToSay');?>" name="<?php echo $this->get_field_name('TextToSay');?>"><?php echo $instance['TextToSay'];?></textarea>
Be sure to read about the <a href="http://cdynecallme.howdymedia.com/advanced-commands" target="_blank">advanced commands</a> available to set up transfer numbers, touchtone responses, and menu sections.
</p>
<p>
 <label for="<?php echo $this->get_field_id('TextToSayVoice');?>"><?php echo 'TextToSay'; _e(' Voice:');?></label>
 <select id="<?php echo $this->get_field_id('TextToSayVoice');?>" name="<?php echo $this->get_field_name('TextToSayVoice');?>"><?php echo cdyneCallMe_optOut($voices,$instance['TextToSayVoice']);?></select>
</p>
<p>
 <label for="<?php echo $this->get_field_id('CallerIDNumber');?>"><?php echo 'Caller ID Number to Display:';?></label>
 <input id="<?php echo $this->get_field_id('CallerIDNumber');?>" name="<?php echo $this->get_field_name('CallerIDNumber');?>" type="text" value="<?php echo $instance['CallerIDNumber'];?>" />
 <label for="<?php echo $this->get_field_id('CallerIDName');?>"><?php echo 'Caller ID Name to Display:';?></label>
 <input id="<?php echo $this->get_field_id('CallerIDName');?>" name="<?php echo $this->get_field_name('CallerIDName');?>" type="text" value="<?php echo $instance['CallerIDName'];?>" />
</p>
<p><strong>Optional:</strong> - <em>If not using the <a href="http://cdynecallme.howdymedia.com/advanced-commands"  target="_blank">advanced commands</a> in the TextToSay script</em><br />
 <label for="<?php echo $this->get_field_id('TransferNumber');?>"><?php _e('Transfer Phone Number:');?></label>
 <input id="<?php echo $this->get_field_id('TransferNumber');?>" name="<?php echo $this->get_field_name('TransferNumber');?>" type="text" value="<?php echo $instance['TransferNumber'];?>" />
If you don't specify a transfer number in your TextToSay script above, you can use this to have the caller can be directed to this number by pressing '0'.
</p>
<?php
  }

  public function update( $new_instance, $old_instance ) {
   $instance = $old_instance;
   $instance['WidgetTitle'] = strip_tags($new_instance['WidgetTitle']);
   $instance['WidgetHTML'] = strip_tags($new_instance['WidgetHTML']);
   $instance['WidgetButton'] = strip_tags($new_instance['WidgetButton']);
   $instance['LicenseKey'] = strip_tags($new_instance['LicenseKey']);
   $instance['TextToSay'] = strip_tags($new_instance['TextToSay']);
   $instance['TextToSayVoice'] = strip_tags($new_instance['TextToSayVoice']);
   $instance['CallerIDNumber'] = strip_tags($new_instance['CallerIDNumber']);
   $instance['CallerIDName'] = strip_tags($new_instance['CallerIDName']);
   $instance['TransferNumber'] = strip_tags($new_instance['TransferNumber']);

   return $instance;
  }

  public function widget($args, $instance) {
   $before_title = $before_widget = $after_widget = $after_title = '';
   extract($args, EXTR_IF_EXISTS);
   echo $before_widget.$before_title.$instance['WidgetTitle'].$after_title.($instance['WidgetHTML']? '<p>'.$instance['WidgetHTML'].'</p>':'');

   $phone_number_display = preg_replace('/[^0-9\-\.]/','', $_POST['cdyne_call_me_phone_'.$this->id]);
   $phone_number = preg_replace('/[^0-9]/','', $phone_number_display);

   if ($phone_number_display) {
    $transfer_number = preg_replace('/[^0-9]/','',$instance['TransferNumber']);
    if (strlen($phone_number) >= 10) {
     if (strlen($transfer_number) >= 10) {
      $post = "PhoneNumberToDial=$phone_number&TransferNumber=$transfer_number&TextToSay=".urlencode($instance['TextToSay'])."&CallerID=".urlencode($instance['CallerIDNumber'])."&CallerIDname=".urlencode($instance['CallerIDName'])."&VoiceID=".intval($instance['TextToSayVoice'])."&LicenseKey=".urlencode($instance['LicenseKey']);
      $response = cdyneCallMe_cURL('http://ws.cdyne.com/NotifyWS/PhoneNotify.asmx/NotifyPhoneBasicWithTransfer',$post);
     } else {
      $post = "PhoneNumbersToDial=$phone_number&TextToSay=".urlencode($instance['TextToSay'])."&CallerID=".urlencode($instance['CallerIDNumber'])."&CallerIDname=".urlencode($instance['CallerIDName'])."&VoiceID=".intval($instance['TextToSayVoice'])."&LicenseKey=".urlencode($instance['LicenseKey']);
      $response = cdyneCallMe_cURL('http://ws.cdyne.com/NotifyWS/PhoneNotify.asmx/NotifyMultiplePhoneBasic',$post);
     }
     $responseCode = intval(cdyneCallMe_parseXML($response,'responsecode'));
     if ($responseCode > 1 && !in_array($responseCode,array(12,18,19,20,21,22,24,29,30,31,46))) {
      switch($responseCode) {
       case 1:
        $_SESSION['cdyne_call_me_error'] = 'An error occurred, please try again.';
        break;
       case 3:
        $_SESSION['cdyne_call_me_error'] = 'Please enter a Valid Phone Number. (ex: +1 212-555-1234)';
        break;
       case 4:
        $_SESSION['cdyne_call_me_error'] = 'Invalid CallerID.';
        break;
       case 5:
        $_SESSION['cdyne_call_me_error'] = 'Invalid VoiceID.';
        break;
       case 8:
       case 40:
        $_SESSION['cdyne_call_me_error'] = 'Your number was busy, please try again.';
        break;
       case 13:
        $_SESSION['cdyne_call_me_error'] = 'CallerID and Phone Number to dial cannot match.';
        break;
       default:
        $_SESSION['cdyne_call_me_error'] = 'An error occurred, please try again.';
      }
     } else {
      $_SESSION['cdyne_call_me_success'] = 'We will be calling you shortly.';
     }
    } else {
     $_SESSION['cdyne_call_me_error'] = 'Please enter a Valid Phone Number. (ex: +1 212-555-1234)';
    }
   }
?>
<div class="cdyne-call-me">
<form method="POST">
<label for="cdyne_call_me_phone_<?php echo $this->id;?>">Your Phone Number:</label>
<input id="cdyne_call_me_phone_<?php echo $this->id;?>" name="cdyne_call_me_phone_<?php echo $this->id;?>" type="text" value="<?php echo $phone_number_display;?>" />
<?php
if ($_SESSION['cdyne_call_me_error']) {
 echo '<p style="background:#a00;color:#fff;padding:3px 6px;">'.$_SESSION['cdyne_call_me_error'].'</p>';
 $_SESSION['cdyne_call_me_error'] = '';
}
if ($_SESSION['cdyne_call_me_success']) {
 echo '<p style="background:#afa;color:#0a0;padding:3px 6px;">'.$_SESSION['cdyne_call_me_success'].'</p>';
 $_SESSION['cdyne_call_me_success'] = '';
}
?>
<input type="submit" value="<?php echo ($instance['WidgetButton'] ? $instance['WidgetButton'] : 'Call Me!');?>" />
</form>
</div>
<?php
echo $after_widget;
  }
 }
 add_action('widgets_init', create_function('', 'return register_widget("cdyneCallMe");'));
}
?>