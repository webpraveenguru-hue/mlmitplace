<?php

/**
 * Generate random strings based on format
 * 
 * @example CharRand::customRand("0123-XXXX-XXXX-XXXX");
 * Generates a serial that always start with 0123-
 *
 * @version 2.0
 */
class CharRand {

    /**
     * Alpha characters to use
     * @var string
     */
    const CHAR_ALPHA = "abcdefghijklmopqrstuvwxyz";

    /**
     * Capital alpha characters to use
     * @var string
     */
    const CHAR_ALPHA_CAP = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";

    /**
     * Numeric characters to use
     * @var string
     */
    const CHAR_NUMERIC = "0123456789";

    /**
     * Special characters
     * @var string
     */
    // const CHAR_SPECIAL  = "~@#$%^*()_+-={}|][";

    /**
     * Character that will be replaced in the serial format
     * @var string
     */
    const FORMAT_CHAR = "?"; // random characters
    const FORMAT_CHAR_ALPHA = "@"; // random alpha characters
    const FORMAT_CHAR_NUMERIC = "#"; // random numeric characters

    /**
     * Generate a random string
     * 
     * @param int $length
     * @param string $characters
     */
    public static function random($length = 4, $characters = "") {

        // If characters is not defined, add some default characters to use
        if ($characters == "")
            $characters .= self::CHAR_ALPHA . self::CHAR_ALPHA_CAP . self::CHAR_NUMERIC;

        $random = "";
        $characters_length = strlen($characters ?? '');
        for ($i = 0; $i < $length; $i++) {
            $random .= $characters[mt_rand(0, $characters_length - 1)];
        }
        return $random;
    }

    /**
     * Example: generate a serial number
     * 
     * @param string $seperator		Character that separates the parts
     * @param int $part_length		Number of characters in a part
     * @param int $parts		Number of parts
     * @param string $characters	Characters to use
     * @param array $exclude		Don't return serials array
     */
    public static function serial($seperator = "-", $part_length = 4, $parts = 5, $characters = "", $exclude = array()) {
        $serial = "";
        do {
            for ($i = 0; $i < $parts; $i++) {
                if ($i > 0)
                    $serial .= $seperator;
                $serial .= self::random($part_length, $characters);
            }
        }while (in_array($serial, $exclude));

        return $serial;
    }

    /**
     * Generate a random characters with a custom format
     * 
     * @param string $format
     * - Format of the serial, the FORMAT_CHAR (default=X) will be replace with a random char
     * @param string $characters
     * - Characters to use
     * @param array $exclude
     * - Don't return serials array
     */
    public static function customRand($format = "????-????-????-????", $characters = "", $exclude = array()) {

        $serial = array();
        $format_length = strlen($format ?? '');
        $characters_alpha = str_replace(self::CHAR_NUMERIC, '', $characters);
        $characters_numeric = str_replace(self::CHAR_ALPHA, '', $characters);
        $characters_numeric = str_replace(self::CHAR_ALPHA_CAP, '', $characters_numeric);
        do {
            for ($i = 0; $i < $format_length; $i++) {
                if ($format[$i] == self::FORMAT_CHAR) {
                    $serial[] = self::random(1, $characters);
                } else if ($format[$i] == self::FORMAT_CHAR_ALPHA) {
                    $serial[] = self::random(1, $characters_alpha);
                } else if ($format[$i] == self::FORMAT_CHAR_NUMERIC) {
                    $serial[] = self::random(1, $characters_numeric);
                } else {
                    $serial[] .= $format[$i];
                }
                if (in_array(implode("", $serial), $exclude))
                    $i--;
            }
        }while (in_array($serial, $exclude));
        return implode("", $serial);
    }
}
