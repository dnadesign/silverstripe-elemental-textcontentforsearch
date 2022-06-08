<?php

namespace DNADesign\Elemental\Extensions;

use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\TextareaField;

class ElementalPageTextSearchExtension extends Extension
{
    private static $show_text_content_search_in_cms = true;

    /**
     * This is an alternative to DNADesign\Elemental\Extensions\ElementalPageExtension::getElementsForSearch()
     * Instead of rendering the entire elementalArea, this will rely on a config to decide which element
     * and which field of each element to include in the search.
     * This should speed up the Solr re-index process and avoid having a lot of noise in the index.
     *
     * @return string
     */
    public function getTextContentForSearch()
    {
        $output = [];
        foreach ($this->owner->hasOne() as $key => $class) {
            if ($class !== ElementalArea::class) {
                continue;
            }
            /** @var ElementalArea $area */
            $area = $this->owner->$key();
            if ($area) {
                $output[] = $area->getTextContentForSearch();
            }
        }

        return implode(' ', $output);
    }

    /**
     * Display the search text in the settings for debugging
     */
    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->config()->get('show_text_content_search_in_cms') === true) {
            $searchTerms = TextareaField::create('TextContentForSearch', 'Text Content For Search', $this->getTextContentForSearch());
            $searchTerms->setReadonly(true);
            $fields->addFieldToTab('Root.Search', $searchTerms);
        }
    }
}
