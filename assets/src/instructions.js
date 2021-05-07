const isAlphaNumeric = function(char) {
    return char.match(/^[a-z0-9]+$/i) !== null;
}

export function parseInstructions(content) {
    const instructions = [];

    if (!content) {
        return instructions;
    }

    let currentActionName = '';
    let currentArgumentName = '';
    let currentArguments = [];
    let state = 'action';

    const getLastActionName = function() {
        if (instructions.length === 0) {
            throw new Error('Could not find any instructions');
        }

        return instructions[instructions.length - 1].action;
    }
    const pushInstruction = function() {
        instructions.push({
            action: currentActionName,
            args: currentArguments,
        });
        currentActionName = '';
        currentArgumentName = '';
        currentArguments = [];
    }
    const pushArgument = function() {
        currentArguments.push(currentArgumentName);
        currentArgumentName = '';
    }

    for (var i = 0; i < content.length; i++) {
        const char = content[i];
        switch(state) {
            case 'action_separation':
                // we just finished an action(), and now we need a space
                if (char !== ' ') {
                    throw new Error(`Missing space after ${getLastActionName()}()`)
                }

                state = 'action';

                break;

            case 'action':
                // we're expecting more characters for an action name
                if (isAlphaNumeric(char)) {
                    currentActionName += char;

                    break;
                }

                if (char === '(') {
                    state = 'arguments';

                    break;
                }

                if (char === ' ') {
                    // this is the end of the action and it has no arguments
                    // if the action had args(), it was already recorded
                    if (currentActionName) {
                        pushInstruction();
                    }

                    break;
                }

                throw new Error(`Unexpected character ${char} after action ${currentActionName}`);

            case 'arguments':
                if (char === ')') {
                    // end of action and arguments
                    pushArgument();
                    pushInstruction();

                    state = 'action_separation';

                    break;
                }

                if (char === ' ') {
                    // end of current argument
                    pushArgument();

                    break;
                }

                // add next character to argument
                currentArgumentName += char;
        }
    }

    switch (state) {
        case 'action':
            if (currentActionName) {
                pushInstruction();
            }

            break;
        case 'arguments':
            throw new Error(`Did you forget to add a closing ") after "${currentActionName}"?`)
    }

    return instructions;
}
