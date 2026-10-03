<?php

declare(strict_types=1);

namespace VexPay\Laravel\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * README samples reference application classes (App\Models\Order…), so they are parsed rather
 * than run; behaviour is covered by the feature tests. Every imported VEXPay class must exist.
 */
final class ReadmeTest extends BaseTestCase
{
    /**
     * @return iterable<string, array{0: string}>
     */
    public static function examples(): iterable
    {
        $readme = (string) file_get_contents(dirname(__DIR__) . '/README.md');
        preg_match_all('/```php\n(.*?)```/s', $readme, $matches);
        foreach ($matches[1] as $index => $code) {
            yield 'example ' . ($index + 1) => [$code];
        }
    }

    #[DataProvider('examples')]
    public function testExampleParsesAndImportsRealClasses(string $code): void
    {
        self::assertNotEmpty(token_get_all("<?php\n" . $code, TOKEN_PARSE));

        preg_match_all('/^use (VexPay\\\\[\w\\\\]+);/m', $code, $imports);
        foreach ($imports[1] as $class) {
            self::assertTrue(
                class_exists($class) || trait_exists($class) || interface_exists($class),
                "README imports {$class}, which does not exist",
            );
        }
    }

    public function testDocumentsEveryEnvVariableTheConfigReads(): void
    {
        $readme = (string) file_get_contents(dirname(__DIR__) . '/README.md');
        preg_match_all("/env\\('(VEXPAY_[A-Z_]+)'/", (string) file_get_contents(dirname(__DIR__) . '/config/vexpay.php'), $vars);

        foreach ($vars[1] as $var) {
            self::assertStringContainsString($var, $readme);
        }
    }
}
