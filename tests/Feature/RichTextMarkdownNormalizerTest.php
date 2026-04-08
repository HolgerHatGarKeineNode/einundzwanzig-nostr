<?php

use App\Support\RichTextMarkdownNormalizer;

beforeEach(function () {
    $this->normalizer = new RichTextMarkdownNormalizer;
});

it('returns null and empty values untouched', function () {
    expect($this->normalizer->normalize(null))->toBeNull();
    expect($this->normalizer->normalize(''))->toBe('');
    expect($this->normalizer->normalize('   '))->toBe('   ');
});

it('converts heading markdown wrapped in paragraph tags', function () {
    $html = '<p># EINUNDZWANZIG STANDUP</p><p>## Wer ich bin</p><p>Regular text.</p>';

    $result = $this->normalizer->normalize($html);

    expect($result)->toContain('<h1');
    expect($result)->toContain('EINUNDZWANZIG STANDUP');
    expect($result)->toContain('<h2');
    expect($result)->toContain('Wer ich bin');
    expect($result)->toContain('Regular text.');
});

it('converts bullet list markdown wrapped in paragraph tags', function () {
    $html = '<p>- first item</p><p>- second item</p><p>- third item</p>';

    $result = $this->normalizer->normalize($html);

    expect($result)->toContain('<ul>');
    expect($result)->toContain('first item');
    expect($result)->toContain('second item');
    expect($result)->toContain('third item');
    expect(substr_count($result, '<li>'))->toBe(3);
});

it('leaves structural html untouched when headings already exist', function () {
    $html = '<h1>Real heading</h1><p># not a heading</p>';

    expect($this->normalizer->normalize($html))->toBe($html);
});

it('leaves structural html untouched when list tags already exist', function () {
    $html = '<ul><li>existing</li></ul><p>- not a list</p>';

    expect($this->normalizer->normalize($html))->toBe($html);
});

it('leaves plain paragraph html untouched when it is not markdown', function () {
    $html = '<p>Just some normal text without any markdown syntax.</p>';

    expect($this->normalizer->normalize($html))->toBe($html);
});

it('renders pure plain text with paragraph breaks as html paragraphs', function () {
    $text = "First paragraph with some text.\n\nSecond paragraph follows.";

    $result = $this->normalizer->normalize($text);

    expect($result)->toContain('<p>First paragraph with some text.</p>');
    expect($result)->toContain('<p>Second paragraph follows.</p>');
});

it('renders plain text markdown (headings, lists, images) as html', function () {
    $text = "## Heading Two\n\nSome intro line.\n\n- first\n- second\n\n![alt](https://example.com/img.png)";

    $result = $this->normalizer->normalize($text);

    expect($result)->toContain('<h2');
    expect($result)->toContain('Heading Two');
    expect($result)->toContain('<ul>');
    expect($result)->toContain('<li>first</li>');
    expect($result)->toContain('<img');
    expect($result)->toContain('https://example.com/img.png');
});

it('is idempotent when re-run on already-rendered output', function () {
    $text = "## Heading\n\nBody text.";

    $first = $this->normalizer->normalize($text);
    $second = $this->normalizer->normalize($first);

    expect($second)->toBe($first);
});

it('preserves inline bold, code and links when converting pasted markdown', function () {
    $html = '<p><strong>Antragsteller:</strong> DrShift — <code>user@example.com</code></p>'
        .'<p><a href="https://example.com">Website</a></p>'
        .'<p># Heading</p>';

    $result = $this->normalizer->normalize($html);

    expect($result)->toContain('<h1');
    expect($result)->toContain('Heading');
    expect($result)->toContain('<strong>Antragsteller:</strong>');
    expect($result)->toContain('<code>user@example.com</code>');
    expect($result)->toContain('<a href="https://example.com">Website</a>');
});

it('preserves images embedded via img tags', function () {
    $html = '<p># Heading</p><p><img src="https://example.com/i.png" alt="caption"></p>';

    $result = $this->normalizer->normalize($html);

    expect($result)->toContain('<h1');
    expect($result)->toContain('<img');
    expect($result)->toContain('src="https://example.com/i.png"');
    expect($result)->toContain('alt="caption"');
});
