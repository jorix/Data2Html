<?php
class aixada_providers extends Data2Html {
    
    protected function definitions()
    {
        return array(
            'table' => 'aixada_provider',
            'fields' => array(
                'id' => array('integer', 'autoKey'),
                'name' => array(),
                'group' => array(),
                'text' => array(
                    'value' => '$${name} #$${id}'
                ),
                'active' => array(
                    'type' => 'boolean'
                )
            ),
            'grids' => array(
                'default' => array(
                    'sort' => 'name',
                    'filter' => array('active' => 'EQ')
                )
            )
        );
    }
}
