<?hh // strict
/**
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

/**
 * This file was moved from fbsource to www. View old history in diffusion:
 * https://fburl.com/k0eh6x88
 */
namespace Facebook\ShipIt;


<<\Oncalls('open_source')>>
final class SyncTrackingTest extends BaseTest {
  public function testLastSourceCommitWithGit(): void {
    $tempdir = new ShipItTempDir('git-sync-test');
    $path = $tempdir->getPath();

    // Prepare an empty repo
    (new ShipItShellCommand($path, 'git', 'init'))->runSynchronously();
    (
      new ShipItShellCommand(
        $path,
        'git',
        'config',
        'user.name',
        'FBShipIt Unit Test',
      )
    )->runSynchronously();
    (
      new ShipItShellCommand(
        $path,
        'git',
        'config',
        'user.email',
        'fbshipit@example.com',
      )
    )->runSynchronously();

    // Add a tracked commit
    /* HH_IGNORE_ERROR[2049] __PHPStdLib */
    /* HH_IGNORE_ERROR[4107] __PHPStdLib */
    $fake_commit_id = \bin2hex(\random_bytes(16));
    $message = ShipItSync::addTrackingData(
      (new ShipItChangeset())->withID($fake_commit_id),
    )->getMessage();
    (
      new ShipItShellCommand(
        $path,
        'git',
        'commit',
        '--allow-empty',
        '-m',
        $message,
      )
    )->runSynchronously();

    $repo = new ShipItRepoGIT($path, 'master');
    \expect($repo->findLastSourceCommit(ImmSet {}))->toBeSame($fake_commit_id);
  }

  public function testLastSourceCommitWithMercurial(): void {
    $tempdir = new ShipItTempDir('hg-sync-test');
    $path = $tempdir->getPath();

    // Prepare an empty repo
    (new ShipItShellCommand($path, 'hg', 'init'))->runSynchronously();
    $this->configureHg($tempdir);

    // Add a tracked commit
    /* HH_IGNORE_ERROR[2049] __PHPStdLib */
    /* HH_IGNORE_ERROR[4107] __PHPStdLib */
    $fake_commit_id = \bin2hex(\random_bytes(16));
    $message = ShipItSync::addTrackingData(
      (new ShipItChangeset())->withID($fake_commit_id),
    )->getMessage();
    (new ShipItShellCommand($path, 'touch', 'testfile'))->runSynchronously();
    (
      new ShipItShellCommand($path, 'hg', 'commit', '-A', '-m', $message)
    )->runSynchronously();

    $repo = new ShipItRepoHG($path, 'master');
    \expect($repo->findLastSourceCommit(ImmSet {}))->toBeSame($fake_commit_id);
  }
}
