<?php

namespace Formwork\Utils;

class Visitor
{
    /**
     * Array containing regex tokens that identify bots
     *
     * @var array
     */
    protected static $bots = array(
        'bot', 'crawl', 'search', 'sp[iy]der', 'check', 'findlinks', 'url',
        'yahoo', 'feed', 'archive', 'heritrix', 'perl', 'links?\s?check',
        'fetch', 'search[-\s]?engine', 'monitor', 'curl', 'seer', 'thumb',
        'web[-\s]?search', 'whatsapp', 'scan', 'validator', 'analyz(a|er)',
        '(http_|media|w)get', 'reader', 'python', 'auto', 'reaper', 'loader',
        '(apis|appengine|mediapartners)-google', 'download(s|er)',
        'link\ check', 'images', '(apache-http|go-http-|http_)client', 'finder',
        'program', 'collect', 'spy', 'site[-\s]?(check|scan)', 'bingpreview',
        'parse', 'ips-agent', 'verif(y|ier)', 'detector', 'harvest',
        '(ok|pcore-)http', 'webinator', 'extract', 'aggregator', 'sniff',
        'index\ ', 'tracker', 'library', 'capture', 'utility', 'scrape',
        'locat(e|or)', 'gather', 'java\/', 'getter',
        'html2', 'worth', 'archiving', 'leech', 'hound', 'retrieve', 'sweep',
        'rating', 'google\ web\ preview', 'somewhere', 'php\/\d', 'control',
        'fantom', 'http\.rb', 'jorgee', 'linkman', 'wget', 'gopher'
    );

    /**
     * Compiled bots regex
     *
     * @var string
     */
    protected static $regex;

    /**
     * Return whether current visitor is a bot
     *
     * @return bool
     */
    public static function isBot()
    {
        if (is_null(static::$regex)) {
            static::$regex = '/' . implode('|', static::$bots) . '/i';
        }
        return (bool) preg_match(static::$regex, HTTPRequest::userAgent());
    }

    /**
     * Return whether current user agent is a browser
     *
     * @return bool
     */
    public static function isBrowser()
    {
        return !static::isBot();
    }

    /**
     * Detect whether current visitor prefers not to be tracked
     *
     * @return bool
     */
    public static function isTrackable()
    {
        return !HTTPRequest::hasHeader('Dnt') || HTTPRequest::headers()['Dnt'] !== '1';
    }
}
