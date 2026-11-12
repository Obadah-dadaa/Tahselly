<?php

namespace App\Support;

/**
 * Just enough of a raw email (RFC 5322 / MIME) to read who sent it, who it was for and its attached files.
 */
final class MimeMessage
{
    /**
     * @param  array<string, list<string>>  $headers  lower-cased name => values
     */
    private function __construct(private array $headers, private string $body) {}

    public static function parse(string $raw): self
    {
        $raw = str_replace("\r\n", "\n", $raw);
        [$head, $body] = array_pad(explode("\n\n", $raw, 2), 2, '');

        $headers = [];
        // Unfold continuation lines, then split "Name: value"
        foreach (explode("\n", preg_replace('/\n[ \t]+/', ' ', $head)) as $line) {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))][] = trim($value);
            }
        }

        return new self($headers, $body);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)][0] ?? null;
    }

    /**
     * Every email address in the given headers.
     *
     * @return list<string>
     */
    public function addresses(string ...$names): array
    {
        $found = [];
        foreach ($names as $name) {
            foreach ($this->headers[strtolower($name)] ?? [] as $value) {
                preg_match_all('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $value, $m);
                array_push($found, ...array_map('strtolower', $m[0]));
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * The single sender address (the one in angle brackets when there is a display name); null when the
     * header is missing, ambiguous or names more than one mailbox.
     */
    public function from(): ?string
    {
        $headers = $this->headers['from'] ?? [];
        if (count($headers) !== 1) {
            return null;
        }
        // Comments and escapes make addresses ambiguous between parsers: refuse them instead of guessing
        if (preg_match('/[()\\\\]/', $headers[0])) {
            return null;
        }
        // Drop a quoted display name so an address written inside it cannot pose as the sender
        $value = preg_replace('/"[^"]*"/', '', $headers[0]);
        if (str_contains($value, '"') || str_contains($value, ',') || preg_match_all('/<([^<>]*)>/', $value, $m) > 1) {
            return null;
        }
        $address = trim($m[1][0] ?? $value);
        $outside = isset($m[0][0]) ? str_replace($m[0][0], '', $value) : '';
        if (str_contains($outside, '@')) {
            return null;
        }

        return preg_match('/^[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}$/i', $address) ? strtolower($address) : null;
    }

    /**
     * The sender, only when the receiving server vouches for it: its own (topmost) Authentication-Results
     * header shows DMARC passing for the From domain, or DKIM passing for that same domain. Fails closed.
     */
    public function authenticatedFrom(string $authserv): ?string
    {
        $from = $this->from();
        $results = array_map(self::authResults(...), $this->headers['authentication-results'] ?? []);
        $trusted = fn (array $r) => $authserv !== '' && ($r['server'] === $authserv || str_ends_with($r['server'], '.'.$authserv));
        // The receiving server prepends its own header, so it must be the topmost; a second header claiming
        // to be from it can only have been written by the sender
        if (! $from || ! $results || ! $trusted($results[0]) || count(array_filter($results, $trusted)) > 1) {
            return null;
        }
        $domain = substr($from, strpos($from, '@') + 1);
        $aligned = fn (?string $d) => $d !== null && ($d === $domain || str_ends_with($domain, '.'.$d));

        $verified = false;
        foreach ($results[0]['methods'] as $method) {
            if ($method['method'] === 'dmarc' && $method['result'] !== 'pass' && $method['result'] !== 'none') {
                return null; // the server judged the From domain forged
            }
            $verified = $verified || ($method['result'] === 'pass' && match ($method['method']) {
                'dmarc' => $aligned($method['props']['header.from'] ?? null),
                'dkim' => $aligned($method['props']['header.d'] ?? null),
                default => false,
            });
        }

        return $verified ? $from : null;
    }

    /**
     * Parse one Authentication-Results header (RFC 8601): the server id, then "method=result prop=value..."
     * per ";"-separated statement. Comments are removed first; quoted values are kept whole.
     *
     * @return array{server: string, methods: list<array{method: string, result: string, props: array<string, string>}>}
     */
    private static function authResults(string $header): array
    {
        // Escapes or unbalanced quotes and comments read differently between parsers: trust nothing in them
        if (str_contains($header, '\\') || substr_count($header, '"') % 2 || substr_count($header, '(') !== substr_count($header, ')')) {
            return ['server' => '', 'methods' => []];
        }
        $plain = '';
        $depth = 0;
        $quoted = false;
        foreach (mb_str_split($header) as $char) {
            if ($char === '"' && $depth === 0) {
                $quoted = ! $quoted;
            } elseif (! $quoted && $char === '(') {
                $depth++;

                continue;
            } elseif (! $quoted && $char === ')' && $depth > 0) {
                $depth--;

                continue;
            }
            if ($depth === 0) {
                $plain .= $quoted && $char === ';' ? "\0" : $char;
            }
        }

        $statements = array_map('trim', explode(';', strtolower($plain)));
        $server = preg_split('/\s+/', array_shift($statements))[0] ?? '';
        $methods = [];
        foreach ($statements as $statement) {
            $tokens = preg_split('/\s+/', $statement, -1, PREG_SPLIT_NO_EMPTY);
            if (! $tokens || ! preg_match('/^([a-z0-9\-]+)=([a-z]+)$/', array_shift($tokens), $m)) {
                continue;
            }
            $props = [];
            foreach ($tokens as $token) {
                if (preg_match('/^((?:header|body|smtp|policy)\.[a-z0-9\-]+)=(.+)$/', $token, $p)) {
                    $props[$p[1]] ??= trim($p[2], '"');
                }
            }
            $methods[] = ['method' => $m[1], 'result' => $m[2], 'props' => $props];
        }

        return ['server' => $server, 'methods' => $methods];
    }

    public function subject(): string
    {
        return self::decodeWords((string) $this->header('subject'));
    }

    /**
     * Attached (or inline) files with a name.
     *
     * @return list<array{filename: string, content: string}>
     */
    public function attachments(): array
    {
        return $this->walk($this->headers, $this->body);
    }

    /**
     * @param  array<string, list<string>>  $headers
     * @return list<array{filename: string, content: string}>
     */
    private function walk(array $headers, string $body, int $depth = 0): array
    {
        [$type, $params] = self::parameters($headers['content-type'][0] ?? 'text/plain');

        if (str_starts_with($type, 'multipart/') && isset($params['boundary']) && $depth < 10) {
            $files = [];
            $parts = explode('--'.$params['boundary'], $body);
            // Drop the preamble and everything after the closing boundary
            foreach (array_slice($parts, 1) as $part) {
                if (str_starts_with($part, '--')) {
                    break;
                }
                $part = ltrim($part, "\n");
                $inner = self::parse($part);
                array_push($files, ...$this->walk($inner->headers, $inner->body, $depth + 1));
            }

            return $files;
        }

        [, $disposition] = self::parameters($headers['content-disposition'][0] ?? '');
        $filename = $disposition['filename'] ?? $params['name'] ?? null;
        if ($filename === null || $filename === '') {
            return [];
        }

        $content = match (strtolower(trim($headers['content-transfer-encoding'][0] ?? ''))) {
            'base64' => (string) base64_decode(preg_replace('/\s+/', '', $body)),
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };

        return [['filename' => basename(str_replace('\\', '/', self::decodeWords($filename))), 'content' => $content]];
    }

    /**
     * Split "type/sub; a=b; c*=UTF-8''x" into the value and its parameters (RFC 2231 names decoded).
     *
     * @return array{0: string, 1: array<string, string>}
     */
    private static function parameters(string $value): array
    {
        $pieces = explode(';', $value, 2);
        $params = [];
        preg_match_all('/([\w*\-]+)\s*=\s*(?:"((?:[^"\\\\]|\\\\.)*)"|([^;\s]+))/', $pieces[1] ?? '', $m, PREG_SET_ORDER);
        $continued = [];
        foreach ($m as $match) {
            $name = strtolower($match[1]);
            $val = ($match[2] ?? '') !== '' ? stripslashes($match[2]) : ($match[3] ?? '');
            if (preg_match('/^(.+?)\*(\d+)?\*?$/', $name, $parts)) {
                // filename*=UTF-8''%D9%81 or filename*0*=...; filename*1*=...
                $continued[$parts[1]][(int) ($parts[2] ?? 0)] = $val;

                continue;
            }
            $params[$name] = $val;
        }
        foreach ($continued as $name => $chunks) {
            ksort($chunks);
            $joined = implode('', $chunks);
            if (preg_match("/^([\w\-]+)'[^']*'(.*)$/", $joined, $enc)) {
                $joined = mb_convert_encoding(rawurldecode($enc[2]), 'UTF-8', $enc[1]);
            }
            $params[$name] = $joined;
        }

        return [strtolower(trim($pieces[0])), $params];
    }

    private static function decodeWords(string $value): string
    {
        if (! str_contains($value, '=?')) {
            return $value;
        }
        $decoded = function_exists('iconv_mime_decode') ? @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8') : false;

        return $decoded !== false ? $decoded : mb_decode_mimeheader($value);
    }
}
