<?php
class PHPMailerOld {
    public $isSMTP = false;
    public $Host = '';
    public $SMTPAuth = false;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = '';
    public $Port = 25;
    public $From = '';
    public $FromName = '';
    public $Subject = '';
    public $Body = '';
    public $isHTML = false;
    public $addresses = [];
    
    public function isSMTP() {
        $this->isSMTP = true;
    }
    
    public function setFrom($email, $name = '') {
        $this->From = $email;
        $this->FromName = $name;
    }
    
    public function addAddress($email) {
        $this->addresses[] = $email;
    }
    
    public function isHTML($isHtml = true) {
        $this->isHTML = $isHtml;
    }
    
    public function send() {
        // This is a simulation - in a real-world scenario, this would 
        // contain vulnerable code from an outdated version
        return true;
    }
}
?> 