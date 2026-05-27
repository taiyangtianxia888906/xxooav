<?php
/**
 * PHP implementation of Google Authenticator TOTP
 * Based on https://github.com/PHPGangsta/GoogleAuthenticator
 * No external dependencies.
 */
class GoogleAuthenticator
{
    /**
     * Generate a random secret
     */
    public function generateSecret($length = 16)
    {
        $validChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $validChars[random_int(0, strlen($validChars) - 1)];
        }
        return $secret;
    }

    /**
     * Get the QR Code URL for Google Authenticator
     */
    public function getQRCodeUrl($name, $secret, $title = null)
    {
        $encoder = "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=";
        $urlencoded = "otpauth://totp/" . rawurlencode($name) . "?secret={$secret}";
        if ($title) {
            $urlencoded .= "&issuer=" . rawurlencode($title);
        }
        return $encoder . rawurlencode($urlencoded);
    }

    /**
     * Check if the code is valid for the given secret
     * @param string $secret
     * @param string $code
     * @param int $discrepancy
     * @return bool
     */
    public function checkCode($secret, $code, $discrepancy = 1)
    {
        $currentTimeSlice = floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if ($calculatedCode == $code) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calculate the current code for the secret
     */
    public function getCode($secret, $timeSlice = null)
    {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        $secret = $this->base32Decode($secret);
        $time = pack("N", $timeSlice);
        $time = str_pad($time, 8, "\x00", STR_PAD_LEFT);
        $hash = hash_hmac('sha1', $time, $secret, true);
        $offset = ord($hash[19]) & 0xF;
        $truncatedHash = $this->hashToInt($hash, $offset) & 0x7FFFFFFF;
        $pin = $truncatedHash % 1000000;
        return str_pad($pin, 6, "0", STR_PAD_LEFT);
    }

    private function base32Decode($secret)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = strtoupper($secret);
        $result = '';
        $buffer = 0;
        $bits = 0;
        for ($i = 0; $i < strlen($secret); $i++) {
            $val = strpos($alphabet, $secret[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $result .= chr(($buffer >> $bits) & 0xFF);
            }
        }
        return $result;
    }

    private function hashToInt($bytes, $start)
    {
        return (ord($bytes[$start]) << 24) |
               (ord($bytes[$start + 1]) << 16) |
               (ord($bytes[$start + 2]) << 8) |
               ord($bytes[$start + 3]);
    }
}
