<?php

namespace Nigo\Fb2Book;

class Fb2Book
{
    private string $authorFirstName = '';

    private string $authorLastName = '';

    private string $annotation = '';

    private string $lang = '';

    private string $title = '';

    private string $text = '';

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
        $this->text .= '<section>';

        if (!empty($title)) {
            $this->text .= "<title><p><strong>$title</strong></p></title>";
        }

        $this->text .= "<p>$text</p>";
        $this->text .= '</section>';

        return $this;
    }

    public function create(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';

        return $xml . '<FictionBook>' . $this->createBookDescription() . $this->createBookBody() . '</FictionBook>';
    }

    protected function createBookDescription(): string
    {

        $description = $this->createTitleInfo();

        $description .= $this->createDocumentInfoAttribute();

        $description .= $this->createPublishInfoAttribute();

        return "<description>$description</description>";
    }

    protected function createTitleInfo(): string
    {
        $titleInfo = '<title-info>';
        $titleInfo .= $this->createAuthorAttribute();
        $titleInfo .= $this->createBookTitleAttribute();
        $titleInfo .= $this->createAnnotationAttribute();
        $titleInfo .= $this->createLangAttribute();
        $titleInfo .= '</title-info>';

        return $titleInfo;
    }

    protected function createAuthorAttribute(): string
    {
        $author = '';

        if (!empty($this->authorFirstName)) {
            $author .= "<first-name>{$this->authorFirstName}</first-name>";
        }

        if (!empty($this->authorLastName)) {
            $author .= "<first-name>{$this->authorLastName}</first-name>";
        }

        return "<author>$author</author>";
    }

    protected function createBookTitleAttribute(): string
    {
        return "<book-title>{$this->title}</book-title>";
    }

    protected function createAnnotationAttribute(): string
    {
        return "<annotation><p>{$this->annotation}</p></annotation>";
    }

    protected function createLangAttribute(): string
    {
        return "<lang>{$this->lang}</lang>";
    }

    protected function createDocumentInfoAttribute(): string
    {
        $info = $this->createAuthorAttribute();

        return "<document-info>$info</document-info>";
    }

    protected function createPublishInfoAttribute(): string
    {
        $publishInfo = $this->title;

        return "<publish-info><book-name>$publishInfo</book-name></publish-info>";
    }

    protected function createBookBody(): string
    {
        return "<body>{$this->text}</body>";
    }
}