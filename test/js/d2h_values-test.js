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
    after(function() {
        testDiv_remove();
    });
    
    describe('toHtml()', function() {
        it('return same value without argument type', function() {
            expect(d2h_values.toHtml('text')).to.equal('text');
        });
    });
    
    describe('validateValue()', function() {
        var _validateValue = function(val, visual) {
            var val = d2h_values.validateValue(val, visual);
            if (val.errors) {
                return val.errors[0];
            } else {
                return null;
            }
        };
        describe("required", function() {
            it("blank text is integer if is not required", function() {
                expect(_validateValue('  ', {type: 'integer'})).to.be.null;
            });
            it("blank text is not valid, is required", function() {
                expect(_validateValue('  ', {required:true})).to.equal(__('validate/required'));
            });
        });
        describe("validate type", function() {
            it("throw error where uses a type not supported", function() {
                expect(function () {
                    _validateValue('1234', {type: 'fake-type'})
                }).to.throw();
            });
            describe("numeric", function() {
                it(".0 is valid numeric expression", function() {
                    expect(_validateValue('.0', {type: 'number'})).to.be.null;
                });
                it(".0 is valid integer expression", function() {
                    expect(_validateValue('.0', {type: 'integer'})).to.be.null;
                });
                it("number with start or end spaces is parsed as valid", function() {
                    expect(_validateValue(' 1234 ', {type: 'number'})).to.be.null;
                });
                it("not numeric expressions are detected", function() {
                    expect(_validateValue('12 34.001', {type: 'number'})).to
                        .equal(__('validate/not-number'));
                    expect(_validateValue('12+34.001', {type: 'number'})).to
                        .equal(__('validate/not-number'));
                    expect(_validateValue('12.345.123', {type: 'number'})).to
                        .equal(__('validate/not-number'));
                    expect(_validateValue('12.345,123', {type: 'number'})).to
                        .equal(__('validate/not-number'));
                });
                it("value with decimals is the correct number without error", function() {
                    expect(d2h_values.validateValue(' -12,345.678 ', {type: 'number'}).value).to
                        .equal(-12345.678);
                    expect(_validateValue(' -12,345.678 ', {type: 'number'})).to.null;
                });
            });
            describe("numeric using comma as decimal separator (lang=es)", function() {
                before(function () {
                    var div = testDiv_create({
                        js: [
                            '/external/date_time/moment-2.18.1/min/moment-with-locales.js',
                            '/test/php/lang-test.js.php?lang=es',
                            '/code/js/d2h_values.js'
                        ]
                    });
                });
                after(function () {
                    var div = testDiv_create({
                        js: [
                            '/external/date_time/moment-2.18.1/min/moment-with-locales.js',
                            '/test/php/lang-test.js.php',
                            '/code/js/d2h_values.js'
                        ]
                    });
                });
                it("value with decimals is the correct number without error", function() {
                    expect(d2h_values.validateValue(' +12.345,6789 ', {type: 'number'}).value).to
                        .equal(12345.6789);
                    expect(_validateValue(' +12.345,6789 ', {type: 'number'})).to.null;
                    expect(_validateValue(' +12,345.6789 ', {type: 'number'})).to.not.null;
                });
            });
            describe("integer", function() {
                it("number with only zeros as decimals are integer", function() {
                    expect(_validateValue('1,234.000', {type: 'integer'})).to.be.null;
                });
                it("number with decimals are not integer", function() {
                    expect(_validateValue('1234.001', {type: 'integer'})).to
                        .equal(__('validate/not-integer'));
                });
                
            });
        });
    });
    
    describe('validateData()', function() {
        var _result = null;
        it("it is executed!", function() {
            _result = d2h_values.validateData(
                {
                    a:'is a',
                    number:'123.456',
                    no_number:'12 34',
                    'null': ''
                }, {
                    number:{type: 'number'},
                    no_number:{type: 'number'},
                    'null': {type: 'number'}
                }
            );
        });
        it("only error messages for incorrect items", function() {
            console.log(_result);
            expect(_result).to.nested.include({
                'errors.no_number[0]': __('validate/not-number')
            });
            expect(Object.keys(_result.errors)).to.have.lengthOf(1);
        });
        it("values are as expected", function() {
            expect(_result).to.nested.include({'data.a': 'is a'});
            expect(_result).to.nested.include({'data.number': 123.456});
            expect(_result).to.nested.include({'data.null': null});
        });
    });
});
