<?php
class aixada_accounts extends Data2Html {

    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_account',
            'title' => 'Diners',
            'fields' => array(
                'id' => array('autoKey', 'required', 'hidden'),
                'account_id' => array('foreignKey' => 'aixada_ufs:account'),
                'operator' => array(
                    'title' => 'Usuari',
                    'db' => null, //'operator_id',
                ),
                'description' => array(),
                'method' => array(
                    'db' => null
                ),
                'quantity' => array('number', 
                    'format' => 2, 'visualClass' => 'red:<0'),
                'balance' => array('currency', 'visualClass' => 'red:<0'),
                'ts' => array(
                    'title' => 'data', 'date',
                    'format' => 'dd-MM-yy HH:mm'// 'medium'
                )
            ),
            'filter' => array(
                'account_id' => array('check'=>'EQ', 'default'=>1005, 'required'),
                'description' => array('check'=>'LK', 'required'),
            )
        );
    }
}
