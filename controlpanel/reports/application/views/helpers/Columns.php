<?php
class Zend_View_Helper_Columns extends Zend_View_Helper_Abstract {

    public $view;
    protected $postedVars;

    public function setView(Zend_View_Interface $view) {
        $this->view = $view;
    }

    public function setPostedVars($post) {
        $this->postedVars = $post;
        return true;
    }

    public function columns($name, $value, $postedVars=array()) {
        return '<input type="checkbox"
            name="'.$name.'"
            value = "'.$value.'"
            class="chk_box"
            '.(!empty($postedVars) ? ((in_array($value, $postedVars) == true) ? 'checked="checked"' : '') : '').' />';
    }
}