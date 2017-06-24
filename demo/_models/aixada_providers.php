<?php
class aixada_providers extends Data2Html_Model {
    
    protected function definitions()
    {
        return array(
            'table' => 'aixada_provider',
            'base' => array(
                'id' => array('integer', 'autoKey'),
                'name' => array('title' => '$$Provider'),
                //'group' => array(),
                'text' => array(
                    'sortBy' => null,
                    'value' => '$${name} #$${id}'
                ),
                'active' => array(
                    'type' => 'boolean'
                )
            ),
            'grids' => array(
                'default' => array(
                    'sort' => 'name',
                    'filter' => array(
                        'layout' => 'inline',
                        'fields' => array(
                            'name' => 'LK',
                            'active' => 'EQ'
                        )
                    )
                )
            )
        );
    }
}
