<?php namespace JumpLink\Venue\Tests;

use JumpLink\Venue\Classes\FeedBuilder;
use JumpLink\Venue\Classes\HoursView;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/FeedBuilder.php';
require_once __DIR__ . '/../classes/HoursView.php';

class HoursViewTest extends TestCase
{
    private array $labels = [
        'days' => ['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat', 'Sunday' => 'Sun'],
        'daily' => 'Daily',
        'time_range' => ':from – :until',
    ];

    private function row(array $row): array
    {
        return $row + ['kind' => 'regular', 'days' => FeedBuilder::WEEKDAYS, 'opens' => '09:00', 'closes' => '11:30', 'is_closed' => false];
    }

    public function testDaysBecomeRunsAndShortenToDaily(): void
    {
        $this->assertSame('Daily', HoursView::days(FeedBuilder::WEEKDAYS, $this->labels));
        $this->assertSame('Mon – Fri', HoursView::days(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], $this->labels));
        $this->assertSame('Sat + Sun', HoursView::days(['Sunday', 'Saturday'], $this->labels));
        $this->assertSame('Mon + Wed – Fri', HoursView::days(['Monday', 'Wednesday', 'Thursday', 'Friday'], $this->labels));
        $this->assertSame('', HoursView::days([], $this->labels));
    }

    public function testClockDropsLeadingZeroAndEmptyMinutes(): void
    {
        $this->assertSame('9', HoursView::clock('09:00'));
        $this->assertSame('11:30', HoursView::clock('11:30'));
        $this->assertSame('21', HoursView::clock('21:00:00'));
    }

    public function testRegularHoursGroupBySeasonAndService(): void
    {
        $view = HoursView::build([
            $this->row(['season' => 'Winter', 'period_text' => '1 Nov to Easter', 'name' => 'Kitchen', 'days' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '17:00', 'closes' => '21:00', 'valid_from' => '2026-11-01', 'valid_through' => '2027-03-27']),
            $this->row(['season' => 'Winter', 'period_text' => '1 Nov to Easter', 'name' => 'Kitchen', 'days' => ['Saturday', 'Sunday'], 'opens' => '12:00', 'closes' => '21:00', 'valid_from' => '2026-11-01', 'valid_through' => '2027-03-27']),
            $this->row(['season' => 'Summer', 'name' => 'Kitchen', 'opens' => '12:00', 'closes' => '21:00', 'description' => 'All day', 'valid_from' => '2027-03-28', 'valid_through' => '2027-10-31']),
        ], '2026-11-10', $this->labels);

        $this->assertCount(2, $view['seasons']);
        $this->assertSame('Winter', $view['seasons'][0]['season']);
        $this->assertSame('1 Nov to Easter', $view['seasons'][0]['period']);
        $lines = $view['seasons'][0]['services'][0]['lines'];
        $this->assertSame([['days' => 'Mon – Fri', 'time' => '17 – 21'], ['days' => 'Sat + Sun', 'time' => '12 – 21']], $lines);
        $this->assertSame('Daily', $view['seasons'][1]['services'][0]['lines'][0]['days']);
        $this->assertSame('All day', $view['seasons'][1]['services'][0]['description']);
    }

    public function testFinishedEntriesAreLeftOut(): void
    {
        $view = HoursView::build([
            $this->row(['season' => 'Old', 'name' => 'Kitchen', 'valid_from' => '2025-04-01', 'valid_through' => '2025-10-31']),
            $this->row(['kind' => 'exception', 'name' => 'Past holiday', 'days' => [], 'valid_from' => '2026-05-01', 'valid_through' => '2026-05-01']),
            $this->row(['season' => 'All year', 'name' => 'Kitchen']),
        ], '2026-11-10', $this->labels);

        $this->assertCount(1, $view['seasons']);
        $this->assertSame('All year', $view['seasons'][0]['season']);
        $this->assertSame([], $view['exceptions']);
    }

    public function testExceptionsKeepDateOrderAndHolidaysComeLast(): void
    {
        $view = HoursView::build([
            $this->row(['kind' => 'holiday', 'name' => 'Every holiday', 'days' => [], 'description' => 'Kitchen', 'opens' => '12:00', 'closes' => '21:00']),
            $this->row(['kind' => 'exception', 'name' => 'Christmas', 'days' => [], 'description' => 'Breakfast', 'valid_from' => '2026-12-25', 'valid_through' => '2027-01-01']),
            $this->row(['kind' => 'exception', 'name' => 'Eve', 'days' => [], 'description' => 'Kitchen', 'is_closed' => true, 'valid_from' => '2026-12-24', 'valid_through' => '2026-12-24']),
            $this->row(['kind' => 'exception', 'name' => 'Eve', 'days' => [], 'description' => 'Breakfast', 'valid_from' => '2026-12-24', 'valid_through' => '2026-12-24']),
        ], '2026-11-10', $this->labels);

        $this->assertSame(['Eve', 'Christmas', 'Every holiday'], array_column($view['exceptions'], 'name'));
        $this->assertSame(
            [['service' => 'Kitchen', 'time' => null, 'closed' => true], ['service' => 'Breakfast', 'time' => '9 – 11:30', 'closed' => false]],
            $view['exceptions'][0]['lines']
        );
    }
}
