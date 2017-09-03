<?php die("It's uncooked!"); ?>
<div class="row"
    id="$${id}"
    data-d2h="
        url:'$${url}',
        repeat:'table tbody tr',
        visual: $${visual},
        actions: {
            'edit': function(elem) {d2h_switchTo.go(this, 'edit').load({elemKeys:elem}); },
            'copy': function() {d2h_sortBy.show(this, '!name');},
            'delete': function(elem) {d2h_switchTo.go(this, 'edit').load({elemKeys:elem});},
            'create': function() {d2h_switchTo.go(this, 'edit').clear();}
        },
        filter:['#$${id}_filter', {
            actions: {
                'readPage': function() {this.load();}
            }
        }],
        page: ['#$${id}_page', {
            actions: {
                'readPage': function() {this.load();},
                'nextPage': function() {this.load({add:true});}
            }
        }],
        sortElem: '#$${id}_sort',
        sortBy: '$${sortBy}'"
        
>
    $${filter}
    <div class="col-md-12">
        <h3>$${title}</h3>
        <input id="$${id}_sort" type="text">
        <table class="table table-striped table-bordered">
            <thead><tr>$${thead}</tr></thead>
            <tfoot><tr>
                <td colspan="$${colCount}">
                    $${page}
                </td>
            </tr></tfoot>
            <tbody><tr data-d2h-keys="${[keys]}">$${tbody}</tr></tbody>
        </table>
    </div>
</div>
