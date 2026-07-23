<?php

function sendResetEmail(string $recipientEmail, string $recipientName, string $resetLink): bool
{
    $apiKey = getenv('SENDGRID_API_KEY');
    $senderEmail = "tehjingjing2006@gmail.com";
    $senderName = "School Portal System";

    // Build email payload
    $emailData = [
        'personalizations' => [
            [
                'to' => [
                    [
                        'email' => $recipientEmail,
                        'name' => $recipientName
                    ]
                ],
                'subject' => 'Password Reset Request - Student Portal'
            ]
        ],
        'from' => [
            'email' => $senderEmail,
            'name' => $senderName
        ],
        'content' => [
            [
                'type' => 'text/html',
                'value' => "
                    <h2>Password Reset Request</h2>
                    <p>Hi {$recipientName},</p>
                    <p>You requested a password reset for your student account.</p>
                    <p>This link is valid for 1 hour only:</p>
                    <p><a href='{$resetLink}'>{$resetLink}</a></p>
                    <p>If you did not request this, ignore this email.</p>
                "
            ]
        ]
    ];

    // Call SendGrid Web API via cURL
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($emailData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // SendGrid returns HTTP 202 Accepted on successful send
    if ($httpCode === 202) {
        return true;
    }

    // Error handling, compatible with original debug logic
    $errMsg = "SendGrid API Error: HTTP {$httpCode} | Response: {$response} | Curl Error: {$curlError}";
    error_log($errMsg);
    $GLOBALS['mailDebug'] = $errMsg;
    return false;
}
