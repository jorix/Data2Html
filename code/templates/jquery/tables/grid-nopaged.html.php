<?php die("It's uncooked!"); ?>
<div class="row" id="$${id}">
    <h3>$${title}</h3>
    <div class="col-md-12">
        $${filter}
        <input type="hidden" id="$${id}_sort" value="$${sort}">
        <input type="hidden" id="$${id}_lastKeys" value="">
        <table class="table table-striped table-hover">
            <thead><tr>$${head}</tr></thead>
            <tbody><tr class="clickable" data-d2h-keys="${[keys]}">$${body}</tr></tbody>
        </table>
    </div>
</div>
