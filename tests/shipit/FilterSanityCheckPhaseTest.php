<?hh // strict
/**
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

/**
 * This file was moved from fbsource to www. View old history in diffusion:
 * https://fburl.com/beb4xz2n
 */
namespace Facebook\ShipIt;

use namespace HH\Lib\Str;


<<\Oncalls('open_source')>>
final class FilterSanityCheckPhaseTest extends BaseTest {
  public function testAllowsValidCombination(): void {
    $phase = new ShipItFilterSanityCheckPhase(
      $changeset ==> $changeset->withDiffs(
        $changeset->getDiffs()
          ->filter($diff ==> Str\slice($diff['path'], 0, 4) === 'foo/'),
      ),
    );
    $phase->assertValid(ImmSet {'foo/'});
    // no exception thrown :)
  }

  public function exampleEmptyRoots(): dict<string, vec<ImmSet<string>>> {
    return dict[
      'empty set' => vec[ImmSet {}],
      'empty string' => vec[ImmSet {''}],
      '.' => vec[ImmSet {'.'}],
      './' => vec[ImmSet {'./'}],
    ];
  }

  <<\DataProvider('exampleEmptyRoots')>>
  public function testAllowsIdentityFunctionForEmptyRoots(
    ImmSet<string> $roots,
  ): void {
    $phase = new ShipItFilterSanityCheckPhase($changeset ==> $changeset);
    $phase->assertValid($roots);
    // no exception thrown :)
  }

  public function testThrowsForIdentityFunctionWithRoots(): void {
    \expect(() ==> {
      $phase = new ShipItFilterSanityCheckPhase(
        $changeset ==> $changeset, // stuff outside of 'foo' should be removed
      );
      $phase->assertValid(ImmSet {'foo/'});
    })
      // @oss-disable: ->toThrow(\InvariantViolationException::class);
    ->toThrow(\HH\InvariantException::class); // @oss-enable
  }

  public function testThrowsForEmptyChangeset(): void {
    \expect(() ==> {
      $phase = new ShipItFilterSanityCheckPhase(
        $_changeset ==> (new ShipItChangeset()),
      );
      $phase->assertValid(ImmSet {'foo/'});
    })
      // @oss-disable: ->toThrow(\InvariantViolationException::class);
    ->toThrow(\HH\InvariantException::class); // @oss-enable
  }

  public function testThrowsForPartialMatch(): void {
    \expect(() ==> {
      $phase = new ShipItFilterSanityCheckPhase(
        $changeset ==> $changeset->withDiffs(
          $changeset->getDiffs()
            ->filter($diff ==> Str\slice($diff['path'], 0, 3) === 'foo'),
        ),
      );
      $phase->assertValid(ImmSet {'foo/', 'herp/'});
    })
      // @oss-disable: ->toThrow(\InvariantViolationException::class);
    ->toThrow(\HH\InvariantException::class); // @oss-enable
  }
}
