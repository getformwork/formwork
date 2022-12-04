<?php

namespace Formwork\Languages;

class Language
{
    /**
     * Language code
     */
    protected string $code;

    /**
     * Language name (in English)
     */
    protected ?string $name = null;

    /**
     * Language native name
     */
    protected ?string $nativeName = null;

    public function __construct(string $code)
    {
        $this->code = $code;

        if (LanguageCodes::hasCode($code)) {
            $this->name = LanguageCodes::codeToName($code);
            $this->nativeName = LanguageCodes::codeToNativeName($code);
        }
    }

    public function __toString(): string
    {
        return $this->code;
    }

    /**
     * Get language code
     */
    public function code(): string
    {
        return $this->code;
    }

    /**
     * Get language name
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * Get language native name
     */
    public function nativeName(): ?string
    {
        return $this->nativeName;
    }
}
