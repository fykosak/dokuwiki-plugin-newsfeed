<?php

/**
 * DokuWiki Plugin fksdbexport (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michal Červeňák <miso@fykos.cz>
 */
// must be run within Dokuwiki
if (!defined('DOKU_INC')) {
    die();
}

class action_plugin_fksnewsfeed extends DokuWiki_Action_Plugin {

    private $modFields = array('name', 'email', 'author', 'newsdate', 'text');
    private $helper;

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function __construct() {
        $this->helper = $this->loadHelper('fksnewsfeed');
    }

    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('HTML_SECEDIT_BUTTON', 'BEFORE', $this, 'handle_html_secedit_button');
        $controller->register_hook('HTML_EDIT_FORMSELECTION', 'BEFORE', $this, 'handle_html_edit_formselection');
        $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, 'handle_action_act_preprocess');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_action_ajax_request');
    }

    public function handle_html_secedit_button(Doku_Event &$event, $param) {

        if (!p_get_metadata('fks_news')) {
            return;
        }
        //$event->data['name'] = $this->getLang('Edit'); // it's set in redner()
    }

    /**
     * [Custom event handler which performs action]
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function handle_action_ajax_request(Doku_Event &$event, $param) {
        global $INPUT;

        if ($INPUT->str('target') != 'feed') {
            return;
        }
        $event->stopPropagation();
        $event->preventDefault();
        if ($INPUT->str('do') == 'edit') {
            if ($_SERVER['REMOTE_USER']) {
                $form = new Doku_Form(array('id' => 'editnews', 'method' => 'POST', 'class' => 'fksreturn'));
                $form->addHidden("do", "edit");
                $form->addHidden('id', $this->helper->getwikinewsurl($INPUT->str('id')));
                $form->addHidden("target", "plugin_fksnewsfeed");
                $form->addElement(form_makeButton('submit', '', $this->getLang('subeditnews')));
                ob_start();
                html_form('editnews', $form);


                $r = ob_get_contents();
                ob_end_clean();
            }

            require_once DOKU_INC . 'inc/JSON.php';
            $json = new JSON();
            header('Content-Type: application/json');
            //echo $r;
            echo $json->encode(array("r" => $r));
        } else {


            $data["fullhtml"] = $this->helper->renderfullnews($INPUT->str('id'), 'fkseven');

            require_once DOKU_INC . 'inc/JSON.php';
            $json = new JSON();
            header('Content-Type: application/json');

            echo $json->encode(array("fullhtml" => p_render('xhtml', p_get_instructions($data["fullhtml"]), $info)));
        }
    }

    public function handle_html_edit_formselection(Doku_Event &$event, $param) {
        global $TEXT;
        global $INPUT;
        //print_r();
        if ($INPUT->str('target') !== 'plugin_fksnewsfeed') {

            return;
        }

        $event->preventDefault();
        unset($event->data['intro_locale']);
        echo $this->locale_xhtml('edit_intro');
        $form = $event->data['form'];

        if (array_key_exists('wikitext', $_POST)) {
            foreach ($this->modFields as $field) {
                $data[$field] = $INPUT->param($field);
            }
        } else {

            $data = $this->extractParamACT(io_readFile(metaFN($INPUT->str("id"), ".txt")));
        }

        $form->startFieldset('Newsfeed');
        foreach ($this->modFields as $field) {

            if ($field == 'text') {
                $value = $INPUT->post->str('wikitext', $data[$field]);
                $form->addElement(form_makeWikiText($TEXT, array()));
            } else {
                $value = $INPUT->post->str($field, $data[$field]);
                $form->addElement(form_makeTextField($field, $value, $this->getLang($field), $field, null, array()));
            }
        }
        $form->endFieldset();
    }

    public function handle_action_act_preprocess(Doku_Event &$event, $param) {
        global $ACT;
        if (!isset($_POST['do']['save'])) {
            return;
        }
        global $INPUT;
        global $TEXT;
        global $ID;

        if ($INPUT->str("target") == "plugin_fksnewsfeed") {
            $data = array();
            //print_r($_REQUEST);
            foreach ($this->modFields as $field) {
                if ($field == 'text') {

                    $data[$field] = cleanText($_POST['wikitext']);
                    unset($_POST['wikitext']);
                } else {
                    $data[$field] = $INPUT->param($field);
                }
            }


            $this->helper->saveNewNews(array('author' => $data['author'],
                'newsdate' => $data['newsdate'],
                'email' => $data['email'],
                'text' => $data['text'],
                'name' => $data['name']
                    ), $_POST["id"]);

            // $TEXT = $news;
            unset($TEXT);
            unset($_POST['wikitext']);
            $ACT = "show";
            $ID = 'start';
        }
    }

    private function extractParamACT($ntext) {
        global $TEXT;


        $cleantext = str_replace(array("\n", '<fksnewsfeed', '</fksnewsfeed>'), array('', '', ''), $ntext);
        list($params, $text) = preg_split('/\>/', $cleantext, 2);
        $param = $this->helper->FKS_helper->extractParamtext($params);


        $TEXT = $text;

        return $param;
    }

}
