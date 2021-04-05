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

    renderDebounceTimeout;

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

    async $render() {
        const params = new URLSearchParams({
            component: this.componentValue,
            // no "action" here: we are only rendering the model with
            // the given data
            data: JSON.stringify(this.dataValue),
            props: JSON.stringify(this.propsValue),
        });
        const response = await fetch(`/components?${params.toString()}`);
        const data = await response.json();

        // "html" is the key on the JSON where the HTML is stored
        const newElement = this.element.cloneNode();
        newElement.innerHTML = data.html;

        morphdom(this.element, newElement);

        // "data" holds the new, updated data
        // TODO: solve race condition where data was updated while we were
        // waiting for this AJAX call to finish. Or document it
        this.dataValue = data.data;
        // "props" holds the original props... which should not have changed...
        // but in theory, they could have. If they did, they would come with
        // a new checksum attached anyways
        this.propsValue = data.props;
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
                this.$render();
                this.renderDebounceTimeout = null;
            }, this.debounceTimeout || 150);
        }
    }
}
