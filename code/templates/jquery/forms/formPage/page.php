<?php 
$return = array(
    'page' => array(
        'folder' => './',
        'template' => 'page.html.php',
        'startItems' => 'page-inputs.php',
        "include" => "../assign-template.php",
        "layouts" => array(
            "folderTemplates" => "../i_layouts_block/"
        ),
        "contents" => array(
            "folderTemplates" => "../inputs/"
        )
    )
);