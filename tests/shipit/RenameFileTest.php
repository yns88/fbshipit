<?hh // strict
/**
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

/**
 * This file was moved from fbsource to www. View old history in diffusion:
 * https://fburl.com/z5l3wuo7
 */
namespace Facebook\ShipIt;


<<\Oncalls('open_source')>>
final class RenameFileTest extends BaseTest {
  /**
   * We need separate 'delete file', 'create file' diffs for renames, in case
   * one side is filtered out - eg:
   *
   *   mv fbonly/foo public/foo
   *
   * The filter is likely to strip out the fbonly/foo change, leaving 'rename
   * from fbonly/foo' in the diff, but as fbonly/foo isn't on github, that's
   * not enough information.
   */
  public function testRenameFile(): void {
    $temp_dir = new ShipItTempDir('rename-file-test');
    /* HH_IGNORE_ERROR[2049] __PHPStdLib */
    /* HH_IGNORE_ERROR[4107] __PHPStdLib */
    \file_put_contents(
      $temp_dir->getPath().'/initial.txt',
      'my content here',
    );

    $this->execSteps($temp_dir->getPath(), ImmVector {'hg', 'init'});
    $this->configureHg($temp_dir);

    $this->execSteps(
      $temp_dir->getPath(),
      ImmVector {'hg', 'commit', '-Am', 'initial commit'},
      ImmVector {'hg', 'mv', 'initial.txt', 'moved.txt'},
      ImmVector {'chmod', '755', 'moved.txt'},
      ImmVector {'hg', 'commit', '-Am', 'moved file'},
    );

    $repo = new ShipItRepoHG($temp_dir->getPath(), 'master');
    $changeset = $repo->getChangesetFromID('.');
    $changeset = \expect($changeset)->toNotBeNull();
    /* HH_IGNORE_ERROR[2049] __PHPStdLib */
    /* HH_IGNORE_ERROR[4107] __PHPStdLib */
    \shell_exec('rm -rf '.\escapeshellarg($temp_dir->getPath()));

    \expect($changeset->getSubject())->toBeSame('moved file');

    $diffs = Map {};
    foreach ($changeset->getDiffs() as $diff) {
      $diffs[$diff['path']] = $diff['body'];
    }
    $wanted_files = ImmSet {'initial.txt', 'moved.txt'};
    foreach ($wanted_files as $file) {
      \expect($diffs->keys())->toContain($file);
      $diff = $diffs[$file];
      \expect($diff)->toContainSubstring('my content here');
    }

    \expect($diffs['initial.txt'])
      ->toContainSubstring('deleted file mode 100644');
    \expect($diffs['moved.txt'])->toContainSubstring('new file mode 100755');
  }
}
