<?php
function sendSMS($to, $message) {
    // Method 1: Use a free SMS API (e.g., Textlocal, Twilio, Africa's Talking)
    // For demo, we log to a file and display a notice.
    // Replace this with actual API call.
    
    // Example using Africa's Talking (you need API key)
    /*
    $username = 'YOUR_USERNAME';
    $apiKey = 'YOUR_API_KEY';
    $url = 'https://api.africastalking.com/version1/messaging';
    $data = array('username' => $username, 'to' => $to, 'message' => $message);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['ApiKey: ' . $apiKey]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    */
    
    // For local development: log to file
    file_put_contents(__DIR__ . '/../sms_log.txt', date('Y-m-d H:i:s') . " - To: $to - Message: $message\n", FILE_APPEND);
    return true;
}
?>