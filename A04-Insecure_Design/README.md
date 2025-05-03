# A04: Insecure Design

## Description
Insecure Design refers to flaws in the design and architecture of an application. Unlike implementation issues, these are design-level problems that cannot be fixed simply by implementing the existing design correctly. Insecure design vulnerabilities happen when threat modeling, secure design patterns, and principles aren't adequately implemented from the start.

Insecure design vulnerabilities may include:
- Missing or ineffective security controls
- Business logic flaws
- Lack of rate limiting and other anti-automation features
- Inadequate threat modeling
- Trust boundaries not properly identified or enforced

## Vulnerable Code Examples


### Hard Example: Insecure Password Reset Mechanism

```
Vulnerable Code Snippet
phpelseif (isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $email = $_POST['email'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword !== $confirmPassword) {
        $error = "Passwords do not match.";
    } 
    elseif (!isset($reset_tokens[$token])) {
        $error = "Invalid or expired token.";
    } 
    else {
        $tokenInfo = $reset_tokens[$token];
        
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $users[$email]['password'] = $hashedPassword;
        
        $reset_tokens[$token]['used'] = true;
        
        $success = "Password has been reset successfully!";
        logAction("PASSWORD_RESET", "Email: $email, Token: $token");
        
        $currentStep = 'success';
    }
}
Proof of Concept (Attack Steps)

The attacker requests a password reset for their own account (e.g., john.smith@example.com).
The system generates a valid token and creates a reset link.
Instead of using this token to reset their own password, the attacker modifies the email parameter in the reset form to target another user (e.g., admin@example.com).
When the form is submitted, the system:

Checks if the token exists (it does)
Uses the attacker-supplied email (admin@example.com) without verifying that this email matches the one associated with the token
Resets the password for the admin account



This happens because the system only checks if the token exists (!isset($reset_tokens[$token])) but doesn't validate that the token was created for the specific email being reset. The validateResetToken() function has this check, but it's not used during the actual password reset process.
Exploit URL Example
After obtaining a valid token for their own account, an attacker could craft a request like:
POST /reset.php?step=reset
token=<valid-token-for-john>
email=admin@example.com
new_password=hacked123
confirm_password=hacked123
```