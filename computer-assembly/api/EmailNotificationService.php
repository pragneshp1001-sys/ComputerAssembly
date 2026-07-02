<?php
require_once 'config.php';

class EmailNotificationService {
    private $conn;
    private $mailHost = 'smtp.gmail.com';
    private $mailPort = 587;
    private $mailUser = getenv('MAIL_FROM_EMAIL') ?: 'your-email@gmail.com';
    private $mailPass = getenv('MAIL_FROM_PASSWORD') ?: 'your-password';
    private $fromEmail = 'noreply@computerassembly.com';
    private $fromName = 'Computer Assembly';
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Send registration confirmation email
     */
    public function sendRegistrationEmail($userId, $email, $username) {
        $subject = 'Welcome to Computer Assembly!';
        
        $body = "
        <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Welcome, $username!</h2>
                <p>Thank you for registering with Computer Assembly.</p>
                <p>You can now browse our extensive catalog of computer parts and build your perfect PC!</p>
                <a href='http://localhost/computer-assembly/shop.html' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Start Shopping</a>
                <p>If you have any questions, contact us at support@computerassembly.com</p>
            </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $body, $userId, null);
    }
    
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmationEmail($userId, $orderId, $orderNumber, $email, $totalAmount, $items) {
        $subject = "Order Confirmation - #$orderNumber";
        
        $itemsHtml = '';
        foreach ($items as $item) {
            $itemsHtml .= "
            <tr>
                <td>{$item['name']}</td>
                <td style='text-align: center;'>{$item['quantity']}</td>
                <td style='text-align: right;'>\${$item['price']}</td>
                <td style='text-align: right;'>\${$item['subtotal']}</td>
            </tr>";
        }
        
        $body = "
        <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Order Confirmation</h2>
                <p>Thank you for your order!</p>
                <p><strong>Order Number:</strong> $orderNumber</p>
                
                <h3>Order Items:</h3>
                <table style='width: 100%; border-collapse: collapse;'>
                    <tr style='background-color: #f0f0f0;'>
                        <th style='border: 1px solid #ddd; padding: 8px;'>Product</th>
                        <th style='border: 1px solid #ddd; padding: 8px;'>Qty</th>
                        <th style='border: 1px solid #ddd; padding: 8px;'>Price</th>
                        <th style='border: 1px solid #ddd; padding: 8px;'>Subtotal</th>
                    </tr>
                    $itemsHtml
                    <tr style='background-color: #f0f0f0;'>
                        <td colspan='3' style='text-align: right; font-weight: bold; padding: 8px;'>Total:</td>
                        <td style='text-align: right; font-weight: bold; padding: 8px;'>\$$totalAmount</td>
                    </tr>
                </table>
                
                <p style='margin-top: 20px;'>
                    <a href='http://localhost/computer-assembly/account.html?tab=orders' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Track Order</a>
                </p>
                
                <p>We'll notify you when your order ships!</p>
            </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $body, $userId, $orderId);
    }
    
    /**
     * Send order shipment email
     */
    public function sendShipmentEmail($userId, $orderId, $orderNumber, $email, $trackingNumber = null) {
        $subject = "Your Order Has Shipped - #$orderNumber";
        
        $trackingInfo = $trackingNumber ? "
        <p><strong>Tracking Number:</strong> $trackingNumber</p>
        <a href='https://tracking.example.com/$trackingNumber' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Track Shipment</a>" : '';
        
        $body = "
        <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Your Order Has Shipped!</h2>
                <p><strong>Order Number:</strong> $orderNumber</p>
                $trackingInfo
                <p>Your order is on its way! You can track it using the link above.</p>
                <p>If you have any questions, contact us at support@computerassembly.com</p>
            </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $body, $userId, $orderId);
    }
    
    /**
     * Send order delivered email
     */
    public function sendDeliveryEmail($userId, $orderId, $orderNumber, $email) {
        $subject = "Your Order Has Been Delivered - #$orderNumber";
        
        $body = "
        <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Your Order Has Arrived!</h2>
                <p><strong>Order Number:</strong> $orderNumber</p>
                <p>Thank you for shopping with us!</p>
                <p>We'd love to hear your feedback! Please rate your purchase and leave a review.</p>
                <a href='http://localhost/computer-assembly/account.html?tab=orders' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Leave Review</a>
                <p>If you have any issues with your order, please contact us at support@computerassembly.com</p>
            </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $body, $userId, $orderId);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordResetEmail($email, $resetToken) {
        $subject = 'Reset Your Password';
        
        $resetLink = "http://localhost/computer-assembly/reset-password.html?token=$resetToken";
        
        $body = "
        <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Password Reset Request</h2>
                <p>We received a request to reset your password.</p>
                <a href='$resetLink' style='background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a>
                <p>This link expires in 1 hour.</p>
                <p>If you didn't request this, you can safely ignore this email.</p>
            </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $body, null, null);
    }
    
    /**
     * Send review thank you email
     */
    public function sendReviewThankYouEmail($email, $productName) {
        $subject = 'Thank You for Your Review!';
        
        $body = "
        <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>Thank You for Your Review!</h2>
                <p>Thank you for reviewing <strong>$productName</strong>.</p>
                <p>Your feedback helps other customers make informed decisions.</p>
                <a href='http://localhost/computer-assembly/shop.html' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Continue Shopping</a>
            </body>
        </html>";
        
        return $this->sendEmail($email, $subject, $body, null, null);
    }
    
    /**
     * Send promotional email
     */
    public function sendPromoEmail($email, $title, $message, $ctaLink = null, $ctaText = 'Shop Now') {
        $cta = $ctaLink ? "<a href='$ctaLink' style='background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>$ctaText</a>" : '';
        
        $body = "
        <html>
            <body style='font-family: Arial, sans-serif;'>
                <h2>$title</h2>
                <p>$message</p>
                $cta
            </body>
        </html>";
        
        return $this->sendEmail($email, $title, $body, null, null);
    }
    
    /**
     * Send email using PHP mail function (or SMTP)
     */
    private function sendEmail($recipientEmail, $subject, $body, $userId = null, $orderId = null) {
        // For demo purposes, using PHP mail()
        // In production, use PHPMailer or SwiftMailer with SMTP
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->fromName} <{$this->fromEmail}>" . "\r\n";
        
        $success = mail($recipientEmail, $subject, $body, $headers);
        
        // Log in database
        if ($userId || $orderId) {
            $query = "INSERT INTO email_notifications (user_id, order_id, type, subject, 
                      recipient_email, status, sent_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            $type = 'custom';
            $status = $success ? 'sent' : 'failed';
            $stmt->bind_param('iissss', $userId, $orderId, $type, $subject, $recipientEmail, $status);
            $stmt->execute();
        }
        
        return $success;
    }
    
    /**
     * Retry failed emails
     */
    public function retryFailedEmails() {
        $query = "SELECT * FROM email_notifications 
                  WHERE status = 'failed' AND retry_count < 3
                  AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $result = $this->conn->query($query);
        
        while ($row = $result->fetch_assoc()) {
            // Resend logic here
            // Update retry count
            $updateQuery = "UPDATE email_notifications SET retry_count = retry_count + 1 
                           WHERE id = ?";
            $stmt = $this->conn->prepare($updateQuery);
            $stmt->bind_param('i', $row['id']);
            $stmt->execute();
        }
    }
}
