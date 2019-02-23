<?php

namespace Mod\SecureMenu;

use \Lib\CCMS\Utilities;
use \Mod\SecureMenu\PanelPage;

class Panel
{
    const SLIDE_HORIZONTAL = 0;
    const SLIDE_VERTICAL = 1;

    private $id;
    private $title;
    private $content;
    private $direction;

    private $pages = [];

    /**
     * @param string $id ID of this panel
     * @param string $title Title to show in this panel
     * @param string $content Content to show in this panel
     * @param $direction Which direction this panel opens and closes
     **/
    public function __construct(string $id, string $title, string $content, $direction=self::SLIDE_HORIZONTAL)
    {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
        $this->direction = $direction;
    }

    public function addPage(PanelPage $page)
    {
        array_push($this->pages, $page);
    }

    public function getCompiledPanel()
    {
        $template = file_get_contents(dirname(__FILE__) . "/templates/SecureMenuPanel.template.html");

        $compiledPages = "";
        foreach ($this->pages as $page) {
            $compiledPages .= $page->getCompiledPage($this->id);
        }

        $template_vars = [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'pages' => $compiledPages,
            'direction' => ($this->direction ? "vertical" : "horizontal"),
        ];

        return Utilities::fillTemplate($template, $template_vars);
    }
}