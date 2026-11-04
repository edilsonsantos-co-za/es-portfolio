<?php

namespace Tests\Unit;

use App\Services\ApplicationVersion;
use App\Services\Themes\ThemePackageException;
use App\Services\Themes\ThemePackageVerifier;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class ThemePackageVerifierTest extends TestCase
{
    private string $dir;
    private string $secret;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/theme-verify-' . uniqid();
        mkdir($this->dir . '/keys', 0777, true);
        file_put_contents($this->dir . '/VERSION', "0.0.9\n");
        $pair = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($pair);
        file_put_contents($this->dir . '/keys/test.pub', base64_encode(sodium_crypto_sign_publickey($pair)));
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
    }

    public function test_accepts_a_signed_package(): void
    {
        $result = $this->verifier()->verify($this->package());

        $this->assertTrue($result['signed']);
        $this->assertSame('vega', $result['manifest']['id']);
        $this->assertArrayHasKey('views/theme.blade.php', $result['files']);
    }

    public function test_rejects_an_unsigned_package(): void
    {
        $this->expectException(ThemePackageException::class);
        $this->verifier()->verify($this->package(sign: false));
    }

    public function test_allows_unsigned_when_configured(): void
    {
        $this->assertFalse($this->verifier(true)->verify($this->package(sign: false))['signed']);
    }

    public function test_rejects_a_file_changed_after_signing(): void
    {
        $this->expectExceptionMessage('checksum');
        $this->verifier()->verify($this->package(tamper: true));
    }

    public function test_rejects_files_the_manifest_does_not_list(): void
    {
        $this->expectExceptionMessage('does not list');
        $this->verifier()->verify($this->package(extra: ['views/extra.blade.php' => 'x']));
    }

    public function test_rejects_paths_outside_the_theme(): void
    {
        $this->expectExceptionMessage('not allowed');
        $this->verifier()->verify($this->package(files: ['../../app/Http/Kernel.php' => '<?php']));
    }

    public function test_rejects_another_apps_package(): void
    {
        $this->expectExceptionMessage('not ES Portfolio');
        $this->verifier()->verify($this->package(manifest: ['app' => 'es-blog']));
    }

    public function test_accepts_a_package_signed_before_the_rename(): void
    {
        $result = $this->verifier()->verify($this->package(manifest: ['app' => ThemePackageVerifier::LEGACY_APPS[0]]));
        $this->assertSame('vega', $result['manifest']['id']);
    }

    public function test_rejects_an_incompatible_app_version(): void
    {
        $this->expectExceptionMessage('needs ES Portfolio');
        $this->verifier()->verify($this->package(manifest: ['requires' => '>=2.0.0']));
    }

    public function test_allowed_paths(): void
    {
        $this->assertTrue(ThemePackageVerifier::allowedPath('views/theme.blade.php'));
        $this->assertTrue(ThemePackageVerifier::allowedPath('assets/css/styles.css'));
        $this->assertFalse(ThemePackageVerifier::allowedPath('assets/shell.php'));
        $this->assertFalse(ThemePackageVerifier::allowedPath('views/helper.php'));
        $this->assertFalse(ThemePackageVerifier::allowedPath('assets/../../x.css'));
        $this->assertFalse(ThemePackageVerifier::allowedPath('/etc/passwd'));
        $this->assertFalse(ThemePackageVerifier::allowedPath('assets/.htaccess'));
    }

    private function verifier(bool $allowUnsigned = false): ThemePackageVerifier
    {
        return new ThemePackageVerifier(new ApplicationVersion($this->dir . '/VERSION'), $this->dir . '/keys', $allowUnsigned);
    }

    private function package(bool $sign = true, bool $tamper = false, array $files = [], array $extra = [], array $manifest = []): string
    {
        $files += ['views/theme.blade.php' => '<html></html>', 'assets/css/styles.css' => 'body{}'];
        $json = json_encode($manifest + [
            'schema' => 1, 'id' => 'vega', 'name' => 'Vega', 'version' => '1.0.0', 'app' => 'es-portfolio',
            'requires' => '>=0.0.9 <1.0.0', 'files' => array_map(fn ($d) => 'sha256:' . hash('sha256', $d), $files),
        ]);

        $path = $this->dir . '/' . uniqid() . '.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('manifest.json', $json);
        if ($sign) {
            $zip->addFromString('manifest.sig', json_encode(['key' => 'test', 'alg' => 'ed25519', 'sig' => base64_encode(sodium_crypto_sign_detached($json, $this->secret))]));
        }
        foreach ($files + $extra as $name => $data) {
            $zip->addFromString($name, $tamper && $name === 'assets/css/styles.css' ? 'body{color:red}' : $data);
        }
        $zip->close();

        return $path;
    }
}
