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
        "filter": {
            "folder": "forms",
            "template": "filter.html.php",
            "includes": [
                "inputs_layouts.json.php",
                "inputs_fields.json.php"
            ]
        },
        "page": {
            "folder": "./forms/",
            "template": "page.html.php",
            "includes": [
                "inputs_layouts.json.php",
                "inputs_fields.json.php"
            ]
        }
    }
}
