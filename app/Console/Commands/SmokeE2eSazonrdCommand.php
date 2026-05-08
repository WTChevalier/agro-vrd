<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
class SmokeE2eSazonrdCommand extends Command {
    protected $signature = 'smoke:e2e-sazonrd';
    protected $description = 'Sprint 694 — Smoke E2E SazónRD';
    public function handle(): int {
        $base = 'https://sazonrd.com';
        $tests = [
            ['name' => 'home', 'url' => $base.'/', 'expect' => 200],
            ['name' => 'sitemap', 'url' => $base.'/sitemap.xml', 'expect' => 200],
            ['name' => 'robots', 'url' => $base.'/robots.txt', 'expect' => 200],
            ['name' => 'platos', 'url' => $base.'/platos', 'expect' => [200, 404]],
            ['name' => 'restaurantes', 'url' => $base.'/restaurantes', 'expect' => [200, 404]],
        ];
        $failed = 0;
        foreach ($tests as $t) {
            try {
                $code = Http::timeout(15)->get($t['url'])->status();
                $expect = is_array($t['expect']) ? $t['expect'] : [$t['expect']];
                $ok = in_array($code, $expect, true);
                if (!$ok) $failed++;
                $this->line(sprintf('  %s %-15s HTTP %d', $ok?'✓':'✗', $t['name'], $code));
            } catch (\Throwable $e) { $failed++; }
        }
        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
