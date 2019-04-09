<?hh // strict
/**
 * Copyright (c) Facebook, Inc. and its affiliates.
 *
 * This source code is licensed under the MIT license found in the
 * LICENSE file in the root directory of this source tree.
 */

/**
 * This file was moved from fbsource to www. View old history in diffusion:
 * https://fburl.com/8yredn7r
 */
namespace Facebook\ShipIt;

use namespace HH\Lib\{Str, Math};

class ShipItPhaseRunner {
  public function __construct(
    protected ShipItBaseConfig $config,
    protected ImmVector<ShipItPhase> $phases,
  ) {}

  public function run(): void {
    $this->parseCLIArguments();
    foreach ($this->phases as $phase) {
      $phase->run($this->config);
    }
  }

  protected function getBasicCLIArguments(): ImmVector<ShipItCLIArgument> {
    return ImmVector {
      shape(
        'short_name' => 'h',
        'long_name' => 'help',
        'description' => 'show this help message and exit',
      ),
      shape(
        'long_name' => 'base-dir::',
        'description' => 'Path to store repositories',
        'write' => $x ==> $this->config = $this->config
          ->withBaseDirectory(Str\trim($x)),
      ),
      shape(
        'long_name' => 'temp-dir::',
        'replacement' => 'base-dir',
        'write' => $x ==> $this->config = $this->config
          ->withBaseDirectory(Str\trim($x)),
      ),
      shape(
        'long_name' => 'source-repo-dir::',
        'description' => 'path to fetch source from',
        'write' => $x ==> $this->config = $this->config
          ->withSourcePath(Str\trim($x)),
      ),
      shape(
        'long_name' => 'destination-repo-dir::',
        'description' => 'path to push filtered changes to',
        'write' => $x ==> $this->config = $this->config
          ->withDestinationPath(Str\trim($x)),
      ),
      shape(
        'long_name' => 'source-branch::',
        'description' => "Branch to sync from",
        'write' => $x ==> $this->config = $this->config
          ->withSourceBranch(Str\trim($x)),
      ),
      shape(
        'long_name' => 'src-branch::',
        'replacement' => 'source-branch',
        'write' => $x ==> $this->config = $this->config
          ->withSourceBranch(Str\trim($x)),
      ),
      shape(
        'long_name' => 'destination-branch::',
        'description' => 'Branch to sync to',
        'write' => $x ==> $this->config = $this->config
          ->withDestinationBranch(Str\trim($x)),
      ),
      shape(
        'long_name' => 'dest-branch::',
        'replacement' => 'destination-branch',
        'write' => $x ==> $this->config = $this->config
          ->withDestinationBranch(Str\trim($x)),
      ),
      shape(
        'long_name' => 'debug',
        'replacement' => 'verbose',
      ),
      shape(
        'long_name' => 'skip-project-specific',
        'description' => 'Skip anything project-specific',
        'write' => $_ ==> $this->config = $this->config
          ->withProjectSpecificPhasesDisabled(),
      ),
      shape(
        'short_name' => 'v',
        'long_name' => 'verbose',
        'description' => 'Give more verbose output',
        'write' => $_ ==> $this->config = $this->config->withVerboseEnabled(),
      ),
    };
  }

  final protected function getCLIArguments(): ImmVector<ShipItCLIArgument> {
    $args = $this->getBasicCLIArguments()->toVector();
    foreach ($this->phases as $phase) {
      $args->addAll($phase->getCLIArguments());
    }

    // Check for correctness
    foreach ($args as $arg) {
      $description = Shapes::idx($arg, 'description');
      $replacement = Shapes::idx($arg, 'replacement');
      $handler = Shapes::idx($arg, 'write');
      $name = $arg['long_name'];

      invariant(
        !($description !== null && $replacement !== null),
        '--%s is documented and deprecated',
        $name,
      );

      invariant(
        !(
          $handler !== null && !($description !== null || $replacement !== null)
        ),
        '--%s does something, and is undocumented',
        $name,
      );
    }

    return $args->toImmVector();
  }

