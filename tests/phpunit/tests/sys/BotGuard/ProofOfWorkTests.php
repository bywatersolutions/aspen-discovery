<?php
namespace sys\BotGuard;

use PHPUnit\Framework\TestCase;
use ProofOfWork;

require_once __DIR__ . '/../../../../../code/web/sys/BotGuard/TrustCookie.php';
require_once __DIR__ . '/../../../../../code/web/sys/BotGuard/ProofOfWork.php';

class ProofOfWorkTests extends TestCase {
	const SECRET = 'unit test secret';
	const NOW = 1_800_000_000;

	public function testSolvedPuzzleVerifiesToItsNonce(): void {
		$puzzle = ProofOfWork::issue(self::SECRET, 8, self::NOW);
		$this->assertMatchesRegularExpression('/^1800000000\.[0-9a-f]{16}\.8\.[0-9a-f]{64}$/', $puzzle);
		$counter = ProofOfWork::solve($puzzle, 8);
		$this->assertSame(explode('.', $puzzle)[1], ProofOfWork::verify($puzzle, (string)$counter, self::SECRET, 8, self::NOW + 10));
	}

	public function testHarderPuzzleSatisfiesAnEasierRequirement(): void {
		$puzzle = ProofOfWork::issue(self::SECRET, 10, self::NOW);
		$counter = (string)ProofOfWork::solve($puzzle, 10);
		$this->assertNotNull(ProofOfWork::verify($puzzle, $counter, self::SECRET, 8, self::NOW));
		$this->assertNull(ProofOfWork::verify($puzzle, $counter, self::SECRET, 12, self::NOW));
	}

	public function testWrongCounterIsRejected(): void {
		$puzzle = ProofOfWork::issue(self::SECRET, 8, self::NOW);
		$counter = ProofOfWork::solve($puzzle, 8);
		$this->assertNull(ProofOfWork::verify($puzzle, (string)($counter + 1), self::SECRET, 8, self::NOW));
		$this->assertNull(ProofOfWork::verify($puzzle, 'x', self::SECRET, 8, self::NOW));
		$this->assertNull(ProofOfWork::verify($puzzle, '', self::SECRET, 8, self::NOW));
	}

	public function testTamperedPuzzleIsRejected(): void {
		$puzzle = ProofOfWork::issue(self::SECRET, 8, self::NOW);
		$counter = (string)ProofOfWork::solve($puzzle, 8);
		$easier = preg_replace('/\.8\./', '.1.', $puzzle);
		$this->assertNull(ProofOfWork::verify($easier, $counter, self::SECRET, 1, self::NOW));
		$this->assertNull(ProofOfWork::verify($puzzle, $counter, 'another secret', 8, self::NOW));
		$this->assertNull(ProofOfWork::verify('', $counter, self::SECRET, 8, self::NOW));
	}

	public function testPuzzleExpires(): void {
		$puzzle = ProofOfWork::issue(self::SECRET, 8, self::NOW);
		$counter = (string)ProofOfWork::solve($puzzle, 8);
		$this->assertNotNull(ProofOfWork::verify($puzzle, $counter, self::SECRET, 8, self::NOW + ProofOfWork::TTL));
		$this->assertNull(ProofOfWork::verify($puzzle, $counter, self::SECRET, 8, self::NOW + ProofOfWork::TTL + 1));
		$this->assertNull(ProofOfWork::verify($puzzle, $counter, self::SECRET, 8, self::NOW - 301));
	}

	public function testLeadingZeroBits(): void {
		$this->assertSame(0, ProofOfWork::leadingZeroBits("\x80"));
		$this->assertSame(7, ProofOfWork::leadingZeroBits("\x01\xff"));
		$this->assertSame(20, ProofOfWork::leadingZeroBits("\x00\x00\x0f\x00"));
		$this->assertSame(256, ProofOfWork::leadingZeroBits(str_repeat("\x00", 32)));
	}
}
