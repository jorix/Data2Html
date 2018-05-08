cd C:\_Apache\Aixada\Data2Html\test\php
composer require "codeception/codeception" --dev
codecept bootstrap

codecept generate:test unit Example

codecept run --steps --no-colors

