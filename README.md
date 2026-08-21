# php_wex

Version: 0.0.3

`wexample/php-wex` is a Composer library that lets PHP applications invoke `wex` commands as subprocesses and read their structured output. It sends the command address and arguments to the `wex` binary — or to a per-app `.wex/bin/app-manager` shim when targeting another project — asks `wex` to write its JSON response to a private temporary file, and returns the decoded payload as a `WexResult`. PHP backends and suites that need to drive `wex`-managed services programmatically, without parsing stdout, are the intended consumers.

## Table of Contents

- [Architecture](#architecture)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Architecture

The library is a thin PHP bridge to the `wex` Python CLI. It never reimplements CLI logic: it runs `wex` as a subprocess and deserialises the response. The source lives entirely under `src/`, split into four namespaces.

### `Common/` — public surface

Three classes form the public API.

**src/Common/WexClient.php** is the only entry point callers touch. It holds the binary path, working directory, and optional timeout. Two constructors cover the two use-cases: the default one targets the global `wex` binary found on `PATH`; `WexClient::forApp(string $appPath)` targets a specific app's `.wex/bin/app-manager` shim, which is how a suite drives its sub-packages without caring which `wex` version the app pins.

**src/Common/CommandAddress.php** is a value object that carries the structured identity of one wex command: its `CommandType`, an optional scope (addon or service name), a group, and a name. `CommandAddress::fromString()` parses the four surface syntaxes (`addon::group/name`, `.group/name`, `@service::group/name`, `~group/name`). `fromFunctionName()` parses the Python double-underscore form (`addon__group__name`). `toString()` reconstructs the CLI form. The class is `readonly`; it never mutates.

**src/Common/ShellResult.php** and **src/Common/WexResult.php** are the two result types. `ShellResult` holds the raw subprocess outcome: args, cwd, return code, stdout, stderr, start and end timestamps. `WexResult` extends it with the request ID and the decoded JSON payload (`mixed $output`). Callers check `$result->hasOutput()` before reading `$result->output`.

### `Helper/` — stateless utilities

**src/Helper/ShellHelper.php** runs the subprocess. It calls `proc_open()` directly — no shell wrapper, so arguments never need escaping. When `$inheritStdio` is false, stdout and stderr are captured via a non-blocking `stream_select()` loop that reads in 8 KB chunks. When `$inheritStdio` is true, the process inherits the parent's file descriptors and output goes straight to the terminal. Timeout is enforced in both modes: if `microtime(true) - $startTime` exceeds the limit, `proc_terminate()` is called and `ProcessFailedException` is thrown.

**src/Helper/RequestHelper.php** owns the request lifecycle around the output file. `generateId()` produces a timestamp+PID string matching wex's own format (`YYYYmmdd-HHMMSS-ffffff-<pid>`). `createOutputDirectory()` creates a private temp directory (`sys_get_temp_dir()/wex-<random-hex>`, mode `0700`) so that response data — which may carry secrets — is never readable by other users. `discardOutputDirectory()` removes the directory and every file it contains once the caller has read the response.

**src/Helper/WorkdirHelper.php** builds paths inside an app's `.wex/` directory. `workdirPath()` appends `/.wex` to a given `$cwd`; `appManagerPath()` further appends `/bin/app-manager`. These are the only two places in the library that know the internal layout of a wex app directory.

### `Const/` — shared constants and enums

**src/Const/Globals.php** holds every string constant that appears in more than one file: the core command name (`wex`), directory and file names (`.wex`, `bin`, `app-manager`), command separator characters (`::`, `/`, `__`), and all CLI option flags (`--force-request-id`, `--output-format`, `--output-target`, `--output-file`, `--subprocess`).

**src/Const/CommandType.php** is a backed enum with four cases: `ADDON`, `APP`, `SERVICE`, `USER`. Each case provides its regex `pattern()` (used by `CommandAddress::fromString()`), its CLI `prefix()` character, and `hasScope()` indicating whether the type carries a scope segment.

**src/Const/OutputFormat.php** and **src/Const/OutputTarget.php** are small backed enums. `OutputFormat` lists `str`, `json`, and `yaml`. `OutputTarget` lists `stdout`, `file`, and `none`. `WexClient::run()` always passes `json` and `file`.

### `Exceptions/` — error hierarchy

All exceptions extend **src/Exceptions/WexException.php**, itself a `RuntimeException`. Three subclasses cover the failure modes: **src/Exceptions/InvalidCommandAddressException.php** when a command string cannot be parsed, **src/Exceptions/WexBinaryNotFoundException.php** when an explicit binary path is not executable, and **src/Exceptions/ProcessFailedException.php** for subprocess failures (unable to start, timed out, unable to create the output directory).

### Call path through `WexClient::run()`

1. `CommandAddress::fromString()` (or the caller's existing `CommandAddress`) validates and structures the command.
2. `RequestHelper::generateId()` produces a unique ID; `RequestHelper::createOutputDirectory()` creates the private temp dir.
3. `WexClient::execute()` forwards to `ShellHelper::run()` with the assembled argument list: `--force-request-id <id> --output-format json --output-target file --output-file <dir>/<id> --subprocess <address> [...extra args]`. The subprocess writes its JSON response to `<dir>/<id>` and its human-readable output to stdout.
4. `ShellHelper::run()` returns a `ShellResult` once the process exits (or times out).
5. Back in `run()`, `readOutput()` reads and `json_decode()`s the response file. The temp directory is removed in a `finally` block regardless of outcome.
6. `WexResult::fromShellResult()` merges the shell result, request ID, and decoded output into the returned `WexResult`.

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.2
- wexample/php-helpers: >=2.0.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
