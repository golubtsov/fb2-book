<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Nigo\Fb2Book\Fb2Book;

/**
 * Split plain text into FB2 sections.
 *
 * Supported title formats:
 * - Markdown headings: "# Chapter one", "## Part two"
 * - Simple chapter lines: "Chapter 1", "Chapter One", "Part 2"
 * - Short uppercase lines: "INTRODUCTION"
 *
 * Paragraphs are separated by one or more empty lines. Single newlines inside
 * a paragraph are normalized by Fb2Book::addSection().
 *
 * @return list<array{title: string, text: string}>
 */
function parseTextToSections(string $text): array
{
    $lines = preg_split('/\R/u', trim($text)) ?: [];
    $sections = [];
    $currentTitle = '';
    $currentText = [];

    $flushSection = static function () use (&$sections, &$currentTitle, &$currentText): void {
        $body = trim(implode("\n", $currentText));

        if ($currentTitle === '' && $body === '') {
            return;
        }

        $sections[] = [
            'title' => $currentTitle,
            'text' => $body,
        ];

        $currentTitle = '';
        $currentText = [];
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if (isSectionTitle($trimmed)) {
            $flushSection();
            $currentTitle = normalizeSectionTitle($trimmed);
            continue;
        }

        $currentText[] = $line;
    }

    $flushSection();

    if ($sections === []) {
        return [
            [
                'title' => '',
                'text' => trim($text),
            ],
        ];
    }

    return $sections;
}

function isSectionTitle(string $line): bool
{
    if ($line === '') {
        return false;
    }

    if (preg_match('/^#{1,6}\s+\S/u', $line) === 1) {
        return true;
    }

    if (preg_match('/^(chapter|part)\s+[\p{L}\p{N}]+/iu', $line) === 1) {
        return true;
    }

    return strlen($line) <= 120
        && preg_match('/\p{Ll}/u', $line) !== 1
        && preg_match('/\p{L}/u', $line) === 1;
}

function normalizeSectionTitle(string $line): string
{
    return trim(preg_replace('/^#{1,6}\s+/u', '', $line) ?? $line);
}

$inputPath = $argv[1] ?? null;
$outputPath = $argv[2] ?? __DIR__ . '/text-book.fb2';

$text = $inputPath !== null
    ? file_get_contents($inputPath)
    : <<<'TEXT'
# Introduction

This is the first paragraph of the introduction.
This line is still part of the same paragraph.

This is the second paragraph.

# Chapter one

This is chapter one.

It has another paragraph.

CHAPTER TWO

This title is detected because it is a short uppercase line.
TEXT;

if ($text === false) {
    throw new RuntimeException(sprintf('Unable to read input text: %s', $inputPath));
}

$fb2book = Fb2Book::make()
    ->setTitle('Generated FB2 Book')
    ->setAuthor('First Name', 'Last Name')
    ->setBookLang('en')
    ->setGenre('prose_contemporary')
    ->setDate(date('Y'), date('Y-m-d'))
    ->setDocumentInfo('generated-fb2-book', 1);

foreach (parseTextToSections($text) as $section) {
    $fb2book->addSection($section['text'], $section['title']);
}

$bytes = $fb2book->save($outputPath);

echo sprintf("Saved %s bytes to %s\n", $bytes, $outputPath);
