<?php

namespace Formwork\Commands;

interface CommandInterface
{
    /**
     * Invoke the command
     *
     * @param list<string>|null $argv Command line arguments, or null to use global `$_SERVER['argv']`
     */
    public function __invoke(?array $argv = null): never;
}
