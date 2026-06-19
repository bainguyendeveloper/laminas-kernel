<?php

namespace AppKernel\Traits;

use NumberFormatter;

/**
 * cleanText
 * createAlias
 * get_file_extension
 * get_numerics
 * nl2p
 * wordLimit
 * characterLimit
 * random
 * UUID
 * generatePassword
 * unichr
 * remove_special_character
 * fix_latin1_mangled_with_utf8_maybe_hopefully_most_of_the_time
 * utf8_encode_callback
 * vt_safe_vietnamese_meta
 * vt_remove_vietnamese_accent
 * vt_remove_special_characters
 * vt_replace_vietnamese_characters
 * stripExt
 * makeSafe
 * splitURL
 * buildURLFromParts
 * normalizeURL
 * getHTTPStatusCode
 * getRedirectURLFromHeader
 * getHeaderValue
 * getCookiesFromHeader
 * getRootUrl
 * serializeToFile
 * deserializeFromFile
 * sort2dArray
 * getSystemTempDir
 * isUTF8String
 * isValidUrlString
 * decodeGZipContent
 * isGzipEncoded
 * getDateInWeek
 * timer
 * website
 * get_client_ip
 * formatMoney
 * 
 */
trait Utils {

    public static $golden_primes = [
        '1' => '1',
        '41' => '59',
        '2377' => '1677',
        '147299' => '187507',
        '9132313' => '5952585',
        '566201239' => '643566407',
        '35104476161' => '22071637057',
        '2176477521929' => '294289236153',
        '134941606358731' => '88879354792675',
        '8366379594239857' => '7275288500431249',
        '518715534842869223' => '280042546585394647'
    ];
    public static $chars62 = [
        0 => 48, 1 => 49, 2 => 50, 3 => 51, 4 => 52, 5 => 53, 6 => 54, 7 => 55, 8 => 56, 9 => 57, 10 => 65,
        11 => 66, 12 => 67, 13 => 68, 14 => 69, 15 => 70, 16 => 71, 17 => 72, 18 => 73, 19 => 74, 20 => 75,
        21 => 76, 22 => 77, 23 => 78, 24 => 79, 25 => 80, 26 => 81, 27 => 82, 28 => 83, 29 => 84, 30 => 85,
        31 => 86, 32 => 87, 33 => 88, 34 => 89, 35 => 90, 36 => 97, 37 => 98, 38 => 99, 39 => 100, 40 => 101,
        41 => 102, 42 => 103, 43 => 104, 44 => 105, 45 => 106, 46 => 107, 47 => 108, 48 => 109, 49 => 110,
        50 => 111, 51 => 112, 52 => 113, 53 => 114, 54 => 115, 55 => 116, 56 => 117, 57 => 118, 58 => 119,
        59 => 120, 60 => 121, 61 => 122
    ];

    public static function removeHtml($str) {
        return trim(preg_replace('/<[^>]*>/', '', $str));
    }

    public function check_similar_text(string $string1, string $string2, $numbermatch = false) {
        $perc = 0;
        $numbercharmatch = similar_text($string1, $string2, $perc);
        if ($numbermatch):
            return ['numbermatch' => $numbercharmatch, 'perc' => $perc];
        endif;
        return $perc;
    }

    public static function convertToNumber($number = 0) {
        $prices = explode('.', $number, 2);
        if (count($prices) == 2):
            $price1 = preg_replace('/(?!^-)\D/', '', $prices[0]);
            $price2 = preg_replace('/\D/', '', $prices[1]);
            if ((int) $price2):
                $number = $price1 . '.' . $price2;
            else:
                $number = $price1;
            endif;
        else:
            $number = preg_replace('/(?!^-)\D/', '', $prices[0]);
        endif;
        return floatval($number);
    }
    public function validatePhoneNumber($_telephone, int $minDigits = 9, int $maxDigits = 12) {
        $telephone = preg_replace('/\D/', '', $_telephone);

        if (!substr($telephone, 0, 1) == '0'):
            $telephone = '0' . $telephone;
        endif;
        $length = strlen($telephone);
        if (($length >= $minDigits) && ($length <= $maxDigits)):
            return $telephone;
        else:
            return '';
        endif;
    }

    public function validateEmail($_email) {
        $email = $this->normalize($_email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return '';
        } else {
            return $email;
        }
    }

