import { Controller } from 'stimulus';
import morphdom from 'morphdom';

export default class extends Controller {
    connect() {
        console.log('connected!');
    }
}
