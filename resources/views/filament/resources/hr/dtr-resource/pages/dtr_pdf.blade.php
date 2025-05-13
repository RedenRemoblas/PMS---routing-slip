<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly DTR Report</title>
    <style>
      * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    font-size: 12px; /* Keeping font size as it is */
    margin: 0;
    padding: 10px;
}

.page-container {
    display: flex;
    flex-wrap: nowrap;
    justify-content: space-between;
    width: 100%;
}

.container {
    width: 48%; /* Adjust container width to allow space for both copies */
    border: 1px solid black;
    padding: 3px;
    float: left;
    page-break-inside: avoid;
    margin-right: 2%; /* Adding equal space between the containers */
}

.header, .sign-section {
    text-align: center;
    margin-bottom: 5px;
    line-height: 1.2;
}

.dtr-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    table-layout: fixed;
}

.dtr-table th, .dtr-table td {
    border: 1px solid black;
    padding: 1px 3px; /* Minimal padding for each cell to save space */
    text-align: center;
}

.dtr-table th {
    background-color: #f4f4f4;
}

.dtr-table th:nth-child(6), .dtr-table td:nth-child(6) {
    width: 8%; /* Decrease width of the "Hours" column */
}

.dtr-table th:nth-child(1), .dtr-table td:nth-child(1) {
    width: 20%; /* Slightly wider "Day" column */
}

.dtr-table th:nth-child(2), .dtr-table td:nth-child(2),
.dtr-table th:nth-child(3), .dtr-table td:nth-child(3),
.dtr-table th:nth-child(4), .dtr-table td:nth-child(4),
.dtr-table th:nth-child(5), .dtr-table td:nth-child(5) {
    width: 16%; /* Distribute the remaining space equally among these columns */
}

.remarks-col {
    text-align: left;
}

.note {
    font-size: 8px;
    margin-top: 5px;
    text-align: left;
}

@media print {
    .page-container {
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        justify-content: space-between;
    }

    .container {
        float: left;
        width: 48%; /* Same width for both containers */
        page-break-inside: avoid;
        margin-right: 2%; /* Ensure equal margin between containers */
    }
}
    </style>
</head>

