<?hh // strict
/**
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

/**
 * This file was moved from fbsource to www. View old history in diffusion:
 * https://fburl.com/xm1y32k1
 */
namespace Facebook\ShipIt;

abstract class BaseTest extends \Facebook\HackTest\HackTest { // @oss-enable
// @oss-disable: abstract class BaseTest extends \HackTest {
  protected static function diffsFromMap(
    ImmMap<string, string> $diffs,
  ): ImmVector<ShipItDiff> {
    return $diffs
      ->mapWithKey(($path, $body) ==> shape('path' => $path, 'body' => $body))
      ->toImmVector();
  }

  protected function execSteps(string $cwd, Container<string> ...$steps): void {
    foreach ($steps as $step) {
      /* HH_FIXME[4128] Use ShipItShellCommand */
      ShipItUtil::shellExec(
        $cwd,
        /* stdin = */ null,
        ShipItUtil::DONT_VERBOSE,
        ...$step,
      );
    }
  }

  static protected function invoke_static_bypass_visibility<T>(
    classname<T> $classname,
    string $method,
    mixed ...$args
  ): mixed {
    invariant(
      /* HH_IGNORE_ERROR[2049] __PHPStdLib */
      /* HH_IGNORE_ERROR[4107] __PHPStdLib */
      \method_exists($classname, $method),
      'Method "%s" does not exists on "%s"!',
      $method,
      $classname,
    );
    $rm = new \ReflectionMethod($classname, $method);
    invariant(
      $rm->isStatic(),
      '"%s" is not a static method on "%s"!',
      $method,
      $classname,
    );
    $rm->setAccessible(true);
    return $rm->invokeArgs(null, $args);
  }

  protected function configureGit(ShipItTempDir $temp_dir): void {
    $this->execSteps(
      $temp_dir->getPath(),
      vec['git', 'config', 'user.name', 'FBShipIt Unit Test'],
      vec['git', 'config', 'user.email', 'fbshipit@example.com'],
    );
  }

  protected function configureHg(ShipItTempDir $temp_dir): void {
    /* HH_IGNORE_ERROR[2049] __PHPStdLib */
    /* HH_IGNORE_ERROR[4107] __PHPStdLib */
    \file_put_contents(
      $temp_dir->getPath().'/.hg/hgrc',
      '[ui]
username = FBShipIt Unit Test <fbshipit@example.com>',
    );
  }
}
