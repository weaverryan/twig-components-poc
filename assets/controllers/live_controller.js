import { Controller } from 'stimulus';
import morphdom from 'morphdom';

export default class extends Controller {
    static values = {
        component: String,
        data: Object,
        props: Object,
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
            props: JSON.stringify(this.propsValue),
        });
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
        // "props" holds the original props... which should not have changed...
        // but in theory, they could have. If they did, they would come with
        // a new checksum attached anyways
        this.propsValue = data.props;
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
