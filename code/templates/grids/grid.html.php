<?php die("It's uncooked!"); ?>
<div class="row" id="$${id}">
<div data-d2h-message="{for:'#$${id} tbody', position:'top-start'}"></div>
<div data-d2h-message="{for:'#$${id} tbody', position:'bottom-start'}"></div>
    <h3>$${title}</h3>
    <div class="col-md-12">
        $${filter ? [[$${filter}]]}
        <input type="hidden" id="$${id}_sort" value="$${sort}">
        <input type="hidden" id="$${id}_lastKeys" value="">
        <table class="table table-sm table-striped table-hover">
            <thead><tr>$${head}</tr></thead>
            <tbody><tr class="clickable" data-d2h-keys="${[keys]}" data-d2h-on="click:show-edit">$${body}</tr></tbody>
            $${page ? [[
            <tfoot><tr>
                <td colspan="$${colCount}">
                    $${page}
                </td>
            </tr></tfoot>
            ]]}
        </table>
    </div>
</div>
