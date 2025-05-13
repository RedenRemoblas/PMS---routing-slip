<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <style>
        @page {
            size: A4;
            margin-left: 5mm;
            /* Adjust left margin as needed */
            margin-right: 10mm;
            /* Adjust right margin as needed */
            margin-top: 5mm;
            margin-bottom: 5mm;

        }


        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }

        .container {
            width: 100%;
            border: 1px solid #000;
            padding: 10px;
            box-sizing: border-box;
        }

        .container-noboarder {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            margin-bottom: -10px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header-left {
            text-align: left;
            margin-bottom: 10px;
        }

        .header-right {
            text-align: right;
            margin-bottom: 10px;
        }

        .header img {
            width: 200px;
            /* Make the logo bigger */
            height: auto;
        }

        .header h1 {
            font-size: 16px;
            margin: 5px 0;
        }

        .section {
            margin-bottom: 10px;
        }

        .7-content,
        .section-title {
            font-weight: bold;
            margin-left: 25px;
            padding: 2px 0;
            font-size: 9px;
        }

        .7C-content,
        .section-title {
            font-weight: bold;
            margin-left: 0px;
            padding: 2px 0;
            font-size: 9px;
        }

        .section-title-1,
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 5px;
            padding: 2px 0;
            font-size: 10px;
        }

        .section-title-notbold,
        .section-title {
            text-transform: uppercase;
            margin-bottom: 5px;
            padding: 2px 0;
            font-size: 10px;
        }

        .section-title {
            border-top: 1px solid #000;
        }

        .content {
            padding: 5px;
            font-size: 12px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .column {
            width: 48%;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            table-layout: auto;
            /* Allow the table to adjust column widths */
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            font-size: 10px;
            overflow: hidden;
            /* Hide overflow content */
            word-wrap: break-word;
            /* Allow long words to break */
            hyphens: auto;
            /* Enable hyphenation for long words */
        }

        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
        }

        .checkbox-group div {
            width: 50%;
            font-size: 12px;
        }

        .signature {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .signature div {
            width: 45%;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
        }

        .left-aligned {
            text-align: left;
            padding-left: 5px;
        }

        .content-table td {
            padding: 2px 5px;
        }

        .stamp-receipt {
            text-align: left;
            /* Align text to the left */
            border: 1px dashed #000;
            /* Thin, dashed border */
            border-radius: 2px;
            /* Optional: rounded corners */
            padding: 5px;
            box-sizing: border-box;
            font-size: 12px;
            display: inline-block;
            /* Prevent it from taking full width */
            margin-left: auto;
            /* Push it to the right */
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            padding: 0;
        }

        .header-table td {
            padding: 0;
            vertical-align: top;
        }

        .header-table .logo {
            text-align: left;
        }

        .header-table .spacer {
            width: 30%;
            /* Take full width */
        }

        .header-table .stamp {
            text-align: left;
            border: 1px dashed #000;
            padding: 12px;
            border-radius: 2px;
            font-size: 10px;
        }

        .centered-heading {
            text-align: center;
            margin-bottom: 10px;
            /* Adjust as needed */
            font-size: 13px;

        }

        .centered-h5 {
            text-align: center;
            margin: 0px 0;
            /* Adjust spacing around the line */
            padding: 0;
            /* Remove any padding if needed */
            font-size: 12px;

        }

        .centered-approver {
            text-align: center;
            margin-top: 11px;
            /* Adjust the margin-top value as needed */
            font-size: 10px;
            color: gray;
            /* Set the text color to gray */
        }

        .notbold-h4 {
            margin: 0px 0;
            /* Adjust spacing around the line */
            padding: 0;
            /* Remove any padding if needed */
        }

        .right-align {
            text-align: right;
            /* Align text to the right */
        }

        .separator {
            border-top: 1px solid #000;
            /* Change color and thickness as needed */
            margin: 1px 0;
            /* Adjust spacing around the line */
            width: 100%;
            /* Full width of the parent container */
        }

        .separator-invisible {
            margin: 2px 0;
            /* Adjust spacing around the line */
            width: 100%;
            /* Full width of the parent container */
        }

        .checkbox-label {
            line-height: 1.0;
            /* Adjust as needed */
        }

        .checkbox-label input[type="checkbox"] {
            vertical-align: middle;
            /* Align checkbox with text */
            margin-right: 5px;
            /* Space between checkbox and text */
        }

        p {
            padding: 0;
            /* Remove padding from all <p> elements */
            margin: 0;
            /* Optionally remove margin as well */
        }

        .bordered-table-container {
            display: flex;
            justify-content: center;
            /* Align horizontally to the center */
            align-items: center;
            /* Align vertically to the center */
            margin-top: 10px;
            /* Optional: Add space above the table if needed */
        }

        .bordered-table {
            border-collapse: collapse;
            width: auto;
            /* Adjust table width as needed */
            text-align: center;
            /* Center align text in cells */
            table-layout: auto;
            /* Allow the table to adjust column widths */
        }

        .bordered-table th,
        .bordered-table td {
            border: 1px solid #000;
            /* Border color and thickness */
            padding: 8px;
            /* Padding inside cells */
            text-align: center;
            /* Center align text in cells */
            font-size: 12px;
            /* Font size for table text */
            overflow: hidden;
            word-wrap: break-word;
            /* Handle long text wrapping */
            hyphens: auto;
            /* Enable hyphenation for long words */
        }

        .bordered-table th {
            font-weight: bold;
            /* Make header text bold */
        }

        .S7B {
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container-noboarder">
        <div class="section">
            <table class="header-table">
                <tr>
                    <td class="logo" style="width: 50%;"><img src="../public/images/dict-logo.png"
                            style="margin: 0; padding: 0;" width="200px;"></td>
                    <td class="spacer"></td> <!-- Spacer to push stamp to the right -->
                    <td class="stamp">Stamp of Date Receipt</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="centered-heading">
        <h3>APPLICATION FOR LEAVE</h3>
    </div>
    <div class="container">
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    <div class="section-title-1">1. OFFICE/DEPARTMENT - DISTRICT/SCHOOL &nbsp;&nbsp;&nbsp;&nbsp;2. NAME:
                    </div>
                </td>
                <td style="width: 15%;">
                    <div class="section-title-1 left-align">(Last)</div>
                </td>
                <td style="width: 15%;">
                    <div class="section-title-1 left-align">(First)</div>
                </td>
                <td style="width: 15%;">
                    <div class="section-title-1 left-align">(Middle)</div>
                </td>
            </tr>
            <tr>
                <td>
                    <div class="content">DICT {{  $employee->region }}</div>
                </td>
                <td style="text-align: left;">
                    <div class="content"> {{  $employee->lastname }}</div>
                </td>
                <td style="text-align: left;">
                    <div class="content"> {{  $employee->firstname }}</div>
                </td>
                <td style="text-align: left;">
                    <div class="content"> {{  $employee->middlename }}</div>
                </td>
            </tr>
        </table>
        <div class="separator"></div>

        <table class="header-table">
            <tr>
                <td style="width: 40%;">
                <div class="section-title-1">3. DATE OF FILING: {{ \Carbon\Carbon::parse($leave->date_filed)->format('F j, Y') }}</div>

                </td>
                <td style="width: 25%;">
                    <div class="section-title-1 left-align">4. POSITION:  {{  $employee->position->name }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="section-title-1 left-align">5. SALARY: ₱  </div>
                </td>

            </tr>

        </table>

        <div class="separator"></div>
        <div class="separator"></div>

        <div class="centered-h5">
            <b>6. DETAILS OF APPLICATION</b>
        </div>
        <div class="section">

            <table class="table">
                <tbody>
                    <tr>
                    <td width="60%">
    <div class="separator-invisible"></div>
    <notbold-h4>6.A TYPE OF LEAVE TO BE AVAILED OF</notbold-h4>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Vacation Leave' ? 'checked' : '' }}>
        Vacation Leave (Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Mandatory/Forced Leave' ? 'checked' : '' }}>
        Mandatory/Forced Leave (Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Sick Leave' ? 'checked' : '' }}>
        Sick Leave (Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Maternity Leave' ? 'checked' : '' }}>
        Maternity Leave (R.A. No. 11210 / IRR issued by CSC, DOLE and SSS)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Paternity Leave' ? 'checked' : '' }}>
        Paternity Leave (R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Special Privilege Leave' ? 'checked' : '' }}>
        Special Privilege Leave (Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Solo Parent Leave' ? 'checked' : '' }}>
        Solo Parent Leave (RA No. 8972 / CSC MC No. 8, s. 2004)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Study Leave' ? 'checked' : '' }}>
        Study Leave (Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == '10-Day VAWC Leave' ? 'checked' : '' }}>
        10-Day VAWC Leave (RA No. 9262 / CSC MC No. 15, s. 2005)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Rehabilitation Privilege' ? 'checked' : '' }}>
        Rehabilitation Privilege (Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Special Leave Benefits for Women' ? 'checked' : '' }}>
        Special Leave Benefits for Women (RA No. 9710 / CSC MC No. 25, s. 2010)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Special Emergency (Calamity) Leave' ? 'checked' : '' }}>
        Special Emergency (Calamity) Leave (CSC MC No. 2, s. 2012, as amended)
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="approval" {{ $leave->leaveType->leave_name == 'Adoption Leave' ? 'checked' : '' }}>
        Adoption Leave (R.A. No. 8552)
    </p>
    <p>Others: ________________________________________</p>
</td>

<td width="40%">
    <notbold-h4>6.B DETAILS OF LEAVE</notbold-h4>
    <div class="separator-invisible"></div>
    <p>In case of Vacation/Special Privilege Leave:</p>
    <div class="separator-invisible"></div>
    <div class="separator-invisible"></div>
    <div class="separator-invisible"></div>
    <p class="checkbox-label">
        <input type="checkbox" name="leave_location" value="philippines"
               {{ $leave->details == 'vacation_within_philippines' ? 'checked' : '' }}>
        Within the Philippines: ___________________
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="leave_location" value="abroad"
               {{ $leave->details == 'vacation_abroad' ? 'checked' : '' }}>
        Abroad (Specify): _______________________
    </p>
    <div class="separator-invisible"></div>
    <p class="checkbox-label">In case of Sick Leave:</p>
    <div class="separator-invisible"></div>
    <p class="checkbox-label">
        <input type="checkbox" name="sick_leave" value="hospital"
               {{ $leave->details == 'sick_in_hospital' ? 'checked' : '' }}>
        In Hospital (Specify Illness): _______________
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="sick_leave" value="out_patient"
               {{ $leave->details == 'sick_out_patient' ? 'checked' : '' }}>
        Out Patient (Specify Illness): _______________
        __________________________________________
    </p>
    <div class="separator-invisible"></div>
    <p>In case of Special Leave Benefits for Women:</p>
    <div class="separator-invisible"></div>
    <p>(Specify Illness): ____________________________
        __________________________________________</p>
    <p>In case of Study Leave:</p>
    <div class="separator-invisible"></div>
    <p class="checkbox-label">
        <input type="checkbox" name="study_leave" value="masters_degree"
               {{ $leave->details == 'study_masters_degree' ? 'checked' : '' }}>
        Completion of Masters Degree
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="study_leave" value="bar_review"
               {{ $leave->details == 'study_bar_board_review' ? 'checked' : '' }}>
        BAR/Board Examination Review
    </p>
    <div class="separator-invisible"></div>
    <div class="separator-invisible"></div>
    <div class="separator-invisible"></div>
    <p class="checkbox-label">Other purpose:</p>
    <div class="separator-invisible"></div>
    <div class="separator-invisible"></div>
    <div class="separator-invisible"></div>
    <p class="checkbox-label">
        <input type="checkbox" name="other_purpose" value="monetization"
               {{ $leave->details == 'monetization' ? 'checked' : '' }}>
        Monetization of Leave Credits
    </p>
    <p class="checkbox-label">
        <input type="checkbox" name="other_purpose" value="terminal_leave"
               {{ $leave->details == 'terminal_leave' ? 'checked' : '' }}>
        Terminal Leave
    </p>
</td>

                    </tr>
                    <tr>
                        <td>
                            <div class="row">

                                <p>6.C NUMBER OF WORKING DAYS APPLIED FOR</p>



                                <div class="column" style="margin-left: 20px;">
                                    <p>{{ $leave->total_days}} Day(s)</p>
                                    <div class="separator"></div>
                                    <p>INCLUSIVE DATES</p>
                                    <div class="separator-invisible"></div>
                                    <p>


@php
    $leaveDetails = $leave->leaveDetails->sortBy('leave_date');
    $dateRanges = [];
    $currentRange = null;

    foreach ($leaveDetails as $detail) {
        $date = \Carbon\Carbon::parse($detail->leave_date);

        if ($currentRange === null) {
            $currentRange = ['start' => $date, 'end' => $date];
        } elseif ($date->diffInDays($currentRange['end']) === 1) {
            $currentRange['end'] = $date;
        } else {
            $dateRanges[] = $currentRange;
            $currentRange = ['start' => $date, 'end' => $date];
        }
    }

    if ($currentRange !== null) {
        $dateRanges[] = $currentRange;
    }
@endphp

@foreach($dateRanges as $range)
    @if($range['start']->equalTo($range['end']))
        {{ $range['start']->format('F j, Y') }}
    @else
        {{ $range['start']->format('F j') }} - {{ $range['end']->format('j, Y') }}
    @endif
    @if(!$loop->last)
        ,
    @endif
@endforeach


</p>
                                    <div class="separator"></div>
                                </div>

                            </div>
                        </td>
                        <td>
                            <div class="row">

                            <p>6.D COMMUTATION</p>
<p class="checkbox-label">
    <input type="checkbox" name="commutation" value="Not Requested" {{ $leave->commutation == 'Not Requested' ? 'checked' : '' }}> Not Requested
</p>
<p class="checkbox-label">
    <input type="checkbox" name="commutation" value="Requested" {{ $leave->commutation == 'Requested' ? 'checked' : '' }}> Requested
</p>



                                <center>
                                    <div class="separator-invisible"></div>
                                    <p style="text-decoration: overline; ">
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(Signature of Applicant)
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                    </p>
                                </center>


                            </div>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <div class="centered-h5">
            <div class="separator"></div>
            <div class="separator"></div>
            <b>7. DETAILS OF ACTION ON APPLICATION</b>
        </div>




        <div class="section">

        <table class="table">
    <thead>
        <tr>
            <th style="width:60%">7.A CERTIFICATION OF LEAVE CREDITS</th>
            <th style="width:40%">7.B RECOMMENDATION</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="width:60%">
                <center>
                    <p>As of {{ now()->format('F j, Y') }}</p>
                </center>
                <div class="bordered-table-container">
                    <table class="bordered-table" style="margin: 0 auto; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr>
                                <th style="padding: 2px 5px;"></th>
                                <th style="padding: 2px 5px;">Vacation Leaves</th>
                                <th style="padding: 2px 5px;">Sick Leave</th>
                                <th style="padding: 2px 5px;">CO Credit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $vacationLeave = $leave->employee->leaveBalances->firstWhere('leaveType.leave_name', 'Vacation Leave');
                                $sickLeave = $leave->employee->leaveBalances->firstWhere('leaveType.leave_name', 'Sick Leave');
                                $cto = $leave->employee->leaveBalances->firstWhere('leaveType.id', 14);

                            @endphp
                           <tr style="height: 1px;">
                            <td style="padding: 2px 5px;">Total Earned</td>
                            <td style="padding: 2px 5px;">{{ $vacationLeave->days_remaining ?? 0 }}</td>
                            <td style="padding: 2px 5px;">{{ $sickLeave->days_remaining ?? 0 }}</td>
                            <td style="padding: 2px 5px;">{{ $cto->days_remaining ?? 0 }}</td>
                        </tr>
                        <tr style="height: 1px;">
                            <td style="padding: 2px 5px;">Less this Application</td>
                            <td style="padding: 2px 5px;">
                                {{ $vacationLeave && $leave->leave_type_id == $vacationLeave->leave_type_id ? $leave->total_days : 0 }}
                            </td>
                            <td style="padding: 2px 5px;">
                                {{ $sickLeave && $leave->leave_type_id == $sickLeave->leave_type_id ? $leave->total_days : 0 }}
                            </td>
                            <td style="padding: 2px 5px;">
                                {{ $cto && $leave->leave_type_id == $cto->leave_type_id ? $leave->total_days : 0 }}
                            </td>
                        </tr>
                        <tr style="height: 1px;">
                            <td style="padding: 2px 5px;">Balance</td>
                            <td style="padding: 2px 5px;">
                                {{ $vacationLeave ? ($vacationLeave->days_remaining - ($leave->leave_type_id == $vacationLeave->leave_type_id ? $leave->total_days : 0)) : 0 }}
                            </td>
                            <td style="padding: 2px 5px;">
                                {{ $sickLeave ? ($sickLeave->days_remaining - ($leave->leave_type_id == $sickLeave->leave_type_id ? $leave->total_days : 0)) : 0 }}
                            </td>
                            <td style="padding: 2px 5px;">
                                {{ $cto ? ($cto->days_remaining - ($leave->leave_type_id == $cto->leave_type_id ? $leave->total_days : 0)) : 0 }}
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    &nbsp; &nbsp;   &nbsp; &nbsp;
                    <div style="text-align: center;">
                        <hr style="width: 80%; margin: 0 auto;">
                        <p style="font-size: 12px; margin: 0;">HR</p>
                    </div>
                </div>

            </td>
            <td style="width:40%">
                <!-- Your 7.B RECOMMENDATION content here -->

                <p class="checkbox-label">
                    <input type="checkbox" name="other_purpose" value="terminal_leave"> For approval
                </p>
                <p class="checkbox-label">
                    <input type="checkbox" name="other_purpose" value="terminal_leave"> For approval/disapproval due to ___________________
                </p>
                <p style="text-align: right;">
                    __________________________________________<br>
                    __________________________________________<br>
                    __________________________________________
                </p>
                &nbsp;
                <div style="text-align: center;">
                    <p>
                        ENGR REYNALDO T. SY
                    </p>
                    <p>
                        Regional Director
                    </p>
                    <hr style="width: 80%; border: 1px solid; margin: 0 auto;">
                    <p>
                        (Authorized Officer)
                    </p>
                </div>
            </td>

        </tr>
    </tbody>
</table>



            <div class="separator"></div>
            <div class="separator"></div>
            <table class="header-table">
                <tr>
                    <td style="width: 50%;">
                        <div class="section-title-notbold">7.C APPROVED FOR:</div>
                        <div class="7-content">_______ days with pay</div>
                        <div class="7-content">_______ days without pay</div>
                        <div class="7-content">_______ others (specify)</div>

                    </td>

                    <td style="width: 10%;">
                        <div class="section-title-notbold left-align"></div>
                    </td>
                    <td style="width: 40%;">
                        <div class="section-title-notbold left-align">7.D DISAPPROVED DUE TO:</div>
                        <div class="7C-content">______________________________________________________________</div>
                        <div class="7C-content">______________________________________________________________</div>
                        <div class="7C-content">______________________________________________________________</div>


                    </td>


                </tr>
                <br>
            </table>
            <table class="header-table">
                <div class="centered-approver">
                    <b>MARIA TERRESA M. CAMBA
                        <br>
                        Assistant Secretary for Regional Operations
                    </b>
                    <div class="7C-content">___________________________________________________</div>
                </div>



            </table>

        </div>

    </div>

</body>

</html>
