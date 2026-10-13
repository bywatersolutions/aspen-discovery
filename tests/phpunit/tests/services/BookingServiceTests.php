<?php

use PHPUnit\Framework\TestCase;

/**
 * Aspen stores plain dates, Koha returns full timestamps, and the difference
 * must not read as a staff edit. That normalisation, and the tri-state result
 * the booking badge renders from, are what these cover.
 */
class BookingServiceTests extends TestCase {
	private static int $userId = 9901;
	private static int $nextIlsId = 1;

	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../code/web/services/BookingService.php';
	}

	public static function setUpBeforeClass(): void {
		global $aspen_db;
		$aspen_db->exec("CREATE TABLE IF NOT EXISTS user_booking (
			id int(11) NOT NULL AUTO_INCREMENT,
			userId int(11) NOT NULL,
			recordId varchar(50) NOT NULL,
			itemId varchar(50) NOT NULL,
			ils_booking_id int(11) NOT NULL,
			ils_start_date date NOT NULL,
			ils_end_date date NOT NULL,
			ils_pickup_library_id varchar(50) DEFAULT NULL,
			ils_status varchar(50) DEFAULT NULL,
			createdAt int(11) NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY userId (userId, ils_booking_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
	}

	protected function setUp(): void {
		parent::setUp();
		global $aspen_db;
		$aspen_db->exec("DELETE FROM user_booking WHERE userId = " . self::$userId);
	}

	private function nextIlsId(): int {
		return self::$nextIlsId++;
	}

	private function setUpSyncTest(int $ilsId): User {
		$patron = new User();
		$patron->id = self::$userId;

		BookingService::storeBooking(
			$patron,
			'ITEM-' . $ilsId,
			'BIB-' . $ilsId,
			'2026-06-01',
			'2026-06-07',
			'CPL',
			['booking_id' => $ilsId, 'status' => 'confirmed']
		);

		return $patron;
	}

	private function liveBooking(int $ilsId, array $overrides = []): array {
		return $overrides + [
			'booking_id'        => $ilsId,
			'biblio_id'         => 'BIB-' . $ilsId,
			'item_id'           => 'ITEM-' . $ilsId,
			'start_date'        => '2026-06-01',
			'end_date'          => '2026-06-07',
			'status'            => 'confirmed',
			'pickup_library_id' => 'CPL',
		];
	}

	private function findStored(int $ilsId): Booking {
		$booking = new Booking();
		$booking->userId = self::$userId;
		$booking->ils_booking_id = $ilsId;
		$booking->find(true);
		return $booking;
	}

	// -------------------------------------------------------------------------

	public function testSyncAndMapBookingsFlagsStaffModifiedWhenDatesDiffer(): void {
		$ilsId = $this->nextIlsId();
		$patron = $this->setUpSyncTest($ilsId);

		$result = BookingService::syncAndMapBookings($patron, [
			$this->liveBooking($ilsId, ['start_date' => '2026-06-03', 'end_date' => '2026-06-10']),
		]);

		$this->assertTrue($result[0]['staffModified']);
	}

	public function testSyncAndMapBookingsWritesTheIlsValuesBackWhenStaffChangedThem(): void {
		$ilsId = $this->nextIlsId();
		$patron = $this->setUpSyncTest($ilsId);

		BookingService::syncAndMapBookings($patron, [
			$this->liveBooking($ilsId, [
				'start_date'        => '2026-06-15',
				'end_date'          => '2026-06-20',
				'status'            => 'waiting',
				'pickup_library_id' => 'MPL',
			]),
		]);

		$stored = $this->findStored($ilsId);
		$this->assertEquals('2026-06-15', $stored->ils_start_date);
		$this->assertEquals('2026-06-20', $stored->ils_end_date);
		$this->assertEquals('waiting', $stored->ils_status);
		$this->assertEquals('MPL', $stored->ils_pickup_library_id);
	}

	public function testSyncAndMapBookingsIgnoresDateFormatDifferencesForTheSameInstant(): void {
		$ilsId = $this->nextIlsId();
		$patron = $this->setUpSyncTest($ilsId);

		$result = BookingService::syncAndMapBookings($patron, [
			$this->liveBooking($ilsId, [
				'start_date' => '2026-06-01T00:00:00+00:00',
				'end_date'   => '2026-06-07T00:00:00+00:00',
			]),
		]);

		$this->assertFalse(
			$result[0]['staffModified'],
			'Koha returns full timestamps where Aspen stores plain dates; the same instant is not a staff change'
		);
	}

	public function testSyncAndMapBookingsLeavesTheStoredRowAloneWhenOnlyTheFormatDiffers(): void {
		$ilsId = $this->nextIlsId();
		$patron = $this->setUpSyncTest($ilsId);

		BookingService::syncAndMapBookings($patron, [
			$this->liveBooking($ilsId, [
				'start_date' => '2026-06-01T00:00:00+00:00',
				'end_date'   => '2026-06-07T00:00:00+00:00',
			]),
		]);

		$stored = $this->findStored($ilsId);
		$this->assertEquals('2026-06-01', $stored->ils_start_date);
		$this->assertEquals('2026-06-07', $stored->ils_end_date);
	}

	public function testSyncAndMapBookingsReportsUnknownWhenIlsDatesCannotBeParsed(): void {
		$ilsId = $this->nextIlsId();
		$patron = $this->setUpSyncTest($ilsId);

		$result = BookingService::syncAndMapBookings($patron, [
			$this->liveBooking($ilsId, ['start_date' => 'not-a-date', 'end_date' => 'also-not-a-date']),
		]);

		$this->assertNull(
			$result[0]['staffModified'],
			'Unparseable ILS dates mean the comparison failed, which is null rather than false'
		);
	}
}
