<?php

declare(strict_types=1);

namespace Dskripchenko\LaravelAdminHealth\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Every Cyrillic string literal in src/ is a translation key, so each one
 * must have an English line in resources/lang/en.json.
 */
final class TranslationCoverageTest extends TestCase
{
    public function test_every_cyrillic_string_in_src_has_an_english_translation(): void
    {
        $root = dirname(__DIR__, 2);
        /** @var array<string, string> $en */
        $en = json_decode((string) file_get_contents($root.'/resources/lang/en.json'), true, flags: JSON_THROW_ON_ERROR);

        $missing = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/src'));
        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            foreach (token_get_all((string) file_get_contents($file->getPathname())) as $token) {
                if (! is_array($token) || $token[0] !== T_CONSTANT_ENCAPSED_STRING) {
                    continue;
                }
                $literal = $token[1];
                if (preg_match('/\p{Cyrillic}/u', $literal) !== 1) {
                    continue;
                }
                $key = $literal[0] === "'"
                    ? str_replace(["\\'", '\\\\'], ["'", '\\'], substr($literal, 1, -1))
                    : stripcslashes(substr($literal, 1, -1));
                if (! array_key_exists($key, $en) || $en[$key] === '') {
                    $missing[] = $key.'  ('.basename($file->getPathname()).')';
                }
            }
        }

        $this->assertSame([], $missing, "Missing in resources/lang/en.json:\n".implode("\n", $missing));
    }
}
