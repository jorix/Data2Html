// import d2h_display from '../../code/js/d2h_display.js';


// var _testServer = function(objElem) {
    // this.objElem = objElem;
// };
// _testServer.prototype = {
    // objElem: null, // The DOM element
    // $: function(selector, elem) {
        // return $(selector, (elem ? elem : this.objElem))
            // .filter('[data-d2h-from=' + this.objElem.id + ']');
    // }
// };
var i = 4;
describe('dh2_display', function() {
    before(function () {
        var div = testDiv_create();
        $(div).html('<scrip src="../../code/js/d2h_display.js"></scrip><div id="testWork"></div>');
        
    });
    beforeEach(function () {
        $('#testWork').text('<div>' + ++i + '</div>');
    });
    afterEach(function() {
        $('#testWork').text('<div>(' + ++i + ')</div>');
    });
    after(function() {
        testDiv_remove();
    });
    
    describe('#indexOf()', function() {
        it('should return -1 whxxxxen the value is not present', function() {
            expect([1,2,3].indexOf(4)).to.be(-1);
        });
    });
    describe('#intttdexOf()', function() {
        it('should return -1 whxxxxen the value is not present', function() {
            expect([1,2,3].indexOf(4)).to.be(-1);
        });
    });
});
