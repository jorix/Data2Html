<?php
class aixada_providers extends Data2Html_Model {
    
    protected function definitions()
    {
        return array(
            'table' => 'aixada_provider',
            'fields' => array(
                'id' => array('integer', 'autoKey'),
                'name' => array('orderBy' => array('name')),
                //'group' => array(),
                'text' => array(
                    'value' => '$${name} #$${id}'
                ),
                'active' => array(
                    'type' => 'boolean'
                )
            ),
            'grids' => array(
                'default' => array(
                    'columns' => array(),
                    'sort' => 'name',
                    'filter' => array(
                        'layout' => 'inline',
                        'fields' => array(
                            array('name' => 'name', 'check'=>'LK', 'required'),
                            'active' => 'EQ'
                        )
                    )
                )
            )
        );
    }
}
