<?php
class aixada_accounts extends Data2Html_Model {

    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_account',
            'title' => 'Diners',
            'base' => array(
                'id' => array('autoKey', 'required', 'hidden'),
                'account_id' => array(
                    'sortBy' => array('account_id', '!id'),
                    'hidden'
                ),
                'account_uf_id' => array(
                    'db' => 'account_id',
                    'link' => 'aixada_ufs:account',
                    'title' => 'Compte'
                ),
                'operator_id' =>       array('link' => 'aixada_members'),
                'payment_method_id' => array('link' => 'aixada_payment_methods'),
                'description' => array('title' => 'Descripció'),
                'quantity' => array('number', 
                    'format' => 2, 'visualClass' => 'red:<0'),
                'balance' => array('currency', 'visualClass' => 'red:<0'),
                'ts' => array(
                    'title' => 'Data',
                    'date',
                    'format' => 'dd-MM-yy HH:mm'// 'medium'
                )
            ),
            'grids' => array(
                'default' => array(
                    'sort' => 'account_id', // TODO detectar no existeix
                    'layouts' => array('sorted', 'default'),
                    'columns' => array(
                        'id',
                        'ts',
                        'operator_id[name]',
                        'account_id',
                        'account_uf_id[text]',
                        //'account_id[1]',
                        'description',
                        'payment_method_id[1]',
                        '  ',
                        'balance'),
                    'filter' => array(
                        'layout' => 'inline',
                        'fields' => array(
                            array(
                                'base' => 'account_uf_id',
                                'check'=>'EQ', 'default'=>1005, 'required'
                            ),
                            array('base' => 'description', 'check'=>'LK', 'required')
                        )
                    )
                )
            
            )
        );
    }
}
