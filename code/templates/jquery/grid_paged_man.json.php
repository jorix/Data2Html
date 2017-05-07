<?php die("It's uncooked!"); ?>
{
    "grid": {
        "table": {
            "folder": "tables/",
            "template": "paged.html.php",
            "heads": {
                "folder": "heads/",
                "templates": {
                    "sortable": "sortable.html.php"
                }
            },
            "cells": {
                "folder": "cells/",
                "templates": {
                    "default": "default.html.php"
                }
            }
        },
        "includes": [
            "forms/formFilter/filter_man_.json.php",
            "forms/formPage/page_.json.php"
        ]
    }
}
