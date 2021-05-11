import { Controller } from 'stimulus';
import morphdom from 'morphdom';
import { parseInstructions } from '../src/instructions';
import '../styles/live.css';

export default class extends Controller {
    static values = {
        component: String,
        data: Object,
        /**
         * The Debounce timeout.
         *
         * Default: 150
         */
        debounce: Number,
    }

    /**
     * The current "timeout" that's waiting before a model update
     * triggers a re-render.
     */
    renderDebounceTimeout = null;

    /**
     * A stack of all current AJAX Promises for re-rendering.
     *
     * @type {PromiseStack}
     */
    renderPromiseStack = new PromiseStack();

    connect() {
        // hide "loading" elements to begin with
        // TODO: this might be done with CSS, as Livewire does
        // e.g. [wire\:loading] {display: none;}
        this._onLoadingFinish();
    }

    /**
     * Called to update one piece of the model
     */
    update(event) {
        const model = event.currentTarget.dataset.model;
        const value = event.currentTarget.value;

        // todo - handle modifiers like "defer"
        this.$updateModel(model, value, true);
    }

    updateDefer(event) {
        const model = event.currentTarget.dataset.model;
        const value = event.currentTarget.value;

        // todo - handle modifiers like "defer"
        this.$updateModel(model, value, false);
    }

    action(event) {
        // TODO - add validation for this in case it's missing
        const action = event.currentTarget.dataset.actionName;

        this._makeRequest('POST', action);
    }

    $render() {
        this._makeRequest('GET', null);
        const params = new URLSearchParams({
            component: this.componentValue,
            // no "action" here: we are only rendering the model with
            // the given data
            data: JSON.stringify(this.dataValue),
        });

        // todo: make this work for specific actions, or models
        this._onLoadingStart();
        const thisPromise = fetch(`/components?${params.toString()}`);
        this.renderPromiseStack.addPromise(thisPromise);
        thisPromise.then(async (response) => {
            // if another re-render is scheduled, do not "run it over"
            if (this.renderDebounceTimeout) {
                return;
            }

            const isMostRecent = this.renderPromiseStack.removePromise(thisPromise);
            if (isMostRecent) {
                this._processRerender(await response.json())
                this._onLoadingFinish();
            }
        })
    }

    $updateModel(model, value, shouldRender) {
        // we do not send old and new data to the server
        // we merge in the new data now,
        this.dataValue = { ...this.dataValue, [model]: value}

        if (shouldRender) {
            // clear any pending renders
            if (this.renderDebounceTimeout) {
                clearTimeout(this.renderDebounceTimeout);
                this.renderDebounceTimeout = null;
            }

            // todo - make timeout configurable with a value
            this.renderDebounceTimeout = setTimeout(() => {
                this.renderDebounceTimeout = null;
                this.$render();
            }, this.debounceValue || 150);
        }
    }

    _makeRequest(method, action) {
        const params = {
            component: this.componentValue,
        };

        if (action) {
            params.action = action;
        }

        const fetchOptions = { method };
        if (method === 'GET') {
            // TODO: we should query params, not JSON here
            params.data = JSON.stringify(this.dataValue);
        } else {
            fetchOptions.body = JSON.stringify(this.dataValue);
        }

        // todo: make this work for specific actions, or models
        this._onLoadingStart();
        const thisPromise = fetch(`/components?${new URLSearchParams(params).toString()}`, fetchOptions);
        this.renderPromiseStack.addPromise(thisPromise);
        thisPromise.then(async (response) => {
            // if another re-render is scheduled, do not "run it over"
            // todo: think if this should behave differently for actions
            if (this.renderDebounceTimeout) {
                return;
            }

            const isMostRecent = this.renderPromiseStack.removePromise(thisPromise);
            if (isMostRecent) {
                this._processRerender(await response.json())
                this._onLoadingFinish();
            }
        })
    }

