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
                    'display' => 'hidden',
                    'isKey' => true
                ),
                "operator" => array(
                    "label" => 'Usuari',
                    'db' => null, //'operator_id',
                    'list' => array(
                        'table' => 'aixada_member',
                        'key' => 'id',
                        'description' => 'name',
                        'default' => '(nobody)'
                    )
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
                    'label' => 'Cuenta',
                    'type' => 'integer',
                    'validations' => 'required',
                    'check' => 'EQ',
                    'list' => array(
                        'table' => 'aixada_account_desc',
                        'value' => 'id',
                        'text' => 'description',
                        'filter' => 'active'
                    ),
                    //'value' => -4,
                    //'display' => 'hidden'
                ),
                array(
                    'name' => 'active',
                    'type' => 'boolean',
                    'default' => true,
                    'check' => 'EQ',
                    'target' => 'list:account_id'
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
