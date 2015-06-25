<?php
/*-----------------------------------------------------------------------------------*/
/*	AJAX Contact Form
/*-----------------------------------------------------------------------------------*/
class wplauncher_contactform {
    public $plugin_slug = 'wp-launcher';
    public $errors = array();
    public $userinput = array('name' => '', 'email' => '', 'message' => '');
    public $success = false;

    public $labels = array();
    public $sendto = '';
    public $format = '';
    
    public function __construct() {  
    	add_action('wp_ajax_wplauncher_contact', array($this, 'ajax_wplauncher_contact'));
        add_action('wp_ajax_nopriv_wplauncher_contact', array($this, 'ajax_wplauncher_contact'));      
        add_action('init', array($this, 'init'));
    }
    public function setvars( $label_name, $label_email, $label_message, $label_submit, $sendto ) {
        $this->labels = array('name' => $label_name, 'email' => $label_email, 'message' => $label_message, 'submit' => $label_submit);
        $this->sendto = $sendto;
    }
    public function ajax_wplauncher_contact() {
        if ($this->validate()) {
            if ($this->send_mail()) {
                echo json_encode('success');
                wp_create_nonce( "wplauncher_contact" ); // purge used nonce
            } else {
                // wp_mail() unable to send
                $this->errors['sendmail'] = __('An error occurred. Please contact site administrator.', $this->plugin_slug);
                echo json_encode($this->errors);
            }
        } else {
            echo json_encode($this->errors);
        }
        die();
    }
    public function init() {
        // No-js fallback
        if ( !defined( 'DOING_AJAX' ) || !DOING_AJAX ) {
            if (!empty($_POST['action']) && $_POST['action'] == 'wplauncher_contact') {
                if ($this->validate()) {
                    if (!$this->send_mail()) {
                        $this->errors['sendmail'] = __('An error occurred. Please contact site administrator.', $this->plugin_slug);
                    } else {
                        $this->success = true;
                    }
                }
            }
        }
    }
    
    private function validate() {
        // check nonce
        if (!check_ajax_referer( 'wplauncher_contact', 'wplauncher_contact_nonce', false )) {
            $this->errors['nonce'] = __('Please try again.', $this->plugin_slug);
        }
        
        // check honeypot // must be empty
        if (!empty($_POST['wplauncher_contact_captcha'])) {
            $this->errors['captcha'] = __('Please try again.', $this->plugin_slug);
        }
        
        // name field
        $name = trim(str_replace(array("\n", "\r", "<", ">"), '', strip_tags($_POST['wplauncher_contact_name'])));
        if (empty($name)) {
            $this->errors['name'] = __('Please enter your name.', $this->plugin_slug);
        }
        
        // email field
        $useremail = trim($_POST['wplauncher_contact_email']);
        if (!is_email($useremail)) {
            $this->errors['email'] = __('Please enter a valid email address.', $this->plugin_slug);
        }
        
        // message field
        $message = strip_tags($_POST['wplauncher_contact_message']);
        if (empty($message)) {
            $this->errors['message'] = __('Please enter a message.', $this->plugin_slug);
        }
        
        // store fields for no-js
        $this->userinput = array('name' => $name, 'email' => $useremail, 'message' => $message);
        
        return empty($this->errors);
    }
    private function send_mail() {
        $email_to = $this->sendto;
        $email_subject = get_bloginfo('name');
        $email_message = $this->labels['name'].': '.$this->userinput['name']."\n\n".
                         $this->labels['email'].': '.$this->userinput['email']."\n\n".
                         $this->labels['message'].': '.$this->userinput['message'];
        return wp_mail($email_to, $email_subject, $email_message);
    }
    public function get_form() {
        wp_enqueue_script('wplauncher_contact');
        
        $return = '';
        if (!$this->success) {
            $return .= '<form method="post" action="" id="wplauncher_contact_form">
            <input type="text" name="wplauncher_contact_captcha" value="" style="display: none;" />
            <input type="hidden" name="wplauncher_contact_nonce" value="'.wp_create_nonce( "wplauncher_contact" ).'" />
            <input type="hidden" name="action" value="wplauncher_contact" />
            
            <input type="text" name="wplauncher_contact_name" value="'.esc_attr($this->userinput['name']).'" id="wplauncher_contact_name" placeholder="'.$this->labels['name'].'"/>
            
            <input type="text" name="wplauncher_contact_email" value="'.esc_attr($this->userinput['email']).'" id="wplauncher_contact_email" placeholder="'.$this->labels['email'].'"/>
            
            <textarea name="wplauncher_contact_message" id="wplauncher_contact_message" placeholder="'.$this->labels['message'].'">'.esc_textarea($this->userinput['message']).'</textarea>
            
            <input type="submit" value="'.esc_attr($this->labels['submit']).'" id="wplauncher_contact_submit" />
        </form>';

        $return .= '<script type="text/javascript">
        jQuery(document).ready(function($) {
            $("#wplauncher_contact_form").submit(function(e) {
                e.preventDefault();
                var $form = $(this);
                $form.addClass("loading");
                $.post("'.admin_url( 'admin-ajax.php' ).'", $form.serialize(), function(data) {
                    $form.removeClass("loading");
                    if (data == "success") {
                        $form.remove();
                        $("#wplauncher_contact_success").show();
                    } else {
                        $(".wplauncher_contact_error").remove();
                        $.each(data, function(i, v) {
                            if ($("#wplauncher_contact_"+i).length) {
                                $("#wplauncher_contact_"+i).after("<div class=\"wplauncher_contact_error\">"+v+"</div>");
                            } else {
                                $form.prepend("<div class=\"wplauncher_contact_error\">"+v+"</div>");
                            }
                        });
                    }
                }, "json");
            });
        });
        </script>';
        }
        $return .= '<div id="wplauncher_contact_success"'.($this->success ? '' : ' style="display: none;"').'>'.__('Your message has been sent.', $this->plugin_slug).'</div>';
        return $return;
    }
    public function get_errors() {
        $html = '';
        foreach ($this->errors as $error) {
            $html .= '<div class="wplauncher_contact_error">'.$error.'</div>';
        }
        return $html;
    }
}

$wplauncher_contact_form = new wplauncher_contactform();