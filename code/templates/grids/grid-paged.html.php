<?php die("It's uncooked!"); ?>
<div class="row" id="$${id}">
<div data-d2h-message-for="$${id}" data-d2h-message-pos="bottom-start"></div>
<div data-d2h-message-for="$${id}" data-d2h-message-pos="top-start"></div>
    <h3>$${title}</h3>
    <div class="col-md-12">
        $${filter}
        <input type="hidden" id="$${id}_sort" value="$${sort}">
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
