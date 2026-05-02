<?php
/**
 * Canadian Fashion Project - Form Processing Script
 * Handles Email Notifications via SMTP using PHPMailer
 */

// Error Reporting for Debugging (Set to 0 in production)
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Configuration
$admin_email = 'info@canadianfashionw.com';
$smtp_user = 'toolgram3@gmail.com';
$smtp_pass = 'fihwrjdzscwhxixy';

// PHPMailer configuration
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

/**
 * Helper function to send email via SMTP
 */
function sendEmail($to, $subject, $message, $from_name = 'Canadian Fashion Project', $attachments = [])
{
    global $smtp_user, $smtp_pass;
    $mail = new PHPMailer(true);
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = function ($str, $level) {
        $logPath = dirname(__DIR__) . '/data/smtp_debug.log';
        file_put_contents($logPath, date('Y-m-d H:i:s') . " [$level] " . $str . PHP_EOL, FILE_APPEND);
    };

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom($smtp_user, $from_name);
        $mail->addAddress($to);
        $mail->addReplyTo($smtp_user, 'Canadian Fashion Project');

        // Add attachments if any (standard $_FILES array)
        if (!empty($attachments)) {
            foreach ($attachments as $key => $file) {
                if (isset($file['tmp_name']) && !empty($file['tmp_name'])) {
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
        }

        // Add custom string attachments (e.g., base64 decoded signatures)
        if (isset($attachments['custom_string_attachments']) && is_array($attachments['custom_string_attachments'])) {
            foreach ($attachments['custom_string_attachments'] as $filename => $content) {
                $mail->addStringAttachment($content, $filename);
            }
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '</p>'], "\n", $message));

        $mail->send();
        return true;
    } catch (Exception $e) {
        $errorLogPath = dirname(__DIR__) . '/data/smtp_error.log';
        file_put_contents($errorLogPath, date('Y-m-d H:i:s') . " Error: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        throw new Exception("Mailer Error: {$mail->ErrorInfo}");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_type = $_POST['form_type'] ?? 'General Inquiry';
    $timestamp = date('Y-m-d H:i:s');

    // Collect data
    $data = $_POST;
    unset($data['form_type']); // Remove helper field

    // Explicitly handle checkboxes for Petition form
    if ($form_type === 'Petition') {
        $data['showPublicly'] = isset($_POST['showPublicly']) ? 'Yes' : 'No';
    }

    // Determine the user's email for the greeting
    $user_email = $_POST['email'] ?? '';
    $first_name = $_POST['firstName'] ?? $_POST['first_name'] ?? $_POST['fullName'] ?? 'Guest';
    if (strpos($first_name, ' ') !== false) {
        $first_name = explode(' ', $first_name)[0];
    }

    /**
     * Helper to compress base64 images (like signatures)
     */
    function compressBase64Image($base64_string, $max_width = 500, $quality = 50)
    {
        if (!extension_loaded('gd') || empty($base64_string) || strpos($base64_string, 'data:image/') !== 0) {
            return $base64_string;
        }

        try {
            $parts = explode(',', $base64_string);
            $data = base64_decode($parts[1]);
            $img = imagecreatefromstring($data);
            if (!$img)
                return $base64_string;

            $width = imagesx($img);
            $height = imagesy($img);

            // Resize if too wide
            if ($width > $max_width) {
                $new_height = ($height / $width) * $max_width;
                $tmp_img = imagecreatetruecolor($max_width, $new_height);

                // Set white background (signatures are usually black on white/transparent)
                $white = imagecolorallocate($tmp_img, 255, 255, 255);
                imagefill($tmp_img, 0, 0, $white);

                imagecopyresampled($tmp_img, $img, 0, 0, 0, 0, $max_width, $new_height, $width, $height);
                $img = $tmp_img;
            }

            ob_start();
            imagejpeg($img, null, $quality);
            $compressed_data = ob_get_clean();
            imagedestroy($img);

            return 'data:image/jpeg;base64,' . base64_encode($compressed_data);
        } catch (\Exception $e) {
            return $base64_string;
        }
    }

    // Debug Logging
    $log_file = dirname(__DIR__) . '/data/form_debug.log';
    $debug_msg = date('Y-m-d H:i:s') . " | Form: $form_type | Fields: " . implode(', ', array_keys($data)) . PHP_EOL;
    if (!empty($data['digitalSignature'])) {
        $debug_msg .= "Signature Length: " . strlen($data['digitalSignature']) . PHP_EOL;
        $debug_msg .= "Signature Prefix: " . substr($data['digitalSignature'], 0, 30) . PHP_EOL;
    } else {
        $debug_msg .= "Signature is MISSING or EMPTY in POST data" . PHP_EOL;
    }
    file_put_contents($log_file, $debug_msg, FILE_APPEND);

    /* 
    // Temporarily disabling compression to rule out GD library issues
    foreach ($data as $key => &$val) {
        if (is_string($val) && strpos($val, 'data:image/') === 0) {
            $val = compressBase64Image($val);
        }
    }
    unset($val); 
    */

    try {
        // 1. Save to CSV
        $dataDir = dirname(__DIR__) . '/data';
        if (!file_exists($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // Sanitize form type for filename
        $safe_form_name = preg_replace('/[^a-z0-9]/', '_', strtolower($form_type));
        if (empty($safe_form_name))
            $safe_form_name = "submission";

        $csv_file = $dataDir . "/submissions_" . $safe_form_name . ".csv";
        $file_handle = fopen($csv_file, 'a');

        if ($file_handle) {
            // Add headers if file is new
            if (filesize($csv_file) === 0) {
                $headers = ['Timestamp'];
                foreach ($data as $key => $value) {
                    $headers[] = ucwords(str_replace(['_', '-'], ' ', $key));
                }
                fputcsv($file_handle, $headers);
            }

            $row = [$timestamp];
            foreach ($data as $value) {
                if (is_array($value)) {
                    $value = implode('; ', $value);
                }

                // If value is a base64 image (signature), don't store the whole thing in CSV
                if (strpos($value, 'data:image/') === 0) {
                    $value = "[Digital Signature Provided]";
                }

                $row[] = $value;
            }
            fputcsv($file_handle, $row);
            fclose($file_handle);
        }

        // 2. Admin Notification Email Template
        $details_html = "";
        $signature_to_attach = null;

        foreach ($data as $key => $value) {
            $label = ucwords(str_replace(['_', '-'], ' ', $key));
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            // Check if value is a base64 image (like digital signature)
            $display_value = htmlspecialchars($value);
            if (is_string($value) && strpos($value, 'data:image/') === 0) {
                $signature_to_attach = $value;
                $display_value = "<span style='color: #b91c1c; font-weight: bold;'>[Digital Signature - See Attachment]</span>";
            }

            $details_html .= "<tr>
                <td style='padding: 12px 0; border-bottom: 1px solid #edf2f7; font-family: sans-serif; font-size: 13px; font-weight: 600; color: #4a5568; width: 40%;'>$label</td>
                <td style='padding: 12px 0; border-bottom: 1px solid #edf2f7; font-family: sans-serif; font-size: 13px; color: #1a202c;'>$display_value</td>
            </tr>";
        }

        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        $logo_url = $base_url . "/images/white-logo.png";

        $admin_email_content = "
        <body style='margin: 0; padding: 0; background-color: #f4f7f9;'>
            <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f4f7f9; padding: 40px 20px;'>
                <tr>
                    <td align='center'>
                        <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05);'>
                            <tr>
                                <td style='background-color: #fff; padding: 30px; text-align: center;'>
                                    <img src='$logo_url' alt='Canadian Fashion Project' style='height: 45px; width: auto;'>
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

        $admin_subject = "CFP | New $form_type from $first_name";

        // Prepare attachments array
        $email_attachments = $_FILES;

        // Handle Digital Signature as an attachment
        if (!empty($signature_to_attach)) {
            $sig_parts = explode(',', $signature_to_attach);
            if (count($sig_parts) > 1) {
                $sig_data = base64_decode($sig_parts[1]);
                if ($sig_data) {
                    $email_attachments['custom_string_attachments']['signature.png'] = $sig_data;
                }
            }
        }

        sendEmail($admin_email, $admin_subject, $admin_email_content, 'CFP Notification System', $email_attachments);

        // 2. User Confirmation Email Template
        if (!empty($user_email)) {
            $is_newsletter = (stripos($form_type, 'know') !== false || stripos($form_type, 'newsletter') !== false);
            $is_guestlist = (stripos($form_type, 'guestlist') !== false);

            $user_subject = $is_newsletter ? "Welcome to Canadian Fashion Project!" : ($is_guestlist ? "Guestlist Request Received!" : "Thank you for contacting Canadian Fashion Project!");

            if ($is_newsletter) {
                $thank_you_msg = "Thank you for joining our exclusive community! You're now subscribed to <strong>Stay In The Know</strong>.";
                $sub_msg = "You'll be the first to receive updates on season schedules, exclusive guestlists, and behind-the-scenes content.";
            } elseif ($is_guestlist) {
                $thank_you_msg = "We've successfully received your request for the <strong>Guestlist</strong>.";
                $sub_msg = "Our team will review your application and if approved, you will receive a formal invitation with further details.";

                // Add comments to the user confirmation if provided
                if (!empty($_POST['comments'])) {
                    $comments = htmlspecialchars($_POST['comments']);
                    $sub_msg .= "<br><br><strong>Your Message:</strong><br><i style='color: #666;'>\"$comments\"</i>";
                }
            } elseif (stripos($form_type, 'Petition') !== false) {
                $first_name = $_POST['fullName'] ?? 'Supporter';
                $user_subject = "Thank you for signing the Petition!";
                $thank_you_msg = "Thank you for adding your voice to the <strong>Canadian Fashion Project Petition</strong>.";
                $sub_msg = "By standing with us, you are helping to create a safer, fairer, and more transparent fashion industry for models across Canada and beyond.<br><br>Please share this petition with your network to help us reach our goal!";
            } elseif (stripos($form_type, 'Opt-Out') !== false) {
                $user_subject = "Privacy Request Received - Canadian Fashion Project";
                $thank_you_msg = "We have received your <strong>Opt-Out Request</strong> regarding your personal information.";
                $sub_msg = "Our legal team will process your request within 15 business days in accordance with applicable privacy laws. You will receive a follow-up confirmation once the process is complete.";
            } else {
                $thank_you_msg = "Thank you for reaching out to <strong>Canadian Fashion Project</strong>. We have received your $form_type and our team will review it shortly.";
                $sub_msg = "We appreciate your interest and will get back to you as soon as possible.";
            }

            $user_email_content = "
            <body style='margin: 0; padding: 0; background-color: #f4f7f9;'>
                <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f4f7f9; padding: 40px 20px;'>
                    <tr>
                        <td align='center'>
                            <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05);'>
                                <tr>
                                    <td style='background-color: #fff; padding: 40px; text-align: center;'>
                                        <img src='$logo_url' alt='Canadian Fashion Project' style='height: 50px; width: auto;'>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding: 50px; text-align: center;'>
                                        <h1 style='font-family: sans-serif; font-size: 28px; font-weight: 700; color: #000; margin: 0 0 20px 0;'>Hello $first_name,</h1>
                                        <p style='font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #333333; margin: 0 0 25px 0;'>
                                            $thank_you_msg
                                        </p>
                                        <p style='font-family: sans-serif; font-size: 15px; color: #666666; margin: 0 0 35px 0;'>
                                            $sub_msg
                                        </p>
                                        <a href='https://canadianfashionproject.com' style='display: inline-block; background-color: #000; color: #ffffff; padding: 16px 40px; text-decoration: none; border-radius: 12px; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s ease;'>Visit Website</a>
                                        
                                        <div style='margin-top: 50px; padding-top: 30px; border-top: 1px solid #eeeeee;'>
                                            <p style='font-family: sans-serif; font-size: 14px; color: #999999; margin: 0;'>
                                                Best regards,<br>
                                                <span style='color: #1a1a1a; font-weight: 600;'>The CFP Team</span><br>
                                                <a href='https://canadianfashionproject.com' style='color: #000; text-decoration: none;'>canadianfashionproject.com</a>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <p style='font-family: sans-serif; font-size: 11px; color: #a0aec0; text-align: center; margin-top: 20px;'>
                                © 2026 Canadian Fashion Project. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </body>";

            sendEmail($user_email, $user_subject, $user_email_content, 'Canadian Fashion Project');
        }

        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
        exit();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
} else {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['status' => 'error', 'message' => 'Direct access forbidden']);
    exit();
}
