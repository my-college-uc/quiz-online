<?php

use Illuminate\Support\Facades\File;

/**
 * Create a throwaway git repository with one committed baseline file.
 */
function makeTempRepo(): string
{
    $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'changes_'.bin2hex(random_bytes(6));
    File::ensureDirectoryExists($dir);

    $git = function (string $args) use ($dir): void {
        exec('git -C '.escapeshellarg($dir).' '.$args.' 2>&1');
    };

    $git('init -q');
    $git('config user.email test@example.com');
    $git('config user.name Test');

    // Baseline committed files.
    File::put($dir.'/keep.txt', 'baseline');
    File::put($dir.'/old.txt', 'to be deleted');
    File::put($dir.'/.gitignore', "ignored.txt\n");
    $git('add -A');
    $git('commit -q -m baseline');

    return $dir;
}

beforeEach(function () {
    $this->repo = makeTempRepo();
});

afterEach(function () {
    File::deleteDirectory($this->repo);
});

it('packs modified and untracked files under a wrapper folder, ignoring gitignored files', function () {
    File::put($this->repo.'/keep.txt', 'edited');                 // modified
    File::ensureDirectoryExists($this->repo.'/app');
    File::put($this->repo.'/app/New.php', '<?php // new');          // untracked (added)
    File::put($this->repo.'/ignored.txt', 'secret');               // gitignored -> excluded

    $zip = $this->repo.'/changes.zip';

    $this->artisan('changes:pack', ['--root' => $this->repo, '--output' => $zip])
        ->assertExitCode(0);

    expect(File::exists($zip))->toBeTrue();

    $archive = new ZipArchive;
    $archive->open($zip);
    $names = [];
    for ($i = 0; $i < $archive->numFiles; $i++) {
        $names[] = $archive->getNameIndex($i);
    }
    $archive->close();

    expect($names)->toContain('changes/keep.txt');
    expect($names)->toContain('changes/app/New.php');
    expect($names)->not->toContain('changes/ignored.txt');
});

it('records deleted files in the manifest', function () {
    File::delete($this->repo.'/old.txt');                          // deleted

    $zip = $this->repo.'/changes.zip';

    $this->artisan('changes:pack', ['--root' => $this->repo, '--output' => $zip])
        ->assertExitCode(0);

    $archive = new ZipArchive;
    $archive->open($zip);
    $manifest = $archive->getFromName('changes/.changes-manifest.json');
    $archive->close();

    expect($manifest)->not->toBeFalse();
    $decoded = json_decode($manifest, true);
    expect($decoded['deleted'])->toContain('old.txt');
});

it('applies a zip by writing files into the root and stripping the wrapper', function () {
    $zip = $this->repo.'/incoming.zip';
    $archive = new ZipArchive;
    $archive->open($zip, ZipArchive::CREATE);
    $archive->addFromString('changes/keep.txt', 'from-zip');
    $archive->addFromString('changes/nested/deep/File.php', '<?php // deep');
    $archive->addFromString('changes/.changes-manifest.json', json_encode(['deleted' => []]));
    $archive->close();

    $this->artisan('changes:apply', ['zip' => $zip, '--root' => $this->repo])
        ->assertExitCode(0);

    expect(File::get($this->repo.'/keep.txt'))->toBe('from-zip');           // overwritten
    expect(File::get($this->repo.'/nested/deep/File.php'))->toBe('<?php // deep');
    expect(File::exists($this->repo.'/.changes-manifest.json'))->toBeFalse(); // manifest not written as file
});

it('deletes files listed in the manifest on apply', function () {
    $zip = $this->repo.'/incoming.zip';
    $archive = new ZipArchive;
    $archive->open($zip, ZipArchive::CREATE);
    $archive->addFromString('changes/keep.txt', 'still here');
    $archive->addFromString('changes/.changes-manifest.json', json_encode(['deleted' => ['old.txt']]));
    $archive->close();

    expect(File::exists($this->repo.'/old.txt'))->toBeTrue();

    $this->artisan('changes:apply', ['zip' => $zip, '--root' => $this->repo])
        ->assertExitCode(0);

    expect(File::exists($this->repo.'/old.txt'))->toBeFalse();
});

it('blocks path traversal entries during apply', function () {
    $outside = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pwned_'.bin2hex(random_bytes(4)).'.txt';
    @unlink($outside);

    $zip = $this->repo.'/evil.zip';
    $archive = new ZipArchive;
    $archive->open($zip, ZipArchive::CREATE);
    $archive->addFromString('changes/../../../../../../../../tmp/pwned.txt', 'hacked');
    $archive->close();

    $this->artisan('changes:apply', ['zip' => $zip, '--root' => $this->repo]);

    expect(File::exists($outside))->toBeFalse();
    expect(File::exists('/tmp/pwned.txt'))->toBeFalse();
});
