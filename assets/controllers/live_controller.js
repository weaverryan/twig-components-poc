import { Controller } from 'stimulus';

export default class extends Controller {
    static values = {
        component: String,
        state: Object,
    }

    /**
     * Called to update one piece of the model
     */
    async update(event) {
        const model = event.currentTarget.dataset.model;
        // todo - handle modifiers like "defer"

        const params = new URLSearchParams({
            component: this.componentValue,
            action: 'updateModel',
            state: new URLSearchParams(this.stateValue).toString(),
            // these is extra data that will be available as controller args
            values: new URLSearchParams({
                model,
                value: event.currentTarget.value
            }).toString()
        });

        // need to think about the URL structure... I really had this RPC stuff
        const response = await fetch(`/components?${params.toString()}`);
        const data = await response.json();

        // TODO - diff the HTML
        // "html" is the key on the JSON where the HTML is stored
        this.element.innerHTML = data.html;
        // "state" holds the new, updated state
        this.stateValue = data.state;
    }

    action(event) {
        console.log('todo');
    }
}
