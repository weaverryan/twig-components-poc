import { Controller } from 'stimulus';
import morphdom from 'morphdom';

export default class extends Controller {
    static values = {
        component: String,
        data: Object,
        props: Object,
    }

    /**
     * Called to update one piece of the model
     */
    async update(event) {
        const model = event.currentTarget.dataset.model;
        // todo - handle modifiers like "defer"

        // we do not send old and new data to the server
        // we merge in the new data now,
        this.dataValue = { ...this.dataValue, [model]: event.currentTarget.value}

        const params = new URLSearchParams({
            component: this.componentValue,
            // no "action" here: we are only rendering the model with
            // the given data
            data: JSON.stringify(this.dataValue),
            props: JSON.stringify(this.propsValue),
        });

        // need to think about the URL structure... I really had this RPC stuff
        const response = await fetch(`/components?${params.toString()}`);
        const data = await response.json();

        // "html" is the key on the JSON where the HTML is stored
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
