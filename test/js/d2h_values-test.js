var i = 4;
describe('d2h_values', function() {
    before(function () {
        var div = testDiv_create({
            js: [
                '/external/date_time/moment-2.18.1/min/moment-with-locales.js',
                '/test/php/lang-test.js.php',
                '/code/js/d2h_values.js'
            ]
        });
    });
    beforeEach(function () {
        $('#testDiv').text('<div>' + ++i + '</div>');
    });
    afterEach(function() {
        $('#testDiv').text('<div>(' + ++i + ')</div>');
    });
    after(function() {
        testDiv_remove();
    });
    
    describe('toHtml()', function() {
        it('return same value without argument type', function() {
            expect(d2h_values.toHtml('text')).to.be('text');
        });
    });
    describe('validate()', function() {
        describe("check required", function() {
            it("blank text is integer if is not required", function() {
                expect(
                    d2h_values.validate('  ', {type: 'integer'})
                ).to.be(null);
            });
            it("blank text is not valid, is required", function() {
                expect(
                    d2h_values.validate('  ', {required:true})[0]
                ).to.be(__('validate/required'));
            });
        });
        describe("check types", function() {
            it("number with only zeros as decimals are integer", function() {
                expect(
                    d2h_values.validate('1234.000', {type: 'integer'})
                ).to.be(null);
            });
        });
    });
});
