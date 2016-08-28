<?php die(basename(__FILE__) . ': It is crude!'); ?>
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
            "_description": 
                "Use load layouts+inputs form, this  definition file.",
            "layouts":  {
                "folder": "./layouts/",
                "templates": {"inline": "inline.html.php"}
            },
            "inputs":  {
                "folder": "./inputs/",
                "templates": {
                    "text":         "text.html.php",
                    "select":       "select.html.php",
                    "ui-select":    "ui-select.html.php",
                    "button":       "button.html.php"
                }
            }
        },
        "page": {
            "folder": "./forms/",
            "template": "page.html.php",
            "_description": 
                "Use includes to load layouts+inputs as on filter form",
            "includes": [
                "inputs_layouts.json.php",
                "inputs_fields.json.php"
            ]
        }
    }
}
