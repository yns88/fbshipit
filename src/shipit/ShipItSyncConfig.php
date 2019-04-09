<?hh // strict
/**
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

/**
 * This file was moved from fbsource to www. View old history in diffusion:
 * https://fburl.com/4z5pgrps
 */
namespace Facebook\ShipIt;

final class ShipItSyncConfig {
  const type TFilterFn = (function(
    ShipItBaseConfig,
    ShipItChangeset,
  ): ShipItChangeset);
  const type TPostFilterChangesetsFn = (function(
    ImmVector<ShipItChangeset>,
    ShipItRepo,
  ): ImmVector<ShipItChangeset>);

  private ?string $firstCommit = null;
  private ImmSet<string> $skippedSourceCommits = ImmSet {};
  private ?string $patchesDirectory = null;
  private ImmSet<string> $destinationRoots = ImmSet {};
  private ?string $statsFilename = null;
  private ?bool $allowEmptyCommit = false;

  public function __construct(
    private ImmSet<string> $sourceRoots,
    private self::TFilterFn $filter,
    private ?self::TPostFilterChangesetsFn $postFilterChangesets = null,
  ) {
  }

  public function getFirstCommit(): ?string {
    return $this->firstCommit;
  }

  public function withFirstCommit(?string $commit): this {
    invariant($commit !== '', 'Pass null instead of empty string');
    return $this->modified($ret ==> $ret->firstCommit = $commit);
  }

  public function getSkippedSourceCommits(): ImmSet<string> {
    return $this->skippedSourceCommits;
  }

  public function withSkippedSourceCommits(ImmSet<string> $commits): this {
    return $this->modified($ret ==> $ret->skippedSourceCommits = $commits);
  }

  public function getPatchesDirectory(): ?string {
    return $this->patchesDirectory;
  }

  public function withPatchesDirectory(?string $dir): this {
    invariant($dir !== '', 'Pass null instead of empty string');
    return $this->modified($ret ==> $ret->patchesDirectory = $dir);
  }

  public function getDestinationRoots(): ImmSet<string> {
    return $this->destinationRoots;
  }

  public function withDestinationRoots(ImmSet<string> $roots): this {
    return $this->modified($ret ==> $ret->destinationRoots = $roots);
  }

  public function getSourceRoots(): ImmSet<string> {
    return $this->sourceRoots;
  }

  public function getFilter(
  ): (function(ShipItBaseConfig, ShipItChangeset): ShipItChangeset) {
    return $this->filter;
  }

  public function postFilterChangesets(
    ImmVector<ShipItChangeset> $changesets,
    ShipItRepo $dest,
  ): ImmVector<ShipItChangeset> {
    $post_filter_changesets = $this->postFilterChangesets;
    if ($post_filter_changesets === null) {
      return $changesets;
    }
    return $post_filter_changesets($changesets, $dest);
  }

  public function getStatsFilename(): ?string {
    return $this->statsFilename;
  }

  public function withStatsFilename(?string $filename): this {
    invariant($filename !== '', 'Pass null instead of empty string');
    return $this->modified($ret ==> $ret->statsFilename = $filename);
  }

  public function withAllowEmptyCommits(?bool $allow_empty_commit): this {
    return $this->modified(
      $ret ==> $ret->allowEmptyCommit = $allow_empty_commit,
    );
  }
  public function getAllowEmptyCommits(): bool {
    return $this->allowEmptyCommit !== null && $this->allowEmptyCommit;
  }

  private function modified<Tignored>(
    (function(ShipItSyncConfig): Tignored) $mutator,
  ): ShipItSyncConfig {
    $ret = clone $this;
    $mutator($ret);
    return $ret;
  }
}
