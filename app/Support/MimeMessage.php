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

    public function from(): ?string
    {
        return $this->addresses('from')[0] ?? null;
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
