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
            "forms/filter.json.php",
            "forms/page.json.php"
        ]
    }
}