    public static function cleanText($str) {
        if (is_array($str)) :
            $str = array_shift($str);
        endif;
        $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;
        $patterns = [
            '/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ|À|Á|Ạ|Ả|Ã|Â|A|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ|𝗔|𝐀|ɑ)/',
            '/(B|𝐁|Ɓ|ß)/',
            '/(C)/',
            '/(đ|D|Đ|𝗗|Ɗ)/',
            '/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ|È|É|Ẹ|E|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ|𝗘|𝐄)/',
            '/(F|Ƒ)/',
            '/(G|𝗚)/',
            '/(H|𝗛|𝐇)/',
            '/(ì|í|ị|ỉ|ĩ|Ì|Í|Ị|Ỉ|Ĩ|𝗜)/',
            '/(J)/',
            '/(K|𝗞)/',
            '/(L|𝐋)/',
            '/(M|𝗠|𝐌)/',
            '/(N|𝗡|̛́𝗡|𝐍|Ŋ|Ɲ)/',
            '/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ|Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|O|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ|̛𝗢|𝗢|𝗢|𝐎)/',
            '/(P|𝗣|𝐏|Ƥ)/',
            '/(Q)/',
            '/(R|𝗥|𝐑)/',
            '/(S|𝗦|𝐒)/',
            '/(T|𝗧|̂́𝗧|𝐓|Ƭ)/',
            '/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ|Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ|𝗨|𝐔)/',
            '/(V|𝗩)/',
            '/(W)/',
            '/(X|𝗫)/',
            '/(ỳ|ý|ỵ|ỷ|ỹ|Ỳ|Ý|Ỵ|Ỷ|Ỹ|𝐘)/',
            '/(Z)/',
            '/(\[|\]|\(|\))/',
            '/(\!|\@|\#|\%|\^|\&|\*|\_|\+|\=|\<|\>|\?|\/|\,|\;|\÷\:|\'|\"|\“|\”|\~|\`|\{|\}|\|)/',
            $regex
        ];
        $replacements = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '-', '-', '$1'];

        $str1 = preg_replace($patterns, $replacements, self::normalize($str));

        $newPatterns = ['&#212;', '&#8230;', '&*#39;', '&amp;', '®', '™', 'æ', 'Ø', '́', '̀', '̉', '̣', '̃', '$'];
        $newReplacements = ['o', '', '', '', 'r', 'tm', 'ae', '', '', '', '', '', '', ''];

