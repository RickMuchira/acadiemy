<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Class Marks – {{ $class->display_name }}</title>
    <style>
        @page { size: A4 landscape; margin: 6mm; }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #000;
            background: #fff;
        }

        .page {
            width: 100%;
            max-width: 277mm;
            margin: 0 auto;
            padding: 5mm 6mm;
        }

        /* ── HEADER ── */
        .header {
            display: table;
            width: 100%;
            border-bottom: 1.5px solid #000;
            padding-bottom: 3px;
            margin-bottom: 3px;
        }

        .header-logo {
            display: table-cell;
            width: 36px;
            vertical-align: middle;
            text-align: center;
        }

        .header-logo img {
            max-height: 32px;
            max-width: 32px;
        }

        .logo-placeholder {
            width: 32px;
            height: 32px;
            border: 1px dashed #bbb;
            display: inline-block;
        }

        .header-center {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .school-name {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .school-sub {
            font-size: 7.5pt;
            color: #444;
            margin-top: 1px;
        }

        /* ── REPORT TITLE ── */
        .report-title {
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 2px 0;
            background: #e8e8e8;
            border: 1px solid #999;
            margin-bottom: 3px;
        }

        /* ── META STRIP ── */
        .meta-strip {
            display: table;
            width: 100%;
            border: 1px solid #999;
            margin-bottom: 3px;
        }

        .meta-cell {
            display: table-cell;
            padding: 2px 5px;
            border-right: 1px solid #999;
            font-size: 6pt;
        }

        .meta-cell:last-child { border-right: none; }

        .meta-label {
            font-size: 6pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #555;
        }

        .meta-value {
            font-size: 7.5pt;
            font-weight: bold;
        }

        /* ── MARKS TABLE ── */
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
            table-layout: fixed;
        }

        .marks-table thead tr:first-child th {
            background: #1a5c35;
            color: #fff;
            padding: 4px 4px;
            text-align: center;
            border: 1px solid #0f3d22;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .marks-table thead tr:first-child th:first-child {
            text-align: left;
            width: 14%;
        }

        /* Sub-header row (Opener / Midterm per subject) */
        .marks-table thead tr.sub-header th {
            background: #d0d0d0;
            color: #000;
            padding: 4px 4px;
            text-align: center;
            border: 1px solid #aaa;
            font-size: 6.5pt;
            font-weight: bold;
        }

        .marks-table tbody td {
            padding: 4px 5px;
            border: 1px solid #ccc;
            text-align: center;
            vertical-align: middle;
        }

        .marks-table tbody td.student-name {
            text-align: left;
            font-weight: bold;
            white-space: nowrap;
        }

        .marks-table tbody tr:nth-child(even) td {
            background: #f5f5f5;
        }

        /* subject group columns */
        .marks-table td.group-a, .marks-table th.group-a { background-color: #fafafa; }
        .marks-table td.group-b, .marks-table th.group-b { background-color: #f0f0f0; }
    </style>
</head>
<body>
<div class="page">

    {{-- HEADER --}}
    <div class="header">
        <div class="header-logo">
            @if(!empty($logoPath))
                <img src="{{ $logoPath }}" alt="Logo">
            @else
                <div class="logo-placeholder"></div>
            @endif
        </div>
        <div class="header-center">
            <div class="school-name">{{ $settings->school_name ?? 'Circle Spring Academy' }}</div>
            <div class="school-sub">{{ $settings->location ?? '' }}</div>
        </div>
        <div class="header-logo">
            @if(!empty($logoPath))
                <img src="{{ $logoPath }}" alt="Logo">
            @else
                <div class="logo-placeholder"></div>
            @endif
        </div>
    </div>

    {{-- TITLE --}}
    <div class="report-title">Class Marks Report</div>

    {{-- META --}}
    <div class="meta-strip">
        <div class="meta-cell">
            <div class="meta-label">Class</div>
            <div class="meta-value">{{ $class->display_name }}</div>
        </div>
        <div class="meta-cell">
            <div class="meta-label">Class Teacher</div>
            <div class="meta-value">{{ $class->class_teacher_name ?: '—' }}</div>
        </div>
        @if($openerExam)
        <div class="meta-cell">
            <div class="meta-label">Opener Exam</div>
            <div class="meta-value">{{ $openerExam->name }}</div>
        </div>
        @endif
        @if($midtermExam)
        <div class="meta-cell">
            <div class="meta-label">Midterm Exam</div>
            <div class="meta-value">{{ $midtermExam->name }}</div>
        </div>
        @endif
        <div class="meta-cell">
            <div class="meta-label">Students</div>
            <div class="meta-value">{{ count($rows) }}</div>
        </div>
        <div class="meta-cell">
            <div class="meta-label">Academic Year</div>
            <div class="meta-value">{{ $openerExam?->academicYear?->year ?? $midtermExam?->academicYear?->year ?? '—' }}</div>
        </div>
    </div>

    {{-- MARKS TABLE --}}
    @php $showBoth = $openerExam && $midtermExam; @endphp
    <table class="marks-table">
        <thead>
            <tr>
                <th rowspan="2" style="text-align:left; width:120px;">Student Name</th>
                @foreach($subjects as $i => $subject)
                    <th colspan="{{ $showBoth ? 2 : 1 }}" class="{{ $i % 2 === 0 ? 'group-a' : 'group-b' }}">
                        {{ $subject->name }}
                    </th>
                @endforeach
                @if($showBoth)
                    <th colspan="3">Totals</th>
                @else
                    <th colspan="2">Totals</th>
                @endif
            </tr>
            <tr class="sub-header">
                @if($showBoth)
                    @foreach($subjects as $i => $subject)
                        @php $bg = $i % 2 === 0 ? 'group-a' : 'group-b'; @endphp
                        <th class="{{ $bg }}">Opener</th>
                        <th class="{{ $bg }}">Midterm</th>
                    @endforeach
                    <th>Opener</th>
                    <th>Midterm</th>
                    <th>Average</th>
                @else
                    @foreach($subjects as $i => $subject)
                        @php $bg = $i % 2 === 0 ? 'group-a' : 'group-b'; @endphp
                        <th class="{{ $bg }}">{{ $singleExamLabel ?? 'Score' }}</th>
                    @endforeach
                    <th>Total</th>
                    <th>Average</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $idx => $row)
            <tr>
                <td class="student-name">{{ $idx + 1 }}. {{ $row['name'] }}</td>
                @foreach($row['subjects'] as $i => $sr)
                    @php $bg = $i % 2 === 0 ? 'group-a' : 'group-b'; @endphp
                    @if($showBoth)
                        <td class="{{ $bg }}">{{ $sr['opener'] ?? '—' }}</td>
                        <td class="{{ $bg }}">{{ $sr['midterm'] ?? '—' }}</td>
                    @else
                        <td class="{{ $bg }}">{{ $sr['opener'] ?? $sr['midterm'] ?? '—' }}</td>
                    @endif
                @endforeach
                @if($showBoth)
                    <td>{{ $row['opener_total'] ?: '—' }}</td>
                    <td>{{ $row['midterm_total'] ?: '—' }}</td>
                    <td>{{ $row['midterm_average'] ?? $row['opener_average'] ?? '—' }}</td>
                @else
                    <td>{{ $row['opener_total'] ?: $row['midterm_total'] ?: '—' }}</td>
                    <td>{{ $row['opener_average'] ?? $row['midterm_average'] ?? '—' }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

</div>
</body>
</html>
