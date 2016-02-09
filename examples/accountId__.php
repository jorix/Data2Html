<?php
require_once("../code/php/Data2Html.php");
class accountId extends Data2Html {

    protected function init() {
        $this->sql = 
            "SELECT id+1000 value, concat(name, ' #', id) text, active FROM aixada_uf";
        $this->serviceUrl = 'accountId_controller.php?';
        
        /*
        // UF accounts
        if ($filter['show_uf_generic']) {
            $strXML .= array_to_XML(array(
                'id'    => 1000,
                'name'  => i18n('mon_all_active_uf')
            ));            
        }
        if ($filter['show_uf']) {
            $sqlStr = "SELECT id+1000 id, concat(id,' ',name) name FROM aixada_uf";
            $sqlStr .= $all ? "" :" where active=1";
            $sqlStr .= " order by id";
            $strXML .= query_to_XML($sqlStr);
        }
        // Providers
        if ($filter['show_providers']) {
            $sqlStr = "SELECT id+2000 id, concat(name,'#',id) name FROM aixada_provider";
            $sqlStr .= $all ? "" :" where active=1";
            $sqlStr .= " order by id";
            $strXML .= query_to_XML($sqlStr);
        }
        */
        
        
        #Set columns
        $this->setCols(
            array(
                'value' => array(
                    'type' => 'integer',
                    'isKey' => true
                ),
                'text' => array(),
                'active' => array(
                    'type' => 'boolean'
                )
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
