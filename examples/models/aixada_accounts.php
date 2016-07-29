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
                'account_id' => array(
                    'link' => 'aixada_ufs:account',
                    'title' => 'Compte',
                    'orderBy' => array('account_id', '!id')
                ),
                'operator_id' => array(
                    'link' => 'aixada_members'
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
            'grids' => array(
                'default' => array(
                    'sort' => 'account_id', // TODO detectar no existeix
                    'columns' => array(
                        'id', 'account_id', 
                        'account_id[name]',
                        'operator_id[name]',
                        //'account_id[1]',
                        //'account_id[uf_name]',
                        'ts',
                        'description',
                        'method',
                        'quantity',
                        'balance'),
                    'filter' => array(
                        'layout' => 'inline',
                        'fields' => array(
                            array(
                                'name' => 'account_id',
                                'check'=>'EQ', 'default'=>1005, 'required'
                            ),
                            array('name' => 'description', 'check'=>'LK', 'required')
                        )
                    )
                )
            
            )
        );
    }
}
