<?php 
$return = array(
    "grid" => array(
        "table" => array(
            "template" => "tables/grid-paged.html.php",
            "startItems" => "tables/edit-buttons.php",
            "include" => "tables/grid-elements.php"
        ),
        "includes" => array(
            "forms/formFilter/filter-auto.php",
            "forms/formPage/page.php"
        )
    )
);
