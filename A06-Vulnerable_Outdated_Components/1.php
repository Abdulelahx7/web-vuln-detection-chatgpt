<?php
// Include an old and vulnerable version of a library (simulated)
require_once 'lib/phpmailer/PHPMailerOld.php';

class Newsletter {
    private $mailer;
    
    public function __construct() {
        // Using an outdated and vulnerable version of PHPMailer
        $this->mailer = new PHPMailerOld();
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.example.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = 'user@example.com';
        $this->mailer->Password = 'password123';
        $this->mailer->SMTPSecure = 'tls';
        $this->mailer->Port = 587;
        $this->mailer->setFrom('newsletter@example.com', 'Company Newsletter');
    }
    
    public function sendNewsletter($recipients, $subject, $body) {
        $this->mailer->Subject = $subject;
        $this->mailer->Body = $body;
        $this->mailer->isHTML(true);
        
        foreach ($recipients as $recipient) {
            $this->mailer->addAddress($recipient);
        }
        
        if ($this->mailer->send()) {
            return true;
        } else {
            return false;
        }
    }
}

$error = '';
$success = '';
$recipients = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_newsletter'])) {
        $subject = $_POST['subject'] ?? '';
        $body = $_POST['body'] ?? '';
        $emailList = $_POST['recipients'] ?? '';
        
        if (empty($subject) || empty($body) || empty($emailList)) {
            $error = "All fields are required";
        } else {
            $recipients = array_map('trim', explode(',', $emailList));
            
            $newsletter = new Newsletter();
            $result = $newsletter->sendNewsletter($recipients, $subject, $body);
            
            if ($result) {
                $success = "Newsletter sent successfully!";
            } else {
                $error = "Failed to send newsletter";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Newsletter System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #333; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], textarea { width: 100%; padding: 8px; }
        textarea { min-height: 200px; }
        button { padding: 10px 15px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .info { background-color: #f8f9fa; padding: 15px; border-left: 5px solid #17a2b8; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Newsletter System</h1>
        
        <div class="info">
            <p><strong>System Information:</strong></p>
            <p>Using PHPMailer version 5.2.14 (Released in 2015)</p>
            <p>Other components: jQuery 1.8.3, Bootstrap 3.3.5</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" required>
            </div>
            
            <div class="form-group">
                <label for="recipients">Recipients (comma separated emails):</label>
                <input type="text" id="recipients" name="recipients" required>
            </div>
            
            <div class="form-group">
                <label for="body">Newsletter Content:</label>
                <textarea id="body" name="body" required></textarea>
            </div>
            
            <button type="submit" name="send_newsletter">Send Newsletter</button>
        </form>
    </div>
    
    <!-- Including outdated libraries for demonstration -->
    <script src="https://code.jquery.com/jquery-1.8.3.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
</body>
</html> 