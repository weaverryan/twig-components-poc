# Twig Components POC

Adds "component" twig function/tag which is similar to twig's standard `embed` but backed by a PHP object
(Symfony service). Components define their name to be used with the twig function/tag (defaults to snake
case class short name). They also define the template to render (defaults to `components/{name}.html.twig`).

Component templates can have "slots" as twig blocks to be overridden in your template (just like `embed`).

All data passed to the component is added to public properties on the object. If no public property exists,
it is assumed to be "attributes" on the component's root html element. The component object can be accessed
within the component template (and "slots") with `this`. There is a special `attributes()` twig function
to be used within your components. This is used to render the root element's html attributes.

This is heavily inspired by [Blade Components](https://laravel.com/docs/8.x/blade#components).

## Examples

1. Alert: [component](https://github.com/kbond/twig-components-poc/blob/master/src/Twig/Components/Alert.php) - [template](https://github.com/kbond/twig-components-poc/blob/master/templates/components/alert.html.twig) - [usage](https://github.com/kbond/twig-components-poc/blob/master/templates/main/index.html.twig#L7-L9)
2. Dialog: [component](https://github.com/kbond/twig-components-poc/blob/master/src/Twig/Components/DialogComponent.php) - [template](https://github.com/kbond/twig-components-poc/blob/master/templates/components/dialog.html.twig) - [usage](https://github.com/kbond/twig-components-poc/blob/master/templates/main/index.html.twig#L11-L15)
3. Table: [component](https://github.com/kbond/twig-components-poc/blob/master/src/Twig/Components/DataTable.php) - [template](https://github.com/kbond/twig-components-poc/blob/master/templates/components/data_table.html.twig) - [usage](https://github.com/kbond/twig-components-poc/blob/master/templates/main/index.html.twig#L17-L20)
4. Input: [component](https://github.com/kbond/twig-components-poc/blob/master/src/Twig/Components/Input.php) - [template](https://github.com/kbond/twig-components-poc/blob/master/templates/components/input.html.twig) - [usage](https://github.com/kbond/twig-components-poc/blob/master/templates/main/index.html.twig#L22-L26)

## Future Scope

1. Default "slot". It should be possible to have any value within `{% component %}{% endcomponent %}`
not in a block to be part of the "default slot" (like Vue or [Blade Components](https://laravel.com/docs/8.x/blade#slots)).
2. `LiveComponent` base class to provide Laravel Livewire-like interactions
3. `make:component` (`make:component --live`)
4. "Anonymous" components (just a template) to take advantage of the `attributes()` system

## Documentation

### Actions 

You can also trigger actions on your component. First, add the method:

TODO: update when the `@LiveAction` exists.

```php
    public function save(EntityManagerInterface $entityManager)
    {
        $this->isSaved = true;
        $entityManager->flush();
    }
```

Actions are true Symfony controllers, so you can autowire services
like you normally can. To trigger this from your component:

```twig
<button
    data-action="live#action"
    data-action-name="save"
>Save</button>
```

That's it! On click, the `save()` action will be triggered and your component
will be re-rendered.

You can also add several "modifiers" to the event:

```twig
<form>
    <button
        data-action="live#action"
        data-action-name="prevent.debounce(300).save"
    >Save</button>
</form>
```

The `prevent` modifier will prevent the form from submitting
(`event.preventDefault()`). The `debounce(300)` modifier will
add 300ms if "debouncing" before the action is executed.

### Loading States

Often, you'll want to show (or hide) an element while a component is
re-rendering or an action is processing. For example:

```twig
<!-- show only when the component is loading -->
<span data-loading>Loading</span>

<!-- equalivalent, longer syntax -->
<span data-loading="show">Loading</span>
```

Or, to *hide* an element while the component is loading:

```twig
<!-- hide when the component is loading -->
<span
    data-loading="hide"
>Saved!</span>
```

#### Adding and Removing Classes or Attributes

Instead of hiding or showing an entire element, you could
add or remove a class:

```twig
<!-- add this class when loading -->
<div data-loading="addClass(opacity-50)">...</div>

<!-- remove this class when loading -->
<div data-loading="removeClass(opacity-50)">...</div>

<!-- add multiple classes when loading -->
<div data-loading="addClass(opacity-50 disabled)">...</div>
```

Sometimes you may want to add or remove an attribute when loading.
That can be accomplished with `addAttribute` or `removeAttribute`:

```twig
<!-- add the "disabled" attribute when loading -->
<div data-loading="addAttribute(disabled)">...</div>
```

You can also combine any number of directives by separating them
with a space:

```twig
<div data-loading="addClass(opacity-50) addAttribute(disabled)">...</div>
```

Finally, you can add the `delay` modifier to not trigger the loading
changes until loading has taken longer than 200ms:

```twig
<div data-loading="delay.addClass(opacity-50)">...</div>

<!-- Show after 200ms of loading -->
<div data-loading="delay.show">Loading</div>
```
