<?php die("It's uncooked!"); ?>
<div class="row" id="$${id}">
    $${filter}
    <div class="col-md-12">
        <h3>$${title}</h3>
        <input type="hidden" id="$${id}_sort" value="$${sortBy}">
        <input type="hidden" id="$${id}_lastKeys" value="">
        <table class="table table-striped table-hover">
            <thead><tr>$${head}</tr></thead>
            <tfoot><tr>
                <td colspan="$${colCount}">
                    $${page}
                </td>
            </tr></tfoot>
            <tbody><tr class="clickable" data-d2h-keys="${[keys]}">$${body}</tr></tbody>
        </table>
    </div>
</div>