<body>
    <div class="page-container">
        <!-- First Copy on the Left -->
        <div class="container">
            <div class="header">
                <h2>DAILY TIME RECORD</h2>
                <p>{{ $employee->full_name }}</p>
                <div class="note">
                    <p>For the month: {{ $month }}</p>
                    <p>Official hours for ARRIVALS and DEPARTURES</p>
                    <p>REGULAR DAYS: Mon. (8-5pm) Tuesday-Friday (Flexi Time)</p>
                </div>
            </div>

            <div class="dtr-section">
                <table class="dtr-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>AM In</th>
                            <th>AM Out</th>
                            <th>PM In</th>
                            <th>PM Out</th>
                            <th>Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalHours = 0; // Initialize total hours
                        @endphp

                        @foreach (array_slice($processedDtrRecords, 0, ceil(count($processedDtrRecords))) as $day)
                        <tr>
                            <td>{{ $day['day'] ?? 'N/A' }} {{ $day['dayname'] ?? '' }}</td>
                            @if (!empty($day['remarks']))
                            <td colspan="5" class="remarks-col">
                                {{ $day['remarks'] }}
                            </td>
                            @else
                            <td>{{ $day['am_in'] ?? '--:--' }}</td>
                            <td>{{ $day['am_out'] ?? '--:--' }}</td>
                            <td>{{ $day['pm_in'] ?? '--:--' }}</td>
                            <td>{{ $day['pm_out'] ?? '--:--' }}</td>
                            <td>{{ isset($day['hrs_rendered']) ? number_format($day['hrs_rendered'], 2) : '0.00' }}</td>

                            @php
                                $totalHours += $day['hrs_rendered'] ?? 0; // Add the hours to total
                            @endphp
                            @endif
                        </tr>
                        @endforeach

                        <!-- Total Hours Row -->
                        <tr>
                            <td colspan="5"><strong>Total Hours</strong></td>
                            <td><strong>{{ number_format($totalHours, 2) }}</strong></td>
                        </tr>
                        <!-- Additional Rows for VL, OT/UT, PL/SPL, and SL -->
                        <tr>
                            <td colspan="5" style="text-align: left;">Vacation Leave (VL)</td>
                            <td>{{ $vlHours ?? '0.00' }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: left;">Overtime/Undertime (OT/UT)</td>
                            <td>{{ $otutHours ?? '0.00' }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: left;">Privilege Leave/Special Privilege Leave (PL/SPL)</td>
                            <td>{{ $plsplHours ?? '0.00' }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: left;">Sick Leave (SL)</td>
                            <td>{{ $slHours ?? '0.00' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="sign-section">
                <p class="note">I certify on my honor that the above is true and correct report of the hours of work performed. Record of
                    which was made daily at the time of arrival and departure from office.</p>
                    <br>
                <p style="margin-top: 20px">{{ $employee->full_name }}</p>
                <p>(Signature of official or employee)</p>
                <br>
                <p class="note">Verified as to correctness:</p>
                <p style="margin-top: 10px">{{ $employee->supervisor }}</p>
                <p>IMMEDIATE SUPERVISOR</p>
            </div>
        </div>

        <!-- Second Copy on the Right (Duplicate of the first one) -->
        <div class="container">
            <div class="header">
                <h2>DAILY TIME RECORD</h2>
                <p>{{ $employee->full_name }}</p>
                <div class="note">
                    <p>For the month: {{ $month }}</p>
                    <p>Official hours for ARRIVALS and DEPARTURES</p>
                    <p>REGULAR DAYS: Mon. (8-5pm) Tuesday-Friday (Flexi Time)</p>
                </div>
            </div>

            <div class="dtr-section">
                <table class="dtr-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>AM In</th>
                            <th>AM Out</th>
                            <th>PM In</th>
                            <th>PM Out</th>
                            <th>Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalHours = 0; // Initialize total hours
                        @endphp

                        @foreach (array_slice($processedDtrRecords, 0, ceil(count($processedDtrRecords))) as $day)
                        <tr>
                            <td>{{ $day['day'] ?? 'N/A' }} {{ $day['dayname'] ?? '' }}</td>
                            @if (!empty($day['remarks']))
                            <td colspan="5" class="remarks-col">
                                {{ $day['remarks'] }}
                            </td>
                            @else
                            <td>{{ $day['am_in'] ?? '--:--' }}</td>
                            <td>{{ $day['am_out'] ?? '--:--' }}</td>
                            <td>{{ $day['pm_in'] ?? '--:--' }}</td>
                            <td>{{ $day['pm_out'] ?? '--:--' }}</td>
                            <td>{{ isset($day['hrs_rendered']) ? number_format($day['hrs_rendered'], 2) : '0.00' }}</td>

                            @php
                                $totalHours += $day['hrs_rendered'] ?? 0; // Add the hours to total
                            @endphp
                            @endif
                        </tr>
                        @endforeach

                        <!-- Total Hours Row -->
                        <tr>
                            <td colspan="5"><strong>Total Hours</strong></td>
                            <td><strong>{{ number_format($totalHours, 2) }}</strong></td>
                        </tr>
                        <!-- Additional Rows for VL, OT/UT, PL/SPL, and SL -->
                        <tr>
                            <td colspan="5" style="text-align: left;">Vacation Leave (VL)</td>
                            <td>{{ $vlHours ?? '0.00' }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: left;">Overtime/Undertime (OT/UT)</td>
                            <td>{{ $otutHours ?? '0.00' }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: left;">Privilege Leave/Special Privilege Leave (PL/SPL)</td>
                            <td>{{ $plsplHours ?? '0.00' }}</td>
                        </tr>
                        <tr>
                            <td colspan="5" style="text-align: left;">Sick Leave (SL)</td>
                            <td>{{ $slHours ?? '0.00' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="sign-section">
                <p class="note">I certify on my honor that the above is true and correct report of the hours of work performed. Record of
                    which was made daily at the time of arrival and departure from office.</p>
                    <br>
                <p style="margin-top: 20px">{{ $employee->full_name }}</p>
                <p>(Signature of official or employee)</p>
                <br>
                <p class="note">Verified as to correctness:</p>
                <p style="margin-top: 10px">{{ $employee->supervisor }}</p>
                <p>IMMEDIATE SUPERVISOR</p>
            </div>
        </div>
    </div>
</body>

</html>
