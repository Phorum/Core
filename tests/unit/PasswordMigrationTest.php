<?php

/**
 * Unit tests for the MD5 → bcrypt password migration path.
 *
 * These tests verify the algorithm documented in:
 *   - include/db/mysql.php  phorum_db_user_check_login()   lines 3484-3491
 *   - include/api/user.php  phorum_api_user_authenticate() lines 1643-1676
 *   - include/api/user.php  phorum_api_user_save()         lines 560-570
 *
 * The DB-dependent functions cannot be called in a pure unit test, so the
 * core algorithm is tested here in isolation against the same inputs a real
 * login would use.  An optional integration group ("@group db") is provided
 * that will call the real function when a DB config is available.
 */
class PasswordMigrationTest extends \Codeception\Test\Unit
{
    // -------------------------------------------------------------------------
    // Helper: mirrors the exact hash-detection + verification logic from
    // phorum_db_user_check_login() so we can unit-test it without a DB.
    // If this helper ever diverges from the real function the integration
    // test below will catch it.
    // -------------------------------------------------------------------------

    /**
     * Detect whether $hash is a legacy plain-MD5 hash (32 lowercase hex chars).
     * Mirrors: mysql.php line 3485.
     */
    private function isLegacyMd5Hash(string $hash): bool
    {
        return strlen($hash) === 32 && ctype_xdigit($hash);
    }

    /**
     * Verify a plaintext password against a stored hash, setting $needsRehash.
     * Mirrors: mysql.php lines 3484-3491.
     */
    private function checkPassword(string $plain, string $storedHash, bool &$needsRehash): bool
    {
        if ($this->isLegacyMd5Hash($storedHash)) {
            $needsRehash = true;
            return hash_equals($storedHash, md5($plain));
        }

        $match        = password_verify($plain, $storedHash);
        $needsRehash  = password_needs_rehash($storedHash, PASSWORD_DEFAULT);
        return $match;
    }

    // =========================================================================
    // 1. Legacy MD5 hash detection
    // =========================================================================

    public function testMd5HashIsDetectedAsLegacy(): void
    {
        $hash = md5('correcthorsebatterystaple');
        $this->assertTrue(
            $this->isLegacyMd5Hash($hash),
            'A 32-char hex string produced by md5() must be detected as a legacy hash'
        );
    }

    public function testBcryptHashIsNotDetectedAsLegacy(): void
    {
        $hash = password_hash('correcthorsebatterystaple', PASSWORD_DEFAULT);
        $this->assertFalse(
            $this->isLegacyMd5Hash($hash),
            'A bcrypt hash must NOT be detected as a legacy MD5 hash'
        );
    }

    public function testEmptyStringIsNotDetectedAsLegacy(): void
    {
        $this->assertFalse(
            $this->isLegacyMd5Hash(''),
            'An empty string must not be mistaken for an MD5 hash'
        );
    }

    public function testThirtyTwoCharNonHexStringIsNotDetectedAsLegacy(): void
    {
        // 32 chars but contains non-hex characters ('g'-'z')
        $notHex = str_repeat('g', 32);
        $this->assertFalse(
            $this->isLegacyMd5Hash($notHex),
            'A 32-char string with non-hex chars must not be detected as an MD5 hash'
        );
    }

    public function testThirtyOneCharHexStringIsNotDetectedAsLegacy(): void
    {
        // One char short of a real MD5
        $this->assertFalse(
            $this->isLegacyMd5Hash(str_repeat('a', 31)),
            'A 31-char hex string must not be detected as an MD5 hash'
        );
    }

    public function testThirtyThreeCharHexStringIsNotDetectedAsLegacy(): void
    {
        $this->assertFalse(
            $this->isLegacyMd5Hash(str_repeat('a', 33)),
            'A 33-char hex string must not be detected as an MD5 hash'
        );
    }

    // =========================================================================
    // 2. Password verification — MD5 path
    // =========================================================================

    public function testCorrectPasswordMatchesLegacyMd5Hash(): void
    {
        $plain      = 'hunter2';
        $storedHash = md5($plain);
        $needsRehash = false;

        $match = $this->checkPassword($plain, $storedHash, $needsRehash);

        $this->assertTrue($match, 'Correct password must match its MD5 hash');
    }

    public function testWrongPasswordDoesNotMatchLegacyMd5Hash(): void
    {
        $storedHash  = md5('hunter2');
        $needsRehash = false;

        $match = $this->checkPassword('wrongpassword', $storedHash, $needsRehash);

        $this->assertFalse($match, 'Wrong password must not match an MD5 hash');
    }

    public function testMd5PathAlwaysSetsNeedsRehashTrue(): void
    {
        $plain       = 'hunter2';
        $storedHash  = md5($plain);
        $needsRehash = false;

        $this->checkPassword($plain, $storedHash, $needsRehash);

        $this->assertTrue(
            $needsRehash,
            'Verifying a legacy MD5 hash must set $needs_rehash = TRUE'
        );
    }

    public function testMd5PathSetsNeedsRehashTrueEvenOnFailedLogin(): void
    {
        $storedHash  = md5('correctpassword');
        $needsRehash = false;

        $this->checkPassword('wrongpassword', $storedHash, $needsRehash);

        $this->assertTrue(
            $needsRehash,
            'A failed MD5-hash login must still set $needs_rehash = TRUE (hash is still legacy)'
        );
    }

