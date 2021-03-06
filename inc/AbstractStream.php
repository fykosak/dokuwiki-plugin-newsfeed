<?php

namespace FYKOS\dokuwiki\Extension\PluginNewsFeed;

use Doku_Renderer;
use dokuwiki\Extension\SyntaxPlugin;
use dokuwiki\Form\Form;
use helper_plugin_newsfeed;

/**
 * Class AbstractStream
 * @author Michal Červeňák <miso@fykos.cz>
 */
abstract class AbstractStream extends SyntaxPlugin {

    protected helper_plugin_newsfeed $helper;

    public function __construct() {
        $this->helper = $this->loadHelper('newsfeed');
    }

    public function getType(): string {
        return 'substition';
    }

    public function getPType(): string {
        return 'block';
    }

    public function getSort(): int {
        return 3;
    }

    protected function renderEditModal(Doku_Renderer $renderer, array $params): void {
        $id = uniqid();
        global $ID;
        $renderer->nocache();
        if (auth_quickaclcheck($ID) >= AUTH_EDIT) {
            $this->renderModalContent($renderer, $id, $params);
        }
    }

    protected function renderModalContent(Doku_Renderer $renderer, string $id, array $params): void {
        //button
        $renderer->doc .= '<button data-toggle="modal" data-target="#feedModal' . $id . '" class="btn btn-primary" >
<span class="fa fa-edit"></span>
</button>';
        // content
        $renderer->doc .= '<div id="feedModal' . $id . '" class="modal" data-id="' . $id . '">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">' . $this->helper->getLang('edit_stream') . '</h5>
<button type="button" class="close" data-dismiss="modal" aria-label="Close">
<span aria-hidden="true">×</span>
</button>
</div>
<div class="modal-body">';
        $this->renderStreamHead($renderer, $params);
        $renderer->doc .= '</div></div></div></div>';
    }

    protected function renderStream(Doku_Renderer $renderer, array $params): void {
        $attributes = [];

        foreach ($params as $key => $value) {
            $attributes['data-' . $key] = $value;
        }
        $renderer->doc .= '<div class="news-stream">';
        $this->renderEditModal($renderer, $params);

        $renderer->doc .= '<div class="stream row" ' . buildAttributes($attributes) . '></div>';

        $renderer->doc .= '<div class="load-bar w-100" style="text-align:center;clear:both"><svg xmlns="http://www.w3.org/2000/svg" width="25%" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-blank"><rect x="0" y="0" width="100" height="100" fill="none" class="bk"/><g transform="scale(0.55)"><circle cx="30" cy="150" r="30" fill="#1175da"><animate attributeName="opacity" from="0" to="1" dur="1s" begin="0" repeatCount="indefinite" keyTimes="0;0.5;1" values="0;1;1"/></circle><path d="M90,150h30c0-49.7-40.3-90-90-90v30C63.1,90,90,116.9,90,150z" fill="#1175da"><animate attributeName="opacity" from="0" to="1" dur="1s" begin="0.1" repeatCount="indefinite" keyTimes="0;0.5;1" values="0;1;1"/></path><path d="M150,150h30C180,67.2,112.8,0,30,0v30C96.3,30,150,83.7,150,150z" fill="#1175da"><animate attributeName="opacity" from="0" to="1" dur="1s" begin="0.2" repeatCount="indefinite" keyTimes="0;0.5;1" values="0;1;1"/></path></g></svg></div>';
        $renderer->doc .= '<button class="more-news btn btn-info w-100" disabled="disabled" data-stream="' . $params['stream'] . '">
            ' . $this->getLang('btn_more_news') . '
                </button>';

        $renderer->doc .= '</div>';
    }


    protected function renderStreamHead(Doku_Renderer $renderer, array $params): void {
        global $ID;
        if (auth_quickaclcheck($ID) >= AUTH_EDIT) {
            $renderer->doc .= '<div class="btn-group-vertical">';
            $renderer->doc .= '<div class="mb-3">';
            $renderer->doc .= $this->printPreviewBtn($params['stream']);
            $renderer->doc .= '</div>';
            $renderer->doc .= '<div class="mb-3">';
            $renderer->doc .= $this->printCreateBtn($params['stream']);
            $renderer->doc .= '</div>';
            $renderer->doc .= '<div class="mb-3">';
            $renderer->doc .= $this->printPullBtn($params['stream']);
            $renderer->doc .= '</div>';
            $renderer->doc .= '<div class="mb-3">';
            $renderer->doc .= $this->printCacheBtn();
            $renderer->doc .= '</div>';
            $renderer->doc .= '</div>';
        }
    }

    private function printPullBtn($stream): string {
        $form = new Form();
        $form->setHiddenField('do', 'admin');
        $form->setHiddenField('page', 'newsfeed_push');
        $form->setHiddenField('news[stream]', $stream);
        $form->addButton('submit', $this->getLang('btn_push_stream'))
            ->addClass('btn btn-info');
        return $form->toHTML();
    }

    private function printPreviewBtn($stream): string {
        return '<a class="btn btn-secondary" href="' . wl(null, [
                'do' => helper_plugin_newsfeed::FORM_TARGET,
                'news[do]' => 'preview',
                'news[stream]' => $stream,
            ]) . '">' . $this->getLang('Preview') . '</a>';
    }

    private function printCreateBtn($stream): string {
        $form = new Form();
        $form->setHiddenField('do', helper_plugin_newsfeed::FORM_TARGET);
        $form->setHiddenField('news[do]', 'create');
        $form->setHiddenField('news[id]', 0);
        $form->setHiddenField('news[stream]', $stream);
        $form->addButton('submit', $this->getLang('btn_create_news'))
            ->addClass('btn btn-primary');
        return $form->toHTML();
    }

    private function printCacheBtn(): string {
        $form = new Form();
        $form->setHiddenField('do', helper_plugin_newsfeed::FORM_TARGET);
        $form->setHiddenField('news[do]', 'purge');
        $form->addButton('submit', $this->getLang('cache_del_full'))
            ->addClass('btn btn-warning');
        return $form->toHTML();
    }

}
