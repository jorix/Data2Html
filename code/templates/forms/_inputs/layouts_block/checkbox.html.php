<?php die("It's uncooked!"); ?>
$${body-width = 2}
$${_level-0?[[
    $${label-width = 2}
    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-2 $${visualClassLayout}">
            <div class="checkbox">
                <label>$${body} $${title}</label>
            </div>
        </div>
    </div>
]]:[[
    $${label-width = 0}
    <div class="col-sm-2 $${visualClassLayout}">
        <div class="checkbox">
            <label>$${body} $${title}</label>
        </div>
    </div>
]]}