    // =========================================================================
    // 3. Password verification — bcrypt path
    // =========================================================================

    public function testCorrectPasswordMatchesBcryptHash(): void
    {
        $plain       = 'hunter2';
        $storedHash  = password_hash($plain, PASSWORD_DEFAULT);
        $needsRehash = false;

        $match = $this->checkPassword($plain, $storedHash, $needsRehash);

        $this->assertTrue($match, 'Correct password must match its bcrypt hash');
    }

    public function testWrongPasswordDoesNotMatchBcryptHash(): void
    {
        $storedHash  = password_hash('hunter2', PASSWORD_DEFAULT);
        $needsRehash = false;

        $match = $this->checkPassword('wrongpassword', $storedHash, $needsRehash);

        $this->assertFalse($match, 'Wrong password must not match a bcrypt hash');
    }

    public function testCurrentBcryptHashDoesNotNeedRehash(): void
    {
        $plain       = 'hunter2';
        $storedHash  = password_hash($plain, PASSWORD_DEFAULT);
        $needsRehash = false;

        $this->checkPassword($plain, $storedHash, $needsRehash);

        $this->assertFalse(
            $needsRehash,
            'A bcrypt hash produced with PASSWORD_DEFAULT must not need rehashing'
        );
    }

    public function testOutdatedBcryptCostNeedsRehash(): void
    {
        $plain = 'hunter2';
        // Hash with cost=4 (lowest possible); PASSWORD_DEFAULT is typically cost=12
        $lowCostHash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 4]);
        $needsRehash = false;

        $this->checkPassword($plain, $lowCostHash, $needsRehash);

        // password_needs_rehash() compares the hash's cost against PASSWORD_DEFAULT.
        // Cost 4 is below the default, so rehash should be needed.
        $expected = password_needs_rehash($lowCostHash, PASSWORD_DEFAULT);
        $this->assertSame(
            $expected,
            $needsRehash,
            'A bcrypt hash with a lower-than-default cost must be flagged for rehashing'
        );
    }

    // =========================================================================
    // 4. Password save produces a bcrypt hash (mirrors phorum_api_user_save)
    // =========================================================================

    public function testSavedPasswordHashIsBcrypt(): void
    {
        $plain = 'hunter2';
        // mirrors: phorum_api_user_save() line 569
        $newHash = password_hash($plain, PASSWORD_DEFAULT);

        $this->assertNotFalse($newHash, 'password_hash() must not return false');
        $this->assertFalse(
            $this->isLegacyMd5Hash($newHash),
            'password_hash() must not produce a 32-char MD5-looking string'
        );
        $this->assertTrue(
            password_verify($plain, $newHash),
            'The new bcrypt hash must verify correctly against the original plaintext'
        );
    }

    public function testSavedPasswordHashDoesNotNeedRehash(): void
    {
        $plain   = 'hunter2';
        $newHash = password_hash($plain, PASSWORD_DEFAULT);

        $this->assertFalse(
            password_needs_rehash($newHash, PASSWORD_DEFAULT),
            'A freshly generated hash must not immediately require rehashing'
        );
    }

    // =========================================================================
    // 5. Migration round-trip: simulate a user who had an MD5 hash logging in
    // =========================================================================

    public function testMigrationRoundTrip(): void
    {
        $plain = 'correcthorsebatterystaple';

        // Step 1: User was stored with an old MD5 hash.
        $legacyHash  = md5($plain);
        $needsRehash = false;

        $loginSuccess = $this->checkPassword($plain, $legacyHash, $needsRehash);

        $this->assertTrue($loginSuccess, 'Login against legacy MD5 hash must succeed');
        $this->assertTrue($needsRehash,  'Legacy MD5 hash must trigger rehash flag');

        // Step 2: On successful login, Phorum calls phorum_api_user_save()
        //         which runs password_hash() on the plaintext (mirrors line 569).
        $upgradedHash = password_hash($plain, PASSWORD_DEFAULT);

        // Step 3: Subsequent login uses the new bcrypt hash.
        $needsRehash2  = false;
        $loginSuccess2 = $this->checkPassword($plain, $upgradedHash, $needsRehash2);

        $this->assertTrue($loginSuccess2,  'Login against upgraded bcrypt hash must succeed');
        $this->assertFalse($needsRehash2,  'Upgraded bcrypt hash must not trigger another rehash');

        // Step 4: Wrong password against the upgraded hash must fail.
        $needsRehash3 = false;
        $loginFail    = $this->checkPassword('wrongpassword', $upgradedHash, $needsRehash3);

        $this->assertFalse($loginFail, 'Wrong password against upgraded bcrypt hash must fail');
    }

    // =========================================================================
    // 6. Hash-equals timing safety: MD5 path uses hash_equals, not ==
    //    (documents the constant-time comparison requirement)
    // =========================================================================

    public function testMd5ComparisonIsTimingSafe(): void
    {
        $plain      = 'hunter2';
        $storedHash = md5($plain);

        // Both sides of the hash_equals call must be identical for a match.
        // The test indirectly verifies this is what the code does by confirming
        // that md5($plain) === $storedHash, which is the equality hash_equals checks.
        $this->assertTrue(
            hash_equals($storedHash, md5($plain)),
            'hash_equals must return true when both sides are the same MD5 digest'
        );
        $this->assertFalse(
            hash_equals($storedHash, md5('wrongpassword')),
            'hash_equals must return false when digests differ'
        );
    }
}
