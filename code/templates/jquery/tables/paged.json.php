<?php die("It's uncooked!"); ?>
{
    "table": {
        "template": "paged.html.php",
        "startItems": "crud.buttons.php",
        "heads": {
            "folder": "heads/",
            "templates": {
                "base": "base.html.php",
                "blank": "blank.html",
                "sortable": "sortable.html.php"
            }
        },
        "heads_layouts": {
            "folder": "heads_layouts/",
            "templates": {
                "base": "base.html.php"
            }
        },
        "cells": {
            "folder": "cells/",
            "templates": {
                "base": "base.html.php"
            }
        },
        "cells_layouts": {
            "folder": "cells_layouts/",
            "templates": {
                "base": "base.html.php"
            }
        },
        "includeFolders": {
            "inputs": "../forms/inputs"
        }
    }
}
