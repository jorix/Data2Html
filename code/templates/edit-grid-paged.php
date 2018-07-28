<?php 
return [
    'grid' => [
        'template' => 'grids/grid.html.php',
        'startItems' => 'grids/edit-buttons.php',
        'include' => 'grids/grid-elements.php'
    ],
    'filter' => '@ forms/filter/filter-auto.php',
    'page' =>   '@ forms/page/page.php',
    'require' =>'@ require.php'
];
