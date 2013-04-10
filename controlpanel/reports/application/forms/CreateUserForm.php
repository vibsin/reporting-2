<?php

class Forms_CreateUserForm extends Zend_Form {
    
    public function init() {
        parent::init();
        
        $this->setName("create_user_form");
        $this->setMethod('post');
        $this->setAction(BASE_URL.'/settings/index');
        
        $this->addElement('text', 'username', array(
            'filters'    => array('StringTrim', 'StringToLower'),
            'validators' => array(
                array('StringLength', false, array(0, 50)),
            ),
            'required'   => true,
            'label'      => '*Username:',
        ));

        $this->addElement('password', 'password', array(
            'filters'    => array('StringTrim'),
            'validators' => array(
                array('StringLength', false, array(0, 50)),
            ),
            'required'   => true,
            'label'      => '*Password:',
        ));
        
        //sections
        
        //fetch sections from DB first
        try {
            $model = new Model_AdminUsers(array("db"=> Zend_Registry::get("authDbConnection")));
            $sectionList = $model->getSections();
            
        } catch (Exception $e) {
            echo $e->getMessage();
        }
       
        
        
        
        $sections = new Zend_Form_Element_MultiCheckbox('sections',array(
            "multiOptions" => $sectionList,
            "required"   => true,
            "label" => "*Sections"
            
        ));
        
        $this->addElement($sections);
        
        $userType = new Zend_Form_Element_Radio('user_type',array(
            "multiOptions" => array("admin" => "Admin","user" => "User","ro" => "RO"),
            "required"   => true,
            "label" => "*User type"
            
        ));
        
        $this->addElement($userType);
        
        
        $ros = $model->getRoTypeUsers();
        //print_r($ros);
        if(!empty($ros)) {
            $data = array();
            foreach($ros as $k => $v) {
                $data[$v["id"]] = $v["id"]."|".$v["username"];
            }
        }
        //print_r($data);
        
        $ro = new Zend_Form_Element_Select('ro_parent',array(
            "multiOptions" => $data,
            "label" => "Supervisor (applicable only if 'RO')"
            
        ));
        
        $this->addElement($ro);
        
        
       
        $this->addElement('submit', 'create', array(
            //'required' => false,
            //'ignore'   => true,
            'label'    => 'Create User',
        ));
        
//        $this->addElement('reset', 'reset', array(
//            //'required' => false,
//            //'ignore'   => true,
//            'label'    => 'Reset',
//        ));
        
    }
    
}
