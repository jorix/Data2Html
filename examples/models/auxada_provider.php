<?php
require_once("../code/php/Data2Html.php");
class accountProviderId extends Data2Html {

    protected function init() {
        $this->table = 'aixada_provider';

        #Set columns
        $this->setList(
            array(
                'cols' => array(
                    'value' => array(
                        'type' => 'integer',
                        'db' => 'id+2000',
                        'isKey' => true
                    ),
                    'group' => array(
                        
                    ),
                    'text' => array(
                        'db' => "concat(name, ' #', id)",
                        'ex' => '$${name} #$${id}'
                    ),
                    'active' => array(
                        'type' => 'boolean'
                    )
                ),
                'sort' => 'name'
            )
        );
        $this->setFilter(
            array(
                array(
                    'name' => 'active',
                    'default' => true,
                    'check' => 'EQ'
                )
            )
        );
        /*
        $this->setSort(
            array('name', 'a')
        );
        */
    }
}
