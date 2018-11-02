<?php die("It's uncooked!"); ?>
$${include d2h_message, d2h_serverBlock}
<div class="row" id="$${id}" data-d2h-keys="${[keys]}">
<div data-d2h-message="{for:'#$${id} .form-horizontal', position:'top-start'}"></div>
<div data-d2h-message="{for:'#$${id} .form-horizontal', position:'bottom-start'}"></div>
    <h3>edit $${title}</h3>
    <div class="col-md-12">
        <div class="form-horizontal">$${body}</div>
    </div>
</div>
