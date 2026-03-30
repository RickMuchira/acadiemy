<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card – {{ $student->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #000;
            background: #fff;
        }

        .page {
            width: 178mm;
            min-height: 257mm;
            margin: 0 auto;
            padding: 10mm 12mm;
        }

        /* ═══════════════════════════════
           OUTER BORDER (double-line frame)
        ═══════════════════════════════ */
        .outer-border {
            border: 3px solid #000;
            padding: 3px;
        }

        .inner-border {
            border: 1px solid #000;
            padding: 0;
        }

        /* ═══════════════════════════════
           HEADER
        ═══════════════════════════════ */
        .header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #000;
            padding: 8px 10px;
        }

        .header-logo-cell {
            display: table-cell;
            width: 62px;
            vertical-align: middle;
            text-align: center;
        }

        .header-logo-cell img {
            max-height: 56px;
            max-width: 56px;
        }

        .logo-placeholder {
            width: 56px;
            height: 56px;
            border: 1px dashed #aaa;
            display: inline-block;
        }

        .header-center {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
            padding: 0 8px;
        }

        .school-name {
            font-size: 16pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .school-motto {
            font-size: 9pt;
            font-style: italic;
            margin-top: 3px;
            color: #333;
        }

        .school-location {
            font-size: 8.5pt;
            margin-top: 2px;
            color: #555;
        }

        /* ═══════════════════════════════
           REPORT CARD TITLE STRIP
        ═══════════════════════════════ */
        .report-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 3px;
            text-transform: uppercase;
            padding: 5px 0;
            border-bottom: 1px solid #000;
            background: #f2f2f2;
        }

        /* ═══════════════════════════════
           STUDENT INFO BAND
        ═══════════════════════════════ */
        .student-band {
            display: table;
            width: 100%;
            border-bottom: 1px solid #000;
        }

        .student-band-left,
        .student-band-right {
            display: table-cell;
            width: 50%;
            padding: 6px 10px;
            vertical-align: top;
        }

        .student-band-left {
            border-right: 1px solid #000;
        }

        .info-line {
            font-size: 9.5pt;
            margin-bottom: 4px;
            line-height: 1.5;
        }

        .info-label {
            font-weight: bold;
        }

        .info-value {
            border-bottom: 1px dotted #555;
            min-width: 80px;
            display: inline-block;
            padding-bottom: 1px;
        }

        /* ═══════════════════════════════
           SECTION HEADING
        ═══════════════════════════════ */
        .section-heading {
            font-size: 9.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 4px 10px;
            border-bottom: 1px solid #000;
            background: #e8e8e8;
        }

        /* ═══════════════════════════════
           SUBJECT TABLE
        ═══════════════════════════════ */
        .subject-table {
            width: 100%;
            border-collapse: collapse;
        }

        .subject-table thead tr {
            background: #d0d0d0;
        }

        .subject-table thead th {
            padding: 5px 10px;
            font-size: 8pt;
            font-weight: bold;
            text-align: center;
            border-right: 1px solid #000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .subject-table thead th:first-child {
            text-align: left;
        }

        .subject-table thead th:last-child {
            border-right: none;
        }

        .subject-table tbody td {
            padding: 5px 10px;
            font-size: 8pt;
            border-bottom: 1px solid #ccc;
            border-right: 1px solid #ccc;
        }

        .subject-table tbody td:last-child {
            border-right: none;
        }

        .subject-table tbody tr:nth-child(even) td {
            background: #f7f7f7;
        }

        .subject-table tbody td.center {
            text-align: center;
        }

        /* Totals and average rows */
        .subject-table .totals-row td {
            background: #e0e0e0 !important;
            font-weight: bold;
            font-size: 9pt;
            border-top: 1.5px solid #000;
            border-bottom: 1px solid #999;
        }

        .subject-table .average-row td {
            background: #ebebeb !important;
            font-weight: bold;
            font-size: 9pt;
            border-bottom: none;
        }

        /* CBC grade badge */
        .grade-badge {
            display: inline-block;
            border: 1px solid #555;
            padding: 1px 5px;
            border-radius: 2px;
            font-size: 8pt;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        /* ═══════════════════════════════
           TEACHER REVIEW BAND
        ═══════════════════════════════ */
        .comment-band {
            border-top: 1px solid #000;
            padding: 8px 10px;
        }

        .comment-label {
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }

        .review-text {
            font-size: 9pt;
            margin-bottom: 4px;
            min-height: 12px;
        }

        .review-line {
            border-bottom: 1px solid #555;
            height: 1px;
            margin: 4px 0 6px;
        }

        .review-name {
            font-size: 9pt;
            font-weight: bold;
        }

        /* ═══════════════════════════════
           SIGNATURE SECTION
        ═══════════════════════════════ */
        .signature-section {
            border-top: 1px solid #000;
            padding: 8px 10px;
        }

        .sig-grid {
            display: table;
            width: 100%;
        }

        .sig-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 12px;
            font-size: 9pt;
        }

        .sig-col:last-child {
            padding-right: 0;
            padding-left: 12px;
            border-left: 1px dashed #bbb;
        }

        .sig-row-line {
            margin-top: 14px;
            display: table;
            width: 100%;
        }

        .sig-row-line-label {
            font-size: 8.5pt;
            color: #333;
            display: table-cell;
            white-space: nowrap;
            padding-right: 6px;
            vertical-align: bottom;
        }

        .sig-row-line-blank {
            display: table-cell;
            border-bottom: 1px solid #555;
            width: 100%;
            vertical-align: bottom;
            height: 16px;
        }

        .sig-title {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .sig-name-value {
            font-size: 9pt;
            color: #333;
        }

        /* ═══════════════════════════════
           FOOTER
        ═══════════════════════════════ */
        .doc-footer {
            border-top: 1.5px solid #000;
            padding: 4px 10px;
            display: table;
            width: 100%;
        }

        .footer-left {
            display: table-cell;
            font-size: 7.5pt;
            color: #555;
            vertical-align: middle;
        }

        .footer-right {
            display: table-cell;
            font-size: 7.5pt;
            color: #555;
            text-align: right;
            vertical-align: middle;
        }

        /* ═══════════════════════════════
           TERM BOXES (optional row above subjects)
        ═══════════════════════════════ */
        .meta-band {
            display: table;
            width: 100%;
            border-bottom: 1px solid #000;
        }

        .meta-box {
            display: table-cell;
            text-align: center;
            padding: 4px 6px;
            border-right: 1px solid #000;
            font-size: 8.5pt;
        }

        .meta-box:last-child {
            border-right: none;
        }

        .meta-box-label {
            font-weight: bold;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #444;
        }

        .meta-box-value {
            font-size: 9.5pt;
            font-weight: bold;
            margin-top: 1px;
        }

        .page-break {
            page-break-after: always;
        }

        .page-break:last-child {
            page-break-after: auto;
        }

        /* Performance Level Key — shown once, compact design */
        .grading-key-wrap {
            margin-top: 10px;
            padding: 8px 10px;
            border: 1px solid #333;
            background: #f9f9f9;
        }

        .grading-key-title {
            font-size: 7.5pt;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
            text-transform: uppercase;
            color: #333;
        }

        .grading-key-grid {
            display: table;
            width: 100%;
            font-size: 7pt;
            border-collapse: collapse;
        }

        .grading-key-grid td {
            padding: 2px 8px;
            border: none;
            vertical-align: top;
        }

        .grading-key-col {
            width: 25%;
        }

        .grading-key-col-title {
            font-weight: bold;
            font-size: 7pt;
            padding-bottom: 2px;
            border-bottom: 1px solid #999;
            margin-bottom: 2px;
            color: #222;
        }

        .grading-key-item {
            white-space: nowrap;
        }
    </style>
</head>
<body>
<div class="page">
<div class="outer-border">
<div class="inner-border">

    {{-- ── HEADER ────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-logo-cell">
            @if(!empty($logoPath))
                <img src="{{ $logoPath }}" alt="Logo">
            @else
                <div class="logo-placeholder"></div>
            @endif
        </div>

        <div class="header-center">
            <div class="school-name">{{ $settings->school_name ?? 'Circle Spring Academy' }}</div>
            <div class="school-motto">&ldquo;{{ $settings->motto ?? 'My Character, My Destiny' }}&rdquo;</div>
            <div class="school-location">{{ $settings->location ?? 'Nairobi, Kenya' }}</div>
        </div>

        <div class="header-logo-cell">
            @if(!empty($logoPath))
                <img src="{{ $logoPath }}" alt="Logo">
            @else
                <div class="logo-placeholder"></div>
            @endif
        </div>
    </div>

    @foreach($pages as $index => $page)
    <div class="{{ $index < count($pages) - 1 ? 'page-break' : '' }}">
    {{-- ── TITLE STRIP (per page) ──────────────────────────── --}}
    <div class="report-title">Academic Progress Report Card &bull; {{ $page['examName'] }}</div>
    {{-- ── META BOXES (exam-specific) ──────────────────────── --}}
    <div class="meta-band">
        <div class="meta-box">
            <div class="meta-box-label">Exam</div>
            <div class="meta-box-value">{{ $page['examName'] }}</div>
        </div>
        <div class="meta-box">
            <div class="meta-box-label">Academic Year</div>
            <div class="meta-box-value">{{ $page['exam']->academicYear->year }}</div>
        </div>
        <div class="meta-box">
            <div class="meta-box-label">Class</div>
            <div class="meta-box-value">{{ $student->schoolClass->display_name }}</div>
        </div>
        <div class="meta-box">
            <div class="meta-box-label">Total Subjects</div>
            <div class="meta-box-value">{{ count($page['rows']) }}</div>
        </div>
    </div>

    {{-- ── STUDENT INFO ────────────────────────────────────── --}}
    <div class="student-band">
        <div class="student-band-left">
            <div class="info-line">
                <span class="info-label">Student Name: </span>
                <span class="info-value">{{ $student->name }}</span>
            </div>
            <div class="info-line">
                <span class="info-label">Class Teacher: </span>
                <span class="info-value">{{ $student->schoolClass->class_teacher_name ?: '—' }}</span>
            </div>
        </div>
        <div class="student-band-right">
            <div class="info-line">
                <span class="info-label">Total Score: </span>
                <span class="info-value">{{ $page['total'] }}</span>
            </div>
            <div class="info-line">
                <span class="info-label">Average Score: </span>
                <span class="info-value">{{ $page['average'] }}
                    @if($page['averageSublevel'])
                        &nbsp;({{ $page['averageSublevel'] }})
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- ── SUBJECT PERFORMANCE COMPARISON (Opener vs Midterm) ─ --}}
    <div class="section-heading">Subject Performance</div>

    <table class="subject-table">
        <thead>
            <tr>
                <th style="width:26%; text-align:left;">Subject</th>
                <th style="width:14%;">Opener</th>
                <th style="width:14%;">Midterm</th>
                @if($showDeviation ?? true)
                    <th style="width:12%;">Deviation</th>
                @endif
                <th style="width:12%;">Grade</th>
                <th style="width:22%;">Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($page['rows'] as $row)
            <tr>
                <td>{{ $row['subject'] }}</td>
                <td class="center">{{ $row['opener_score'] ?? '—' }}</td>
                <td class="center">{{ $row['midterm_score'] ?? '—' }}</td>
                @if($showDeviation ?? true)
                    <td class="center">
                        @php
                            $opener = $row['opener_score'] ?? null;
                            $midterm = $row['midterm_score'] ?? null;
                        @endphp
                        @if(is_numeric($opener) && is_numeric($midterm))
                            {{ $midterm - $opener }}
                        @else
                            —
                        @endif
                    </td>
                @endif
                <td class="center">{{ $row['sublevel'] ?? '—' }}</td>
                <td class="center">{{ $row['description'] ?? '—' }}</td>
            </tr>
            @endforeach

            <tr class="totals-row">
                <td style="text-align:left;">TOTAL</td>
                <td class="center">{{ $page['opener_total'] ?? '—' }}</td>
                <td class="center">{{ $page['midterm_total'] ?? '—' }}</td>
                @if($showDeviation ?? true)
                    <td class="center">
                        @if(isset($page['opener_total'], $page['midterm_total']))
                            {{ $page['midterm_total'] - $page['opener_total'] }}
                        @else
                            —
                        @endif
                    </td>
                @endif
                <td class="center"></td>
                <td class="center"></td>
            </tr>
            <tr class="average-row">
                <td style="text-align:left;">AVERAGE</td>
                <td class="center">{{ $page['opener_average'] ?? '—' }}</td>
                <td class="center">{{ $page['midterm_average'] ?? '—' }}</td>
                @if($showDeviation ?? true)
                    <td class="center">
                        @if(isset($page['opener_average'], $page['midterm_average']))
                            {{ $page['midterm_average'] - $page['opener_average'] }}
                        @else
                            —
                        @endif
                    </td>
                @endif
                <td class="center">{{ $page['averageSublevel'] ?? '—' }}</td>
                <td class="center">{{ $page['averageDescription'] ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    @if($index === 0)
    {{-- ── PERFORMANCE LEVEL KEY (once, first page only) ─────── --}}
    <div class="grading-key-wrap">
        <div class="grading-key-title">Performance Level Key (Score %)</div>
        <table class="grading-key-grid">
            <tr>
                @foreach(['EE' => 'Exceeding Expectation', 'ME' => 'Meeting Expectation', 'AE' => 'Approaching Expectation', 'BE' => 'Below Expectation'] as $code => $desc)
                <td class="grading-key-col">
                    <div class="grading-key-col-title">{{ $code }} — {{ $desc }}</div>
                    @foreach($gradingScale as $e)
                        @if($e['grade'] === $code)
                        <div class="grading-key-item">{{ $e['sublevel'] }}: {{ $e['range'] }}%</div>
                        @endif
                    @endforeach
                </td>
                @endforeach
            </tr>
        </table>
    </div>
    @endif

    {{-- ── CLASS TEACHER & HEAD TEACHER REVIEWS ─────────────── --}}
    <div class="comment-band">
        <div class="sig-grid">
            <div class="sig-col">
                <div class="comment-label">Class Teacher Review</div>
                <div class="review-text">{{ $page['averageDescription'] ?? '—' }}</div>
                <div class="review-line"></div>
                <div class="review-name">{{ $student->schoolClass->class_teacher_name ?: '—' }}</div>
            </div>
            <div class="sig-col">
                <div class="comment-label">Head Teacher Review</div>
                <div class="review-text">{{ $page['averageDescription'] ?? '—' }}</div>
                <div class="review-line"></div>
                <div class="review-name">{{ $settings->head_teacher_name ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- ── FOOTER ──────────────────────────────────────────── --}}
    <div class="doc-footer">
        <div class="footer-left">
            {{ $settings->school_name ?? 'Circle Spring Academy' }}
            &bull; {{ $settings->location ?? 'Nairobi, Kenya' }}
        </div>
        <div class="footer-right"></div>
    </div>
    </div>{{-- page-break wrapper --}}
    @endforeach

</div>{{-- inner-border --}}
</div>{{-- outer-border --}}
</div>{{-- page --}}
</body>
</html>
