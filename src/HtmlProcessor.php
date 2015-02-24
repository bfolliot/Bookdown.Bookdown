<?php
namespace Bookdown\Content;

use League\CommonMark\CommonMarkConverter;

class HtmlProcessor
{
    protected $commonMarkConverter;

    public function __construct(CommonMarkConverter $commonMarkConverter)
    {
        $this->commonMarkConverter = $commonMarkConverter;
    }

    public function __invoke($page)
    {
        $text = $page->getOriginData();
        $html = $this->commonMarkConverter->convertToHtml($text);

        $file = $page->getTargetFile();
        $dir = dirname($file);
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, $html);
    }
}
