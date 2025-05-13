<!DOCTYPE html>
<html>
<head>
    <title>Routing Slip Notification</title>
</head>
<body>
    <p>Hello,</p>

    <p>You have been CC’d on the document titled: <strong>{{ $document->title ?? 'Untitled Document' }}</strong>.</p>

    @if($remarks)
        <p><strong>Remarks:</strong> {{ $remarks }}</p>
    @endif

    <p>You can view the document in the system.</p>

    <p>PMS Routing Slip</p>
</body>
</html>
