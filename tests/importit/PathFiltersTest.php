<?hh // strict
/**
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

/**
 * This file was moved from fbsource to www. View old history in diffusion:
 * https://fburl.com/6kehzuiw
 */
namespace Facebook\ImportIt;


<<\Oncalls('open_source')>>
final class PathFiltersTest extends \Facebook\ShipIt\BaseTest {
  public function examplesForMoveDirectories(
  ): dict<
    string,
    (ImmMap<string, string>, ImmVector<string>, ImmVector<string>),
  > {
    return dict[
      'second takes precedence (first is more specific)' => tuple(
        ImmMap {
          'foo/public_tld/' => '',
          'foo/' => 'bar/',
        },
        ImmVector {'root_file', 'bar/bar_file'},
        ImmVector {'foo/public_tld/root_file', 'foo/bar_file'},
      ),
      'only one rule applied' => tuple(
        ImmMap {
          'foo/' => '',
          'bar/' => 'project_bar/',
        },
        ImmVector {
          'bar/part of project foo',
          'project_bar/part of project bar',
        },
        ImmVector {'foo/bar/part of project foo', 'bar/part of project bar'},
      ),
      'subdirectories' => tuple(
        ImmMap {
          'foo/test/' => 'testing/',
          'foo/' => '',
        },
        ImmVector {'testing/README', 'src.c'},
        ImmVector {'foo/test/README', 'foo/src.c'},
      ),
    ];
  }

  <<\DataProvider('examplesForMoveDirectories')>>
  public function testMoveDirectories(
    ImmMap<string, string> $map,
    ImmVector<string> $in,
    ImmVector<string> $expected,
  ): void {
    $changeset = (new \Facebook\ShipIt\ShipItChangeset())
      ->withDiffs($in->map($path ==> shape('path' => $path, 'body' => 'junk')));
    $changeset = ImportItPathFilters::moveDirectories($changeset, $map);
    \expect(vec($changeset->getDiffs()->map($diff ==> $diff['path'])))
      ->toBePHPEqual(vec($expected));
  }

  public function testMoveDirectoriesThrowsWithDuplciationMappings(): void {
    \expect(() ==> {
      $in = ImmVector {
        'does/not/matter',
      };
      $changeset = (new \Facebook\ShipIt\ShipItChangeset())
        ->withDiffs(
          $in->map($path ==> shape('path' => $path, 'body' => 'junk')),
        );
      ImportItPathFilters::moveDirectories(
        $changeset,
        ImmMap {
          'somewhere/' => '',
          'elsewhere/' => '',
        },
      );
    })
      // @oss-disable: ->toThrow(\InvariantViolationException::class);
    ->toThrow(\HH\InvariantException::class); // @oss-enable
  }
}
