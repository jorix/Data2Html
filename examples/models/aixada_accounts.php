<?php
require_once("../../code/php/Data2Html.php");
class aixada_accounts extends Data2Html {

    protected function init()
    {
        $this->parse();
    }

    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_account',
            'serviceUrl' => 'account_controller.php?'
            'title' => 'Diners',
            'fields' => array(
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
                        'value' => 'id',
                        'text' => 'name',
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
            ),
            'filter' => array(
                array(
                    'name' =>'account_id',
                    'check' => 'EQ',
                    'label' => 'Cuenta',
                    'validations' => 'required',
                    'default' => -4,
                    'serviceUrl' => 'accountid_controller.php?',
                    'list' => array(1)
                ),
                array(
                    'name' => 'active',
                    'check' => 'EQ',
                    'type' => 'boolean',
                    'default' => true,
                    'target' => 'list:account_id'
                ),
            )
        );
    }
}
