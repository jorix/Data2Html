<?php die("It's uncooked!"); ?>
{
    "table": {
        "template": "paged.html.php",
        "heads": {
            "folder": "heads/",
            "templates": {
                "sortable": "sortable.html.php",
                "none": "none.html"
            }
        },
        "startItems": "crud.buttons.json.php",
        "cells": {
            "folder": "cells/",
            "templates": {
                "default": "default.html.php",
                "button": "button.html.php"
            }
        }
    }
}
