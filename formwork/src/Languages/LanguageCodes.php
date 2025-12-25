<?php

namespace Formwork\Languages;

use Formwork\Traits\StaticClass;
use InvalidArgumentException;

final class LanguageCodes
{
    use StaticClass;

    /**
     * All available language codes
     *
     * @var array<string, array{name: string, native: string, rtl?: bool, continents: list<'AF'|'AS'|'EU'|'NA'|'OC'|'SA'>}>
     */
    private const array LANGUAGE_CODES = [
        'af' => ['name' => 'Afrikaans', 'native' => 'Afrikaans', 'continents' => ['AF']],
        'am' => ['name' => 'Amharic', 'native' => 'አማርኛ', 'continents' => ['AF']],
        'ar' => ['name' => 'Arabic', 'native' => 'العربية', 'rtl' => true, 'continents' => ['AF', 'AS']],
        'az' => ['name' => 'Azerbaijani', 'native' => 'Azərbaycanca / آذربايجان', 'continents' => ['AS']],
        'be' => ['name' => 'Belarusian', 'native' => 'Беларуская', 'continents' => ['EU']],
        'bg' => ['name' => 'Bulgarian', 'native' => 'Български', 'continents' => ['EU']],
        'bn' => ['name' => 'Bengali', 'native' => 'বাংলা', 'continents' => ['AS']],
        'bs' => ['name' => 'Bosnian', 'native' => 'Bosanski', 'continents' => ['EU']],
        'ca' => ['name' => 'Catalan', 'native' => 'Català', 'continents' => ['EU']],
        'cs' => ['name' => 'Czech', 'native' => 'Česky', 'continents' => ['EU']],
        'da' => ['name' => 'Danish', 'native' => 'Dansk', 'continents' => ['EU']],
        'de' => ['name' => 'German', 'native' => 'Deutsch', 'continents' => ['EU']],
        'el' => ['name' => 'Greek', 'native' => 'Ελληνικά', 'continents' => ['EU']],
        'en' => ['name' => 'English', 'native' => 'English', 'continents' => ['AF', 'AS', 'EU', 'NA', 'OC', 'SA']],
        'es' => ['name' => 'Spanish', 'native' => 'Español', 'continents' => ['AF', 'EU', 'NA', 'OC', 'SA']],
        'et' => ['name' => 'Estonian', 'native' => 'Eesti', 'continents' => ['EU']],
        'eu' => ['name' => 'Basque', 'native' => 'Euskara', 'continents' => ['EU']],
        'fa' => ['name' => 'Persian', 'native' => 'فارسی', 'rtl' => true, 'continents' => ['AS']],
        'fi' => ['name' => 'Finnish', 'native' => 'Suomi', 'continents' => ['EU']],
        'fr' => ['name' => 'French', 'native' => 'Français', 'continents' => ['AF', 'AS', 'EU', 'NA', 'OC', 'SA']],
        'ga' => ['name' => 'Irish', 'native' => 'Gaeilge', 'continents' => ['EU']],
        'gl' => ['name' => 'Galician', 'native' => 'Galego', 'continents' => ['EU']],
        'hi' => ['name' => 'Hindi', 'native' => 'हिन्दी', 'continents' => ['AS', 'OC']],
        'hr' => ['name' => 'Croatian', 'native' => 'Hrvatski', 'continents' => ['EU']],
        'hu' => ['name' => 'Hungarian', 'native' => 'Magyar', 'continents' => ['EU']],
        'hy' => ['name' => 'Armenian', 'native' => 'Հայերեն', 'continents' => ['AS', 'EU']],
        'id' => ['name' => 'Indonesian', 'native' => 'Bahasa Indonesia', 'continents' => ['AS']],
        'is' => ['name' => 'Icelandic', 'native' => 'Íslenska', 'continents' => ['EU']],
        'it' => ['name' => 'Italian', 'native' => 'Italiano', 'continents' => ['EU']],
        'ja' => ['name' => 'Japanese', 'native' => '日本語', 'continents' => ['AS']],
        'ka' => ['name' => 'Georgian', 'native' => 'ქართული', 'continents' => ['AS']],
        'kk' => ['name' => 'Kazakh', 'native' => 'Қазақша', 'continents' => ['AS']],
        'km' => ['name' => 'Cambodian', 'native' => 'ភាសាខ្មែរ', 'continents' => ['AS']],
        'ko' => ['name' => 'Korean', 'native' => '한국어', 'continents' => ['AS']],
        'ku' => ['name' => 'Kurdish', 'native' => 'Kurdî / كوردی', 'rtl' => true, 'continents' => ['AS']],
        'ky' => ['name' => 'Kirghiz', 'native' => 'Kırgızca / Кыргызча', 'continents' => ['AS']],
        'lb' => ['name' => 'Luxembourgish', 'native' => 'Lëtzebuergesch', 'continents' => ['EU']],
        'lo' => ['name' => 'Laotian', 'native' => 'ລາວ / Pha xa lao', 'continents' => ['AS']],
        'lt' => ['name' => 'Lithuanian', 'native' => 'Lietuvių', 'continents' => ['EU']],
        'lv' => ['name' => 'Latvian', 'native' => 'Latviešu', 'continents' => ['EU']],
        'mg' => ['name' => 'Malagasy', 'native' => 'Malagasy', 'continents' => ['AF']],
        'mi' => ['name' => 'Maori', 'native' => 'Māori', 'continents' => ['OC']],
        'mk' => ['name' => 'Macedonian', 'native' => 'Македонски', 'continents' => ['EU']],
        'mn' => ['name' => 'Mongolian', 'native' => 'Монгол', 'continents' => ['AS']],
        'ms' => ['name' => 'Malay', 'native' => 'Bahasa Melayu', 'continents' => ['AS']],
        'mt' => ['name' => 'Maltese', 'native' => 'bil-Malti', 'continents' => ['EU']],
        'my' => ['name' => 'Burmese', 'native' => 'Myanmasa', 'continents' => ['AS']],
        'ne' => ['name' => 'Nepali', 'native' => 'नेपाली', 'continents' => ['AS']],
        'nl' => ['name' => 'Dutch', 'native' => 'Nederlands', 'continents' => ['EU', 'NA', 'SA']],
        'no' => ['name' => 'Norwegian', 'native' => 'Norsk (bokmål / riksmål)', 'continents' => ['EU']],
        'ny' => ['name' => 'Chichewa', 'native' => 'Chi-Chewa', 'continents' => ['AF']],
        'pa' => ['name' => 'Panjabi / Punjabi', 'native' => 'ਪੰਜਾਬੀ / पंजाबी / پنجابي', 'continents' => ['AS']],
        'pl' => ['name' => 'Polish', 'native' => 'Polski', 'continents' => ['EU']],
        'ps' => ['name' => 'Pashto', 'native' => 'پښتو', 'rtl' => true, 'continents' => ['AS']],
        'pt' => ['name' => 'Portuguese', 'native' => 'Português', 'continents' => ['AF', 'AS', 'EU', 'OC', 'SA']],
        'ro' => ['name' => 'Romanian', 'native' => 'Română', 'continents' => ['EU']],
        'ru' => ['name' => 'Russian', 'native' => 'Русский', 'continents' => ['AS', 'EU']],
        'si' => ['name' => 'Sinhalese', 'native' => 'සිංහල', 'continents' => ['AS']],
        'sk' => ['name' => 'Slovak', 'native' => 'Slovenčina', 'continents' => ['EU']],
        'sl' => ['name' => 'Slovenian', 'native' => 'Slovenščina', 'continents' => ['EU']],
        'sm' => ['name' => 'Samoan', 'native' => 'Gagana Samoa', 'continents' => ['OC']],
        'sn' => ['name' => 'Shona', 'native' => 'chiShona', 'continents' => ['AF']],
        'so' => ['name' => 'Somalia', 'native' => 'Soomaaliga', 'continents' => ['AF']],
        'sq' => ['name' => 'Albanian', 'native' => 'Shqip', 'continents' => ['EU']],
        'sr' => ['name' => 'Serbian', 'native' => 'Српски', 'continents' => ['EU']],
        'st' => ['name' => 'Southern Sotho', 'native' => 'Sesotho', 'continents' => ['AF']],
        'sv' => ['name' => 'Swedish', 'native' => 'Svenska', 'continents' => ['EU']],
        'sw' => ['name' => 'Swahili', 'native' => 'Kiswahili', 'continents' => ['AF']],
        'ta' => ['name' => 'Tamil', 'native' => 'தமிழ்', 'continents' => ['AS']],
        'tg' => ['name' => 'Tajik', 'native' => 'Тоҷикӣ', 'continents' => ['AS']],
        'th' => ['name' => 'Thai', 'native' => 'ไทย / Phasa Thai', 'continents' => ['AS']],
        'tr' => ['name' => 'Turkish', 'native' => 'Türkçe', 'continents' => ['AS', 'EU']],
        'uk' => ['name' => 'Ukrainian', 'native' => 'Українська', 'continents' => ['EU']],
        'ur' => ['name' => 'Urdu', 'native' => 'اردو', 'rtl' => true, 'continents' => ['AS', 'OC']],
        'uz' => ['name' => 'Uzbek', 'native' => 'Ўзбек', 'continents' => ['AS']],
        'vi' => ['name' => 'Vietnamese', 'native' => 'Tiếng Việt', 'continents' => ['AS']],
        'xh' => ['name' => 'Xhosa', 'native' => 'isiXhosa', 'continents' => ['AF']],
        'zh' => ['name' => 'Chinese', 'native' => '中文', 'continents' => ['AS']],
        'zu' => ['name' => 'Zulu', 'native' => 'isiZulu', 'continents' => ['AF']],
    ];

