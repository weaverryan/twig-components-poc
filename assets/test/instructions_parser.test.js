import { parseInstructions } from '../src/instructions';

describe('instructions_parser', () => {
    it('parses no attribute value', () => {
        // <span data-loading> (no attribute value)
        const instructions = parseInstructions(null);
        expect(instructions).toHaveLength(0);
    });

    it('parses an empty attribute', () => {
        // <span data-loading="">
        const instructions = parseInstructions('');
        expect(instructions).toHaveLength(0);
    });

    it('parses a simple action', () => {
        // data-loading="hide"
        const instructions = parseInstructions('hide');
        expect(instructions).toHaveLength(1);
        expect(instructions[0]).toEqual({
            action: 'hide',
            args: []
        })
    });

    it('parses an action with a simple argument', () => {
        const instructions = parseInstructions('addClass(opacity-50)');
        expect(instructions).toHaveLength(1);
        expect(instructions[0]).toEqual({
            action: 'addClass',
            args: ['opacity-50']
        })
    });

    it('parses an action with multiple arguments', () => {
        const instructions = parseInstructions('addClass(opacity-50 disabled)');
        expect(instructions).toHaveLength(1);
        expect(instructions[0]).toEqual({
            action: 'addClass',
            args: ['opacity-50', 'disabled']
        })
    });

    it('parses multiple actions simple', () => {
        const instructions = parseInstructions('addClass(opacity-50) addAttribute(disabled)');
        expect(instructions).toHaveLength(2);
        expect(instructions[0]).toEqual({
            action: 'addClass',
            args: ['opacity-50']
        })
        expect(instructions[1]).toEqual({
            action: 'addAttribute',
            args: ['disabled']
        })
    });

    it('parses multiple actions with multiple arguments', () => {
        const instructions = parseInstructions('hide addClass(opacity-50 disabled) addAttribute(disabled)');
        expect(instructions).toHaveLength(3);
        expect(instructions[0]).toEqual({
            action: 'hide',
            args: []
        })
        expect(instructions[1]).toEqual({
            action: 'addClass',
            args: ['opacity-50', 'disabled']
        })
        expect(instructions[2]).toEqual({
            action: 'addAttribute',
            args: ['disabled']
        })
    });

    describe('errors on syntax errors', () => {
        it('missing ending )', () => {
            expect(() => {
                parseInstructions('addClass(opacity-50');
            }).toThrow('Did you forget to add a closing ") after "addClass"?')
        });

        it('missing ending before next action', () => {
            expect(() => {
                parseInstructions('addClass(opacity-50 hide');
            }).toThrow('Did you forget to add a closing ") after "addClass"?')
        });

        it('no space between actions', () => {
            expect(() => {
                parseInstructions('addClass(opacity-50)hide');
            }).toThrow('Missing space after addClass()')
        });
    });
});