    /**
     * Processes the response from an AJAX call and uses it to re-render.
     *
     * @todo Make this truly private
     *
     * @private
     */
    _processRerender(data) {
        // merge/patch in the new HTML
        const newElement = this.element.cloneNode();
        newElement.innerHTML = data.html;
        morphdom(this.element, newElement);

        // "data" holds the new, updated data
        this.dataValue = data.data;
    }

    _onLoadingStart() {
        this._handleLoadingToggle(true);
    }

    _onLoadingFinish() {
        this._handleLoadingToggle(false);
    }

    _handleLoadingToggle(isLoading) {
        this._getLoadingInstructions().forEach(({ element, instructions }) => {
            instructions.forEach(({action, args}) => {
                this._handleLoadingInstruction(element, isLoading, action, args)
            });
        });
    }

    /**
     * @param {Element} element
     * @param {boolean} isLoading
     * @param {string} action
     * @param {Array} args
     * @private
     */
    _handleLoadingInstruction(element, isLoading, action, args) {
        const finalAction = parseLoadingAction(action, isLoading);

        switch (finalAction) {
            case 'show':
                // todo error on args
                this._showElement(element);
                break;

            case 'hide':
                // todo error on args
                this._hideElement(element);
                break;

            case 'addClass':
                this._addClass(element, args);
                break;

            case 'removeClass':
                this._removeClass(element, args);
                break;

            case 'addAttribute':
                this._addAttribute(element, args);
                break;

            case 'removeAttribute':
                this._removeAttribute(element, args);
                break;

            default:
                throw new Error(`Unknown data-loading action "${finalAction}"`);
        }
    }

    _getLoadingInstructions() {
        const loadingInstructions = [];

        this.element.querySelectorAll('[data-loading]').forEach((element => {
            const instructions = parseInstructions(element.dataset.loading, 'show');
            // make data-loading === data-loading="show"
            if (instructions.length === 0) {
                instructions.push({
                    action: 'show',
                    args: [],
                })
            }

            loadingInstructions.push({
                element,
                instructions,
            });
        }));

        return loadingInstructions;
    }

    _showElement(element) {
        // TODO - allow different "display" types
        element.style.display = 'inline-block';
    }

    _hideElement(element) {
        element.style.display = 'none';
    }

    _addClass(element, classes) {
        element.classList.add(...classes);
    }

    _removeClass(element, classes) {
        element.classList.remove(...classes);
    }

    _addAttribute(element, attributes) {
        attributes.forEach((attribute) => {
            element.setAttribute(attribute, '');
        })
    }

    _removeAttribute(element, attributes) {
        attributes.forEach((attribute) => {
            element.removeAttribute(attribute);
        })
    }
}

/**
 * Tracks the current "re-render" promises.
 *
 * @todo extract to a module
 */
class PromiseStack {
    stack = [];

    addPromise(promise) {
        this.stack.push(promise);
    }

    /**
     * Removes the promise AND returns if it is the most recent.
     *
     * @param {Promise} promise
     * @return {boolean}
     */
    removePromise(promise) {
        const index = this.findPromiseIndex(promise);

        // promise was not found - it was removed because a new Promise
        // already resolved before it
        if (index === -1) {
            return false;
        }

        // "save" whether this is the most recent or not
        const isMostRecent = this.stack.length === (index + 1);

        // remove all promises starting from the oldest up through this one
        this.stack.splice(0, index + 1);

        return isMostRecent;
    }

    findPromiseIndex(promise) {
        return this.stack.findIndex((item) => item === promise);
    }
}

const parseLoadingAction = function(action, isLoading) {
    switch (action) {
        case 'show':
            return isLoading ? 'show' : 'hide';
        case 'hide':
            return isLoading ? 'hide' : 'show';
        case 'addClass':
            return isLoading ? 'addClass' : 'removeClass';
        case 'removeClass':
            return isLoading ? 'removeClass' : 'addClass';
        case 'addAttribute':
            return isLoading ? 'addAttribute' : 'removeAttribute';
        case 'removeAttribute':
            return isLoading ? 'removeAttribute' : 'addAttribute';
    }

    throw new Error(`Unknown data-loading action "${action}"`);
}
