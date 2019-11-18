<?php

/*
 * Class provides advanced secure encryption functionality across php versions.
 */

class Util_Crypt
{
    // These constants may be changed without breaking existing hashes.
    const PBKDF2_HASH_ALGORITHM = 500;
    const PBKDF2_ITERATIONS     = 1001;
    const PBKDF2_SALT_BYTE_SIZE = 24;
    const PBKDF2_HASH_BYTE_SIZE = 48;
    const HASH_SECTIONS         = 4;
    const HASH_ALGORITHM_INDEX  = 0;
    const HASH_ITERATION_INDEX  = 1;
    const HASH_SALT_INDEX       = 2;
    const HASH_PBKDF2_INDEX     = 3;

    // DON'T CHANGE - obfuscation
    private $hash_array = array(
        500 => 'sha256',
    );

    public function hash_all_plaintext_passwords()
    {
        $users = new Dao_User();
        echo '<pre>';
        foreach ($users->getUsers() as $user) {
            $userId       = $user[Db_User::ID];
            $userName     = $user[Db_User::USERNAME];
            $userPassword = $user[Db_User::PASSWORD];
            if (strstr($userPassword, ':')) {
                // already has : in it? Verify!
                echo $userId . ';' . $userName . ';' . $userPassword . ';Is already hashed' . "\n";
                continue;
            }
            $params = explode(":", $userPassword);
            if (count($params) > 1 && count($params) <> self::HASH_SECTIONS) {
                echo $userId . ';' . $userName . ';' . $userPassword . ';wrong : count' . count($params) . "\n";
                // doesn't have the correct : count
            } elseif (count($params) == 1) {
                // not hashed, hash it
                echo $userId . ';' . $userName . ';' . $userPassword . ';hashing it' . "\n";
                $users->updateUserPassword($userId, $userPassword);
            } else {
                // already hashed
                continue;
            }
        }
        echo '</pre>';

    }

    public function create_hash($password)
    {
        // format: algorithm:iterations:salt:hash
        $salt = $this->create_salt(self::PBKDF2_SALT_BYTE_SIZE);
        return self::PBKDF2_HASH_ALGORITHM . ":" . self::PBKDF2_ITERATIONS . ":" .
            $salt . ":" . base64_encode(
                $this->pbkdf2(
                    $this->hash_array[self::PBKDF2_HASH_ALGORITHM],
                    $password, $salt, self::PBKDF2_ITERATIONS,
                    self::PBKDF2_HASH_BYTE_SIZE, true));
    }

    public function create_salt($length)
    {
        if (function_exists("openssl_random_pseudo_bytes")) {
            return substr(base64_encode(openssl_random_pseudo_bytes($length * 2)), 0, $length);
        } else {
            // Fallback
            $salt = '';
            while (strlen($salt) < $length) {
                $salt .= base64_encode(sha1(mt_rand() . microtime(), true));
            }
            return substr($salt, 0, $length);
        }
    }

    public function create_uniqid()
    {
        if (function_exists("openssl_random_pseudo_bytes")) {
            return hash('sha256', microtime() . openssl_random_pseudo_bytes(100), false);
        }

        return hash('sha256', microtime() . uniqid('', true), false);
    }

    public function validate_password($password, $correct_hash)
    {
        $params = explode(":", $correct_hash);
        /*
         * If correct_hash param count doesn't match its properly not encrypted already
         * If given password and stored password match we will return true for sake
         * of allowing not yet encrypted passwords to login
         */
        if (count($params) != self::HASH_SECTIONS) {
            if ($password === $correct_hash) {
                return true;
            }
            return false;
        }
        $pbkdf2 = base64_decode($params[self::HASH_PBKDF2_INDEX]);

        return $this->slow_equals($pbkdf2,
            $this->pbkdf2(
                $this->hash_array[$params[self::HASH_ALGORITHM_INDEX]],
                $password, $params[self::HASH_SALT_INDEX],
                (int)$params[self::HASH_ITERATION_INDEX],
                strlen($pbkdf2), true));
    }

    // Compares two strings $a and $b in length-constant time.
    private function slow_equals($a, $b)
    {
        $diff = strlen($a) ^ strlen($b);
        for ($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $diff === 0;
    }

    /*
     * PBKDF2 key derivation function as defined by RSA's PKCS #5:
     * https://www.ietf.org/rfc/rfc2898.txt $algorithm - The hash algorithm to
     * use. Recommended: SHA256 $password - The password. $salt - A salt that is
     * unique to the password. $count - Iteration count. Higher is better, but
     * slower. Recommended: At least 1000. $key_length - The length of the
     * derived key in bytes. $raw_output - If true, the key is returned in raw
     * binary format. Hex encoded otherwise. Returns: A $key_length-byte key
     * derived from the password and salt. Test vectors can be found here:
     * https://www.ietf.org/rfc/rfc6070.txt This implementation of PBKDF2 was
     * originally created by https://defuse.ca With improvements by
     * http://www.variations-of-shadow.com
     */
    public function pbkdf2(
        $algorithm, $password, $salt, $count, $key_length,
        $raw_output = false
    )
    {
        $algorithm = strtolower($algorithm);
        if (!in_array($algorithm, hash_algos(), true)) {
            trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
        }
        if ($count <= 0 || $key_length <= 0) {
            trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);
        }

        if (function_exists("hash_pbkdf2")) {
            // The output length is in NIBBLES (4-bits) if $raw_output is false!
            if (!$raw_output) {
                $key_length = $key_length * 2;
            }
            return hash_pbkdf2($algorithm, $password, $salt, $count,
                $key_length, $raw_output);
        }

        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($key_length / $hash_length);

        $output = "";
        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password,
                    true));
            }
            $output .= $xorsum;
        }

        if ($raw_output) {
            return substr($output, 0, $key_length);
        } else {
            return bin2hex(substr($output, 0, $key_length));
        }
    }

    /**
     * Get a new JWT-Builder instance with pre-filled basic information
     *
     * @param integer $secondsLifeTime lifetime of token in seconds
     * @param array   $claims          additional claims to set
     * @return \Lcobucci\JWT\Builder
     */
    public function getJwtBuilder($secondsLifeTime, $claims = array())
    {
        $tokenId = $this->create_salt(60);

        $tokenBuilder = new \Lcobucci\JWT\Builder();
        $tokenBuilder
            ->setIssuer(APPLICATION_URL)// Configures the issuer (iss claim)
            ->setId($tokenId, true)// Configures the id (jti claim), replicating as a header item
            ->setIssuedAt(time())// Configures the time that the token was issued (iat claim)
            ->setExpiration(time() + $secondsLifeTime)// Configures the expiration time of the token (exp claim)
        ;

        foreach ($claims as $claimName => $claimValue) {
            $tokenBuilder->set($claimName, $claimValue);
        }

        return $tokenBuilder;
    }
}