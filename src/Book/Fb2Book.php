<?php

namespace Nigo\Fb2Book;

use DOMDocument;
use DOMElement;

class Fb2Book
{
    private string $authorFirstName = '';

    private string $authorLastName = '';

    private string $annotation = '';

    private string $lang = '';

    private string $title = '';

    /**
     * @var array<int, array{title: string, text: string}>
     */
    private array $sections = [];

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setAuthor(string $firstName = '', string $lastName = ''): static
    {
        $this->authorFirstName = $firstName;
        $this->authorLastName = $lastName;

        return $this;
    }

    public function setBookLang(string $lang): static
    {
        $this->lang = $lang;

        return $this;
    }

    public function setAnnotation(string $annotation): static
    {
        $this->annotation = $annotation;

        return $this;
    }

    public function addSection(string $text, string $title = ''): static
    {
        $this->sections[] = [
            'title' => $title,
            'text' => $text,
        ];

        return $this;
    }

    public function create(): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = false;

        $root = $document->createElement('FictionBook');
        $root->setAttribute('xmlns', 'http://www.gribuser.ru/xml/fictionbook/2.0');
        $root->setAttribute('xmlns:l', 'http://www.w3.org/1999/xlink');
        $document->appendChild($root);

        $root->appendChild($this->createBookDescription($document));
        $root->appendChild($this->createBookBody($document));

        return $document->saveXML();
    }

    protected function createBookDescription(DOMDocument $document): DOMElement
    {
        $description = $document->createElement('description');
        $description->appendChild($this->createTitleInfo($document));
        $description->appendChild($this->createDocumentInfoAttribute($document));
        $description->appendChild($this->createPublishInfoAttribute($document));

        return $description;
    }

    protected function createTitleInfo(DOMDocument $document): DOMElement
    {
        $titleInfo = $document->createElement('title-info');
        $titleInfo->appendChild($this->createAuthorAttribute($document));
        $titleInfo->appendChild($this->createBookTitleAttribute($document));
        $titleInfo->appendChild($this->createAnnotationAttribute($document));
        $titleInfo->appendChild($this->createLangAttribute($document));

        return $titleInfo;
    }

    protected function createAuthorAttribute(DOMDocument $document): DOMElement
    {
        $author = $document->createElement('author');

        if ($this->authorFirstName !== '') {
            $author->appendChild($document->createElement('first-name', $this->authorFirstName));
        }

        if ($this->authorLastName !== '') {
            $author->appendChild($document->createElement('last-name', $this->authorLastName));
        }

        return $author;
    }

    protected function createBookTitleAttribute(DOMDocument $document): DOMElement
    {
        return $document->createElement('book-title', $this->title);
    }

    protected function createAnnotationAttribute(DOMDocument $document): DOMElement
    {
        $annotation = $document->createElement('annotation');

        if ($this->annotation !== '') {
            $annotation->appendChild($document->createElement('p', $this->annotation));
        }

        return $annotation;
    }

    protected function createLangAttribute(DOMDocument $document): DOMElement
    {
        return $document->createElement('lang', $this->lang);
    }

    protected function createDocumentInfoAttribute(DOMDocument $document): DOMElement
    {
        $documentInfo = $document->createElement('document-info');
        $documentInfo->appendChild($this->createAuthorAttribute($document));

        return $documentInfo;
    }

    protected function createPublishInfoAttribute(DOMDocument $document): DOMElement
    {
        $publishInfo = $document->createElement('publish-info');
        $publishInfo->appendChild($document->createElement('book-name', $this->title));

        return $publishInfo;
    }

    protected function createBookBody(DOMDocument $document): DOMElement
    {
        $body = $document->createElement('body');

        foreach ($this->sections as $sectionData) {
            $body->appendChild($this->createSection($document, $sectionData['text'], $sectionData['title']));
        }

        return $body;
    }

    protected function createSection(DOMDocument $document, string $text, string $title = ''): DOMElement
    {
        $section = $document->createElement('section');

        if ($title !== '') {
            $titleElement = $document->createElement('title');
            $paragraph = $document->createElement('p');
            $strong = $document->createElement('strong', $title);
            $paragraph->appendChild($strong);
            $titleElement->appendChild($paragraph);
            $section->appendChild($titleElement);
        }

        foreach ($this->splitParagraphs($text) as $paragraphText) {
            $section->appendChild($document->createElement('p', $paragraphText));
        }

        return $section;
    }

    /**
     * @return list<string>
     */
    protected function splitParagraphs(string $text): array
    {
        $paragraphs = preg_split('/\R{2,}/u', trim($text)) ?: [];

        if ($paragraphs === []) {
            return [''];
        }

        return array_map(
            static fn (string $paragraph): string => preg_replace('/\R/u', ' ', trim($paragraph)) ?? trim($paragraph),
            $paragraphs
        );
    }
}
