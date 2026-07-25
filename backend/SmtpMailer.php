<?php
/**
 * Simple SMTP Mailer for PHP
 */
class SmtpMailer {
    private $host;
    private $port;
    private $username;
    private $password;
    private $fromEmail;
    private $fromName;
    
    public function __construct($host, $port, $username, $password, $fromName) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->fromEmail = $username;
        $this->fromName = $fromName;
    }
    
    public function send($to, $subject, $message) {
        $newline = "\r\n";
        $smtpConnect = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        
        if(empty($smtpConnect)) {
            error_log("Failed to connect to SMTP server: $errno $errstr");
            return false;
        }
        
        $this->readResponse($smtpConnect); // 220
        
        fputs($smtpConnect, "EHLO " . $this->host . $newline);
        $this->readResponse($smtpConnect); // 250
        
        fputs($smtpConnect, "STARTTLS" . $newline);
        $this->readResponse($smtpConnect); // 220
        
        stream_socket_enable_crypto($smtpConnect, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        
        fputs($smtpConnect, "EHLO " . $this->host . $newline);
        $this->readResponse($smtpConnect); // 250
        
        fputs($smtpConnect, "AUTH LOGIN" . $newline);
        $this->readResponse($smtpConnect); // 334
        
        fputs($smtpConnect, base64_encode($this->username) . $newline);
        $this->readResponse($smtpConnect); // 334
        
        fputs($smtpConnect, base64_encode($this->password) . $newline);
        $this->readResponse($smtpConnect); // 235
        
        fputs($smtpConnect, "MAIL FROM: <" . $this->fromEmail . ">" . $newline);
        $this->readResponse($smtpConnect); // 250
        
        fputs($smtpConnect, "RCPT TO: <" . $to . ">" . $newline);
        $this->readResponse($smtpConnect); // 250
        
        fputs($smtpConnect, "DATA" . $newline);
        $this->readResponse($smtpConnect); // 354
        
        $headers = "MIME-Version: 1.0" . $newline;
        $headers .= "Content-type: text/html; charset=utf-8" . $newline;
        $headers .= "To: <" . $to . ">" . $newline;
        $headers .= "From: " . $this->fromName . " <" . $this->fromEmail . ">" . $newline;
        $headers .= "Subject: " . $subject . $newline;
        
        fputs($smtpConnect, $headers . $newline . $message . $newline . "." . $newline);
        $this->readResponse($smtpConnect); // 250
        
        fputs($smtpConnect, "QUIT" . $newline);
        fclose($smtpConnect);
        
        return true;
    }
    
    private function readResponse($smtpConnect) {
        $data = "";
        while($str = fgets($smtpConnect, 515)) {
            $data .= $str;
            if(substr($str, 3, 1) == " ") break;
        }
        return $data;
    }
}
?>
