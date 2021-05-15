import { Controller } from 'stimulus';
import morphdom from 'morphdom';
import { parseDirectives } from '../src/directives_parser';
import '../styles/live.css';
import { combineSpacedArray } from '../src/string_utils';

const DEFAULT_DEBOUNCE = '150';

export default class extends Controller {
    static values = {
        url: String,
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
     * The current "timeout" that's waiting before an action should
     * be taken.
     *
     * TODO: this timeout should possible be specific to the exact action
     * being taken so that another quick action taken doesn't clear this.
     */
    actionDebounceTimeout = null;

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
        const rawAction = event.currentTarget.dataset.actionName;

        // data-action-name="prevent.debounce(1000).save"
        const directives = parseDirectives(rawAction);

        directives.forEach((directive) => {
            // set here so it can be delayed with debouncing below
            const _executeAction = () => {
                this._makeRequest(false, directive.action);
            }

            let handled = false;
            directive.modifiers.forEach((modifier) => {
                switch (modifier.name) {
                    case 'prevent':
                        event.preventDefault();
                        break;
                    case 'stop':
                        event.stopPropagation();
                        break;
                    case 'self':
                        if (event.target !== event.currentTarget) {
                            return;
                        }
                        break;
                    case 'debounce':
                        const length = modifier.value ? modifier.value : DEFAULT_DEBOUNCE;

                        // clear any pending renders
                         if (this.actionDebounceTimeout) {
                             clearTimeout(this.actionDebounceTimeout);
                             this.actionDebounceTimeout = null;
                         }

                         this.actionDebounceTimeout = setTimeout(() => {
                             this.actionDebounceTimeout = null;
                             _executeAction();
                         }, length);

                         handled = true;

                         break;
                    default:
                        console.warn(`Unknown modifier ${modifier.name} in action ${rawAction}`);
                }
            });

            if (!handled) {
                _executeAction();
            }
        })
    }

    $render() {
        this._makeRequest(true, null);
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
            }, this.debounceValue || DEFAULT_DEBOUNCE);
        }
    }

    _makeRequest(allowGetMethod, action) {
        let [url, queryString] = this.urlValue.split('?');
        const params = new URLSearchParams(queryString || '');

        if (action) {
            url += `/${encodeURIComponent(action)}`;
        }

        const fetchOptions = {
            headers: {
                'Accept': 'application/json',
            },
        };
        if (allowGetMethod && this._willDataFitInUrl()) {
            Object.keys(this.dataValue).forEach((key => {
                params.set(key, this.dataValue[key]);
            }));
            fetchOptions.method = 'GET';
        } else {
            const formData = new FormData();
            // todo - handles files
            Object.keys(this.dataValue).forEach((key => {
                formData.append(key, this.dataValue[key]);
            }));
            fetchOptions.method = 'POST';
            fetchOptions.body = formData;
        }

        // todo: make this work for specific actions, or models
        this._onLoadingStart();
        const paramsString = params.toString();
        const thisPromise = fetch(`${url}${paramsString.length > 0 ? `?${paramsString}` : ''}`, fetchOptions);
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
        this._getLoadingDirectives().forEach(({ element, directives }) => {
            // so we can track, at any point, if an element is in a "loading" state
            element.dataset.liveIsLoading = isLoading;

            directives.forEach((directive) => {
                this._handleLoadingDirective(element, isLoading, directive)
            });
        });
    }

    /**
     * @param {Element} element
     * @param {boolean} isLoading
     * @param {Directive} directive
     * @private
     */
    _handleLoadingDirective(element, isLoading, directive) {
        const finalAction = parseLoadingAction(directive.action, isLoading);

        let loadingDirective = null;

        switch (finalAction) {
            case 'show':
                // todo error on args - e.g. show(foo)
                loadingDirective = () => {
                    this._showElement(element)
                };
                break;

            case 'hide':
                // todo error on args
                loadingDirective = () => this._hideElement(element);
                break;

            case 'addClass':
                loadingDirective = () => this._addClass(element, directive.args);
                break;

            case 'removeClass':
                loadingDirective = () => this._removeClass(element, directive.args);
                break;

            case 'addAttribute':
                loadingDirective = () => this._addAttribute(element, directive.args);
                break;

            case 'removeAttribute':
                loadingDirective = () => this._removeAttribute(element, directive.args);
                break;

            default:
                throw new Error(`Unknown data-loading action "${finalAction}"`);
        }

        let isHandled = false;
        directive.modifiers.forEach((modifier => {
            switch (modifier.name) {
                case 'delay':
                    // if loading has *stopped*, the delay modifier has no effect
                    if (!isLoading) {
                        break;
                    }

                    const delayLength = modifier.value || 200;
                    setTimeout(() => {
                        if (element.dataset.liveIsLoading) {
                            loadingDirective();
                        }
                    }, delayLength);

                    isHandled = true;

                    break;
                default:
                    throw new Error(`Unknown modifier ${modifier.name} used in the loading directive ${directive.getString()}`)
            }
        }));

        // execute the loading directive
        if(!isHandled) {
            loadingDirective();
        }
    }

    _getLoadingDirectives() {
        const loadingDirectives = [];

        this.element.querySelectorAll('[data-loading]').forEach((element => {
            // use "show" if the attribute is empty
            const directives = parseDirectives(element.dataset.loading || 'show');

            loadingDirectives.push({
                element,
                directives,
            });
        }));

        return loadingDirectives;
    }

    _showElement(element) {
        // TODO - allow different "display" types
        element.style.display = 'inline-block';
    }

    _hideElement(element) {
        element.style.display = 'none';
    }

    _addClass(element, classes) {
        element.classList.add(...combineSpacedArray(classes));
    }

    _removeClass(element, classes) {
        element.classList.remove(...combineSpacedArray(classes));
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

    _willDataFitInUrl() {
        // if the URL gets remotely close to 2000 chars, it may not fit
        return Object.values(this.dataValue).join(',').length < 1500;
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
