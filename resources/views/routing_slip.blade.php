<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Routing Slip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 5px;
            font-size: 13px;
        }

        .routing-slip-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .routing-slip-header {
            padding-top: 0px;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;

        }

        .header-center {
            text-align: center;
            flex-grow: 1;
            padding: 0 20px;
        }

        .header-center h3 {
            margin: 0;
            color: #003366;
            font-size: 14px;
        }

        .header-center h4 {
            margin: 5px 0;
            color: #003366;
            font-size: 12px;
        }

        .logo-left {
            width: 400px;
            margin-bottom: 50px;
            /* margin-right: 20px; */
            max-width: 100%;
        }

        .logo-right {
            width: 100px;
            max-width: 100%;
        }

        .qr-code {
            margin-top: 100px;
            margin-right: 5px;
            position: absolute;
            top: 10px;
            right: 20px;
            width: 100px;
            height: 100px;
            z-index: 100;
        }

        h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        p {
            font-size: 15px;
            line-height: 1.2;
            margin: 5px 0;
        }

        .document-list,
        .approval-stages {
            margin-top: 20px;
            font-size: 12px;
            line-height: 1.0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 6px 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #f4f4f4;
        }

        .approval-stages tr {
            font-size: 8pt;
        }

        .note {
            font-size: 8pt;
            margin-top: 20px;
            line-height: 1.2;
        }

        .float-left {
            float: left;
        }

        .float-right {
            float: right;
        }

        .clearfix {
            clear: both;
        }
        
        .approver-signatures {
            margin-top: 30px;
            margin-bottom: 20px;
        }
        
        .signature-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 5px;
            margin-top: 15px;
            border: none;
        }
        
        .signature-cell {
            text-align: center;
            vertical-align: bottom;
            width: 25%;
            padding: 5px;
            border: none;
        }
        
        .signature-line {
            border-bottom: 1px solid #000;
            height: 30px;
            margin-bottom: 5px;
            position: relative;
        }
        
        .approved-mark {
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            font-style: italic;
            color: #008000;
        }
        
        .signature-name {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 2px;
        }
        
        .signature-position,
        .signature-division {
            font-size: 8px;
            color: #555;
            line-height: 1.2;
        }
    </style>
</head>

<body>

    <div class="routing-slip-container">
        @php
            $hash = hash_hmac('sha256', $routingSlip->id, config('app.key'));
            $pdfUrl = route('routing.slip.download', ['routingSlip' => $routingSlip->id, 'hash' => $hash]);
            $qrUrl = route('routing-slip.qr', ['routingSlip' => $routingSlip->id]);
        @endphp
        <div class="routing-slip-header">
            <img src="{{ $dict_logo }}" alt="DICT Logo" class="logo-left">
            <div class="header-center">
                <!-- <h3>REPUBLIC OF THE PHILIPPINES</h3>
                <h4>DEPARTMENT OF INFORMATION AND</h4>
                <h4>COMMUNICATIONS TECHNOLOGY</h4> -->
            </div>
            <!-- <img src="{{ $bagong_pilipinas_logo }}" alt="Bagong Pilipinas Logo" class="logo-right"> -->
        </div>
        
        <!-- QR Code for validation -->
        <div class="qr-code">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($qrUrl) }}" alt="QR Code">
        </div>


        <div class="routing-slip-details">
            <h2>Routing Slip No.: {{ $routingSlip->id }}</h2>
            <div style="width: 100%;">
                <div class="float-left">Series of {{ \Carbon\Carbon::parse($routingSlip->created_at)->format('Y') }}</div>
                <div class="float-right">Date: {{ \Carbon\Carbon::parse($routingSlip->created_at)->format('F j, Y') }}</div>
            </div>
            <div class="clearfix"></div>

            <p><strong>Title:</strong> {{ $routingSlip->title }}</p>
            <p><strong>Remarks:</strong> {{ $routingSlip->remarks }}</p>
            <p><strong>Status:</strong> {{ ucfirst($routingSlip->status) }}</p>
            <p><strong>Created By:</strong> {{ $routingSlip->creator->name }}</p>
            <p><strong>Created Date:</strong> {{ \Carbon\Carbon::parse($routingSlip->created_at)->format('F j, Y') }}</p>
        </div>

        @if($routingSlip->document_type !== 'physical')
        <div class="document-list">
            <p><strong>Attached Documents:</strong></p>
            <table>
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Uploaded Date</th>
                        <th>File Size</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($files as $file)
                        <tr>
                            <td>{{ $file['file_name'] }}</td>
                            <td>{{ \Carbon\Carbon::parse($file['uploaded_at'])->format('F j, Y') }}</td>
                            <td>{{ $file['file_size'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
        
        <div class="approval-stages">
            <p><strong>Routing Approval Log:</strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Seq</th>
                        <th>Approver</th>
                        <th>Division</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sequences as $index => $sequence)
                        <tr>
                            <td>{{ $sequence['sequence_number'] }}</td>
                            <td>{{ $sequence['approver_name'] }}</td>
                            <td>{{ $sequence['division'] }}</td>
                            <td>{{ $sequence['position'] }}</td>
                            <td style="text-align: center;">
                                @if ($sequence['status'] === 'approved')
                                    <span style="font-family: DejaVu Sans, sans-serif; font-size: 16px; font-weight: bold; color: #008000;">☑</span>
                                @elseif ($sequence['status'] === 'rejected')
                                    <span style="font-family: DejaVu Sans, sans-serif; font-size: 16px; font-weight: bold; color: #FF0000;">☑</span>
                                @elseif ($sequence['status'] === 'pending')
                                    <span style="font-family: DejaVu Sans, sans-serif; font-size: 16px; font-weight: bold; color: #FFA500;">☐</span>
                                @else
                                    {{ ucfirst($sequence['status']) }}
                                @endif
                            </td>
                            <td>
                                @if ($sequence['acted_at'])
                                    {{ \Carbon\Carbon::parse($sequence['acted_at'])->format('F j, Y g:i A') }}
                                @else
                                    {{ \Carbon\Carbon::parse($sequence['updated_at'])->format('F j, Y g:i A') }}
                                @endif
                            </td>
                            <td>{{ $sequence['remarks'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="approver-signatures">
                <p><strong>Approver Signatures:</strong></p>
                <table class="signature-table">
                    <tr>
                        @foreach ($sequences as $index => $sequence)
                            <td class="signature-cell">
                                <div class="signature-line">
                                    @if ($sequence['status'] === 'approved')
                                        <span class="approved-mark">APPROVED</span>
                                    @endif
                                </div>
                                <div class="signature-name">{{ $sequence['approver_name'] }}</div>
                                <div class="signature-position">{{ $sequence['position'] }}</div>
                                <div class="signature-division">{{ $sequence['division'] }}</div>
                            </td>
                            @if (($index + 1) % 4 == 0 && $index < count($sequences) - 1)
                                </tr><tr>
                            @endif
                        @endforeach
                    </tr>
                </table>
            </div>
            
            <div class="note">
                <strong>NOTE:</strong> This Routing Slip is valid only if FULLY APPROVED, check QR code for validation.
            </div>
        </div>
    </div>

</body>

</html>