<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ZipArchive;

#[Signature('changes:apply {zip : Path file zip yang akan diterapkan} {--root= : Root project (default: base_path)}')]
#[Description('Buka zip perubahan lalu timpa file di project sesuai strukturnya (termasuk hapus sesuai manifest).')]
class ChangesApplyCommand extends Command
{
    private const MANIFEST = '.changes-manifest.json';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $root = rtrim($this->option('root') ?: base_path(), '/\\');
        $zipPath = $this->argument('zip');

        if (! is_file($zipPath)) {
            $this->error('File zip tidak ditemukan: '.$zipPath);

            return self::FAILURE;
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            $this->error('File zip tidak valid: '.$zipPath);

            return self::FAILURE;
        }

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }

        $wrapper = $this->detectWrapper($names);

        $written = [];
        $deleted = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if (str_ends_with($name, '/')) {
                continue; // Direktori.
            }

            $relative = $this->stripWrapper($name, $wrapper);
            if ($relative === '') {
                continue;
            }

            if ($relative === self::MANIFEST) {
                $deleted = $this->applyManifest($zip->getFromIndex($i), $root);

                continue;
            }

            $target = $this->resolveWithin($root, $relative);
            if ($target === null) {
                $this->warn('Dilewati (path tidak aman): '.$name);

                continue;
            }

            File::ensureDirectoryExists(dirname($target));
            File::put($target, $zip->getFromIndex($i));
            $written[] = $relative;
        }

        $zip->close();

        $this->info('Perubahan diterapkan ke: '.$root);
        $this->line('  Ditulis/ditimpa : '.count($written));
        $this->line('  Dihapus         : '.count($deleted));

        return self::SUCCESS;
    }

    /**
     * Determine the single top-level wrapper folder shared by all entries, if any.
     *
     * @param  array<int, string>  $names
     */
    private function detectWrapper(array $names): ?string
    {
        $segments = [];
        foreach ($names as $name) {
            $first = explode('/', str_replace('\\', '/', $name))[0];
            if ($first !== '') {
                $segments[$first] = true;
            }
        }

        return count($segments) === 1 ? array_key_first($segments) : null;
    }

    private function stripWrapper(string $name, ?string $wrapper): string
    {
        $name = str_replace('\\', '/', $name);

        if ($wrapper !== null && str_starts_with($name, $wrapper.'/')) {
            return substr($name, strlen($wrapper) + 1);
        }

        return $name;
    }

    /**
     * Resolve a relative path inside root, returning null if it escapes root.
     */
    private function resolveWithin(string $root, string $relative): ?string
    {
        $stack = [];
        foreach (explode('/', $relative) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                if ($stack === []) {
                    return null;
                }
                array_pop($stack);

                continue;
            }

            $stack[] = $part;
        }

        if ($stack === []) {
            return null;
        }

        return $root.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $stack);
    }

    /**
     * Delete files listed in the manifest, returning the paths that were removed.
     *
     * @return array<int, string>
     */
    private function applyManifest(string $contents, string $root): array
    {
        $decoded = json_decode($contents, true);
        $deletedPaths = $decoded['deleted'] ?? [];

        $removed = [];
        foreach ($deletedPaths as $relative) {
            $target = $this->resolveWithin($root, (string) $relative);
            if ($target !== null && is_file($target)) {
                File::delete($target);
                $removed[] = $relative;
            }
        }

        return $removed;
    }
}
