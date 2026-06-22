<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use ZipArchive;

#[Signature('changes:pack {--output=changes.zip : Path file zip hasil} {--root= : Root project (default: base_path)}')]
#[Description('Bungkus perubahan working tree (tambah/edit/hapus) ke sebuah file zip.')]
class ChangesPackCommand extends Command
{
    private const WRAPPER = 'changes';

    private const MANIFEST = 'changes/.changes-manifest.json';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $root = rtrim($this->option('root') ?: base_path(), '/\\');

        if (! is_dir($root.DIRECTORY_SEPARATOR.'.git')) {
            $this->error('Bukan git repository: '.$root);

            return self::FAILURE;
        }

        $candidates = $this->git($root, ['ls-files', '--modified', '--others', '--exclude-standard']);
        $deleted = $this->git($root, ['ls-files', '--deleted']);

        // File yang ditambah/diubah: kandidat yang benar-benar ada di disk
        // (file yang dihapus tidak ada, jadi otomatis tersaring keluar).
        $included = array_values(array_filter($candidates, function (string $relative) use ($root): bool {
            return is_file($root.DIRECTORY_SEPARATOR.$relative);
        }));

        $output = $this->option('output') ?: 'changes.zip';
        if (! $this->isAbsolutePath($output)) {
            $output = $root.DIRECTORY_SEPARATOR.$output;
        }

        $zip = new ZipArchive;
        if ($zip->open($output, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Gagal membuat zip: '.$output);

            return self::FAILURE;
        }

        foreach ($included as $relative) {
            $zip->addFile($root.DIRECTORY_SEPARATOR.$relative, self::WRAPPER.'/'.$this->normalizeSlashes($relative));
        }

        $zip->addFromString(self::MANIFEST, json_encode(
            ['deleted' => array_map([$this, 'normalizeSlashes'], $deleted)],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ));

        $zip->close();

        $this->info('Perubahan dibungkus ke: '.$output);
        $this->line('  Ditambah/diubah : '.count($included));
        $this->line('  Dihapus         : '.count($deleted));

        return self::SUCCESS;
    }

    /**
     * Run a git command in the given root and return non-empty output lines.
     *
     * @param  array<int, string>  $args
     * @return array<int, string>
     */
    private function git(string $root, array $args): array
    {
        $result = Process::path($root)->run(array_merge(['git'], $args));

        $lines = preg_split('/\r\n|\r|\n/', trim($result->output()));

        return array_values(array_filter($lines, fn (string $line): bool => $line !== ''));
    }

    private function isAbsolutePath(string $path): bool
    {
        return (bool) preg_match('#^(?:[A-Za-z]:[\\\\/]|[\\\\/])#', $path);
    }

    private function normalizeSlashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
