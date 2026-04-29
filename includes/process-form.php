<?php
/**
 * Canadian Fashion Week - Form Processing Script
 * Handles Email Notifications via SMTP using PHPMailer
 */

// Error Reporting for Debugging (Set to 0 in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Configuration
$admin_email = 'toolgrram3@gmail.com'; 
$smtp_user = 'toolgrram3@gmail.com';
$smtp_pass = 'akjki9kj555=--';

// PHPMailer configuration
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

/**
 * Helper function to send email via SMTP
 */
function sendEmail($to, $subject, $message, $from_name = 'Canadian Fashion Week', $attachments = []) {
    global $smtp_user, $smtp_pass;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_user;
        $mail->Password   = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom($smtp_user, $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo($smtp_user, 'Canadian Fashion Week');

        // Add attachments if any (standard $_FILES array)
        if (!empty($attachments)) {
            foreach ($attachments as $key => $file) {
                if (is_array($file['name'])) {
                    foreach ($file['name'] as $idx => $name) {
                        if ($file['error'][$idx] === UPLOAD_ERR_OK) {
                            $mail->addAttachment($file['tmp_name'][$idx], $name);
                        }
                    }
                } else {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $mail->addAttachment($file['tmp_name'], $file['name']);
                    }
                }
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $message));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? 'General Inquiry';
    $timestamp = date('Y-m-d H:i:s');
    
    // Collect data
    $data = $_POST;
    unset($data['form_type']); // Remove helper field
    
    // Determine the user's email for the greeting
    $user_email = $_POST['email'] ?? '';
    $first_name = $_POST['firstName'] ?? $_POST['first_name'] ?? 'Guest';

    // 1. Admin Notification Email Template
    $details_html = "";
    foreach ($data as $key => $value) {
        $label = ucwords(str_replace(['_', '-'], ' ', $key));
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        $details_html .= "<tr>
            <td style='padding: 12px 0; border-bottom: 1px solid #edf2f7; font-family: sans-serif; font-size: 13px; font-weight: 600; color: #4a5568; width: 40%;'>$label</td>
            <td style='padding: 12px 0; border-bottom: 1px solid #edf2f7; font-family: sans-serif; font-size: 13px; color: #1a202c;'>$value</td>
        </tr>";
    }

    $logo_url = "https://canadianfashionweek.ca/images/white-logo.png"; // Fallback URL or relative if absolute needed

    $admin_email_content = "
    <body style='margin: 0; padding: 0; background-color: #f4f7f9;'>
        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f4f7f9; padding: 40px 20px;'>
            <tr>
                <td align='center'>
                    <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
                        <tr>
                            <td style='background-color: #000000; padding: 30px; text-align: center;'>
                                <img src='$logo_url' alt='Canadian Fashion Week' style='height: 45px; width: auto;'>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 40px 50px;'>
                                <h1 style='font-family: sans-serif; font-size: 24px; font-weight: 700; color: #1a1a1a; margin: 0 0 10px 0;'>New Submission: $form_type</h1>
                                <p style='font-family: sans-serif; font-size: 16px; color: #666666; margin: 0 0 30px 0;'>A new form submission has been received.</p>
                                
                                <div style='background-color: #f8fafc; border-radius: 12px; padding: 25px; border: 1px solid #e2e8f0;'>
                                    <table width='100%' border='0' cellspacing='0' cellpadding='0'>
                                        $details_html
                                    </table>
                                </div>

                                <div style='margin-top: 40px; padding-top: 25px; border-top: 1px solid #eeeeee; text-align: center;'>
                                    <p style='font-family: sans-serif; font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin: 0;'>Automated System Notification</p>
                                    <p style='font-family: sans-serif; font-size: 12px; color: #94a3b8; margin: 5px 0 0 0;'>Time: $timestamp</p>
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>";

    $admin_subject = "CFW | New $form_type from $first_name";
    sendEmail($admin_email, $admin_subject, $admin_email_content, 'CFW Notification System', $_FILES);

    // 2. User Confirmation Email Template
    if (!empty($user_email)) {
        $user_subject = "Thank you for contacting Canadian Fashion Week!";
        $user_email_content = "
        <body style='margin: 0; padding: 0; background-color: #f4f7f9;'>
            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f4f7f9; padding: 40px 20px;'>
                <tr>
                    <td align='center'>
                        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>
                            <tr>
                                <td style='background-color: #000000; padding: 40px; text-align: center;'>
                                    <img src='$logo_url' alt='Canadian Fashion Week' style='height: 50px; width: auto;'>
                                </td>
                            </tr>
                            <tr>
                                <td style='padding: 50px; text-align: center;'>
                                    <h1 style='font-family: sans-serif; font-size: 28px; font-weight: 700; color: #000; margin: 0 0 20px 0;'>Hello $first_name,</h1>
                                    <p style='font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #333333; margin: 0 0 25px 0;'>
                                        Thank you for reaching out to <strong>Canadian Fashion Week</strong>. We have received your $form_type and our team will review it shortly.
                                    </p>
                                    <p style='font-family: sans-serif; font-size: 15px; color: #666666; margin: 0 0 35px 0;'>
                                        We appreciate your interest and will get back to you as soon as possible.
                                    </p>
                                    <a href='https://canadianfashionweek.ca' style='display: inline-block; background-color: #000; color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease;'>Visit Website</a>
                                    
                                    <div style='margin-top: 50px; padding-top: 30px; border-top: 1px solid #eeeeee;'>
                                        <p style='font-family: sans-serif; font-size: 14px; color: #999999; margin: 0;'>
                                            Best regards,<br>
                                            <span style='color: #1a1a1a; font-weight: 600;'>The CFW Team</span><br>
                                            <a href='https://canadianfashionweek.ca' style='color: #000; text-decoration: none;'>www.canadianfashionweek.ca</a>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <p style='font-family: sans-serif; font-size: 11px; color: #a0aec0; text-align: center; margin-top: 20px;'>
                            © 2026 Canadian Fashion Week. All rights reserved.
                        </p>
                    </td>
                </tr>
            </table>
        </body>";
        
        sendEmail($user_email, $user_subject, $user_email_content, 'Canadian Fashion Week');
    }

    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
} else {
    header('HTTP/1.1 403 Forbidden');
    echo "Direct access forbidden";
}
