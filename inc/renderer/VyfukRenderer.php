<?php

namespace FYKOS\dokuwiki\Extension\PluginNewsFeed\Renderer;

use FYKOS\dokuwiki\Extension\PluginNewsFeed\Model\ModelNews;
use dokuwiki\Form\Form;
use dokuwiki\Form\InputElement;

/**
 * Class VyfukRenderer
 * @author Michal Červeňák <miso@fykos.cz>
 */
class VyfukRenderer extends AbstractRenderer {

    public function render(string $innerHtml, string $formHtml, ModelNews $news): string {
        $html = '<div class="col-12 row mb-3">';
        $html .= '<div class="col-12">';
        $html .= '<div class="card card-outline-' . $news->category . ' card-outline-vyfuk-orange">';
        $html .= '<div class="card-block">';
        $html .= $innerHtml;
        $html .= $formHtml;
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    protected function getHeader(ModelNews $news) {
        return '<h4 class="card-title">' . $news->title . '</h4>' .
            '<p class="card-text">' .
            '<small class="text-muted">' . $news->getLocalDate(function ($key) {
                return $this->helper->getLang($key);
            }) . '</small>' .
            '</p>';
    }

    public function renderContent(ModelNews $data, array $params): string {
        $innerHtml = $this->getHeader($data);
        $innerHtml .= $this->getText($data);

        $innerHtml .= $this->getLink($data);
        $innerHtml .= $this->getSignature($data);
        return $innerHtml;
    }

    public function renderEditFields(array $params): string {

        if (auth_quickaclcheck('start') < AUTH_EDIT) {
            return '';
        }
        $html = '<button data-toggle="modal" data-target="#feedModal' . $params['id'] . '" class="btn btn-primary" >' .
            $this->helper->getLang('btn_opt') . '</button>';
        $html .= '<div id="feedModal' . $params['id'] . '" class="modal" data-id="' . $params["id"] . '">';
        $html .= '<div class="modal-dialog">';
        $html .= '<div class="modal-content">';
        $html .= $this->getModalHeader();
        $html .= $this->getPriorityField($params['id'], $params['stream'], $params);
        $html .= $this->btnEditNews($params['id'], $params['stream']);
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    protected function getModalHeader() {
        $html = '';
        $html .= '<div class="modal-header">';
        $html .= '<h5 class="modal-title">' . $this->helper->getLang('') . 'Upaviť novinku</h5>';
        $html .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close">';
        $html .= '<span aria-hidden="true">×</span>';
        $html .= '</button>';
        $html .= ' </div>';
        return $html;
    }

    /**
     * @param $id
     * @param $streamName
     * @param $params
     * @return string
     */
    protected function getPriorityField($id, $streamName, $params) {
        $html = '';
        if ($params['editable'] !== 'true') {
            return '';
        }
        if (!$params['stream']) {
            return '';
        }

        $html .= '<div class="modal-body">';
        $form = new Form();
        $form->addClass('block');

        $form->setHiddenField('do', \helper_plugin_newsfeed::FORM_TARGET);
        $form->setHiddenField('news[id]', $id);
        $form->setHiddenField('news[stream]', $streamName);
        $form->setHiddenField('news[do]', 'priority');

        $stream = $this->helper->serviceStream->findByName($streamName);

        $priority = $this->helper->servicePriority->findByNewsAndStream($id, $stream->streamId);
        $form->addTagOpen('div')->addClass('form-group');
        $priorityValue = new InputElement('number', 'priority[value]', $this->helper->getLang('valid_from'));
        $priorityValue->attr('class', 'form-control')->val($priority->getPriorityValue());
        $form->addElement($priorityValue);
        $form->addTagClose('div');

        $form->addTagOpen('div')->addClass('form-group');
        $priorityFromElement = new InputElement('datetime-local', 'priority[from]', $this->helper->getLang('valid_from'));
        $priorityFromElement->val($priority->priorityFrom ?: date('Y-m-d\TH:i:s', time()))
            ->attr('class', 'form-control');
        $form->addElement($priorityFromElement);
        $form->addTagClose('div');

        $form->addTagOpen('div')->addClass('form-group');
        $priorityToElement = new InputElement('datetime-local', 'priority[to]', $this->helper->getLang('valid_to'));
        $priorityToElement->val($priority->priorityTo ?: date('Y-m-d\TH:i:s', time()))
            ->attr('class', 'form-control');
        $form->addElement($priorityToElement);
        $form->addTagClose('div');

        $form->addButton('submit', $this->helper->getLang('btn_save_priority'))->addClass('btn btn-success');
        $html .= $form->toHTML();
        $html .= '</div>';

        return $html;
    }

    protected function btnEditNews($id, $stream) {
        $html = '';

        $html .= '<div class="modal-footer">';
        $html .= '<div class="btn-group">';
        $editForm = new Form();
        $editForm->setHiddenField('do', \helper_plugin_newsfeed::FORM_TARGET);
        $editForm->setHiddenField('news[id]', $id);
        $editForm->setHiddenField('news[do]', 'edit');
        $editForm->addButton('submit', $this->helper->getLang('btn_edit_news'))->addClass('btn btn-info');
        $html .= $editForm->toHTML();

        if ($stream) {
            $deleteForm = new Form();
            $deleteForm->setHiddenField('do', \helper_plugin_newsfeed::FORM_TARGET);
            $deleteForm->setHiddenField('news[do]', 'delete');
            $deleteForm->setHiddenField('news[stream]', $stream);
            $deleteForm->setHiddenField('news[id]', $id);
            $deleteForm->addButton('submit', $this->helper->getLang('delete_news'))->attr('data-warning', true)
                ->addClass('btn btn-danger');
            $html .= $deleteForm->toHTML();
        }

        $purgeForm = new Form();
        $purgeForm->setHiddenField('do', \helper_plugin_newsfeed::FORM_TARGET);
        $purgeForm->setHiddenField('news[do]', 'purge');
        $purgeForm->setHiddenField('news[id]', $id);
        $purgeForm->addButton('submit', $this->helper->getLang('cache_del'))->addClass('btn btn-warning');
        $html .= $purgeForm->toHTML();
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * @param $news ModelNews
     * @return null|string
     */
    protected function getText(ModelNews $news) {
        return $news->renderText();
    }

    /**
     * @param $news ModelNews
     * @return string
     */
    protected function getSignature(ModelNews $news) {
        return '<div class="card-text text-right">
            <a href="mailto:' . hsc($news->authorEmail) . '" class="mail" title="' . hsc($news->authorEmail) .
            '"><span class="fa fa-envelope"></span>' . hsc($news->authorName) . '</a>
        </div>';
    }

    /**
     * @param $news ModelNews
     * @return string
     */
    protected function getLink(ModelNews $news) {
        if ($news->hasLink()) {
            if (preg_match('|^https?://|', $news->linkHref)) {
                $href = hsc($news->linkHref);
            } else {
                $href = wl($news->linkHref, null, true);
            }
            return '<p><a class="btn btn-secondary" href="' . $href . '">' . $news->linkTitle . '</a></p>';
        }
        return '';
    }
}
