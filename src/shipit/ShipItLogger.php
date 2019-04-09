<?hh // strict
/**
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

/**
 * This file was moved from fbsource to www. View old history in diffusion:
 * https://fburl.com/kljtdfab
 */
namespace Facebook\ShipIt;

use namespace HH\Lib\Str;

abstract final class ShipItLogger {

  public static function out(Str\SprintfFormatString $f, mixed ...$args): void {
    if (!\defined('\STDOUT')) {
      // No place to log to.
      return;
    }
    /* HH_IGNORE_ERROR[2049] __PHPStdLib */
    /* HH_IGNORE_ERROR[4107] __PHPStdLib */
    \fprintf(\STDOUT, (string) $f, ...$args);
  }

  public static function err(Str\SprintfFormatString $f, mixed ...$args): void {
    if (!\defined('\STDERR')) {
      // No place to log to.
      return;
    }
    /* HH_IGNORE_ERROR[2049] __PHPStdLib */
    /* HH_IGNORE_ERROR[4107] __PHPStdLib */
    \fprintf(\STDERR, (string) $f, ...$args);
  }
}
