<?php
namespace PHPMailer\src\PHPMailer;
namespace PHPMailer\src\Exception;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
require './PHPMailer/src/Exception.php';
require './PHPMailer/src/PHPMailer.php';
require './PHPMailer/src/SMTP.php';

class Mail
{
public function send_email($address,$content){
try
{
$mail = new PHPMailer;
$mail->isSMTP(); // Set mailer to use SMTP
$mail->Port = 587;
$mail->Host = 'smtp.gmail.com'; // Specify main and backup SMTP servers
$mail->SMTPAuth = true; // Enable SMTP authentication
$mail->Username = 's95516@pollub.edu.pl'; // SMTP username
$mail->Password = '7hv47esn'; // SMTP password
$mail->SMTPSecure = 'tls'; // Enable encryption, 'ssl' also accepted
$mail->From = 's95516@pollub.edu.pl';
$mail->FromName = 'OTP source';
$mail->addAddress($address); // Add a recipient
$mail->WordWrap = 40; // Set word wrap to 40 characters
$mail->isHTML(true); // Set email format to HTML
$mail->Subject = 'Your security code';
$mail->Body = 'This is your authentication code <B>'.$content.'</B>';
$mail->AltBody = 'This is your authentication code '.$content.'';
if (!$mail->send())
{
echo 'Message could not be sent.';
echo 'Mailer Error: ' . $mail->ErrorInfo;
}
else {
echo 'Message has been sent';
}
}catch
(Exception $e){
echo "Exception &nbsp" . $e->getMessage();
}
}
}
