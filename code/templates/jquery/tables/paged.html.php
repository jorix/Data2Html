<?php die("It's uncooked!"); ?>
<div class="row"
    id="$${id}"
    data-d2h="
        url:'$${url}',
        repeat:'table tbody tr',
        visual: $${visual},
        actions: {
            'edit': function(elem) {
                    var keys = this.getKeys(elem, 'info'); 
                    d2h_switchTo.go(this, 'edit').load({keys:keys});
            },
            'copy': function() {},
            'delete': function(elem) {
                    var keys = this.getKeys(elem, 'info'); 
                    d2h_switchTo.go(this, 'edit').load({keys:keys});
            },
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
        sort: '#$${id}_sort'
    "
>
    $${filter}
    <div class="col-md-12">
        <h3>$${title}</h3>
        <input id="$${id}_sort" type="text" value="$${sortBy}">
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
