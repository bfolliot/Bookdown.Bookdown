<?php
namespace Bookdown\Content;

class ContentList
{
    protected $pages = array();
    protected $contentFactory;
    protected $targetBase;

    public function __construct(
        ContentFactory $contentFactory,
        $targetBase
    ) {
        $this->contentFactory = $contentFactory;
        $this->targetBase = $targetBase;
    }

    public function __invoke($bookdownFile, $name = '', $parent = null, $count = 0)
    {
        $base = $this->getBase($bookdownFile);
        $json = $this->getJson($bookdownFile);

        $index = $this->addContentIndex($json, $base, $name, $parent, $count);

        $count = 0;
        foreach ($json->content as $name => $origin) {
            $count ++;
            $origin = $this->fixOrigin($origin, $base);
            if ($this->isJson($origin)) {
                $child = $this->__invoke($origin, $name, $index, $count);
            } else {
                $child = $this->addContentPage($name, $origin, $index, $count);
            }
            $index->addChild($child);
        }

        return $index;
    }

    public function getItems()
    {
        return $this->pages;
    }

    protected function getJson($bookdownFile)
    {
        $data = file_get_contents($bookdownFile);
        $json = json_decode($data);

        if (! $json->content) {
            echo "{$bookdownFile} malformed.";
            exit(1);
        }

        return $json;
    }

    protected function getBase($bookdownFile)
    {
        return dirname($bookdownFile) . DIRECTORY_SEPARATOR;
    }

    protected function fixOrigin($origin, $base)
    {
        if (strpos($origin, '://' !== false)) {
            return;
        }

        if ($origin{0} === DIRECTORY_SEPARATOR) {
            return;
        }

        return $base . ltrim($origin, DIRECTORY_SEPARATOR);
    }

    protected function isJson($origin)
    {
        return substr($origin, -5) == '.json';
    }

    protected function addContentPage($name, $origin, $parent, $count)
    {
        $page = $this->contentFactory->newContentPage($name, $origin, $parent, $count);
        $this->append($page);
        return $page;
    }

    protected function addContentIndex($json, $base, $name, $parent, $count)
    {
        $origin = '';
        if (isset($json->content->index)) {
            $origin = $json->content->index;
            unset($json->content->index);
        }

        if ($parent) {
            $page = $this->contentFactory->newContentIndex($name, $origin, $parent, $count);
        } else {
            $page = $this->contentFactory->newContentRoot($name, $origin, $parent, $count);
            $page->setTargetBase($this->targetBase);
        }

        $page->setTitle($json->title);
        $this->append($page);
        return $page;
    }

    protected function append(ContentPage $page)
    {
        $prev = end($this->pages);
        if ($prev) {
            $prev->setNext($page);
            $page->setPrev($prev);
        }

        $this->pages[] = $page;
    }
}
