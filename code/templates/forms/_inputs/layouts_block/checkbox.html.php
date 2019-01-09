<?php exit("It's uncooked!"); ?>
$${body-width = 2}
$${_level-0?[[
    $${label-width = 2}
    <div class="offset-sm-2 col-sm-auto custom-control custom-checkbox $${visualClassLayout}">
        $${body}
        <label class="custom-control-label col-form-label" for="$${id}">$${title}</label>
    </div>
]]:[[
    $${label-width = 0}
    <div class="col-auto custom-control custom-checkbox $${visualClassLayout}">
        $${body}
        <label class="custom-control-label col-form-label" for="$${id}">$${title}</label>
    </div>
]]}

