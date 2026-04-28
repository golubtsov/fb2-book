<?php

namespace Nigo\Fb2Book;

use DOMDocument;
use DOMElement;
use DOMException;
use RuntimeException;

class Fb2Book
{
    private string $authorFirstName = '';

    private string $authorMiddleName = '';

    private string $authorLastName = '';

    private string $authorNickname = '';

    private string $annotation = '';

    private string $date = '';

    private string $dateValue = '';

    private string $documentId = '';

    private string $documentVersion = '';

    private string $genre = '';

    private string $isbn = '';

    private string $lang = '';

    private string $publisher = '';

    private string $publishCity = '';

    private string $publishYear = '';

    private string $sequenceName = '';

    private string $sequenceNumber = '';

    private string $title = '';

    private string $translatorFirstName = '';

    private string $translatorMiddleName = '';

    private string $translatorLastName = '';

    private string $translatorNickname = '';

    /**
     * @var array<int, array{title: string, text: string}>
     */
    private array $sections = [];

    public static function make(): static
    {
        return new static();
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setAuthor(
        string $firstName = '',
        string $lastName = '',
        string $middleName = '',
        string $nickname = ''
    ): static {
        $this->authorFirstName = $firstName;
        $this->authorLastName = $lastName;
        $this->authorMiddleName = $middleName;
        $this->authorNickname = $nickname;

        return $this;
    }

    public function setTranslator(
        string $firstName = '',
        string $lastName = '',
        string $middleName = '',
        string $nickname = ''
    ): static {
        $this->translatorFirstName = $firstName;
        $this->translatorLastName = $lastName;
        $this->translatorMiddleName = $middleName;
        $this->translatorNickname = $nickname;

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

    public function setGenre(string $genre): static
    {
        $this->genre = $genre;

        return $this;
    }

    public function setDate(string $date, string $value = ''): static
    {
        $this->date = $date;
        $this->dateValue = $value;

        return $this;
    }

    public function setSequence(string $name, string|int $number = ''): static
    {
        $this->sequenceName = $name;
        $this->sequenceNumber = (string) $number;

        return $this;
    }

    public function setDocumentInfo(string $id = '', string|int|float $version = ''): static
    {
        $this->documentId = $id;
        $this->documentVersion = (string) $version;

        return $this;
    }

    public function setPublishInfo(
        string $publisher = '',
        string $city = '',
        string|int $year = '',
        string $isbn = ''
    ): static {
        $this->publisher = $publisher;
        $this->publishCity = $city;
        $this->publishYear = (string) $year;
        $this->isbn = $isbn;

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

    public function save(string $path): int
    {
        $bytes = file_put_contents($path, $this->create());

        if ($bytes === false) {
            throw new RuntimeException(sprintf('Unable to save FB2 book: %s', $path));
        }

        return $bytes;
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

        if ($this->genre !== '') {
            $titleInfo->appendChild($document->createElement('genre', $this->genre));
        }

        $titleInfo->appendChild($this->createAuthorAttribute($document));
        $titleInfo->appendChild($this->createBookTitleAttribute($document));
        $titleInfo->appendChild($this->createAnnotationAttribute($document));

        if ($this->date !== '') {
            $titleInfo->appendChild($this->createDateAttribute($document));
        }

        $titleInfo->appendChild($this->createLangAttribute($document));

        if ($this->translatorFirstName !== ''
            || $this->translatorMiddleName !== ''
            || $this->translatorLastName !== ''
            || $this->translatorNickname !== ''
        ) {
            $titleInfo->appendChild($this->createTranslatorAttribute($document));
        }

        if ($this->sequenceName !== '') {
            $titleInfo->appendChild($this->createSequenceAttribute($document));
        }

        return $titleInfo;
    }

    protected function createAuthorAttribute(DOMDocument $document): DOMElement
    {
        return $this->createPersonAttribute(
            $document,
            'author',
            $this->authorFirstName,
            $this->authorMiddleName,
            $this->authorLastName,
            $this->authorNickname
        );
    }

    protected function createTranslatorAttribute(DOMDocument $document): DOMElement
    {
        return $this->createPersonAttribute(
            $document,
            'translator',
            $this->translatorFirstName,
            $this->translatorMiddleName,
            $this->translatorLastName,
            $this->translatorNickname
        );
    }

    /**
     * @throws DOMException
     */
    protected function createPersonAttribute(
        DOMDocument $document,
        string $elementName,
        string $firstName,
        string $middleName,
        string $lastName,
        string $nickname
    ): DOMElement {
        $person = $document->createElement($elementName);

        if ($firstName !== '') {
            $person->appendChild($document->createElement('first-name', $firstName));
        }

        if ($middleName !== '') {
            $person->appendChild($document->createElement('middle-name', $middleName));
        }

        if ($lastName !== '') {
            $person->appendChild($document->createElement('last-name', $lastName));
        }

        if ($nickname !== '') {
            $person->appendChild($document->createElement('nickname', $nickname));
        }

        return $person;
    }

    /**
     * @throws DOMException
     */
    protected function createBookTitleAttribute(DOMDocument $document): DOMElement
    {
        return $document->createElement('book-title', $this->title);
    }

    /**
     * @throws DOMException
     */
    protected function createAnnotationAttribute(DOMDocument $document): DOMElement
    {
        $annotation = $document->createElement('annotation');

        if ($this->annotation !== '') {
            $annotation->appendChild($document->createElement('p', $this->annotation));
        }

        return $annotation;
    }

    /**
     * @throws DOMException
     */
    protected function createDateAttribute(DOMDocument $document): DOMElement
    {
        $date = $document->createElement('date', $this->date);

        if ($this->dateValue !== '') {
            $date->setAttribute('value', $this->dateValue);
        }

        return $date;
    }

    /**
     * @throws DOMException
     */
    protected function createLangAttribute(DOMDocument $document): DOMElement
    {
        return $document->createElement('lang', $this->lang);
    }

    /**
     * @throws DOMException
     */
    protected function createSequenceAttribute(DOMDocument $document): DOMElement
    {
        $sequence = $document->createElement('sequence');
        $sequence->setAttribute('name', $this->sequenceName);

        if ($this->sequenceNumber !== '') {
            $sequence->setAttribute('number', $this->sequenceNumber);
        }

        return $sequence;
    }

    /**
     * @throws DOMException
     */
    protected function createDocumentInfoAttribute(DOMDocument $document): DOMElement
    {
        $documentInfo = $document->createElement('document-info');
        $documentInfo->appendChild($this->createAuthorAttribute($document));

        if ($this->documentId !== '') {
            $documentInfo->appendChild($document->createElement('id', $this->documentId));
        }

        if ($this->documentVersion !== '') {
            $documentInfo->appendChild($document->createElement('version', $this->documentVersion));
        }

        return $documentInfo;
    }

    /**
     * @throws DOMException
     */
    protected function createPublishInfoAttribute(DOMDocument $document): DOMElement
    {
        $publishInfo = $document->createElement('publish-info');
        $publishInfo->appendChild($document->createElement('book-name', $this->title));

        if ($this->publisher !== '') {
            $publishInfo->appendChild($document->createElement('publisher', $this->publisher));
        }

        if ($this->publishCity !== '') {
            $publishInfo->appendChild($document->createElement('city', $this->publishCity));
        }

        if ($this->publishYear !== '') {
            $publishInfo->appendChild($document->createElement('year', $this->publishYear));
        }

        if ($this->isbn !== '') {
            $publishInfo->appendChild($document->createElement('isbn', $this->isbn));
        }

        return $publishInfo;
    }

    /**
     * @throws DOMException
     */
    protected function createBookBody(DOMDocument $document): DOMElement
    {
        $body = $document->createElement('body');

        foreach ($this->sections as $sectionData) {
            $body->appendChild($this->createSection($document, $sectionData['text'], $sectionData['title']));
        }

        return $body;
    }

    /**
     * @throws DOMException
     */
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