  final protected function parseOptions(
    ImmVector<ShipItCLIArgument> $config,
    dict<string, mixed> $raw_opts,
  ): void {
    foreach ($config as $opt) {
      $is_optional = Str\slice($opt['long_name'], -2) === '::';
      $is_required = !$is_optional && Str\slice($opt['long_name'], -1) === ':';
      $is_bool = !$is_optional && !$is_required;
      $short = Str\trim_right(Shapes::idx($opt, 'short_name', ''), ':');
      $long = Str\trim_right($opt['long_name'], ':');

      /* HH_IGNORE_ERROR[2049] __PHPStdLib */
      /* HH_IGNORE_ERROR[4107] __PHPStdLib */
      if ($short is nonnull && \array_key_exists($short, $raw_opts)) {
        $key = '-'.$short;
        $value = $is_bool ? true : $raw_opts[$short];
        /* HH_IGNORE_ERROR[2049] __PHPStdLib */
        /* HH_IGNORE_ERROR[4107] __PHPStdLib */
      } else if (\array_key_exists($long, $raw_opts)) {
        $key = '--'.$long;
        $value = $is_bool ? true : $raw_opts[$long];
      } else {
        $key = null;
        $value = $is_bool ? false : '';
        $have_value = false;
        $isset_func = Shapes::idx($opt, 'isset');
        if ($isset_func) {
          $have_value = $isset_func();
        }

        if ($is_required && !$have_value) {
          echo "ERROR: Expected --".$long."\n\n";
          self::printHelp($config);
          exit(1);
        }
      }

      $handler = Shapes::idx($opt, 'write');
      if ($handler && $value !== '' && $value !== false) {
        $handler((string)$value);
      }

      if ($key === null) {
        continue;
      }

      /* HH_FIXME[4089] sketchy null check */
      /* HH_FIXME[4276] invalid truthiness test */
      $deprecated = !Shapes::idx($opt, 'description');
      if (!$deprecated) {
        continue;
      }

      $replacement = Shapes::idx($opt, 'replacement');
      if ($replacement !== null) {
        ShipItLogger::err(
          "%s %s, use %s instead\n",
          $key,
          $handler ? 'is deprecated' : 'has been removed',
          $replacement,
        );
        if ($handler === null) {
          exit(1);
        }
      } else {
        invariant(
          $handler === null,
          "Option '%s' is not a no-op, is undocumented, and doesn't have a ".
          'documented replacement.',
          $key,
        );
        ShipItLogger::err( "%s is deprecated and a no-op\n", $key);
      }
    }
  }

  protected function parseCLIArguments(): void {
    $config = $this->getCLIArguments();
    /* HH_IGNORE_ERROR[2049] __PHPStdLib */
    /* HH_IGNORE_ERROR[4107] __PHPStdLib */
    $raw_opts = \getopt(
      Str\join($config->map($opt ==> Shapes::idx($opt, 'short_name', '')), ''),
      $config->map($opt ==> $opt['long_name']),
    )
      |> dict($$);
    /* HH_IGNORE_ERROR[2049] __PHPStdLib */
    /* HH_IGNORE_ERROR[4107] __PHPStdLib */
    if (\array_key_exists('h', $raw_opts) ||
        /* HH_IGNORE_ERROR[2049] __PHPStdLib */
        /* HH_IGNORE_ERROR[4107] __PHPStdLib */
        \array_key_exists('help', $raw_opts)) {
      self::printHelp($config);
      exit(0);
    }
    $this->parseOptions($config, $raw_opts);
  }

  protected static function printHelp(
    ImmVector<ShipItCLIArgument> $config,
  ): void {
    /* HH_FIXME[2050] Previously hidden by unsafe_expr */
    $filename = $_SERVER['SCRIPT_NAME'];
    $max_left = 0;
    $rows = Map {};
    foreach ($config as $opt) {
      $description = Shapes::idx($opt, 'description');
      if ($description === null) {
        $replacement = Shapes::idx($opt, 'replacement');
        if ($replacement !== null) {
          continue;
        } else {
          invariant(
            !Shapes::idx($opt, 'write'),
            '--%s is undocumented, does something, and has no replacement',
            $opt['long_name'],
          );
          $description = 'deprecated, no-op';
        }
      }

      $short = Shapes::idx($opt, 'short_name');
      $long = $opt['long_name'];
      $is_optional = Str\slice($long, -2) === '::';
      $is_required = !$is_optional && Str\slice($long, -1) === ':';
      $long = Str\trim_right($long, ':');
      $prefix = $short !== null ? '-'.Str\trim_right($short, ':').', ' : '';
      $suffix = $is_optional ? "=VALUE" : ($is_required ? "=$long" : '');
      $left = '  '.$prefix.'--'.$long.$suffix;
      $max_left = Math\maxva(Str\length($left), $max_left);

      $rows[$long] = tuple($left, $description);
    }
    /* HH_IGNORE_ERROR[2049] __PHPStdLib */
    /* HH_IGNORE_ERROR[4107] __PHPStdLib */
    \ksort(&$rows);

    $help = $rows['help'];
    $rows->removeKey('help');
    $rows = (Map {'help' => $help})->setAll($rows);

    $opt_help = Str\join(
      $rows->map(
        $row ==> /* HH_IGNORE_ERROR[2049] __PHPStdLib */
      /* HH_IGNORE_ERROR[4107] __PHPStdLib */
      Str\format("%s  %s\n", \str_pad($row[0], $max_left), $row[1]),
      ),
      "",
    );
    echo <<<EOF
Usage:
${filename} [options]

Options:
${opt_help}

EOF;
  }
}
