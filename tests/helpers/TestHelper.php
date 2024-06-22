<?php
declare(strict_types=1);

namespace App\Tests\helpers;

class TestHelper
{
    public static function openFileAndReturnLastXLines(string $filePath, int $numberOfEndingLines): array|string
    {
        $numberOfEndingLines = max(1, $numberOfEndingLines);

        $file = file($filePath);
        $lines = [];

        if (count($file) < 1) {
            return "";
        }

        if ($numberOfEndingLines === 1) {
            return $file[count($file) - $numberOfEndingLines];
        }

        for ($i = max(0, count($file) - $numberOfEndingLines), $iMax = count($file); $i < $iMax; $i++) {
            $lines[] = $file[$i]."\n";
        }

        return $lines;
    }


    // The entropy of a string can theoretically range from 0 to the log2 of the number of possible outcomes. For example:
    //
    // If all characters in the string are the same, the entropy is 0.
    // If all characters are equally probable, the entropy is at its maximum.
    // Here are some general guidelines for interpreting entropy values:
    //
    // Low entropy (close to 0): The data is highly predictable or repetitive. This means there is less information content, and the string may be less secure or less interesting.
    //
    // Medium entropy: The data has a moderate level of unpredictability. It contains a mix of different characters and patterns.
    //
    // High entropy (close to the maximum possible): The data is highly unpredictable and has a high level of information content. This is typically desirable for security-related applications and cryptographic purposes.
    // 1 bit of entropy means there are two equally likely possibilities.
    // 2 bits mean there are four equally likely possibilities.
    // 3 bits mean there are eight equally likely possibilities.
    // 4 bits indicate that there are 2^4 = 16 equally likely possibilities.
    public static function calculateEntropy($string): float|int
    {
        $length = strlen($string);
        $frequency = [];

        // Calculate the frequency of each character in the string
        for ($i = 0; $i < $length; $i++) {
            $char = $string[$i];
            if (isset($frequency[$char])) {
                $frequency[$char]++;
            } else {
                $frequency[$char] = 1;
            }
        }

        // Calculate the entropy using the Shannon entropy formula
        $entropy = 0;
        foreach ($frequency as $count) {
            $probability = $count / $length;
            $entropy -= $probability * log($probability, 2);
        }

        return $entropy;
    }

    public static function calculateStringComplexity($string): int
    {
        // Count the number of unique characters in the string
        return count(array_unique(str_split($string)));
    }

    public static function generateUTF8String(int $range, int $from = 0x0): string
    {
        $result = "";
        $counter = 0;

        for ($unicodeHexValue = $from; $unicodeHexValue <= 0xFFFFD; $unicodeHexValue++) {
            if ($range === $counter++) {
                break;
            }

            $result .= \mb_chr($unicodeHexValue, "UTF-8");
        }

        return $result;
    }
}
