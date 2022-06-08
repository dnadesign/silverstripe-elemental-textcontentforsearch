<?php

namespace DNADesign\Elemental\Extensions;

use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\LiteralField;

class ElementalPageTextSearchExtension extends Extension
{
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Search', LiteralField::create('SearchContent', $this->getTextContentForSearch()));
    }

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
}
