<?php

use CodeIgniter\HTTP\Header;
use CodeIgniter\CodeIgniter;

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex">
    <title><?php echo esc($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        slateGlass: 'rgba(15, 23, 42, 0.65)'
                    }
                }
            }
        };
    </script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-12 space-y-8">
        <header class="rounded-2xl border border-slate-800 bg-slate-900/70 shadow-xl p-6 space-y-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-sm uppercase tracking-widest text-slate-400">Unhandled Exception</p>
                    <h1 class="mt-2 text-3xl font-semibold text-white">
                        <?php echo esc($title), esc($exception->getCode() ? ' #' . $exception->getCode() : '') ?>
                    </h1>
                    <p class="mt-4 text-base text-slate-300 leading-relaxed">
                        <?php echo nl2br(esc($exception->getMessage())) ?>
                    </p>
                    <a href="https://www.duckduckgo.com/?q=<?php echo urlencode($title . ' ' . preg_replace('#\'.*\'|\".*\"#Us', '', $exception->getMessage())) ?>"
                       rel="noreferrer"
                       target="_blank"
                       class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-indigo-300 hover:text-indigo-200">
                        Search this error
                        <span aria-hidden="true">↗</span>
                    </a>
                </div>
                <div class="grid gap-3 text-sm sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Timestamp</p>
                        <p class="mt-1 font-mono text-slate-100"><?php echo esc(date('Y-m-d H:i:s')) ?></p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-slate-400">PHP</p>
                        <p class="mt-1 font-mono text-slate-100"><?php echo esc(PHP_VERSION) ?></p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-slate-400">CodeIgniter</p>
                        <p class="mt-1 font-mono text-slate-100"><?php echo esc(CodeIgniter::CI_VERSION) ?></p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900/80 px-4 py-3">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Environment</p>
                        <p class="mt-1 font-mono text-slate-100"><?php echo esc(ENVIRONMENT) ?></p>
                    </div>
                </div>
            </div>
        </header>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-lg overflow-hidden">
            <div class="border-b border-slate-800 bg-slate-900/70 px-6 py-4">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-300">Exception context</h2>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">File</p>
                        <p class="mt-2 font-mono text-sm text-slate-100 break-all"><?php echo esc(clean_path($file)) ?></p>
                    </div>
                    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Line</p>
                        <p class="mt-2 font-mono text-2xl text-rose-400"><?php echo esc($line) ?></p>
                    </div>
                </div>
                <?php if (is_file($file)) : ?>
                    <div class="space-y-3">
                        <p class="text-xs uppercase tracking-wide text-slate-400">Source preview</p>
                        <div class="overflow-auto rounded-xl border border-slate-800 bg-slate-950/70 shadow-inner">
                            <?php echo static::highlightFile($file, $line, 15); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php
        $previousExceptions = [];
        $last = $exception;
        while ($prevException = $last->getPrevious()) {
            $previousExceptions[] = $prevException;
            $last = $prevException;
        }
        ?>
        <?php if (! empty($previousExceptions)) : ?>
            <section class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-lg overflow-hidden">
                <div class="border-b border-slate-800 bg-slate-900/70 px-6 py-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-300">Previous Exceptions</h2>
                </div>
                <div class="p-6 space-y-4">
                    <?php foreach ($previousExceptions as $index => $prevException) : ?>
                        <details class="group rounded-xl border border-slate-800 bg-slate-900/80 p-4">
                            <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-100">
                                <span>#<?php echo $index + 1 ?> · <?php echo esc($prevException::class) ?> <?php echo esc($prevException->getCode() ? '#' . $prevException->getCode() : '') ?></span>
                                <span class="text-xs text-slate-400 transition-transform group-open:rotate-180">▾</span>
                            </summary>
                            <div class="mt-3 space-y-2 text-sm">
                                <p class="text-slate-300 leading-relaxed">
                                    <?php echo nl2br(esc($prevException->getMessage())) ?>
                                </p>
                                <div class="flex flex-wrap gap-3 text-xs font-mono text-slate-300">
                                    <span class="rounded-full border border-slate-800 bg-slate-900/70 px-3 py-1"><?php echo esc(clean_path($prevException->getFile())) ?></span>
                                    <span class="rounded-full border border-slate-800 bg-slate-900/70 px-3 py-1">Line <?php echo esc($prevException->getLine()) ?></span>
                                </div>
                                <a href="https://www.duckduckgo.com/?q=<?php echo urlencode($prevException::class . ' ' . preg_replace('#\'.*\'|\".*\"#Us', '', $prevException->getMessage())) ?>"
                                   rel="noreferrer"
                                   target="_blank"
                                   class="inline-flex items-center gap-2 text-xs font-medium text-indigo-300 hover:text-indigo-200">
                                    Search this exception
                                    <span aria-hidden="true">↗</span>
                                </a>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE) : ?>
            <section class="rounded-2xl border border-slate-800 bg-slate-900/60 shadow-lg overflow-hidden">
                <div class="border-b border-slate-800 bg-slate-900/70 px-6 py-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-300">Diagnostic data</h2>
                    <p class="mt-1 text-xs text-slate-400">Detailed stack trace and request context captured at the time of failure.</p>
                </div>
                <div class="p-6 space-y-6">

                    <details open class="group rounded-xl border border-slate-800 bg-slate-900/70 p-4">
                        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-100">
                            <span>Backtrace</span>
                            <span class="text-xs text-slate-400 transition-transform group-open:rotate-180">▾</span>
                        </summary>
                        <ol class="mt-4 space-y-4 text-sm">
                            <?php foreach ($trace as $index => $row) : ?>
                                <li class="rounded-lg border border-slate-800 bg-slate-900/80 p-4 space-y-3">
                                    <div class="flex items-start justify-between gap-4">
                                        <p class="font-mono text-slate-200">
                                            <?php if (isset($row['file']) && is_file($row['file'])) : ?>
                                                <?php
                                                if (isset($row['function']) && in_array($row['function'], ['include', 'include_once', 'require', 'require_once'], true)) {
                                                    echo esc($row['function'] . ' ' . clean_path($row['file']));
                                                } else {
                                                    echo esc(clean_path($row['file']) . ' : ' . $row['line']);
                                                }
                                                ?>
                                            <?php else : ?>
                                                {PHP internal code}
                                            <?php endif; ?>
                                        </p>
                                        <?php if (isset($row['class'])) : ?>
                                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php echo esc($row['class'] . $row['type'] . $row['function']) ?></span>
                                        <?php elseif (isset($row['function'])) : ?>
                                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-400"><?php echo esc($row['function']) ?>()</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (! empty($row['args'])) : ?>
                                        <details class="rounded-lg border border-slate-800 bg-slate-900/70 p-3">
                                            <summary class="cursor-pointer text-xs font-medium text-indigo-300">Arguments</summary>
                                            <div class="mt-2 overflow-x-auto">
                                                <table class="min-w-full divide-y divide-slate-800 text-xs">
                                                    <tbody class="divide-y divide-slate-800">
                                                        <?php
                                                        $params = null;
                                                        if (! str_ends_with($row['function'], '}')) {
                                                            $mirror = isset($row['class']) ? new ReflectionMethod($row['class'], $row['function']) : new ReflectionFunction($row['function']);
                                                            $params = $mirror->getParameters();
                                                        }
                                                        foreach ($row['args'] as $key => $value) : ?>
                                                            <tr>
                                                                <td class="whitespace-nowrap px-3 py-2 font-mono text-slate-300">
                                                                    <?php echo esc(isset($params[$key]) ? '$' . $params[$key]->name : "#{$key}") ?>
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <pre class="max-h-64 overflow-auto rounded bg-slate-950/80 p-3 text-[11px] leading-relaxed text-slate-200"><?php echo esc(print_r($value, true)) ?></pre>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </details>
                                    <?php endif; ?>

                                    <?php if (isset($row['file']) && is_file($row['file']) && isset($row['class'])) : ?>
                                        <div class="overflow-auto rounded-lg border border-slate-800 bg-slate-950/70">
                                            <?php echo static::highlightFile($row['file'], $row['line']); ?>
                                        </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </details>

                    <details class="group rounded-xl border border-slate-800 bg-slate-900/70 p-4">
                        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-100">
                            <span>Server</span>
                            <span class="text-xs text-slate-400 transition-transform group-open:rotate-180">▾</span>
                        </summary>
                        <div class="mt-4 space-y-6">
                            <?php foreach (['_SERVER', '_SESSION'] as $var) : ?>
                                <?php if (! empty($GLOBALS[$var]) && is_array($GLOBALS[$var])) : ?>
                                    <div class="space-y-3">
                                        <h3 class="text-xs uppercase tracking-wide text-slate-400">$<?php echo esc($var) ?></h3>
                                        <div class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/70">
                                            <table class="min-w-full divide-y divide-slate-800 text-xs">
                                                <thead class="bg-slate-900/80 text-slate-300">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left font-semibold">Key</th>
                                                        <th class="px-3 py-2 text-left font-semibold">Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-800 text-slate-200">
                                                    <?php foreach ($GLOBALS[$var] as $key => $value) : ?>
                                                        <tr>
                                                            <td class="px-3 py-2 font-mono text-slate-300"><?php echo esc($key) ?></td>
                                                            <td class="px-3 py-2">
                                                                <?php if (is_string($value)) : ?>
                                                                    <?php echo esc($value) ?>
                                                                <?php else : ?>
                                                                    <pre class="max-h-64 overflow-auto rounded bg-slate-950/60 p-3 text-[11px] leading-relaxed text-slate-200"><?php echo esc(print_r($value, true)) ?></pre>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php $constants = get_defined_constants(true); ?>
                            <?php if (! empty($constants['user'])) : ?>
                                <div class="space-y-3">
                                    <h3 class="text-xs uppercase tracking-wide text-slate-400">Constants</h3>
                                    <div class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/70">
                                        <table class="min-w-full divide-y divide-slate-800 text-xs">
                                            <thead class="bg-slate-900/80 text-slate-300">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold">Key</th>
                                                    <th class="px-3 py-2 text-left font-semibold">Value</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-800 text-slate-200">
                                                <?php foreach ($constants['user'] as $key => $value) : ?>
                                                    <tr>
                                                        <td class="px-3 py-2 font-mono text-slate-300"><?php echo esc($key) ?></td>
                                                        <td class="px-3 py-2">
                                                            <?php if (is_string($value)) : ?>
                                                                <?php echo esc($value) ?>
                                                            <?php else : ?>
                                                                <pre class="max-h-64 overflow-auto rounded bg-slate-950/60 p-3 text-[11px] leading-relaxed text-slate-200"><?php echo esc(print_r($value, true)) ?></pre>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>

                    <details class="group rounded-xl border border-slate-800 bg-slate-900/70 p-4">
                        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-100">
                            <span>Request</span>
                            <span class="text-xs text-slate-400 transition-transform group-open:rotate-180">▾</span>
                        </summary>
                        <div class="mt-4 space-y-4">
                            <?php $request = service('request'); ?>
                            <div class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/70">
                                <table class="min-w-full divide-y divide-slate-800 text-xs">
                                    <tbody class="divide-y divide-slate-800 text-slate-200">
                                        <tr>
                                            <td class="w-40 px-3 py-2 font-semibold text-slate-300">Path</td>
                                            <td class="px-3 py-2"><?php echo esc($request->getUri()) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="w-40 px-3 py-2 font-semibold text-slate-300">HTTP Method</td>
                                            <td class="px-3 py-2"><?php echo esc($request->getMethod()) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="w-40 px-3 py-2 font-semibold text-slate-300">IP Address</td>
                                            <td class="px-3 py-2"><?php echo esc($request->getIPAddress()) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="w-40 px-3 py-2 font-semibold text-slate-300">Is AJAX?</td>
                                            <td class="px-3 py-2"><?php echo $request->isAJAX() ? 'yes' : 'no' ?></td>
                                        </tr>
                                        <tr>
                                            <td class="w-40 px-3 py-2 font-semibold text-slate-300">Is CLI?</td>
                                            <td class="px-3 py-2"><?php echo $request->isCLI() ? 'yes' : 'no' ?></td>
                                        </tr>
                                        <tr>
                                            <td class="w-40 px-3 py-2 font-semibold text-slate-300">Is Secure?</td>
                                            <td class="px-3 py-2"><?php echo $request->isSecure() ? 'yes' : 'no' ?></td>
                                        </tr>
                                        <tr>
                                            <td class="w-40 px-3 py-2 font-semibold text-slate-300">User Agent</td>
                                            <td class="px-3 py-2"><?php echo esc($request->getUserAgent()->getAgentString()) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <?php $empty = true; ?>
                            <?php foreach (['_GET', '_POST', '_COOKIE'] as $var) : ?>
                                <?php if (! empty($GLOBALS[$var]) && is_array($GLOBALS[$var])) : ?>
                                    <?php $empty = false; ?>
                                    <div class="space-y-3">
                                        <h3 class="text-xs uppercase tracking-wide text-slate-400">$<?php echo esc($var) ?></h3>
                                        <div class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/70">
                                            <table class="min-w-full divide-y divide-slate-800 text-xs">
                                                <thead class="bg-slate-900/80 text-slate-300">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left font-semibold">Key</th>
                                                        <th class="px-3 py-2 text-left font-semibold">Value</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-800 text-slate-200">
                                                    <?php foreach ($GLOBALS[$var] as $key => $value) : ?>
                                                        <tr>
                                                            <td class="px-3 py-2 font-mono text-slate-300"><?php echo esc($key) ?></td>
                                                            <td class="px-3 py-2">
                                                                <?php if (is_string($value)) : ?>
                                                                    <?php echo esc($value) ?>
                                                                <?php else : ?>
                                                                    <pre class="max-h-64 overflow-auto rounded bg-slate-950/60 p-3 text-[11px] leading-relaxed text-slate-200"><?php echo esc(print_r($value, true)) ?></pre>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if ($empty) : ?>
                                <div class="rounded-lg border border-slate-800 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
                                    No $_GET, $_POST, or $_COOKIE information available.
                                </div>
                            <?php endif; ?>

                            <?php $headers = $request->headers(); ?>
                            <?php if (! empty($headers)) : ?>
                                <div class="space-y-3">
                                    <h3 class="text-xs uppercase tracking-wide text-slate-400">Headers</h3>
                                    <div class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/70">
                                        <table class="min-w-full divide-y divide-slate-800 text-xs">
                                            <thead class="bg-slate-900/80 text-slate-300">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold">Header</th>
                                                    <th class="px-3 py-2 text-left font-semibold">Value</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-800 text-slate-200">
                                                <?php foreach ($headers as $name => $value) : ?>
                                                    <tr>
                                                        <td class="px-3 py-2 font-mono text-slate-300"><?php echo esc($name, 'html') ?></td>
                                                        <td class="px-3 py-2">
                                                            <?php
                                                            if ($value instanceof Header) {
                                                                echo esc($value->getValueLine(), 'html');
                                                            } else {
                                                                foreach ($value as $i => $header) {
                                                                    echo ' (' . ($i + 1) . ') ' . esc($header->getValueLine(), 'html');
                                                                }
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>

                    <details class="group rounded-xl border border-slate-800 bg-slate-900/70 p-4">
                        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-100">
                            <span>Response</span>
                            <span class="text-xs text-slate-400 transition-transform group-open:rotate-180">▾</span>
                        </summary>
                        <div class="mt-4 space-y-4">
                            <?php
                                $response = service('response');
                                $response->setStatusCode(http_response_code());
                            ?>
                            <div class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/70">
                                <table class="min-w-full divide-y divide-slate-800 text-xs">
                                    <tbody class="divide-y divide-slate-800 text-slate-200">
                                        <tr>
                                            <td class="w-48 px-3 py-2 font-semibold text-slate-300">Response Status</td>
                                            <td class="px-3 py-2"><?php echo esc($response->getStatusCode() . ' - ' . $response->getReasonPhrase()) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <?php $headers = $response->headers(); ?>
                            <?php if (! empty($headers)) : ?>
                                <div class="space-y-3">
                                    <h3 class="text-xs uppercase tracking-wide text-slate-400">Headers</h3>
                                    <div class="overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/70">
                                        <table class="min-w-full divide-y divide-slate-800 text-xs">
                                            <thead class="bg-slate-900/80 text-slate-300">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold">Header</th>
                                                    <th class="px-3 py-2 text-left font-semibold">Value</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-800 text-slate-200">
                                                <?php foreach ($headers as $name => $value) : ?>
                                                    <tr>
                                                        <td class="px-3 py-2 font-mono text-slate-300"><?php echo esc($name, 'html') ?></td>
                                                        <td class="px-3 py-2">
                                                            <?php
                                                            if ($value instanceof Header) {
                                                                echo esc($response->getHeaderLine($name), 'html');
                                                            } else {
                                                                foreach ($value as $i => $header) {
                                                                    echo ' (' . ($i + 1) . ') ' . esc($header->getValueLine(), 'html');
                                                                }
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>

                    <details class="group rounded-xl border border-slate-800 bg-slate-900/70 p-4">
                        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-100">
                            <span>Included Files</span>
                            <span class="text-xs text-slate-400 transition-transform group-open:rotate-180">▾</span>
                        </summary>
                        <div class="mt-4">
                            <?php $files = get_included_files(); ?>
                            <ol class="space-y-2 text-xs font-mono text-slate-300 max-h-80 overflow-auto rounded-lg border border-slate-800 bg-slate-950/60 p-4">
                                <?php foreach ($files as $included) : ?>
                                    <li><?php echo esc(clean_path($included)) ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    </details>

                    <details class="group rounded-xl border border-slate-800 bg-slate-900/70 p-4">
                        <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-100">
                            <span>Memory</span>
                            <span class="text-xs text-slate-400 transition-transform group-open:rotate-180">▾</span>
                        </summary>
                        <div class="mt-4 overflow-x-auto rounded-lg border border-slate-800 bg-slate-950/70">
                            <table class="min-w-full divide-y divide-slate-800 text-xs">
                                <tbody class="divide-y divide-slate-800 text-slate-200">
                                    <tr>
                                        <td class="w-48 px-3 py-2 font-semibold text-slate-300">Memory Usage</td>
                                        <td class="px-3 py-2"><?php echo esc(static::describeMemory(memory_get_usage(true))) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="w-48 px-3 py-2 font-semibold text-slate-300">Peak Memory Usage</td>
                                        <td class="px-3 py-2"><?php echo esc(static::describeMemory(memory_get_peak_usage(true))) ?></td>
                                    </tr>
                                    <tr>
                                        <td class="w-48 px-3 py-2 font-semibold text-slate-300">Memory Limit</td>
                                        <td class="px-3 py-2"><?php echo esc(ini_get('memory_limit')) ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>
            </section>
        <?php endif; ?>
    </div>
</body>
</html>
