<?php

namespace Formwork\Admin\Utils;

use LogicException;

class LanguageCodes
{
    protected static $codes = array(
        'af' => array('name' => 'Afrikaans', 'native' => 'Afrikaans', 'continents' => array('AF')),
        'am' => array('name' => 'Amharic', 'native' => 'አማርኛ', 'continents' => array('AF')),
        'ar' => array('name' => 'Arabic', 'native' => 'العربية', 'rtl' => true, 'continents' => array('AF', 'AS')),
        'az' => array('name' => 'Azerbaijani', 'native' => 'Azərbaycanca / آذربايجان', 'continents' => array('AS')),
        'be' => array('name' => 'Belarusian', 'native' => 'Беларуская', 'continents' => array('EU')),
        'bg' => array('name' => 'Bulgarian', 'native' => 'Български', 'continents' => array('EU')),
        'bn' => array('name' => 'Bengali', 'native' => 'বাংলা', 'continents' => array('AS')),
        'bs' => array('name' => 'Bosnian', 'native' => 'Bosanski', 'continents' => array('EU')),
        'ca' => array('name' => 'Catalan', 'native' => 'Català', 'continents' => array('EU')),
        'cs' => array('name' => 'Czech', 'native' => 'Česky', 'continents' => array('EU')),
        'da' => array('name' => 'Danish', 'native' => 'Dansk', 'continents' => array('EU')),
        'de' => array('name' => 'German', 'native' => 'Deutsch', 'continents' => array('EU')),
        'el' => array('name' => 'Greek', 'native' => 'Ελληνικά', 'continents' => array('EU')),
        'en' => array('name' => 'English', 'native' => 'English', 'continents' => array('AF', 'AS', 'EU', 'NA', 'OC', 'SA')),
        'es' => array('name' => 'Spanish', 'native' => 'Español', 'continents' => array('AF', 'EU', 'NA', 'OC', 'SA')),
        'et' => array('name' => 'Estonian', 'native' => 'Eesti', 'continents' => array('EU')),
        'eu' => array('name' => 'Basque', 'native' => 'Euskara', 'continents' => array('EU')),
        'fa' => array('name' => 'Persian', 'native' => 'فارسی', 'rtl' => true, 'continents' => array('AS')),
        'fi' => array('name' => 'Finnish', 'native' => 'Suomi', 'continents' => array('EU')),
        'fr' => array('name' => 'French', 'native' => 'Français', 'continents' => array('AF', 'AS', 'EU', 'NA', 'OC', 'SA')),
        'ga' => array('name' => 'Irish', 'native' => 'Gaeilge', 'continents' => array('EU')),
        'gl' => array('name' => 'Galician', 'native' => 'Galego', 'continents' => array('EU')),
        'hi' => array('name' => 'Hindi', 'native' => 'हिन्दी', 'continents' => array('AS', 'OC')),
        'hr' => array('name' => 'Croatian', 'native' => 'Hrvatski', 'continents' => array('EU')),
        'hu' => array('name' => 'Hungarian', 'native' => 'Magyar', 'continents' => array('EU')),
        'hy' => array('name' => 'Armenian', 'native' => 'Հայերեն', 'continents' => array('AS', 'EU')),
        'id' => array('name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'continents' => array('AS')),
        'is' => array('name' => 'Icelandic', 'native' => 'Íslenska', 'continents' => array('EU')),
        'it' => array('name' => 'Italian', 'native' => 'Italiano', 'continents' => array('EU')),
        'ja' => array('name' => 'Japanese', 'native' => '日本語', 'continents' => array('AS')),
        'ka' => array('name' => 'Georgian', 'native' => 'ქართული', 'continents' => array('AS')),
        'kk' => array('name' => 'Kazakh', 'native' => 'Қазақша', 'continents' => array('AS')),
        'km' => array('name' => 'Cambodian', 'native' => 'ភាសាខ្មែរ', 'continents' => array('AS')),
        'ko' => array('name' => 'Korean', 'native' => '한국어', 'continents' => array('AS')),
        'ku' => array('name' => 'Kurdish', 'native' => 'Kurdî / كوردی', 'rtl' => true, 'continents' => array('AS')),
        'ky' => array('name' => 'Kirghiz', 'native' => 'Kırgızca / Кыргызча', 'continents' => array('AS')),
        'lb' => array('name' => 'Luxembourgish', 'native' => 'Lëtzebuergesch', 'continents' => array('EU')),
        'lo' => array('name' => 'Laotian', 'native' => 'ລາວ / Pha xa lao', 'continents' => array('AS')),
        'lt' => array('name' => 'Lithuanian', 'native' => 'Lietuvių', 'continents' => array('EU')),
        'lv' => array('name' => 'Latvian', 'native' => 'Latviešu', 'continents' => array('EU')),
        'mg' => array('name' => 'Malagasy', 'native' => 'Malagasy', 'continents' => array('AF')),
        'mi' => array('name' => 'Maori', 'native' => 'Māori', 'continents' => array('OC')),
        'mk' => array('name' => 'Macedonian', 'native' => 'Македонски', 'continents' => array('EU')),
        'mn' => array('name' => 'Mongolian', 'native' => 'Монгол', 'continents' => array('AS')),
        'ms' => array('name' => 'Malay', 'native' => 'Bahasa Melayu', 'continents' => array('AS')),
        'mt' => array('name' => 'Maltese', 'native' => 'bil-Malti', 'continents' => array('EU')),
        'my' => array('name' => 'Burmese', 'native' => 'Myanmasa', 'continents' => array('AS')),
        'ne' => array('name' => 'Nepali', 'native' => 'नेपाली', 'continents' => array('AS')),
        'nl' => array('name' => 'Dutch', 'native' => 'Nederlands', 'continents' => array('EU', 'NA', 'SA')),
        'no' => array('name' => 'Norwegian', 'native' => 'Norsk (bokmål / riksmål)', 'continents' => array('EU')),
        'ny' => array('name' => 'Chichewa', 'native' => 'Chi-Chewa', 'continents' => array('AF')),
        'pa' => array('name' => 'Panjabi / Punjabi', 'native' => 'ਪੰਜਾਬੀ / पंजाबी / پنجابي', 'continents' => array('AS')),
        'pl' => array('name' => 'Polish', 'native' => 'Polski', 'continents' => array('EU')),
        'ps' => array('name' => 'Pashto', 'native' => 'پښتو', 'rtl' => true, 'continents' => array('AS')),
        'pt' => array('name' => 'Portuguese', 'native' => 'Português', 'continents' => array('AF', 'AS', 'EU', 'OC', 'SA')),
        'ro' => array('name' => 'Romanian', 'native' => 'Română', 'continents' => array('EU')),
        'ru' => array('name' => 'Russian', 'native' => 'Русский', 'continents' => array('AS', 'EU')),
        'si' => array('name' => 'Sinhalese', 'native' => 'සිංහල', 'continents' => array('AS')),
        'sk' => array('name' => 'Slovak', 'native' => 'Slovenčina', 'continents' => array('EU')),
        'sl' => array('name' => 'Slovenian', 'native' => 'Slovenščina', 'continents' => array('EU')),
        'sm' => array('name' => 'Samoan', 'native' => 'Gagana Samoa', 'continents' => array('OC')),
        'sn' => array('name' => 'Shona', 'native' => 'chiShona', 'continents' => array('AF')),
        'so' => array('name' => 'Somalia', 'native' => 'Soomaaliga', 'continents' => array('AF')),
        'sq' => array('name' => 'Albanian', 'native' => 'Shqip', 'continents' => array('EU')),
        'sr' => array('name' => 'Serbian', 'native' => 'Српски', 'continents' => array('EU')),
        'st' => array('name' => 'Southern Sotho', 'native' => 'Sesotho', 'continents' => array('AF')),
        'sv' => array('name' => 'Swedish', 'native' => 'Svenska', 'continents' => array('EU')),
        'sw' => array('name' => 'Swahili', 'native' => 'Kiswahili', 'continents' => array('AF')),
        'ta' => array('name' => 'Tamil', 'native' => 'தமிழ்', 'continents' => array('AS')),
        'tg' => array('name' => 'Tajik', 'native' => 'Тоҷикӣ', 'continents' => array('AS')),
        'th' => array('name' => 'Thai', 'native' => 'ไทย / Phasa Thai', 'continents' => array('AS')),
        'tr' => array('name' => 'Turkish', 'native' => 'Türkçe', 'continents' => array('AS', 'EU')),
        'uk' => array('name' => 'Ukrainian', 'native' => 'Українська', 'continents' => array('EU')),
        'ur' => array('name' => 'Urdu', 'native' => 'اردو', 'rtl' => true, 'continents' => array('AS', 'OC')),
        'uz' => array('name' => 'Uzbek', 'native' => 'Ўзбек', 'continents' => array('AS')),
        'vi' => array('name' => 'Vietnamese', 'native' => 'Tiếng Việt', 'continents' => array('AS')),
        'xh' => array('name' => 'Xhosa', 'native' => 'isiXhosa', 'continents' => array('AF')),
        'zh' => array('name' => 'Chinese', 'native' => '中文', 'continents' => array('AS')),
        'zu' => array('name' => 'Zulu', 'native' => 'isiZulu', 'continents' => array('AF')),
    );

    public static function names($continent = null)
    {
        $result = array();
        foreach (static::$codes as $code => $data) {
            if (!is_null($continent) && count(array_intersect((array) $continent, $data['continents'])) < 1) {
                continue;
            }
            $result[$code] = $data['native'] . ' (' . $code . ')';
        }
        return $result;
    }

    public static function codeToName($code)
    {
        if (!isset(static::$codes[$code])) {
            throw new LogicException('Invalid language code "' . $code . '"');
        }
        return static::$codes[$code]['name'];
    }

    public static function codeToNativeName($code)
    {
        if (!isset(static::$codes[$code])) {
            throw new LogicException('Invalid language code "' . $code . '"');
        }
        return static::$codes[$code]['native'];
    }
}
