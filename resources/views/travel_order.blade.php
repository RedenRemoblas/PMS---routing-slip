<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 5px;
        }

        .travel-order-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .travel-order-header {
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
            width: 100px;
            max-width: 100%;
        }

        .logo-right {
            width: 100px;
            max-width: 100%;
        }

        .qr-code {
            position: absolute;
            top: 10px;
            right: 20px;
            width: 135px;
            height: 135px;
            z-index: 100;
        }

        h2 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        p {
            font-size: 12px;
            line-height: 1.2;
            margin: 5px 0;
        }

        .employee-list,
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
    </style>
</head>

<body>

    <div class="travel-order-container">
        @php
            $hash = hash_hmac('sha256', $travelOrder->id, config('app.key'));
            $pdfUrl = route('travel.order.download', ['travelOrder' => $travelOrder->id, 'hash' => $hash]);
        @endphp
        <div class="travel-order-header">
            <img src="{{ asset('images/dict-logo.png') }}" alt="DICT Logo" class="logo-left">
            <div class="header-center">
                <h3>REPUBLIC OF THE PHILIPPINES</h3>
                <h4>DEPARTMENT OF INFORMATION AND</h4>
                <h4>COMMUNICATIONS TECHNOLOGY</h4>
            </div>
            <img src="{{ asset('images/bagong-pilipinas-logo.svg') }}" alt="Bagong Pilipinas Logo" class="logo-right">
        </div>
        
        <!-- QR Code for validation -->
        <div class="qr-code">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($pdfUrl) }}" alt="QR Code">
        </div>


        <div class="travel-order-details">
            <h2>Travel Order No.: {{ $travelOrder->id }}</h2>
            <div style="width: 100%;">
                <div class="float-left">Series of {{ \Carbon\Carbon::parse($travelOrder->date_approved)->format('Y') }}</div>
                <div class="float-right">Date: {{ \Carbon\Carbon::parse($travelOrder->date_approved)->format('F j, Y') }}</div>
            </div>
            <div class="clearfix"></div>

            <p><strong>Place of Origin:</strong> Baguio, Benguet, Philippines</p>
            <p><strong>Farthest Destination:</strong> Pangasinan, Philippines</p>
            <p><strong>Travel Date:</strong> {{ \Carbon\Carbon::parse($travelOrder->inclusive_start_date)->format('F j, Y') }}</p>
            <p><strong>Return Date:</strong> {{ \Carbon\Carbon::parse($travelOrder->inclusive_end_date)->format('F j, Y') }}</p>
            <p><strong>Purpose:</strong> {{ $travelOrder->purpose }}</p>
        </div>

        <div class="employee-list">
            <p><strong> Authority to Travel is hereby granted to: </strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Division</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($travelOrderDetails as $detail)
                        <tr>
                            <td>{{ $detail['employee_name'] }}</td>
                            <td>{{ $detail['position'] }}</td>
                            <td>{{ $detail['division'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="destinations">
            <p><strong>Destinations:</strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Destination</th>
                        <th>Aerial Distance (Km)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Pangasinan, Philippines</td>
                        <td>65.40</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="note">
            A report of your travel must be submitted to the Agency Head/Supervising Official within 7 days from
            completion of travel.
            Liquidation of each cash advance should be made after the issuance of travel authority by the Regional
            Director.
        </div>

        <div class="approval-stages">
            <p><strong>Travel Approval Log:</strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Seq</th>
                        <th>Approver</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($approvalStages as $index => $stage)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $stage['approver_name'] }}</td>
                            <td>
                                @if ($stage['status'] === 'approved' && $index < count($approvalStages) - 1)
                                    Endorsed
                                @else
                                    {{ ucfirst($stage['status']) }}
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($stage['date'])->format('F j, Y g:i A') }}</td>
                            <td>{{ $stage['remarks'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="note">
                <strong>NOTE:</strong> This Travel Order is valid only if FULLY APPROVED, check QR code for validation.
            </div>
        </div>
    </div>

</body>

</html>
