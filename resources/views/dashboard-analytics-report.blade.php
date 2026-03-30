<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Academic Analytics Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8pt;
            color: #1a1a1a;
            background: #fff;
        }

        .page { padding: 10mm 10mm 8mm; }

        /* ── Header ── */
        .header {
            display: table;
            width: 100%;
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 3px solid #1a5c35;
            position: relative;
        }
        .header::after {
            content: '';
            display: block;
            border-bottom: 1px solid #a8c8b5;
            margin-top: 3px;
        }
        .header-logo {
            display: table-cell;
            width: 52px;
            vertical-align: middle;
            text-align: center;
        }
        .header-logo img { max-height: 44px; max-width: 44px; }
        .logo-placeholder {
            width: 44px; height: 44px;
            border: 1.5px dashed #b0c9bc;
            border-radius: 4px;
            display: inline-block;
            background: #f5faf7;
        }
        .header-center { display: table-cell; vertical-align: middle; text-align: center; }
        .school-name {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #1a5c35;
        }
        .school-location {
            font-size: 7.5pt;
            color: #6a9b7e;
            margin-top: 3px;
            letter-spacing: 0.3px;
        }

        /* ── Report title block ── */
        .title-block {
            text-align: center;
            margin: 10px 0 14px;
            padding: 8px 0;
            border-top: 1px solid #ddeee5;
            border-bottom: 1px solid #ddeee5;
        }
        .report-title {
            font-size: 11.5pt;
            font-weight: bold;
            color: #1a5c35;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .report-date { font-size: 7.5pt; color: #888; margin-top: 3px; }

        /* ── Sections ── */
        .section { margin-bottom: 16px; }
        .section-title {
            font-size: 8pt;
            font-weight: bold;
            color: #1a5c35;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 0 0 4px 8px;
            border-left: 3px solid #1a5c35;
            margin-bottom: 10px;
        }

        /* ── Summary cards ── */
        .summary-grid { display: table; width: 100%; border-spacing: 0; margin-bottom: 8px; }
        .summary-cell {
            display: table-cell;
            width: 33.3%;
            padding: 8px 6px;
            text-align: center;
            border: 1px solid #d4e9dc;
            background: linear-gradient(160deg, #f5faf7 0%, #eaf3ed 100%);
        }
        .summary-cell:first-child { border-radius: 4px 0 0 4px; }
        .summary-cell:last-child  { border-radius: 0 4px 4px 0; border-left: none; }
        .summary-cell:not(:first-child):not(:last-child) { border-left: none; }
        .summary-cell .value {
            font-size: 18pt;
            font-weight: bold;
            color: #1a5c35;
            line-height: 1;
        }
        .summary-cell .label {
            font-size: 6.5pt;
            color: #6a9b7e;
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .school-avg {
            font-size: 8pt;
            color: #3a6b4f;
            margin-top: 4px;
            padding: 5px 10px;
            background: #f0f8f4;
            border: 1px solid #d4e9dc;
            border-radius: 3px;
            display: inline-block;
        }

        /* ── Per-class block ── */
        .class-block { margin-bottom: 12px; page-break-inside: avoid; }

        .class-heading {
            display: table;
            width: 100%;
            background: #1a5c35;
            padding: 4px 8px;
            border-radius: 3px 3px 0 0;
        }
        .class-heading-left  { display: table-cell; vertical-align: middle; }
        .class-heading-right { display: table-cell; vertical-align: middle; text-align: right; }

        .class-name {
            font-size: 8.5pt;
            font-weight: bold;
            color: #fff;
            letter-spacing: 0.5px;
        }
        .class-avg-pill {
            font-size: 7pt;
            color: #c8e8d4;
            letter-spacing: 0.2px;
        }
        .class-avg-pill span {
            display: inline-block;
            margin-left: 8px;
            padding: 1px 6px;
            border-radius: 10px;
            font-weight: bold;
        }
        .pill-neutral { background: rgba(255,255,255,0.15); color: #e8f5ee; }
        .pill-pos     { background: rgba(100,220,140,0.25); color: #a8f0c0; }
        .pill-neg     { background: rgba(255,120,100,0.25); color: #ffb8b0; }

        /* ── Pivot table ── */
        .pivot-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.5pt;
            border: 1px solid #c8dfd0;
            border-top: none;
            table-layout: fixed;
        }
        .pivot-table th {
            background: #2d7a50;
            color: #e8f5ee;
            font-weight: bold;
            padding: 3px 4px;
            text-align: center;
            border: 1px solid #3a8a5f;
            font-size: 7pt;
            white-space: nowrap;
        }
        .pivot-table th.row-header-th {
            background: #1f6640;
            text-align: left;
            min-width: 52px;
            color: #d0eedd;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .pivot-table th.total-th {
            background: #155230;
            color: #b0ddc0;
            font-size: 7pt;
        }
        .pivot-table td {
            padding: 3px 4px;
            text-align: center;
            border: 1px solid #daeee2;
            vertical-align: middle;
        }

        /* Row backgrounds */
        .row-opener  td { background: #ffffff; }
        .row-dev     td { background: #f7fbf8; }
        .row-midterm td { background: #f0f8f3; }

        /* Row label cells */
        td.row-label {
            text-align: left;
            font-weight: bold;
            font-size: 6.5pt;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            border-right: 2px solid #c8dfd0;
            white-space: nowrap;
            padding-left: 8px;
        }
        .row-opener  td.row-label { color: #3a7a55; background: #f0f8f4; }
        .row-dev     td.row-label { color: #888; background: #f7f7f5; font-style: italic; letter-spacing: 0.5px; }
        .row-midterm td.row-label { color: #1a5c35; background: #e8f4ee; }

        /* Total column */
        td.total-cell {
            font-weight: bold;
            background: #e4f2ea !important;
            border-left: 2px solid #b0d8be;
            color: #1a5c35;
        }

        /* Deviation colors */
        .dev-pos  { color: #1a6b35; font-weight: bold; }
        .dev-neg  { color: #b22222; font-weight: bold; }
        .dev-zero { color: #888; }
        .na       { color: #ccc; font-size: 7pt; }

        /* ── Years table ── */
        table.simple { width: 100%; border-collapse: collapse; font-size: 7pt; }
        table.simple thead th {
            background: #1a5c35;
            color: #e8f5ee;
            padding: 6px 10px;
            text-align: left;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #1f6640;
        }
        table.simple tbody td {
            padding: 5px 10px;
            border: 1px solid #d4e9dc;
        }
        table.simple tbody tr:nth-child(even) td { background: #f5faf7; }

        /* ── Footer ── */
        .footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #d4e9dc;
            font-size: 7pt;
            color: #9ab8a6;
            text-align: center;
            letter-spacing: 0.5px;
        }

        /* ── Page break hint ── */
        .class-block { page-break-inside: avoid; }
    </style>
</head>
<body>
<div class="page">

    {{-- ── Header ── --}}
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
            <div class="school-location">{{ $settings->location ?? '' }}</div>
        </div>
        <div class="header-logo">
            @if(!empty($logoPath))
                <img src="{{ $logoPath }}" alt="Logo">
            @else
                <div class="logo-placeholder"></div>
            @endif
        </div>
    </div>

    {{-- ── Title block ── --}}
    <div class="title-block">
        <div class="report-title">Academic Analytics Report</div>
        <div class="report-date">Generated {{ $generatedAt ?? now()->format('F j, Y \a\t g:i A') }}</div>
    </div>

    @php $show = fn ($key) => empty($sections) || !empty($sections[$key] ?? true); @endphp

    {{-- ── Summary ── --}}
    @if($show('summary'))
    <div class="section">
        <div class="section-title">Summary</div>
        <div class="summary-grid">
            <div class="summary-cell">
                <div class="value">{{ $summary['total_students'] }}</div>
                <div class="label">Total Students</div>
            </div>
            <div class="summary-cell">
                <div class="value">{{ $summary['total_classes'] }}</div>
                <div class="label">Classes</div>
            </div>
            <div class="summary-cell">
                <div class="value">{{ $summary['total_academic_years'] }}</div>
                <div class="label">Academic Years</div>
            </div>
        </div>
        @if($show('school_averages') && ($summary['overall_opener_avg'] !== null || $summary['overall_midterm_avg'] !== null))
        @php
            $overallDev = null;
            if ($summary['overall_opener_avg'] !== null && $summary['overall_midterm_avg'] !== null) {
                $overallDev = round($summary['overall_midterm_avg'] - $summary['overall_opener_avg'], 1);
            }
        @endphp
        <div>
            <span class="school-avg">
                School-wide averages &nbsp;·&nbsp;
                Opener: <strong>{{ $summary['overall_opener_avg'] ?? '—' }}</strong>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                Midterm: <strong>{{ $summary['overall_midterm_avg'] ?? '—' }}</strong>
                @if($overallDev !== null && ($showDeviation ?? true))
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    Deviation: 
                    <strong class="{{ $overallDev > 0 ? 'dev-pos' : ($overallDev < 0 ? 'dev-neg' : 'dev-zero') }}">
                        {{ $overallDev > 0 ? '+' : '' }}{{ number_format($overallDev, 1) }}
                    </strong>
                @endif
            </span>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Subject Averages — per-class pivot ── --}}
    @if($show('class_performance') && !empty($subject_averages))
    <div class="section">
        <div class="section-title">Subject Averages by Class</div>

        @php
            $byClass = [];
            foreach ($subject_averages as $row) {
                $cn = $row['class_name'];
                $sn = $row['subject_name'];
                if (!isset($byClass[$cn])) $byClass[$cn] = [];
                $byClass[$cn][$sn] = [
                    'opener'  => $row['opener_avg'],
                    'midterm' => $row['midterm_avg'],
                ];
            }
            $classSummaryMap = [];
            foreach (($class_summaries ?? []) as $cs) {
                $classSummaryMap[$cs['class_name']] = $cs;
            }
        @endphp

        @foreach($byClass as $className => $subjects)
        @php
            $cs      = $classSummaryMap[$className] ?? null;
            $clsOpen = $cs['opener_avg']  ?? null;
            $clsMid  = $cs['midterm_avg'] ?? null;
            $clsDev  = ($clsOpen !== null && $clsMid !== null)
                           ? round($clsMid - $clsOpen, 1) : null;
            $pillClass = 'pill-neutral';
            if ($clsDev !== null) {
                $pillClass = $clsDev > 0 ? 'pill-pos' : ($clsDev < 0 ? 'pill-neg' : 'pill-neutral');
            }
        @endphp

        <div class="class-block">
            <div class="class-heading">
                <div class="class-heading-left">
                    <span class="class-name">{{ $className }}</span>
                </div>
                <div class="class-heading-right">
                    <span class="class-avg-pill">
                        <span class="pill-neutral">Opener {{ $clsOpen !== null ? number_format($clsOpen,1) : '—' }}</span>
                        <span class="pill-neutral">Midterm {{ $clsMid !== null ? number_format($clsMid,1) : '—' }}</span>
                        @if($clsDev !== null && ($showDeviation ?? true))
                            <span class="{{ $pillClass }}">
                                {{ $clsDev > 0 ? '+' : '' }}{{ number_format($clsDev,1) }}
                            </span>
                        @endif
                    </span>
                </div>
            </div>

            <table class="pivot-table">
                <thead>
                    <tr>
                        <th class="row-header-th">Exam</th>
                        @foreach($subjects as $subjectName => $vals)
                            <th>{{ $subjectName }}</th>
                        @endforeach
                        <th class="total-th">Avg</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Opener row --}}
                    <tr class="row-opener">
                        <td class="row-label">Opener</td>
                        @php $openerSum = 0; $openerCount = 0; @endphp
                        @foreach($subjects as $sn => $vals)
                            @php $o = $vals['opener']; if ($o !== null) { $openerSum += $o; $openerCount++; } @endphp
                            <td>
                                @if($o !== null)
                                    {{ number_format($o, 1) }}
                                @else
                                    <span class="na">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="total-cell">{{ $openerCount > 0 ? number_format($openerSum/$openerCount,1) : '—' }}</td>
                    </tr>

                    {{-- Change row --}}
                    @if($showDeviation ?? true)
                    <tr class="row-dev">
                        <td class="row-label">Deviation</td>
                        @php $devSum = 0; $devCount = 0; @endphp
                        @foreach($subjects as $sn => $vals)
                            @php
                                $o = $vals['opener']; $m = $vals['midterm'];
                                $d = ($o !== null && $m !== null) ? round($m - $o, 1) : null;
                                if ($d !== null) { $devSum += $d; $devCount++; }
                            @endphp
                            <td>
                                @if($d === null) <span class="na">—</span>
                                @elseif($d > 0)  <span class="dev-pos">+{{ number_format($d,1) }}</span>
                                @elseif($d < 0)  <span class="dev-neg">{{ number_format($d,1) }}</span>
                                @else            <span class="dev-zero">0.0</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="total-cell">
                            @if($devCount > 0)
                                @php $avgDev = round($devSum/$devCount,1); @endphp
                                <span class="{{ $avgDev > 0 ? 'dev-pos' : ($avgDev < 0 ? 'dev-neg' : 'dev-zero') }}">
                                    {{ $avgDev > 0 ? '+' : '' }}{{ number_format($avgDev,1) }}
                                </span>
                            @else <span class="na">—</span>
                            @endif
                        </td>
                    </tr>
                    @endif

                    {{-- Midterm row --}}
                    <tr class="row-midterm">
                        <td class="row-label">Midterm</td>
                        @php $midSum = 0; $midCount = 0; @endphp
                        @foreach($subjects as $sn => $vals)
                            @php $m = $vals['midterm']; if ($m !== null) { $midSum += $m; $midCount++; } @endphp
                            <td>
                                @if($m !== null)
                                    {{ number_format($m, 1) }}
                                @else
                                    <span class="na">—</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="total-cell">{{ $midCount > 0 ? number_format($midSum/$midCount,1) : '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endforeach

    </div>
    @endif

    {{-- ── Exams opener & midterm analysis ── --}}
    @if($show('years_exams'))
    <div class="section">
        <div class="section-title">Exams – Opener &amp; Midterm Analysis</div>
        <table class="simple">
            <thead>
                <tr>
                    <th>Academic Year</th>
                    <th style="text-align:center;">Total Exams</th>
                    <th style="text-align:center;">Has Opener</th>
                    <th style="text-align:center;">Has Midterm</th>
                </tr>
            </thead>
            <tbody>
                @foreach($years_with_exams as $y)
                <tr>
                    <td>{{ $y['year'] }}</td>
                    <td style="text-align:center;">{{ $y['exam_count'] }}</td>
                    <td style="text-align:center;">{{ $y['has_opener'] ? 'Yes' : 'No' }}</td>
                    <td style="text-align:center;">{{ $y['has_midterm'] ? 'Yes' : 'No' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($show('footer'))
    <div class="footer">
        {{ $settings->school_name ?? 'Circle Spring Academy' }} &nbsp;·&nbsp; Academic Department Analytics &nbsp;·&nbsp; For internal use only
    </div>
    @endif

</div>
</body>
</html>