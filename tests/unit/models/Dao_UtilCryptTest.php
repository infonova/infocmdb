<?php

class Dao_UtilCryptTest extends \Codeception\Test\Unit
{
    /**
     * @var \Util_Crypt
     */
    protected $crypt;

    protected $testString;

    protected function _before()
    {
        $this->crypt        = new Util_Crypt();
        $this->testString   = "unitTest_€流#äÜ?ß";
    }

    protected function _after()
    {

    }

    // tests
    public function testInitCrypt()
    {
        $this->assertTrue($this->crypt instanceof Util_Crypt);
    }

    public function testCreateHash()
    {
        $testEmptyHash = $this->crypt->create_hash('');
        $this->assertNotNull($testEmptyHash);
        $this->assertTrue($this->crypt->validate_password('', $testEmptyHash));
        $this->assertNotEmpty($this->crypt->create_hash($this->testString));
    }
    
    public function testCreateUniqid()
    {
        $testUniqid = $this->crypt->create_uniqid();
        $this->assertNotEmpty($testUniqid);
        $this->assertNotEquals($testUniqid, $this->crypt->create_uniqid());
    }
    
    public function testCreateSaltd()
    {
        $testSalt = $this->crypt->create_salt(10);
        $this->assertNotEmpty($testSalt);
        $this->assertTrue(strlen($testSalt) === 10);
        $this->assertTrue(strlen($this->crypt->create_salt(5)) === 5);
        $this->assertNotEquals($testSalt, $this->crypt->create_salt(10));
    }

    public function testValidateHash()
    {
        $testStringHash = $this->crypt->create_hash($this->testString);
        $this->assertNotEquals($this->crypt->create_hash($this->testString), $testStringHash, 'Creating new hash must result in different hash!');

        $this->assertTrue($this->crypt->validate_password($this->testString, $testStringHash));
        $this->assertFalse($this->crypt->validate_password('SHOULDNT MATCH'.$this->testString, $testStringHash));
        $this->assertFalse($this->crypt->validate_password('SHOULDNT MATCH'.$this->testString, ''));

        $this->assertTrue($this->crypt->validate_password($this->testString, $this->testString), 'Login without already encrypted password is possible.');
        $this->assertFalse($this->crypt->validate_password($testStringHash, $testStringHash), 'Login with potentially stolen hash must not be possible!');

        $this->assertNotEquals($this->testString, $testStringHash);

        $this->assertFalse(''   === $testStringHash);
        $this->assertFalse(0    === $testStringHash);
        $this->assertFalse(true === $testStringHash);

        $this->assertFalse($this->crypt->validate_password('',   $testStringHash));
        $this->assertFalse($this->crypt->validate_password(0,    $testStringHash));
        $this->assertFalse($this->crypt->validate_password(null, $testStringHash));
    }

}