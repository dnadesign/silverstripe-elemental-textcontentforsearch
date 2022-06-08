<?php

namespace DNADesign\Elemental\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\Connect\DBSchemaManager;
use SilverStripe\ORM\DataObject;

/**
 * Class BaseElement_SearchExtension
 *
 * Provides a method to concatenate all the text fields available
 * on the element for Solr to index
 *
 * @package Extensions
 */
class BaseElementTextSearchExtension extends Extension
{
    /**
     * This method looks for any text field on the element
     * plus any specified fields via config
     * plus content generated by the addTextForSearch method
     * and concatenate everything into a single string
     *
     * @return string
     */
    public function getTextContentForSearch()
    {
        $dbTextField = [
            'Varchar', 'Text', 'HTMLText'
        ];

        // Find all text field
        $class = $this->owner->ClassName;
        $fields = DataObject::getSchema()->databaseFields($class);
        $fields = array_filter($fields, function ($type, $name) use ($dbTextField) {
            $isText = false;
            foreach ($dbTextField as $fieldType) {
                if (strpos($type, $fieldType) !== false) {
                    $isText = true;
                }
            }
            return $isText;
        }, ARRAY_FILTER_USE_BOTH);
        
        $fields = array_keys($fields);

        // Exclude fields via config
        $excluded = $this->owner->config()->get('exclude_fields_from_search');
        if (!empty($excluded)) {
            if (!is_array($excluded)) {
                $excluded = [$excluded];
            }
            $fields = array_diff($fields, $excluded);
        }

        // Add fields via config
        $included = $this->owner->config()->get('include_fields_in_search');
        if (!empty($included)) {
            if (!is_array($included)) {
                $included = [$included];
            }
            $fields = array_merge($fields, $included);
        }

        // Make sure we don't add fields twice
        $fields = array_unique($fields);

        // Allow to update fields list on class
        if ($this->owner->hasMethod('updateTextFieldsForSearch')) {
            $this->owner->updateTextFieldsForSearch($fields);
        }

        // Generate output
        $output = [];

        // Get value of each field configured to be in the index
        foreach ($fields as $field) {
            $output[] = $this->sanitizeStringForSearch($this->owner->{$field});
        }

        // Call special method to pick on more complex content
        if ($this->owner->hasMethod('addTextContentForSearch')) {
            $output[] = $this->sanitizeStringForSearch($this->owner->addTextContentForSearch());
        }

        return implode(' ', $output);
    }

    /**
     * Strip a potentially html string into a plain text string
     *
     * @param string|html
     * @return string
     */
    public function sanitizeStringForSearch($string)
    {
        $string = strip_tags($string);
        $string = str_replace(["\r\n", "\r", "\n"], ' ', $string);
        return $string;
    }
}
