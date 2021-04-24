import { Controller } from 'stimulus';
import morphdom from 'morphdom';

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

    $render() {
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
            const debugData = await response.json();
            if (isMostRecent) {
                this._processRerender(debugData)
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
        this._getLoadingElements().forEach(({ element, action, useOnLoading, ...options }) => {
            switch (action) {
                case 'display':
                    if (useOnLoading) {
                        this._showElement(element);
                    } else {
                        this._hideElement(element);
                    }

                    break;
                case 'class':
                    if (useOnLoading) {
                        this._addClass(element, options.className);
                    } else {
                        this._removeClass(element, options.className);
                    }

                    break;
                default:
                    throw new Error(`Unknown action ${action}`);
            }
        });
    }

    _onLoadingFinish() {
        this._getLoadingElements().forEach(({ element, action, useOnLoading, ...options }) => {
            switch (action) {
                case 'display':
                    if (useOnLoading) {
                        this._hideElement(element);
                    } else {
                        this._showElement(element);
                    }

                    break;
                case 'class':
                    if (useOnLoading) {
                        this._removeClass(element, options.className);
                    } else {
                        this._addClass(element, options.className);
                    }

                    break;
                default:
                    throw new Error(`Unknown action ${action}`);
            }
        });
    }

    _getLoadingElements() {
        const elements = [];

        this.element.querySelectorAll('[live\\:loading]').forEach((element => {
            const options = element.getAttribute('live:loading');

            // data-live-loading with no value OR ="show" -> "show on loading"
            const useOnLoading = options == '' || options === 'show';

            elements.push({
                element,
                action: 'display',
                useOnLoading,
            });
        }));

        this.element.querySelectorAll('[live\\:loading-class]').forEach((element => {
            const className = element.getAttribute('live:loading-class');

            elements.push({
                element,
                action: 'class',
                // TODO - allow a modifier, like
                // live:loading-class="remove->bg-gray"
                // to allow us to HIDE this class on loading
                useOnLoading: true,
                className,
            });
        }));

        return elements;
    }

    _showElement(element) {
        // TODO - allow different "display" types
        element.style.display = 'inline-block';
    }

    _hideElement(element) {
        element.style.display = 'none';
    }

    _addClass(element, className) {
        // todo - do we need to allow multiple classes?
        element.classList.add(className);
    }

    _removeClass(element, className) {
        element.classList.remove(className);
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
