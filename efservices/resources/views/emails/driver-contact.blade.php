<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Contact Message</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .header { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-bottom: 20px; }
        .priority-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .priority-high { background-color: #dc3545; color: white; }
        .priority-normal { background-color: #17a2b8; color: white; }
        .priority-low { background-color: #6c757d; color: white; }
        .content { margin: 20px 0; }
        .message-box { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin-top: 15px; white-space: pre-wrap; }
        .sender-info { background: #e9ecef; padding: 10px; border-radius: 4px; margin-top: 15px; font-size: 14px; }
        .footer { margin-top: 20px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Message from Your Carrier</h2>
            @if(isset($contactData['priority']))
                <span class="priority-badge priority-{{ $contactData['priority'] }}">
                    {{ ucfirst($contactData['priority']) }} Priority
                </span>
            @endif
        </div>
        
        <div class="content">
            <p><strong>Subject:</strong> {{ $contactData['subject'] ?? 'No Subject' }}</p>
            
            <p><strong>Message:</strong></p>
            <div class="message-box">{{ $contactData['message'] ?? 'No message content' }}</div>
            
            @if(isset($senderName) || isset($senderEmail))
            <div class="sender-info">
                <strong>From:</strong><br>
                @if(isset($senderName))
                    {{ $senderName }}<br>
                @endif
                @if(isset($senderEmail))
                    {{ $senderEmail }}
                @endif
            </div>
            @endif
        </div>
        
        <div class="footer">
            <p>This message was sent from EF Services Driver Management System.</p>
            <p>If you need to respond to this message, please contact your carrier directly or through the driver portal.</p>
            <p>Please do not reply to this email directly.</p>
        </div>
    </div>
</body>
</html>