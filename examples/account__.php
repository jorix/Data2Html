<?php
require_once("../code/php/Data2Html.php");
class aixada_account extends Data2Html {

    protected function init() {
        #Set database table
        $this->table = 'aixada_account';
        $this->title = 'Diners';

        #Set columns
        $this->setCols(
            array(
                'id' => array(
                    'type' => 'integer',
                    'display' => 'hidden'
                ),
                "operator" => array(
                    "label" => 'Usuari',
                    'db' => null
                ),
                "description" => array(),
                "method" => array(
                    'db' => null
                ),
                "quantity" => array(
                    'type' => 'number',
                    'format' => 2,
                    'visualClass' => 'red:<0'
                ),
                "balance" => array(
                    'type' => 'currency'
                ),
                'ts' => array(
                    'label' => 'data',
                    'type' => 'date',
                    'format' => 'dd-MM-yy HH:mm'// 'medium'
                )
            )
        );
        $this->setFilter(
            array(
                array(
                    'name' => 'account_id',
                    'type' => 'integer',
                    'check' => 'EQ',
                    'value' => -4,
                    //'display' => 'hidden'
                ),
                /*
                array(
                    'name' => 'description',
                    'type' => 'string',
                    'check' => 'LK',
                    'value' => '%sit%',
                    'display' => 'hidden'
                )
                */
            )
        );
    }

    protected function beforeInsert($values) {
        throw new Exception('Tablxxxxxxxxxxxxxe is not defined');
    }
    protected function beforeUpdate($values, $iid) {
        throw new jqGrid_Exception('Tablxxxxxxxxxxfffffxxxe is not defined');
    }
    protected function beforeDelete($keyArray) {
        global $Text;
        return $this->checkRelated(
            "select id from aixada_product where
                unit_measure_order_id = {id} or
                unit_measure_shop_id = {id}",
            $keyArray,
            $Text['nav_mng_products']
        );
    }
}
