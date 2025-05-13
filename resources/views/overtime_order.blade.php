<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Order</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .overtime-order-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .overtime-order-header img {
            width: 400px;
            margin: 0 auto;
            display: block;
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

    <div class="overtime-order-container">
        <div class="overtime-order-header">
            <img src="../public/images/dict-logo.png" alt="DICT Logo">
        </div>

        <div class="overtime-order-details">
            <h2>Overtime Order No.: {{ $overtimeOrder->id }}</h2>

            <div class="clearfix"></div>

            <p><strong>Purpose:</strong> {{ $overtimeOrder->purpose }}</p>
        </div>

        <div class="employee-list">
            <p><strong> Participating Employees: </strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Position</th>
                        <th>Division</th>
                        <th>Hours To Render</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($overtimeOrderDetails as $detail)
                        <tr>
                            <td>{{ $detail->employee->full_name ?? 'N/A' }}</td>
                            <td>{{ $detail->position ?? 'N/A' }}</td>
                            <td>{{ $detail->division ?? 'N/A' }}</td>
                            <td>{{ $detail->hours_rendered ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="approval-stages">
            <p><strong> Overtime Approval Log: </strong></p>
            <table>
                <thead>
                    <tr>
                        <th>Sequence</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($approvalStages as $index => $stage)
                        <tr>
                            <td>{{ $stage['sequence'] }}</td>
                            <td>{{ $stage['approver_name'] }}</td>
                            <td>
                                @if ($stage['status'] === 'approved' && $index < count($approvalStages) - 1)
                                    Endorsed
                                @else
                                    {{ ucfirst($stage['status']) }}
                                @endif
                            </td>
                            <td>{{ $stage['date'] }}</td>
                            <td>{{ $stage['remarks'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="note">
            A report of your overtime must be submitted to the Agency Head/Supervising Official within 7 days from
            completion.
        </div>

        <div class="note">
            This Request to Render Overtime is FULLY APPROVED.<br><br>NOTE: This Overtime Order is system generated and can be validated
            through the QR code.
        </div>
    </div>

</body>

</html>
