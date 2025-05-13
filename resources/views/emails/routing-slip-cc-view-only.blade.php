<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Routing Slip CC Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .content {
            margin-bottom: 20px;
        }
        .document-details {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .footer {
            font-size: 12px;
            text-align: center;
            margin-top: 20px;
            color: #777;
        }
        .button {
            display: inline-block;
            background-color: #3490dc;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 15px;
            font-weight: bold;
        }
        .button:hover {
            background-color: #2779bd;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Routing Slip Notification</h2>
        </div>
        
        <div class="content">
            @if(isset($isMultipleRecipients) && $isMultipleRecipients)
            <p>Dear Recipients,</p>
            
            <p>You have been added as Carbon Copy (CC) recipients for the following Routing Slip document:</p>
            @else
            <p>Dear {{ $recipientName ?? 'Recipient' }},</p>
            
            <p>You have been added as a Carbon Copy (CC) recipient for the following Routing Slip document:</p>
            @endif
            
            <div class="document-details">
                <h3>Document Details:</h3>
                <p><strong>Document Title:</strong> {{ $document->title }}</p>
                <p><strong>Document ID:</strong> {{ $document->id }}</p>
                <p><strong>Status:</strong> {{ ucfirst($document->status) }}</p>
                <p><strong>Created By:</strong> {{ $document->creator->name ?? 'System User' }}</p>
                <p><strong>Created Date:</strong> {{ $document->created_at->format('F j, Y') }}</p>
                
                @if($remarks)
                <p><strong>Remarks:</strong> {{ $remarks }}</p>
                @endif
            </div>
            
            <p>You can view this document by clicking the button below:</p>
            
            <div style="text-align: center;">
                <a href="{{ url('/admin/routing-slip/documents/' . $document->id) }}" class="button">View Document</a>
            </div>
            
            <p style="margin-top: 20px;">This is for your information only. No approval action is required from you as a CC recipient.</p>
            
            <p>PMS Routing Slip</p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} PMS Mailer System</p>
        </div>
    </div>
</body>
</html>