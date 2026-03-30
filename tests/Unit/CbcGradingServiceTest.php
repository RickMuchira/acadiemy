<?php

use App\Services\CbcGradingService;

beforeEach(function (): void {
    $this->service = new CbcGradingService;
});

it('returns EE1 for scores 90–100', function (): void {
    expect($this->service->getSubLevel(90))->toBe('EE1')
        ->and($this->service->getSubLevel(100))->toBe('EE1')
        ->and($this->service->getSubLevel(95))->toBe('EE1');
});

it('returns EE2 for scores 75–89', function (): void {
    expect($this->service->getSubLevel(75))->toBe('EE2')
        ->and($this->service->getSubLevel(89))->toBe('EE2');
});

it('returns ME1 for scores 58–74', function (): void {
    expect($this->service->getSubLevel(58))->toBe('ME1')
        ->and($this->service->getSubLevel(74))->toBe('ME1');
});

it('returns ME2 for scores 41–57', function (): void {
    expect($this->service->getSubLevel(41))->toBe('ME2')
        ->and($this->service->getSubLevel(57))->toBe('ME2');
});

it('returns AE1 for scores 31–40', function (): void {
    expect($this->service->getSubLevel(31))->toBe('AE1')
        ->and($this->service->getSubLevel(40))->toBe('AE1');
});

it('returns AE2 for scores 21–30', function (): void {
    expect($this->service->getSubLevel(21))->toBe('AE2')
        ->and($this->service->getSubLevel(30))->toBe('AE2');
});

it('returns BE1 for scores 11–20', function (): void {
    expect($this->service->getSubLevel(11))->toBe('BE1')
        ->and($this->service->getSubLevel(20))->toBe('BE1');
});

it('returns BE2 for scores 1–10', function (): void {
    expect($this->service->getSubLevel(1))->toBe('BE2')
        ->and($this->service->getSubLevel(10))->toBe('BE2')
        ->and($this->service->getSubLevel(0))->toBe('BE2');
});

it('extracts the grade from a sublevel', function (): void {
    expect($this->service->getGrade('EE1'))->toBe('EE')
        ->and($this->service->getGrade('ME2'))->toBe('ME')
        ->and($this->service->getGrade('BE1'))->toBe('BE');
});

it('returns correct grade and sublevel from grade()', function (): void {
    $result = $this->service->grade(92);
    expect($result['sublevel'])->toBe('EE1')
        ->and($result['grade'])->toBe('EE');
});

it('formats display string correctly', function (): void {
    expect($this->service->formatDisplay(92))->toBe('92 — EE1');
});
