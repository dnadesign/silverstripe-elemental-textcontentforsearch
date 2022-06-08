# silverstripe-elemental-textcontentforsearch
A set of helper methods to facilitate searching elemental pages

## Requirements

* Silverstripe CMS ^4.3
* Silverstripe Elemental ^4

## Installation

```
composer require dnadesign/silverstripe-elemental-textcontentforsearch ^0.1
```

The following YAML config will enable elements on every `Page` object,
replacing the standard `Content` rich text field and add access to the `getTextContentForSearch` method

**mysite/\_config/elements.yml**

```yaml
Page:
  extensions:
    - DNADesign\Elemental\Extensions\ElementalPageExtension
    - DNADesign\Elemental\Extensions\ElementalPageTextSearchExtension
```

## Usage
### SolrIndex
We recommend you use the `TextContentForSearch` instead of the `ElementForSearch` method in your index.
This avoids rendering the entire elemental area, which would include images and complex elements, which consumes a lot of resources
during render and don't end up in the index anyway.

```php
class ElementalSolrIndex extends SolrIndex
{
    public function init()
    {
        $this->addClass(Page::class);
        $this->addAllFulltextFields();
        /** @see ElementalPageTextContentSearchExtension::getTextContentForSearch */
        $this->addFulltextField('TextContentForSearch');
    }
}
```

## Configuration
By default, the process will extract any `Varchar`, `Text` and `HTMLText` field from the elements.
If you require any other type of fields to be considered for indexing, you ca update the config as follow:

```yaml
Page:
  db_text_field_types:
    - CustomFieldType
```

You can exclude fields per element. By default, the `Style` and `ExtraClass` fields are excluded as they wouldn't bring any value
to the search. To exclude a field, you can either set the config on the class or in the yaml:

```yaml
DNADesign\Elemental\Models\ElementContent:
  exclude_fields_from_search:
    - Title
```
or
```php
class MyCustomElement extends BaseElement
{
    private static $exclude_fields_from_search = [
        'Title'
    ];
}
```

In reverse, you can add a field in the same way.

```yaml
DNADesign\Elemental\Models\ElementContent:
  include_fields_in_search:
    - AlternativeText
```
or
```php
class MyCustomElement extends BaseElement
{
    private static $include_fields_in_search = [
        'AlternativeText'
    ];
}
```

In addition, you can implement a `updateTextFieldsForSearch` method on any element or extension to update the list of fields to include in search.

Some elements may have complex relationships that are not explored by the default process.
You can implement `addTextContentForSearch` to concatenate extra content to the final string.

```php
class MyCustomElement extends BaseElement
{
    private static $has_many = [
        'AccordionItems' => AccordionItem::class
    ];

    public function addTextContentForSearch($string)
    {
        $titles = [];
        foreach($this->AccordionItems() as $item) {
            $titles[] = $item->Title;
        }
        return implode(' ', $titles);
    }
}
```

Ultimately, you can manipulate the final string to be return by implementing `updateTextContentForSearch` method.

```php
class MyCustomElement extends BaseElement
{
    public function updateTextContentForSearch($string)
    {
        // Remove any occurrence of forbiddenWord in the final string
        return = str_replace('forbiddenWord', '', $string);
    }
}
```

## Notes:
- The introspective process will explore any `VirtualElement` and extract the parent element text fields 

## To do:
- Add an option to store the search string in the DB on save?