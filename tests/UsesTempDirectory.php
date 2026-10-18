<?php

namespace Formwork\Tests;

use Attribute;

/**
 * Gives each test of the class an empty temporary directory at `TESTS_TMP_PATH`, removed after the test
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class UsesTempDirectory {}
