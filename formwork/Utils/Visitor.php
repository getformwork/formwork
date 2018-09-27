<?php

namespace Formwork\Utils;

class Visitor
{
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

    protected static $regex;

    public static function isBot()
    {
        if (is_null(static::$regex)) {
            static::$regex = '/' . implode('|', static::$bots) . '/i';
        }
        return (bool) preg_match(static::$regex, HTTPRequest::userAgent());
    }

    public static function isBrowser()
    {
        return !static::isBot();
    }
}
