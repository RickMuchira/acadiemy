<?php

namespace App\Services;

class CbcGradingService
{
    public function getSubLevel(int $score): string
    {
        return match (true) {
            $score >= 90 => 'EE1',
            $score >= 75 => 'EE2',
            $score >= 58 => 'ME1',
            $score >= 41 => 'ME2',
            $score >= 31 => 'AE1',
            $score >= 21 => 'AE2',
            $score >= 11 => 'BE1',
            default => 'BE2',
        };
    }

    public function getGrade(string $subLevel): string
    {
        return substr($subLevel, 0, 2);
    }

    /**
     * Returns a display string like "92 — EE1".
     */
    public function formatDisplay(int $score): string
    {
        return "{$score} — {$this->getSubLevel($score)}";
    }

    /**
     * Returns both subLevel and grade for a given score.
     *
     * @return array{sublevel: string, grade: string}
     */
    public function grade(int $score): array
    {
        $subLevel = $this->getSubLevel($score);

        return [
            'sublevel' => $subLevel,
            'grade' => $this->getGrade($subLevel),
        ];
    }

    /**
     * Returns the CBC grading scale for display (e.g. in report card key).
     *
     * @return array<int, array{range: string, sublevel: string, grade: string, description: string}>
     */
    public function getGradingScale(): array
    {
        $descriptions = [
            'EE' => 'Exceeding Expectation',
            'ME' => 'Meeting Expectation',
            'AE' => 'Approaching Expectation',
            'BE' => 'Below Expectation',
        ];

        return [
            ['range' => '90–100', 'sublevel' => 'EE1', 'grade' => 'EE', 'description' => $descriptions['EE']],
            ['range' => '75–89', 'sublevel' => 'EE2', 'grade' => 'EE', 'description' => $descriptions['EE']],
            ['range' => '58–74', 'sublevel' => 'ME1', 'grade' => 'ME', 'description' => $descriptions['ME']],
            ['range' => '41–57', 'sublevel' => 'ME2', 'grade' => 'ME', 'description' => $descriptions['ME']],
            ['range' => '31–40', 'sublevel' => 'AE1', 'grade' => 'AE', 'description' => $descriptions['AE']],
            ['range' => '21–30', 'sublevel' => 'AE2', 'grade' => 'AE', 'description' => $descriptions['AE']],
            ['range' => '11–20', 'sublevel' => 'BE1', 'grade' => 'BE', 'description' => $descriptions['BE']],
            ['range' => '0–10', 'sublevel' => 'BE2', 'grade' => 'BE', 'description' => $descriptions['BE']],
        ];
    }
}
