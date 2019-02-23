<?php

namespace Mod\SecureMenu;

use \Lib\CCMS\Utilities;

class PanelPage
{
    private $id = "";
    private $title = "";
    private $content = "";

    private $left = [
        'action' => "",
        'title' => "",
        'icon' => "",
        'visible' => false,
    ];

    private $right = [
        'action' => "",
        'title' => "",
        'icon' => "",
        'visible' => false,
    ];

    public function __construct(string $id, string $title, string $content)
    {
        $this->id = $id;
        $this->title = $title;
        $this->content = $content;
    }

    public function setLeftIcon(string $action, string $title, string $icon, bool $visible=true)
    {
        $this->left = [
            'action' => $action,
            'title' => $title,
            'icon' => $icon,
            'visible' => $visible,
        ];
    }

    public function setRightIcon(string $action, string $title, string $icon, bool $visible=true)
    {
        $this->right = [
            'action' => $action,
            'title' => $title,
            'icon' => $icon,
            'visible' => $visible,
        ];
    }

    public function getCompiledPage($parentId="")
    {
        $template = file_get_contents(dirname(__FILE__) . "/templates/SecureMenuPanelPage.template.html");

        $template_vars =[
            'parentid' => $parentId,
            'id' => $this->id,
            'leftaction' => $this->left['action'],
            'lefttitle' => $this->left['title'],
            'lefticon' => $this->left['icon'],
            'rightaction' => $this->right['action'],
            'righttitle' => $this->right['title'],
            'righticon' => $this->right['icon'],
            'title' => $this->title,
            'content' => $this->content,
        ];

        return Utilities::fillTemplate($template, $template_vars);
    }
}