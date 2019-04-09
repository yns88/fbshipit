<?hh // strict
/**
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

/**
 * This file was moved from fbsource to www. View old history in diffusion:
 * https://fburl.com/h7ixv5su
 */
namespace Facebook\ShipIt;


<<\Oncalls('open_source')>>
final class MessageSectionsTest extends BaseTest {
  public function examplesForGetSections(
  ): vec<(string, ?ImmSet<string>, ImmMap<string, string>)> {
    return vec[
      tuple(
        "Summary: Foo\nFor example: bar",
        ImmSet {'summary'},
        ImmMap {
          'summary' => "Foo\nFor example: bar",
        },
      ),
      tuple(
        "Summary: Foo\nTest plan: bar",
        ImmSet {'summary', 'test plan'},
        ImmMap {
          'summary' => 'Foo',
          'test plan' => 'bar',
        },
      ),
      tuple('Foo: bar', null, ImmMap {'foo' => 'bar'}),
      tuple('Foo: Bar: baz', ImmSet {'foo'}, ImmMap {'foo' => 'Bar: baz'}),
      tuple('Foo: Bar: baz', ImmSet {'bar'}, ImmMap {'' => 'Foo: Bar: baz'}),
      tuple('Foo: Bar: baz', ImmSet {'foo', 'bar'}, ImmMap {'bar' => 'baz'}),
    ];
  }

  <<\DataProvider('examplesForGetSections')>>
  public function testGetSections(
    string $message,
    ?ImmSet<string> $valid,
    ImmMap<string, string> $expected,
  ): void {
    $in = (new ShipItChangeset())->withMessage($message);
    $out = ShipItMessageSections::getSections($in, $valid);
    \expect($out->toImmMap())->toBePHPEqual($expected);
  }

  public function examplesForBuildMessage(
  ): vec<(ImmMap<string, string>, string)> {
    return vec[
      tuple(ImmMap {'foo' => 'bar'}, 'Foo: bar'),
      tuple(ImmMap {'foo' => "bar\nbaz"}, "Foo:\nbar\nbaz"),
      tuple(ImmMap {'foo bar' => 'herp derp'}, 'Foo Bar: herp derp'),
      tuple(ImmMap {'foo' => ''}, ''),
      tuple(
        ImmMap {'foo' => 'bar', 'herp' => 'derp'},
        "Foo: bar\n\nHerp: derp",
      ),
      tuple(ImmMap {'foo' => '', 'herp' => 'derp'}, "Herp: derp"),
    ];
  }

  <<\DataProvider('examplesForBuildMessage')>>
  public function testBuildMessage(
    ImmMap<string, string> $sections,
    string $expected,
  ): void {
    \expect(ShipItMessageSections::buildMessage($sections))->toBeSame(
      $expected,
    );
  }

  public function getExamplesForWhitespaceEndToEnd(): vec<(string, string)> {
    return vec[
      tuple("Summary: foo", 'Summary: foo'),
      tuple("Summary:\nfoo", 'Summary: foo'),
      tuple("Summary: foo\nbar", "Summary:\nfoo\nbar"),
      tuple("Summary:\nfoo\nbar", "Summary:\nfoo\nbar"),
    ];
  }

  <<\DataProvider('getExamplesForWhitespaceEndToEnd')>>
  public function testWhitespaceEndToEnd(string $in, string $expected): void {
    $message = (new ShipItChangeset())
      ->withMessage($in)
      |> ShipItMessageSections::getSections($$, ImmSet {'summary'})
      |> ShipItMessageSections::buildMessage($$->toImmMap());
    \expect($message)->toBeSame($expected);
  }
}