    /**
     * Return whether a language code is available
     */
    public static function hasCode(string $code): bool
    {
        return isset(self::LANGUAGE_CODES[$code]);
    }

    /**
     * Return language native names optionally filtered by continent
     * 'AF' = Africa, 'AS' = Asia, 'EU' = Europe,
     * 'NA' = North America, 'OC' = Oceania, 'SA' = South America
     *
     * @return array<string, string>
     */
    public static function names(?string $continent = null): array
    {
        $result = [];
        foreach (self::LANGUAGE_CODES as $code => $data) {
            if ($continent !== null && count(array_intersect((array) $continent, $data['continents'])) < 1) {
                continue;
            }
            $result[$code] = "{$data['native']} ({$code})";
        }
        return $result;
    }

    /**
     * Return language name from language code (e.g. 'it')
     *
     * @throws InvalidArgumentException If the language code is invalid
     */
    public static function codeToName(string $code): string
    {
        if (!self::hasCode($code)) {
            throw new InvalidArgumentException(sprintf('Invalid language code "%s"', $code));
        }
        return self::LANGUAGE_CODES[$code]['name'];
    }

    /**
     * Return language native name from language code (e.g. 'it')
     *
     * @throws InvalidArgumentException If the language code is invalid
     */
    public static function codeToNativeName(string $code): string
    {
        if (!self::hasCode($code)) {
            throw new InvalidArgumentException(sprintf('Invalid language code "%s"', $code));
        }
        return self::LANGUAGE_CODES[$code]['native'];
    }
}
