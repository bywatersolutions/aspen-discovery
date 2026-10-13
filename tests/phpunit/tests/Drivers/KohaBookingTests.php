<?php

use PHPUnit\Framework\TestCase;

/**
 * The driver's booking decisions: how long a booking may run, and which dates
 * are unavailable by each of the two routes — Koha's availability endpoint, and
 * the bookings-table fallback used when it cannot be reached. Every method under
 * test is pure, so no HTTP or database is involved.
 */
class KohaBookingTests extends TestCase {

	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../code/web/Drivers/Koha.php';
	}

	private function capEndDate(string $date, array $rules): string {
		return Koha::applyHardDueDateCap(new DateTime($date), $rules)->format('Y-m-d');
	}

	private function blockedCell(): array {
		return ['blockers' => ['booking' => 1]];
	}

	private function booking(string $start, string $end): array {
		return ['start_date' => $start, 'end_date' => $end];
	}

	// -------------------------------------------------------------------------
	// issuelength + (renewalsallowed * renewalperiod), mirroring place_booking.js

	public function testMaxBookingPeriodAddsEveryRenewalToTheLoanLength(): void {
		$this->assertEquals(42, Koha::getMaxBookingPeriod([
			'issuelength'     => 14,
			'renewalsallowed' => 2,
			'renewalperiod'   => 14,
		]));
	}

	public function testMaxBookingPeriodFallsBackToTheLoanLengthWhenRenewalPeriodIsUnset(): void {
		$this->assertEquals(
			21,
			Koha::getMaxBookingPeriod([
				'issuelength'     => 7,
				'renewalsallowed' => 2,
				'renewalperiod'   => null,
			]),
			'CalcDateDue renews for the loan length when no renewal period is configured'
		);
	}

	public function testMaxBookingPeriodTreatsAZeroRenewalPeriodAsZeroRatherThanAFallback(): void {
		$this->assertEquals(
			7,
			Koha::getMaxBookingPeriod([
				'issuelength'     => 7,
				'renewalsallowed' => 3,
				'renewalperiod'   => 0,
			]),
			'Only an absent renewal period falls back to the loan length, not a configured zero'
		);
	}

	// -------------------------------------------------------------------------
	// lengthunit: bookings are whole days, Koha's loan lengths may be hours

	public function testMaxBookingPeriodRoundsAPartDayHourlyLoanUp(): void {
		$this->assertEquals(
			1,
			Koha::getMaxBookingPeriod([
				'issuelength'     => 4,
				'renewalsallowed' => 0,
				'lengthunit'      => 'hours',
			]),
			'A four hour loan still occupies a calendar day; rounding down would collapse the window'
		);
	}

	public function testMaxBookingPeriodConvertsTheTotalRatherThanEachPartSeparately(): void {
		$this->assertEquals(
			1,
			Koha::getMaxBookingPeriod([
				'issuelength'     => 8,
				'renewalsallowed' => 2,
				'renewalperiod'   => 8,
				'lengthunit'      => 'hours',
			]),
			'24 hours total is one day; rounding each of the three parts up would give three'
		);
	}

	// -------------------------------------------------------------------------
	// hardduedate, with hardduedatecompare deciding whether and how it binds

	public function testHardDueDateIsIgnoredWhenNoneIsConfigured(): void {
		$this->assertEquals('2026-06-20', $this->capEndDate('2026-06-20', []));
	}

	public function testHardDueDateReplacesEvenALaterDateWhenTheRuleIsExact(): void {
		$this->assertEquals(
			'2026-06-30',
			$this->capEndDate('2026-06-20', [
				'hardduedate'        => '2026-06-30',
				'hardduedatecompare' => 0,
			]),
			'Exact means exact in both directions, so it extends as readily as it shortens'
		);
	}

	public function testHardDueDateAsACeilingShortensALaterDate(): void {
		$this->assertEquals('2026-06-10', $this->capEndDate('2026-06-20', [
			'hardduedate'        => '2026-06-10',
			'hardduedatecompare' => -1,
		]));
	}

	public function testHardDueDateAsACeilingLeavesAnEarlierDateAlone(): void {
		$this->assertEquals(
			'2026-06-20',
			$this->capEndDate('2026-06-20', [
				'hardduedate'        => '2026-06-30',
				'hardduedatecompare' => -1,
			]),
			'A ceiling caps the window, it never extends it'
		);
	}

	public function testHardDueDateAsAFloorExtendsToALaterDate(): void {
		$this->assertEquals('2026-06-30', $this->capEndDate('2026-06-20', [
			'hardduedate'        => '2026-06-30',
			'hardduedatecompare' => 1,
		]));
	}

	public function testHardDueDateAsAFloorLeavesAnEarlierDateAlone(): void {
		$this->assertEquals(
			'2026-06-20',
			$this->capEndDate('2026-06-20', [
				'hardduedate'        => '2026-06-10',
				'hardduedatecompare' => 1,
			]),
			'A floor extends only, so it must never shorten the booking window'
		);
	}

	public function testHardDueDateIgnoresAnyTimePartOnTheRule(): void {
		$this->assertEquals('2026-06-10', $this->capEndDate('2026-06-20', [
			'hardduedate'        => '2026-06-10 23:59:00',
			'hardduedatecompare' => 0,
		]));
	}

	public function testHardDueDateHandlesTheStringCompareModeTheRulesApiReturns(): void {
		$this->assertEquals(
			'2026-06-20',
			$this->capEndDate('2026-06-20', [
				'hardduedate'        => '2026-06-10',
				'hardduedatecompare' => '1',
			]),
			'The API returns rule values as strings, so a floor must still read as a floor'
		);
	}

	// -------------------------------------------------------------------------
	// collapseBlockedDates: the endpoint's sparse date => item => reasons map

	public function testBlockedDatesCollapseIntoOneRangeWhenContiguous(): void {
		$blocked = Koha::collapseBlockedDates([
			'2026-06-01' => [55 => $this->blockedCell()],
			'2026-06-02' => [55 => $this->blockedCell()],
			'2026-06-03' => [55 => $this->blockedCell()],
		], 55);

		$this->assertEquals([['start' => '2026-06-01', 'end' => '2026-06-03']], $blocked);
	}

	public function testBlockedDatesSplitIntoSeparateRangesAcrossAGap(): void {
		$blocked = Koha::collapseBlockedDates([
			'2026-06-01' => [55 => $this->blockedCell()],
			'2026-06-03' => [55 => $this->blockedCell()],
		], 55);

		$this->assertEquals([
			['start' => '2026-06-01', 'end' => '2026-06-01'],
			['start' => '2026-06-03', 'end' => '2026-06-03'],
		], $blocked);
	}

	public function testWarningOnlyDatesAreNotBlocked(): void {
		$blocked = Koha::collapseBlockedDates([
			'2026-06-01' => [55 => ['warnings' => ['lead_floor' => 1]]],
		], 55);

		$this->assertEquals(
			[],
			$blocked,
			'Warnings are advisory; the endpoint emits cells for them regardless of pickup library'
		);
	}

	public function testAnotherItemsBlockersDoNotBlockThisItem(): void {
		$blocked = Koha::collapseBlockedDates([
			'2026-06-01' => [99 => $this->blockedCell()],
		], 55);

		$this->assertEquals([], $blocked);
	}

	// -------------------------------------------------------------------------
	// buildBookedRanges: the fallback, padding each booking with lead and trail

	public function testBookedRangeWithoutLeadOrTrailIsTheBookingItself(): void {
		$ranges = Koha::buildBookedRanges([$this->booking('2026-06-10', '2026-06-12')], 0, 0);

		$this->assertEquals([['start' => '2026-06-10', 'end' => '2026-06-12']], $ranges);
	}

	public function testLeadExtendsTheRangeBackwardsAndTrailForwards(): void {
		$ranges = Koha::buildBookedRanges([$this->booking('2026-06-10', '2026-06-12')], 2, 3);

		$this->assertEquals([['start' => '2026-06-08', 'end' => '2026-06-15']], $ranges);
	}

	public function testBookedRangesIgnoreAnyTimePartOnTheIlsDates(): void {
		$ranges = Koha::buildBookedRanges(
			[$this->booking('2026-06-10T00:00:00+00:00', '2026-06-12T00:00:00+00:00')],
			0,
			0
		);

		$this->assertEquals([['start' => '2026-06-10', 'end' => '2026-06-12']], $ranges);
	}

	public function testEveryBookingGetsTheSameLeadAndTrail(): void {
		$ranges = Koha::buildBookedRanges([
			$this->booking('2026-06-10', '2026-06-12'),
			$this->booking('2026-07-10', '2026-07-12'),
		], 1, 1);

		$this->assertEquals([
			['start' => '2026-06-09', 'end' => '2026-06-13'],
			['start' => '2026-07-09', 'end' => '2026-07-13'],
		], $ranges);
	}
}
