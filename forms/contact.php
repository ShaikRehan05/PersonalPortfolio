<?php
session_start();

// Input Validation and Sanitization
function sanitize_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

$name = sanitize_input($_POST['name']);
$email = sanitize_input($_POST['email']);
$subject = sanitize_input($_POST['subject']);
$message = sanitize_input($_POST['message']);

// Validate Email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  die('Invalid email format.');
}

// Validate Message Length
if (strlen($message) < 10) {
  die('Message must be at least 10 characters long.');
}

// Honeypot Field for Spam Protection
if (!empty($_POST['honeypot'])) {
  die('Spam detected!');
}

// CSRF Protection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token.');
  }
}

// Rate Limiting
$ip = $_SERVER['REMOTE_ADDR'];
$rate_limit_file = 'rate_limit.log';
$rate_limit_time = 60; // Time frame in seconds
$rate_limit_count = 3; // Maximum number of submissions

$rate_limit_data = file_get_contents($rate_limit_file);
$rate_limit_entries = explode("\n", $rate_limit_data);

$submission_count = 0;
foreach ($rate_limit_entries as $entry) {
  if (strpos($entry, $ip) !== false) {
    $submission_count++;
  }
}

if ($submission_count >= $rate_limit_count) {
  die('You have reached the maximum number of submissions. Please try again later.');
}

file_put_contents($rate_limit_file, "$ip\n", FILE_APPEND);

// Load PHP Email Form Library
if (file_exists($php_email_form = '../assets/vendor/php-email-form/php-email-form.php')) {
  include($php_email_form);
} else {
  die('Unable to load the "PHP Email Form" Library!');
}

$contact = new PHP_Email_Form;
$contact->ajax = true;

$contact->to = 'rehansalikulquadri@gmail.com'; // Replace with your email
$contact->from_name = $name;
$contact->from_email = $email;
$contact->subject = $subject;

// SMTP Configuration (Uncomment and fill in your SMTP credentials)
/*
$contact->smtp = array(
  'host' => 'smtp.gmail.com',
  'username' => 'your-email@gmail.com',
  'password' => 'your-email-password',
  'port' => '587',
  'encryption' => 'tls'
);
*/

// Email Template
$email_body = "
  <h2>New Contact Form Submission</h2>
  <p><strong>Name:</strong> $name</p>
  <p><strong>Email:</strong> $email</p>
  <p><strong>Subject:</strong> $subject</p>
  <p><strong>Message:</strong> $message</p>
";

$contact->add_message($email_body, 'Email Body');

// Log Form Submissions
$log_file = 'form_submissions.log';
$log_message = "Name: $name, Email: $email, Subject: $subject, Message: $message\n";
file_put_contents($log_file, $log_message, FILE_APPEND);

// Send Email
try {
  if ($contact->send()) {
    echo 'Message sent successfully!';
  } else {
    throw new Exception('Failed to send message. Please try again later.');
  }
} catch (Exception $e) {
  die('Error: ' . $e->getMessage());
}
?>