        return str_replace($newPatterns, $newReplacements, $str1);
    }

    public static function normalize($str) {
        return \Normalizer::normalize(\Normalizer::normalize(\Normalizer::normalize(\Normalizer::normalize($str, \Normalizer::NFD), \Normalizer::NFKD), \Normalizer::NFC), \Normalizer::NFKC);
    }

    public static function createAlias($str, $separator = '-') {

        $str2 = self::cleanText($str);
        $endstring = trim(preg_replace(['/[^A-Za-z0-9\-]/', '/\-+/'], [$separator, $separator], $str2), $separator);

        return strtolower($endstring);
    }

    /**
     * @desc Get file extension
     * @param type $file_name
     * @return type
     */
    public static function get_file_extension($file_name) {
        return substr(strrchr($file_name, '.'), 1);
    }

    /**
     * Extract numbers from a string
     * @param type $str
     * @return type
     */
    public static function get_numerics($str) {
        preg_match("/\d+/", $str, $matches);
        //preg_match_all('!\d+!', $str, $matches);
        return $matches[0];
    }

    /**
     * @desc turns line breaks in forms into HTML <br> <br/> or <p></p> tags
     * @param type $str
     * @param type $line_breaks
     * @param type $xml
     * @return type
     */
    public static function nl2p($str, $line_breaks = true, $xml = true) {
        // remove special characters
        $str = self::cleanText($str);
        // remove existing HTML formatting to avoid double tags
        $str = str_replace(array('<p>', '</p>', '<br>', '<br/>'), '', $str);

        // convert single line breaks into <br> or <br/> tags
        // convert couple lines breaks into <p>
        if ($line_breaks == true) {
            $str = '<p>' . preg_replace(array("/\r/", "/\n{2,}/", "/\n/"), array('', '</p><p>', '<br' . ($xml == true ? '/' : '') . '>'), $str) . '</p>';
        } else {
            $str = '<p>' . preg_replace("/\n/", "</p>\n<p>", trim($str)) . '</p>';
        }
        // xu lu van van co dang LI (can test lai ky)
        // $str = preg_replace(array("/\-\s/", "/\*\s/", "/\•\s/"), array('<br />* ', '<br />* ', '<br />* '), $str);
        return $str;
    }

    /**
     * @desc words limitation
     * 
     * @param type $str
     * @param type $limit
     * @param type $strip_tags
     * @param type $end_char
     * @return type
     */
    public static function wordLimit($_str, $limit = 100, $strip_tags = true, $end_char = ' ...') {
        // remove special characters
//        $str = self::cleanText($_str);
        if (trim($_str) == ''):
            return $_str;
        endif;
        $str = html_entity_decode($_str);

        if ($strip_tags):
            $str = trim(preg_replace('#<[^>]+>#', ' ', $str));
        endif;
        $words = explode(' ', $str);
        $_words = array_filter($words);
        $string = '';
        if (count($_words) > $limit):
            $i = 0;
            foreach ($words as $word) :
                if ($i < $limit) :
                    $string .= $word . ' ';
                    $i++;
                else:
                    break;
                endif;
            endforeach;
            $string .= $end_char;
        else :
            $string = $str;
        endif;

        return rtrim($string);
    }

    /**
     * @desc characters limitation
     * 
     * @param type $str
     * @param type $limit
     * @param type $strip_tags
     * @param type $end_char
     * @param type $enc
     * @return type
     */
    public static function characterLimit($str, $limit = 150, $strip_tags = true, $end_char = ' &#8230;', $enc = 'utf-8') {
        $str = self::cleanText($str);
        if (trim($str) == '') {
            return $str;
        }

        if ($strip_tags) {
            $str = strip_tags($str);
        }

        if (strlen($str) > $limit) {
            if (function_exists("mb_substr")) {
                $str = mb_substr($str, 0, $limit, $enc);
            } else {
                $str = substr($str, 0, $limit);
            }
            return rtrim($str) . $end_char;
        } else {
            return $str;
        }
    }

    /**
     * @desc redomize a string with default lenght is 8 unit
     * 
     * @param type $length
     * @param type $possible
     * @return type
     */
    public static function random($length = 8, $possible = "0123456789abcdefghijklmnopqrstvwxyzABCDEFGHIJKLMNOPQRSXTUVYW") {
        // start with a blank string
        $string = "";

        // set up a counter
        $i = 0;

        // add random characters to $string until $length is reached
        while ($i < $length) {

            // pick a random character from the possible ones
            $char = substr($possible, mt_rand(0, strlen($possible) - 1), 1);

            // we don't want this character if it's already in the string
            if (!strstr($string, $char)) {
                $string .= $char;
                $i++;
            }
        }

        // done!
        return $string;
    }

    /**
     * 
     * @param type $length
     * @return type
     */
    public static function UUID($length = 8) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }
    function shortId(int $length = 8): string
{
    return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(16))), 0, $length);
}

    public static function UUIDNumber($length = 6) {
        $chars = "0123456789";
        $password = substr(str_shuffle($chars), 0, $length);
        return $password;
    }

    /**
     * 
     * @param type $length
     * @return type
     */
    public static function generatePassword($length = 8) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return password_hash(substr(str_shuffle($chars), 0, $length), PASSWORD_DEFAULT, array('cost' => 12));
    }

    /**
     * @desc decode
     * 
     * @param type $dec
     * @return type
     */
    public static function unichr($dec) {
        if ($dec < 128) {
            $utf = chr($dec);
        } else if ($dec < 2048) {
            $utf = chr(192 + (($dec - ($dec % 64)) / 64));
            $utf .= chr(128 + ($dec % 64));
        } else {
            $utf = chr(224 + (($dec - ($dec % 4096)) / 4096));
            $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
            $utf .= chr(128 + ($dec % 64));
        }
        return $utf;
    }

    /**
     * Remove � character
     * http://stackoverflow.com/questions/1401317/remove-non-utf8-characters-from-string
     * 
     * @param type $string
     * @return type
     */
    public static function remove_special_character($str) {
        $str = self::cleanText($str);
        $str = str_replace('&#8230;', '', $str);
        $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;
        return preg_replace($regex, '$1', $str);
    }

    function fix_latin1_mangled_with_utf8($str) {
        $str = self::remove_special_character($str);
        return preg_replace_callback('#[\\xA1-\\xFF](?![\\x80-\\xBF]{2,})#', 'utf8_encode_callback', $str);
    }

    function utf8_encode_callback($m) {
        return utf8_encode($m[0]);
    }

    /*
     * Convert to safe characters
     * 
     * @desc : remove the accent not '-'
     */

    public static function vt_safe_vietnamese_meta($str, $lower = true, $vietnamese = true, $special = false, $accent = false) {
        $str = $lower ? strtolower($str) : $str;
        // Remove Vietnamese accent or not
        $str = $accent ? self::vt_remove_vietnamese_accent($str) : $str;

        // Replace special symbols with spaces or not
        $str = $special ? self::vt_remove_special_characters($str) : $str;

        // Replace Vietnamese characters or not
        $str = $vietnamese ? self::vt_replace_vietnamese_characters($str) : $str;

        return $str;
    }

    /*
     * Remove 5 Vietnamese accent / tone marks if has Combining Unicode characters
     * Tone marks: Grave (`), Acute(�), Tilde (~), Hook Above (?), Dot Bellow(.)
     */

    public static function vt_remove_vietnamese_accent($str) {

        $str = preg_replace("/[\x{0300}\x{0301}\x{0303}\x{0309}\x{0323}]/u", "", $str);

        return $str;
    }

    /*
     * Remove or Replace special symbols with spaces
     */

    public static function vt_remove_special_characters($str, $remove = true) {

        // Remove or replace with spaces
        $substitute = $remove ? "" : " ";

        $str = preg_replace("/[\x{0021}-\x{002D}\x{002F}\x{003A}-\x{0040}\x{005B}-\x{0060}\x{007B}-\x{007E}\x{00A1}-\x{00BF}]/u", $substitute, $str);

        return $str;
    }

    /*
     * Replace Vietnamese vowels with diacritic and Letter D with Stroke with corresponding English characters
     */

    public static function vt_replace_vietnamese_characters($str) {

        $str = preg_replace("/[\x{00C0}-\x{00C3}\x{00E0}-\x{00E3}\x{0102}\x{0103}\x{1EA0}-\x{1EB7}]/u", "a", $str);
        $str = preg_replace("/[\x{00C8}-\x{00CA}\x{00E8}-\x{00EA}\x{1EB8}-\x{1EC7}]/u", "e", $str);
        $str = preg_replace("/[\x{00CC}\x{00CD}\x{00EC}\x{00ED}\x{0128}\x{0129}\x{1EC8}-\x{1ECB}]/u", "i", $str);
        $str = preg_replace("/[\x{00D2}-\x{00D5}\x{00F2}-\x{00F5}\x{01A0}\x{01A1}\x{1ECC}-\x{1EE3}]/u", "o", $str);
        $str = preg_replace("/[\x{00D9}-\x{00DA}\x{00F9}-\x{00FA}\x{0168}\x{0169}\x{01AF}\x{01B0}\x{1EE4}-\x{1EF1}]/u", "u", $str);
        $str = preg_replace("/[\x{00DD}\x{00FD}\x{1EF2}-\x{1EF9}]/u", "y", $str);
        $str = preg_replace("/[\x{0110}\x{0111}]/u", "d", $str);

        return $str;
    }

    /**
     * Strips the last extension off of a file name
     *
     * @param   string  $file  The file name
     *
     * @return  string  The file name without the extension
     *
     * @since   1.0
     */
    public static function stripExt($file) {
        return preg_replace('#\.[^.]*$#', '', $file);
    }

    /**
     * Makes the file name safe to use
     *
     * @param   string  $file        The name of the file [not full path]
     * @param   array   $stripChars  Array of regex (by default will remove any leading periods)
     *
     * @return  string  The sanitised string
     *
     * @since   1.0
     */
    public static function makeSafe($file, array $stripChars = array('#^\.#')) {
        $file = self::createAlias($file);
        $regex = array_merge(array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#'), $stripChars);

        $file = preg_replace($regex, '', $file);

        // Remove any trailing dots, as those aren't ever valid file names.
        $file = rtrim($file, '.');

        return $file;
    }

    /**
     * Splits an URL into its parts
     *
     * @param string $url  The URL
     * @return array       An array containig the parts of the URL
     *
     *                     The keys are:
     *
     *                     "protocol" (z.B. "http://")
     *                     "host"     (z.B. "www.bla.de")
     *                     "path"     (z.B. "/test/palimm/")
     *                     "file"     (z.B. "index.htm")
     *                     "domain"   (z.B. "foo.com")
     *                     "port"     (z.B. 80)
     *                     "auth_username"
     *                     "auth_password"
     */
    public static function splitURL($url) {
        // Protokoll der URL hinzuf�gen (da ansonsten parse_url nicht klarkommt)
        if (!preg_match("#^[a-z]+://# i", $url))
            $url = "http://" . $url;

        $parts = @parse_url($url);

        if (!isset($parts)):
            return null;
        endif;

        $protocol = $parts["scheme"] . "://";
        $host = (isset($parts["host"]) ? $parts["host"] : "");
        $path = (isset($parts["path"]) ? $parts["path"] : "");
        $query = (isset($parts["query"]) ? "?" . $parts["query"] : "");
        $auth_username = (isset($parts["user"]) ? $parts["user"] : "");
        $auth_password = (isset($parts["pass"]) ? $parts["pass"] : "");
        $port = (isset($parts["port"]) ? $parts["port"] : "");

        // File
        preg_match("#^(.*/)([^/]*)$#", $path, $match); // Alles ab dem letzten "/"
        if (isset($match[0])) {
            $file = trim($match[2]);
            $path = trim($match[1]);
        } else {
            $file = "";
        }

        // Der Domainname aus dem Host
        // Host: www.foo.com -> Domain: foo.com
        $parts = @explode(".", $host);
        if (count($parts) <= 2) {
            $domain = $host;
        } else if (preg_match("#^[0-9]+$#", str_replace(".", "", $host))) { // IP
            $domain = $host;
        } else {
            $pos = strpos($host, ".");
            $domain = substr($host, $pos + 1);
        }

        // DEFAULT VALUES f�r protocol, path, port etc. (wenn noch nicht gesetzt)
        // Wenn Protokoll leer -> Protokoll ist "http://"
        if ($protocol == ""):
            $protocol = "http://";
        endif;

        // Wenn Port leer -> Port setzen auf 80 or 443
        // (abh�ngig vom Protokoll)
        if ($port == "") {
            if (strtolower($protocol) == "http://")
                $port = 80;
            if (strtolower($protocol) == "https://")
                $port = 443;
        }

        // Wenn Pfad leet -> Pfad ist "/"
        if ($path == ""):
            $path = "/";
        endif;

        // Rockgabe-Array
        $url_parts["protocol"] = $protocol;
        $url_parts["host"] = $host;
        $url_parts["path"] = $path;
        $url_parts["file"] = $file;
        $url_parts["query"] = $query;
        $url_parts["domain"] = $domain;
        $url_parts["port"] = $port;

        $url_parts["auth_username"] = $auth_username;
        $url_parts["auth_password"] = $auth_password;

        return $url_parts;
    }

    /**
     * Builds an URL from it's single parts.
     *
     * @param array $url_parts Array conatining the URL-parts.
     *                         The keys should be:
     *
     *                         "protocol" (z.B. "http://") OPTIONAL
     *                         "host"     (z.B. "www.bla.de")
     *                         "path"     (z.B. "/test/palimm/") OPTIONAL
     *                         "file"     (z.B. "index.htm") OPTIONAL
     *                         "port"     (z.B. 80) OPTIONAL
     *                         "auth_username" OPTIONAL
     *                         "auth_password" OPTIONAL
     * @param bool $normalize   If TRUE, the URL will be returned normalized.
     *                          (I.e. http://www.foo.com/path/ insetad of http://www.foo.com:80/path/)
     * @return string The URL
     *                         
     */
    public static function buildURLFromParts($url_parts, $normalize = false) {
        // Host has to be set aat least
        if (!isset($url_parts["host"])) {
            throw new Exception("Cannot generate URL, host not specified!");
        }

        if (!isset($url_parts["protocol"]) || $url_parts["protocol"] == "")
            $url_parts["protocol"] = "http://";
        if (!isset($url_parts["port"]))
            $url_parts["port"] = 80;
        if (!isset($url_parts["path"]))
            $url_parts["path"] = "";
        if (!isset($url_parts["file"]))
            $url_parts["file"] = "";
        if (!isset($url_parts["query"]))
            $url_parts["query"] = "";
        if (!isset($url_parts["auth_username"]))
            $url_parts["auth_username"] = "";
        if (!isset($url_parts["auth_password"]))
            $url_parts["auth_password"] = "";

        // Autentication-part
        $auth_part = "";
        if ($url_parts["auth_username"] != "" && $url_parts["auth_password"] != "") {
            $auth_part = $url_parts["auth_username"] . ":" . $url_parts["auth_password"] . "@";
        }

        // Port-part
        $port_part = ":" . $url_parts["port"];

        // Normalize
        if ($normalize == true) {
            if ($url_parts["protocol"] == "http://" && $url_parts["port"] == 80 ||
                    $url_parts["protocol"] == "https://" && $url_parts["port"] == 443) {
                $port_part = "";
            }

            // Don't add port to links other than "http://" or "https://"
            if ($url_parts["protocol"] != "http://" && $url_parts["protocol"] != "https://") {
                $port_part = "";
            }
        }

        // If path is just a "/" -> remove it ("www.site.com/" -> "www.site.com")
        if ($url_parts["path"] == "/" && $url_parts["file"] == "" && $url_parts["query"] == "")
            $url_parts["path"] = "";

        // Put together the url
        $url = $url_parts["protocol"] . $auth_part . $url_parts["host"] . $port_part . $url_parts["path"] . $url_parts["file"] . $url_parts["query"];

        return $url;
    }

    /**
     * Normalizes an URL
     *
     * I.e. converts http://www.foo.com:80/path/ to http://www.foo.com/path/
     *
     * @param string $url
     * @return string OR NULL on failure
     */
    public static function normalizeURL($url) {
        $url_parts = self::splitURL($url);

        if ($url_parts == null)
            return null;

        $url_normalized = self::buildURLFromParts($url_parts, true);
        return $url_normalized;
    }

    /**
     * Gets the HTTP-statuscode from a given response-header.
     *
     * @param string $header  The response-header
     * @return int            The status-code or NULL if no status-code was found.
     */
    public static function getHTTPStatusCode($header) {
        $first_line = strtok($header, "\n");

        preg_match("# [0-9]{3}#", $first_line, $match);

        if (isset($match[0]))
            return (int) trim($match[0]);
        else
            return null;
    }

    /**
     * Returns the redirect-URL from the given HTML-header
     *
     * @return string The redirect-URL or NULL if not found.
     */
    public static function getRedirectURLFromHeader(&$header) {
        // Get redirect-link from header
        preg_match("/((?i)location:|content-location:)(.{0,})[\n]/", $header, $match);

        if (isset($match[2])) {
            $redirect = trim($match[2]);
            return $redirect;
        } else
            return null;
    }

    /**
     * Gets the value of an header-directive from the given HTTP-header.
     *
     * Example:
     * <code>Utils::getHeaderValue($header, "content-type");</code>
     *
     * @param string $header    The HTTP-header
     * @param string $directive The header-directive
     *
     * @return string The value of the given directive found in the header.
     *                Or NULL if not found.
     */
    public static function getHeaderValue($header, $directive) {
        preg_match("#[\r\n]" . $directive . ":(.*)[\r\n\;]# Ui", $header, $match);

        if (isset($match[1]) && trim($match[1]) != "") {
            return trim($match[1]);
        } else
            return null;
    }

    /**
     * Returns all cookies from the give response-header.
     *
     * @param string $header      The response-header
     * @return array Numeric array containing all cookies as array.
     */
    public static function getCookiesFromHeader($header) {
        $cookies = [];

        $hits = preg_match_all("#[\r\n]set-cookie:(.*)[\r\n]# Ui", $header, $matches);

        if ($hits && $hits != 0) {
            for ($x = 0; $x < count($matches[1]); $x++) {
                $cookies[] = $matches[1][$x];
            }
        }

        return $cookies;
    }

    /**
     * Returns the normalized root-URL of the given URL
     *
     * @param string $url The URL, e.g. "www.foo.con/something/index.html"
     * @return string The root-URL, e.g. "http://www.foo.com"
     */
    public static function getRootUrl($url) {
        $url_parts = self::splitURL($url);
        $root_url = $url_parts["protocol"] . $url_parts["host"] . ":" . $url_parts["port"];

        return self::normalizeURL($root_url);
    }

    /**
     * Serializes data (objects, arrays etc.) and writes it to the given file.
     */
    public static function serializeToFile($target_file, $data) {
        $serialized_data = serialize($data);
        file_put_contents($target_file, $serialized_data);
    }

    /**
     * Returns deserialized data that is stored in a file.
     *
     * @param string $file The file containing the serialized data
     *
     * @return mixed The data or NULL if the file doesn't exist
     */
    public static function deserializeFromFile($file) {
        if (file_exists($file)) {
            $serialized_data = file_get_contents($file);
            return unserialize($serialized_data);
        } else
            return null;
    }

    /**
     * Sorts a twodimensiolnal array.
     */
    public static function sort2dArray(&$array, $sort_args) {
        $args = func_get_args();

        // F�r jedes zu sortierende Feld ein eigenes Array bilden
        @reset($array);
        while (list($field) = @each($array)) {
            for ($x = 1; $x < count($args); $x++) {
                // Ist das Argument ein String, sprich ein Sortier-Feld?
                if (is_string($args[$x])) {
                    $value = $array[$field][$args[$x]];

                    ${$args[$x]}[] = $value;
                }
            }
        }

        // Argumente for array_multisort bilden
        for ($x = 1; $x < count($args); $x++) {
            if (is_string($args[$x])) {
                // Argument ist ein TMP-Array
                $params[] = &${$args[$x]};
            } else {
                // Argument ist ein Sort-Flag so wie z.B. "SORT_ASC"
                $params[] = &$args[$x];
            }
        }

        // Der letzte Parameter ist immer das zu sortierende Array (Referenz!)
        $params[] = &$array;

        // Array sortieren
        call_user_func_array("array_multisort", $params);

        @reset($array);
    }

    /**
     * Determinates the systems temporary-directory.
     *
     * @return string
     */
    public static function getSystemTempDir() {
        $dir = sys_get_temp_dir() . "/";
        return $dir;
    }

    /**
     * Checks wether the given string is an UTF8-encoded string.
     *
     * Taken from http://www.php.net/manual/de/function.mb-detect-encoding.php
     * (comment from "prgss at bk dot ru")
     * 
     * @param string $string The string
     * @return bool TRUE if the string is UTF-8 encoded.
     */
    public static function isUTF8String($string) {
        $sample = @iconv('utf-8', 'utf-8', $string);

        if (md5($sample) == md5($string))
            return true;
        else
            return false;
    }

    /**
     * Checks whether the given string is a valid, urlencoded URL (by RFC)
     * 
     * @param string $string The string
     * @return bool TRUE if the string is a valid url-string.
     */
    public static function isValidUrlString($string) {
        if (preg_match("#^[a-z0-9/.&=?%-_.!~*'()]+$# i", $string))
            return true;
        else
            return false;
    }

    /**
     * Decodes GZIP-encoded HTTP-data
     */
    public static function decodeGZipContent($content) {
        return gzinflate(substr($content, 10, -8));
    }

    /**
     * Checks whether the given data is gzip-encoded
     */
    public static function isGzipEncoded($content) {
        if (substr($content, 0, 3) == "\x1f\x8b\x08") {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $week
     * @param type $year
     * @return type
     */
    public static function getDateInWeek($week, $year) {
        $time = strtotime("1 January $year", time());
        $day = date('w', $time);
        $time += ((7 * $week) + 1 - $day) * 24 * 3600;
        $return[0] = date('d-m-Y', $time);
        $time += 6 * 24 * 3600;
        $return[1] = date('d-m-Y', $time);
        return $return;
    }

    /**
     * 
     * @param type $timestamp
     * @return string
     */
    public static function timer($timestamp, $lang = 'vi') {
        $now = new \DateTime();
        $etime = $now->getTimestamp() - $timestamp;
        $beforeText = ($lang == 'vi') ? 'trước' : 'ago';
        $aboutText = ''; //($lang == 'vi') ? 'khoảng' : 'about';
        if ($etime < 1) {
            return ($lang == 'vi') ? 'Vừa xong' : 'Just now';
        }
        $a = array(365 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            7 * 24 * 60 * 60 => 'week',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        );
        $a_plural = array(
            'year' => ($lang == 'vi') ? 'năm' : 'year',
            'month' => ($lang == 'vi') ? 'tháng' : 'month',
            'week' => ($lang == 'vi') ? 'tuần' : 'week',
            'day' => ($lang == 'vi') ? 'ngày' : 'day',
            'hour' => ($lang == 'vi') ? 'giờ' : 'hour',
            'minute' => ($lang == 'vi') ? 'phút' : 'minute',
            'second' => ($lang == 'vi') ? 'giây' : 'second'
        );
        $i = 0;
        $text = '';
        $_r = 1;
        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                $r = floor($d);
                return sprintf('%s %s %s %s', $aboutText, $r, $a_plural[$str], $beforeText);
//                if ($secs == 1):
//                    if ($_r):
//                        $r = floor(($etime - $_r) / $secs);
//                        if ($r):
//                            return $text . sprintf(' %s %s %s', $r, $a_plural[$str], $beforeText);
//                        else:
//                            return $text . sprintf(' %s', $beforeText);
//                        endif;
//                    else:
//                        return sprintf('%s %s %s %s', $aboutText, $r, $a_plural[$str], $beforeText);
//                    endif;
//
//                elseif ($i):
//                    $r = floor(($etime - $_r) / $secs);
//                    if ($r):
//                        return $text . sprintf(' %s %s %s', $r, $a_plural[$str], $beforeText);
//                    else:
//                        return $text . sprintf(' %s', $beforeText);
//                    endif;
//                elseif (!$i):
//                    $_r = floor($d) * $secs;
//                    $text = sprintf('%s %s %s', $aboutText, $r, $a_plural[$str]);
//                    $i++;
//                endif;
            }
        }
    }

    /**
     * 
     * @param type $host
     * @return type
     */
    public static function website($host) {
        // No need for cdn hosting. e.g: https://files.jobinvietnam.com
        if (preg_match("~^(?:f|ht)tps?://~i", $host)):
            // do sth ...
            return $host;
        else:
            return 'http://' . $host;
        endif;
    }

    /**
     * 
     * @return string
     */
    public static function get_server_ip() {

        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public function get_client_ip() {
        $ipaddress = '';
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

    public static function get_public_ip() {
        $externalContent = file_get_contents('http://checkip.dyndns.com/');
        preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $externalContent, $m);
        return $m[1];
    }

    public static function formatMoney($amount, $currency = 'VND', $locale = 'en-US') {
        $fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        return $fmt->formatCurrency($amount, $currency) . "<br>"; // outputs €12.345,12 
    }
    public static function numberFormat($number,$thousandsSeparator=',') {
        $number = (float) ($number);
        $decimalSeparator = ($thousandsSeparator==',')?'.':',';
        if ($number) :
            $split = explode($decimalSeparator, $number);

            $new_number = number_format($split[0], 0, $decimalSeparator, $thousandsSeparator);
            if (count($split) == 2) :
                $new_number .= $decimalSeparator . $split[1];
            endif;
            return $new_number;
        else :
            return 0;
        endif;
    }

    public static function numberOnly($string) {
        return  preg_replace('/\D/', '', $string);
    }

    public static function getBrowser($u_agent='') {
//        $u_agent = 'Mozilla/5.0 (compatible; AhrefsBot/7.0; +http://ahrefs.com/robot/)';
//        $u_agent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_1_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Zalo iOS/638 ZaloTheme/light ZaloLanguage/vn';
        if(!$u_agent):
            $u_agent = $_SERVER['HTTP_USER_AGENT'];
        endif;
//        
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version = "";

        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/iphone os/i', $u_agent)) {
            $platform = 'ios';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }
        $ub = '';
        // Next get the name of the useragent yes seperately and for good reason
        if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        } elseif (preg_match('/Firefox/i', $u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif (preg_match('/Chrome/i', $u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif (preg_match('/Safari/i', $u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif (preg_match('/Opera/i', $u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif (preg_match('/Netscape/i', $u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        } elseif (preg_match('/facebookexternalhit/i', $u_agent) || preg_match('/facebot/i', $u_agent)) {
            $bname = 'Facebook Bot';
            $ub = "facebookexternalhit";
        } elseif (preg_match('/googlebot/i', $u_agent) || preg_match('/adsbot\-google/i', $u_agent) || preg_match('/mediapartners\-google/i', $u_agent)) {
            $bname = 'Googlebot';
            $ub = "Googlebot";
        } elseif (preg_match('/bitsight/i', $u_agent)) {
            $bname = 'BitSightBot';
            $ub = "BitSightBot";
        } elseif (preg_match('/ahrefsbot/i', $u_agent)) {
            $bname = 'AhrefsBot';
            $ub = "AhrefsBot";
        } elseif (preg_match('/baiduspider/i', $u_agent)) {
            $bname = 'Baidu';
            $ub = "Baiduspider-render";
        } elseif (preg_match('/zalo/i', $u_agent)) {
            $bname = 'Zalo';
            $ub = "ZaloTheme";
        } elseif (preg_match('/FBAN/i', $u_agent)) {
            $bname = 'Facebook In-App';
            $ub = "FBAV";
        }

        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
                ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
                $version = $matches['version'][0];
            } else {
                $version = $matches['version'][1] ?? '';
            }
        } else {
            $version = $matches['version'][0];
        }

        // check if we have a number
        if ($version == null || $version == "") {
            $version = "?";
        }

        return array(
            'userAgent' => $u_agent,
            'name' => $bname,
            'version' => $version,
            'platform' => $platform,
            'pattern' => $pattern
        );
    }

    public function getBaseUrl($path='') {
        $uri = $this->getRequest()->getUri();
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        return sprintf('%s://%s', $scheme, $host).$path;
    }

    public function dateClean($dateString = '') {
        $replacestr = preg_replace("/(\!|\@|\#|\%|\^|\&|\*|\_|\+|\=|\<|\>|\?|\/|\,|\.|\;|\÷\:|\'|\"|\“|\”|\~|\`|\{|\}|\|)/", "-", $dateString);
        return new \DateTime($replacestr);
    }

    public static function hash($num, $len = 8) {

        $ceil = bcpow(62, $len);
        $primes = array_keys(self::$golden_primes);
        $prime = $primes[$len];
        $dec = bcmod(bcmul($num, $prime), $ceil);
        $hash = self::base62($dec);
        return str_pad($hash, $len, "0", STR_PAD_LEFT);
    }

    public function unhash($hash) {

        $len = strlen($hash);
        $ceil = bcpow(62, $len);
        $mmiprimes = array_values(self::$golden_primes);
        $mmi = $mmiprimes[$len];
        $num = self::unbase62($hash);
        $dec = bcmod(bcmul($num, $mmi), $ceil);
        return $dec;
    }

    public static function unbase62($key) {
        $int = 0;
        foreach (str_split(strrev($key)) as $i => $char) {
            $dec = array_search(ord($char), self::$chars62);
            $int = bcadd(bcmul($dec, bcpow(62, $i)), $int);
        }
        return $int;
    }

    public static function base62($int) {
        $key = "";
        while (bccomp($int, 0) > 0) {
            $mod = bcmod($int, 62);
            $key .= chr(self::$chars62[$mod]);
            $int = bcdiv($int, 62);
        }
        return strrev($key);
    }

    public function deepMerge(array $array1, array $array2): array {

        foreach ($array2 as $key => $value) {
            // Nếu key tồn tại trong cả hai mảng và cả hai đều là mảng, merge tiếp
            if (isset($array1[$key]) && is_array($array1[$key]) && is_array($value)) {
                $array1[$key] = self::deepMerge($array1[$key], $value);
            } else {
                // Ngược lại, ghi đè giá trị từ $array2
                $array1[$key] = $value;
            }
        }
        return $array1;
    }

    public function getTopkeywords($text = '', $maxWordEachGram = 5) {
        $_plainText =$this->normalize( html_entity_decode(strip_tags($text)));
        
        $plainText = $this->cleanText($_plainText);
        
        $ngrams = [];
        $matches = [];
        preg_match_all('/\p{L}+/u', strtolower($plainText), $matches);
        $words = $matches[0]??[]; // Chuyển về chữ thường và tách từ
        for ($i = 0; $i <= count($words) - 2; $i++):
            $ngram = implode(' ', array_slice($words, $i, 2));
            $ngrams[] = $ngram;
        endfor;
        $ngramCount = array_count_values($ngrams);
        arsort($ngramCount);
        $topWords = array_slice($ngramCount, 0, $maxWordEachGram, true);
        $filteredWords = array_filter($words, fn($word) => strlen($word) >= 3);
        $wordCount = array_count_values($filteredWords); // Đếm số lần xuất hiện của từng từ

        arsort($wordCount); // Sắp xếp theo thứ tự giảm dần

        return array_merge($topWords, array_slice($wordCount, 0, $maxWordEachGram, true)); // Lấy 5 từ phổ biến nhất
    }
    public function genFirstLetter(string $str='') {
        $_str = preg_replace("/[^A-Za-z]/", '', $str);
        return strtoupper(mb_substr($_str, 0, 1));
    }
    public function dateFormat(\DateTime $date, string $format = '', string $locale = '') {
       
        if(!$locale):
            $locale = $this->translator->getTranslator()->getLocale();
        endif;
            
//        en_US
        $formatter = new \IntlDateFormatter(
                $locale,
                \IntlDateFormatter::FULL, // Kiểu ngày đầy đủ (có thể thay đổi)
                \IntlDateFormatter::NONE, // Không hiển thị giờ
                'Asia/Ho_Chi_Minh', // Múi giờ (có thể thay đổi)
                \IntlDateFormatter::GREGORIAN,
                $format // Định dạng tùy chỉnh: Thứ, ngày Tháng
//                "EEEE, d MMMM" // Định dạng tùy chỉnh: Thứ, ngày Tháng
        );
        return $formatter->format($date);
    }
    public function embedOptionStr($str = '') {

        $_option = ($str) ? trim($str) : '';
        $_options = ($_option) ? explode(PHP_EOL, $_option) : [];
        $options = [];
        if ($_options) :
            foreach ($_options as $__option) :
                $__options = explode(':', $__option, 2);
                $opt0 = trim($__options[0]);
                $opt1 = (isset($__options[1])) ? trim($__options[1]) : $opt0;
                $options[$opt0] = $opt1;
            endforeach;
        endif;
        return $options;
    }
    public function convertTextareaToArray($textareaValue) {
        if (empty($textareaValue) || $textareaValue === null || ($textareaValue && !trim($textareaValue))) {
            return array();
        }
        $textareaValue = str_replace("\r\n", "\n", $textareaValue);
        $lines = explode("\n", $textareaValue);
        $finalArray = array();
        foreach ($lines as $line) {
            $array = explode(",", $line);
            $finalArray[] = $array;
        }
        return $finalArray;
    }
}
