<?php

namespace DNADesign\Elemental\Extensions;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\ElementalVirtual\Model\ElementVirtual;
use SilverStripe\Core\Extension;

class ElementalAreaTextSearchExtension extends Extension
{
    /**
    * Return all the elements for an element area
    * including the ones nested in ElementList
    *
    * @return DataList
    */
    public function getAllElements()
    {
        return BaseElement::get()->filter('ParentID', $this->owner->getNestedElementalAreas());
    }

    /**
     * Recursively introspect elemental areas
     * as well as virtual elements
     * to extract the complete list of element presents within a single area
     *
     * @return DataList
     */
    public function getNestedElementalAreas()
    {
        $areas = [$this->owner->ID];

        if ($this->owner->Elements()->exists()) {
            $lists =  $this->owner->Elements()->filterByCallback(function ($item) {
                return $item->hasMethod('Elements');
            });
    
            if ($lists->count() > 0) {
                $areas = array_merge($areas, $lists->column('ElementsID'));
    
                // Recursively looks into nested ElementalAreas
                foreach ($lists as $list) {
                    $nestedAreas = $list->Elements()->getNestedElementalAreas();
                    if (count($nestedAreas) > 0) {
                        $areas = array_merge($areas, $nestedAreas);
                    }
                }
            }
    
            // VirtualElement
            if (class_exists(ElementVirtual::class)) {
                $virtuals = $this->owner->Elements()->filterByCallback(function ($item) {
                    return $item instanceof ElementVirtual;
                });
        
                if ($virtuals && $virtuals->exists()) {
                    foreach ($virtuals as $virtual) {
                        $element = $virtual->LinkedElement();
                        if ($element && $element->exists() && $element->hasMethod('Elements')) {
                            array_push($areas, $element->ElementsID);
        
                            // Recursively looks into nested ElementalAreas
                            $virtualNestedAreas = $element->Elements()->getNestedElementalAreas();
                            if (count($virtualNestedAreas) > 0) {
                                $areas = array_merge($areas, $virtualNestedAreas);
                            }
                        }
                    }
                }
            }
        }
        
        return $areas;
    }

    /**
     * This method will select the elements that are eligible to be included in the solr search index
     * then concatenate the relevant fields and return it as a string
     * @return string
     */
    public function getTextContentForSearch()
    {
        $elements = $this->getAllElements();
        // Remove the virtual elements as their linked elements would already have been included
        // and remove disallowed elements
        if ($elements  && $elements->exists()) {
            $elements = $elements->filterByCallback(function ($element) {
                return !($element instanceof ElementVirtual && $element->config()->get('exclude_content_from_search') !== true);
            });
        }
        
        $output = [];
        if ($elements && $elements->exists()) {
            foreach ($elements as $element) {
                $output[] = $element->getTextContentForSearch();
            }
        }

        return implode(' ', $output);
    }
}
