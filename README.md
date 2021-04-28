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

### Loading States

Often, you'll want to show (or hide) an element while a component is
re-rendering or an action is processing. For example:

```twig
<!-- show only when the component is loading -->
<span
    data-live-loading
>Loading</span>
```

Or, to *hide* an element while the component is loading:

```twig
<!-- hide when the component is loading -->
<span
    data-live-loading="hide"
>Saved!</span>
```

#### Adding and Removing Classes

Instead of hiding or showing an entire element, you could
add or remove a class:

```twig
<div
    data-live-loading="addClass->opacity-50"
>{{ someValue }}
```

Or remove a class during loading:

```twig
<div
    class="opacity-50"
    data-live-loading="removeClass->opacity-50"
>{{ someValue }}
```
