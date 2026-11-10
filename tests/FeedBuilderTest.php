<?php namespace JumpLink\Venue\Tests;

use JumpLink\Venue\Classes\FeedBuilder;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../classes/FeedBuilder.php';

class FeedBuilderTest extends TestCase
{
    private array $venue = [
        'name' => 'Harbour Kitchen',
        'street' => '1 Quay Road',
        'postal_code' => '12345',
        'locality' => 'Seaton',
        'time_zone' => 'Europe/Berlin',
        'language' => 'en',
        'currency' => 'EUR',
    ];

    public function testVenueBecomesTheHeaderOfTheFeed(): void
    {
        $feed = FeedBuilder::build($this->venue, [], [], [], []);

        $this->assertSame(1, $feed['version']);
        $this->assertSame('Europe/Berlin', $feed['timeZone']);
        $this->assertSame('en', $feed['inLanguage']);
        $this->assertSame('Seaton', $feed['address']['addressLocality']);
        $this->assertArrayNotHasKey('openingHoursSpecification', $feed);
        $this->assertArrayNotHasKey('url', $feed);
    }

    public function testNoAddressWhenNothingIsFilledIn(): void
    {
        $feed = FeedBuilder::build(['time_zone' => 'Europe/Berlin'], [], [], [], []);

        $this->assertArrayNotHasKey('address', $feed);
    }

    public function testHoursKeepDaysInWeekOrderAndShortenTheTime(): void
    {
        $feed = FeedBuilder::build($this->venue, [[
            'days' => ['Sunday', 'Monday', 'Nonsense'],
            'opens' => '12:00:00',
            'closes' => '21:30:00',
            'valid_from' => '2026-11-01 00:00:00',
            'valid_through' => '2027-03-28',
        ]], [], [], []);

        $this->assertSame([
            'dayOfWeek' => ['Monday', 'Sunday'],
            'opens' => '12:00',
            'closes' => '21:30',
            'validFrom' => '2026-11-01',
            'validThrough' => '2027-03-28',
        ], $feed['openingHoursSpecification'][0]);
    }

    public function testClosedMeansMidnightToMidnight(): void
    {
        $feed = FeedBuilder::build($this->venue, [], [[
            'name' => 'Christmas',
            'is_closed' => true,
            'opens' => '10:00',
            'valid_from' => '2026-12-25',
            'valid_through' => '2026-12-25',
        ]], [], []);

        $special = $feed['specialOpeningHoursSpecification'][0];
        $this->assertSame('00:00', $special['opens']);
        $this->assertSame('00:00', $special['closes']);
        $this->assertArrayNotHasKey('dayOfWeek', $special);
    }

    public function testHoursWithoutATimeAreLeftOut(): void
    {
        $feed = FeedBuilder::build($this->venue, [['days' => ['Monday'], 'opens' => '', 'closes' => '']], [], [], []);

        $this->assertArrayNotHasKey('openingHoursSpecification', $feed);
    }

    public function testMenuNeedsADescriptionForTheAssistant(): void
    {
        $feed = FeedBuilder::build($this->venue, [], [], [
            ['name' => 'Autumn card', 'description' => '  '],
            ['name' => 'Spring card', 'description' => 'Served from April.'],
        ], []);

        $this->assertCount(1, $feed['hasMenu']);
        $this->assertSame('Spring card', $feed['hasMenu'][0]['name']);
    }

    public function testPricesTypedByAPersonBecomeNumbersWithTheVenueCurrency(): void
    {
        $feed = FeedBuilder::build($this->venue, [], [], [[
            'name' => 'Card',
            'description' => 'Everything.',
            'valid_through' => '2026-11-30',
            'sections' => [[
                'name' => 'Mains',
                'items' => [
                    ['name' => 'Stew', 'description' => 'with cabbage', 'price' => '18,50 €'],
                    ['name' => 'Soup', 'price' => ''],
                    ['name' => '', 'price' => '3'],
                ],
            ]],
        ]], []);

        $items = $feed['hasMenu'][0]['hasMenuSection'][0]['hasMenuItem'];
        $this->assertCount(2, $items);
        $this->assertSame(['price' => 18.5, 'priceCurrency' => 'EUR'], $items[0]['offers']);
        $this->assertArrayNotHasKey('offers', $items[1]);
        $this->assertSame('2026-11-30', $feed['hasMenu'][0]['validThrough']);
    }

    public function testFaqMarksOnlyTheExceptions(): void
    {
        $feed = FeedBuilder::build($this->venue, [], [], [], [
            ['question' => 'Dogs?', 'answer' => 'Yes.', 'assistant_may_say' => true],
            ['question' => 'Staff?', 'answer' => 'Internal.', 'assistant_may_say' => false],
            ['question' => 'Nothing?', 'answer' => ''],
        ]);

        $this->assertCount(2, $feed['faq']);
        $this->assertArrayNotHasKey('assistantMaySay', $feed['faq'][0]);
        $this->assertFalse($feed['faq'][1]['assistantMaySay']);
    }

    public function testJsonLdDropsWhatIsNotSchemaOrg(): void
    {
        $feed = FeedBuilder::build($this->venue, [], [], [], [['question' => 'Dogs?', 'answer' => 'Yes.']]);
        $ld = FeedBuilder::jsonLd($feed, 'Restaurant');

        $this->assertSame('https://schema.org', $ld['@context']);
        $this->assertSame('Restaurant', $ld['@type']);
        $this->assertArrayNotHasKey('faq', $ld);
        $this->assertArrayNotHasKey('version', $ld);
        $this->assertArrayNotHasKey('timeZone', $ld);
    }
}
